<?php

namespace App\Controllers;

use App\Models\MaintenanceModel;
use App\Models\VehicleModel;

class MaintenanceController extends BaseController
{
    public function index()
    {
        $maintenanceModel = new MaintenanceModel();
        $records = $maintenanceModel->getRecordsWithVehicle();

        return view('maintenance/index', [
            'title'   => 'Fleet Maintenance & Work Orders',
            'records' => $records,
        ]);
    }

    public function create()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->findAll();

        return view('maintenance/form', [
            'title'    => 'Create Maintenance Work Order',
            'record'   => null,
            'vehicles' => $vehicles,
        ]);
    }

    public function store()
    {
        $maintenanceModel = new MaintenanceModel();
        $vehicleModel = new VehicleModel();

        $rules = [
            'vehicle_id'     => 'required|numeric',
            'service_type'   => 'required',
            'scheduled_date' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $refNo = 'WO-' . date('Y') . '-' . rand(1000, 9999);
        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $status = $this->request->getPost('status') ?: 'scheduled';

        $data = [
            'reference_no'        => $refNo,
            'vehicle_id'          => $vehicleId,
            'service_type'        => trim($this->request->getPost('service_type')),
            'priority'            => $this->request->getPost('priority') ?: 'medium',
            'scheduled_date'      => $this->request->getPost('scheduled_date'),
            'odometer_at_service' => (float)$this->request->getPost('odometer_at_service'),
            'service_center'      => trim($this->request->getPost('service_center')),
            'technician_name'     => trim($this->request->getPost('technician_name')),
            'cost'                => (float)$this->request->getPost('cost'),
            'status'              => $status,
            'description'         => trim($this->request->getPost('description')),
            'notes'               => trim($this->request->getPost('notes')),
        ];

        $maintenanceModel->insert($data);

        // If in_progress, update vehicle status to maintenance
        if ($status === 'in_progress') {
            $vehicleModel->update($vehicleId, ['status' => 'maintenance']);
        }

        return redirect()->to('/maintenance')->with('success', "Work order {$refNo} created.");
    }

    public function updateStatus(int|string $id)
    {
        $maintenanceModel = new MaintenanceModel();
        $vehicleModel = new VehicleModel();

        $record = $maintenanceModel->find($id);
        if (!$record) {
            return redirect()->to('/maintenance')->with('error', 'Work order not found.');
        }

        $newStatus = $this->request->getPost('status');
        $updateData = ['status' => $newStatus];

        if ($newStatus === 'completed') {
            $updateData['completion_date'] = date('Y-m-d');
            $cost = (float)$this->request->getPost('cost');
            if ($cost > 0) {
                $updateData['cost'] = $cost;
            }
            $vehicle = $vehicleModel->find($record['vehicle_id']);
            $currentOdo = $vehicle ? (float)$vehicle['odometer_km'] : 0.00;
            $vehicleModel->update($record['vehicle_id'], [
                'status'            => 'active',
                'last_service_date' => date('Y-m-d'),
                'next_service_km'   => $currentOdo + 10000.00,
            ]);

            // Unlock and reset vehicle PMS status upon maintenance completion (FR-5.3)
            $pmsModel = new \App\Models\PmsRecordModel();
            $pms = $pmsModel->where('vehicle_id', $record['vehicle_id'])->first();
            if ($pms) {
                $interval = (float)($pms['pms_interval_km'] ?: 5000.00);
                $pmsModel->update($pms['id'], [
                    'last_pms_odometer' => $currentOdo,
                    'next_pms_odometer' => $currentOdo + $interval,
                    'last_pms_date'     => date('Y-m-d'),
                    'status'            => 'ok',
                    'is_locked'         => 0,
                    'notes'             => 'Serviced via Work Order ' . $record['reference_no'],
                ]);
            }
        } elseif ($newStatus === 'in_progress') {
            $vehicleModel->update($record['vehicle_id'], ['status' => 'maintenance']);
        }

        $maintenanceModel->update($id, $updateData);

        return redirect()->to('/maintenance')->with('success', 'Work order updated to ' . ucfirst($newStatus) . '.');
    }
}
