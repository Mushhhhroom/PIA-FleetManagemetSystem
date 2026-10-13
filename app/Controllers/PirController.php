<?php

namespace App\Controllers;

use App\Models\PirReportModel;
use App\Models\VehicleModel;
use App\Models\NotificationModel;
use App\Services\AuditLogger;

/**
 * Module 5 — Mechanic Repair Workflow (FR-5.3)
 *
 * Mechanics record pre-inspection findings and post-repair evaluations
 * on the web portal before restoring a vehicle to Available status.
 */
class PirController extends BaseController
{
    protected PirReportModel $pirModel;
    protected VehicleModel $vehicleModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->pirModel          = new PirReportModel();
        $this->vehicleModel      = new VehicleModel();
        $this->notificationModel = new NotificationModel();
    }

    /** Mechanic PIR queue (FR-5.3). */
    public function index()
    {
        $statusFilter = $this->request->getGet('status');

        $data = [
            'title'  => 'Pre-Repair Inspection (PIR) Mechanic Queue',
            'queue'  => $this->pirModel->getQueue($statusFilter ?: null),
            'activeFilter' => $statusFilter ?: 'open',
        ];

        return view('pir/index', $data);
    }

    public function show($id)
    {
        $pir = $this->pirModel->getWithVehicle($id);
        if (!$pir) {
            return redirect()->to('/pir')->with('error', 'PIR ticket not found.');
        }

        $data = [
            'title' => "PIR Ticket: {$pir['pir_number']}",
            'pir'   => $pir,
        ];

        return view('pir/show', $data);
    }

    /**
     * FR-5.3: record workflow progression — pre-inspection findings,
     * post-repair evaluation, and final release back to Available.
     */
    public function update($id)
    {
        $pir = $this->pirModel->find($id);
        if (!$pir) {
            return redirect()->to('/pir')->with('error', 'PIR ticket not found.');
        }

        $session  = session();
        $userName = $session->get('user_name');
        $action   = (string) $this->request->getPost('action');
        $now      = date('Y-m-d H:i:s');

        $preFindings = trim((string) $this->request->getPost('pre_inspection_findings'));
        $postEval    = trim((string) $this->request->getPost('post_repair_evaluation'));
        $parts       = trim((string) $this->request->getPost('parts_replaced'));
        $cost        = (float) $this->request->getPost('repair_cost');

        switch ($action) {
            case 'inspect':
                // Start inspection: capture pre-inspection findings (FR-5.3)
                if ($preFindings === '') {
                    return redirect()->back()->with('error', 'Record the pre-inspection findings before starting inspection (FR-5.3).');
                }
                $this->pirModel->update($id, [
                    'status'                 => 'in_inspection',
                    'pre_inspection_findings'=> $preFindings,
                    'mechanic_id'            => (int) $session->get('user_id') ?: $pir['mechanic_id'],
                ]);
                $msg = 'Pre-inspection findings recorded. PIR moved to In Inspection.';
                break;

            case 'repair':
                if ($preFindings === '' && $pir['pre_inspection_findings'] === null) {
                    return redirect()->back()->with('error', 'Pre-inspection findings are required before repair begins (FR-5.3).');
                }
                $this->pirModel->update($id, [
                    'status'                 => 'in_repair',
                    'pre_inspection_findings'=> $preFindings ?: $pir['pre_inspection_findings'],
                    'parts_replaced'         => $parts ?: $pir['parts_replaced'],
                    'repair_cost'            => $parts || $cost > 0 ? $cost : $pir['repair_cost'],
                    'mechanic_id'            => (int) $session->get('user_id') ?: $pir['mechanic_id'],
                ]);
                $msg = 'PIR moved to In Repair.';
                break;

            case 'ready':
                // FR-5.3: post-repair evaluation is mandatory before release
                if ($postEval === '') {
                    return redirect()->back()->with('error', 'A post-repair evaluation is mandatory before the vehicle can be readied for release (FR-5.3).');
                }
                $this->pirModel->update($id, [
                    'status'                => 'ready_for_release',
                    'post_repair_evaluation'=> $postEval,
                    'parts_replaced'        => $parts ?: $pir['parts_replaced'],
                    'repair_cost'           => $cost > 0 ? $cost : $pir['repair_cost'],
                    'mechanic_id'           => (int) $session->get('user_id') ?: $pir['mechanic_id'],
                ]);
                $msg = 'Post-repair evaluation recorded. Vehicle ready for release.';
                break;

            case 'release':
                // FR-5.3: both evaluations required to restore Available status
                if ($pir['pre_inspection_findings'] === null || empty($pir['post_repair_evaluation'])) {
                    if ($preFindings === '' || $postEval === '') {
                        return redirect()->back()->with('error', 'Both pre-inspection findings and post-repair evaluation are required before restoring the vehicle to Available status (FR-5.3).');
                    }
                }

                // find() returns the raw row — resolve the plate for messaging
                $vehicleRow = $this->vehicleModel->find($pir['vehicle_id']);
                $plate      = $vehicleRow['plate_number'] ?? ('#' . $pir['vehicle_id']);

                $this->pirModel->update($id, [
                    'status'                 => 'released',
                    'pre_inspection_findings'=> $preFindings ?: $pir['pre_inspection_findings'],
                    'post_repair_evaluation' => $postEval ?: $pir['post_repair_evaluation'],
                    'parts_replaced'         => $parts ?: $pir['parts_replaced'],
                    'repair_cost'            => $cost > 0 ? $cost : $pir['repair_cost'],
                    'released_at'            => $now,
                    'mechanic_id'            => (int) $session->get('user_id') ?: $pir['mechanic_id'],
                ]);

                // Restore vehicle to Available state
                $this->vehicleModel->update($pir['vehicle_id'], ['status' => 'active']);

                // Clear any preventive-maintenance lock left by the failure
                $pmsModel = new \App\Models\PmsRecordModel();
                $pms = $pmsModel->where('vehicle_id', $pir['vehicle_id'])->first();
                if ($pms) {
                    $pmsModel->update($pms['id'], ['status' => 'ok', 'notes' => "PIR {$pir['pir_number']} cleared — vehicle returned to active service."]);
                }

                // Notify dispatchers that the vehicle is back in service
                $userModel = new \App\Models\UserModel();
                foreach ($userModel->whereIn('role', ['dispatcher', 'admin'])->where('status', 'active')->findAll() as $disp) {
                    $this->notificationModel->insert([
                        'user_id'    => $disp['id'],
                        'title'      => "Vehicle Released: {$pir['pir_number']}",
                        'message'    => "Vehicle {$plate} passed post-repair evaluation and is back in Available status.",
                        'type'       => 'success',
                        'link'       => '/pir',
                        'is_read'    => 0,
                        'created_at' => $now,
                    ]);
                }

                AuditLogger::log(
                    AuditLogger::PIR_RELEASED,
                    "PIR {$pir['pir_number']} released by {$userName}; vehicle {$plate} restored to Available (FR-5.3).",
                    'pir_report',
                    (int) $id,
                    ['pre' => $pir['pre_inspection_findings'], 'post' => $postEval ?: $pir['post_repair_evaluation']]
                );

                return redirect()->to('/pir')->with('success', "PIR {$pir['pir_number']} released — vehicle {$plate} is back in Available status.");

            default:
                return redirect()->back()->with('error', 'Unknown PIR action.');
        }

        if ($action === 'inspect') {
            AuditLogger::log(
                AuditLogger::STATUS_CHANGE,
                "PIR {$pir['pir_number']} moved to In Inspection by {$userName}.",
                'pir_report',
                (int) $id
            );
        }

        return redirect()->to("/pir/{$id}")->with('success', $msg);
    }
}
