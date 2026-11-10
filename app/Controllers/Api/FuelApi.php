<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\FuelLogModel;
use App\Models\VehicleModel;

class FuelApi extends BaseController
{
    public function log()
    {
        $fuelModel = new FuelLogModel();
        $vehicleModel = new VehicleModel();

        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $liters = (float)$this->request->getPost('liters');
        $cost = (float)$this->request->getPost('total_cost');
        $odo = (float)$this->request->getPost('odometer_km');
        $station = $this->request->getPost('fuel_station');

        $vehicle = $vehicleModel->find($vehicleId);
        if (!$vehicle) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Vehicle not found.',
            ]);
        }

        // Lock driver_id to authenticated driver when invoked via session
        $session = session();
        $userRole = $session->get('user_role');
        $driverId = (int)$this->request->getPost('driver_id') ?: null;
        if ($session->get('logged_in') && $userRole === 'driver') {
            $driverModel = new \App\Models\DriverModel();
            $currentDriver = $driverModel->where('user_id', $session->get('user_id'))->first();
            if ($currentDriver) {
                $driverId = (int)$currentDriver['id'];
            }
        }

        // Handle receipt image upload with strict type, size, and magic byte validation
        $receiptFile = $this->request->getFile('receipt_image');
        $receiptName = null;
        if ($receiptFile && $receiptFile->isValid() && !$receiptFile->hasMoved()) {
            if ($receiptFile->getSizeByUnit('kb') > 5120) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Receipt file size exceeds maximum permitted limit of 5 MB.',
                ]);
            }
            $clientExt = strtolower($receiptFile->getClientExtension());
            if (!in_array($clientExt, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Security Policy Violation: Only authentic image (.jpg, .png) or PDF receipts are allowed.',
                ]);
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($receiptFile->getTempName());
            $mimeToExt = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
            ];
            if (!isset($mimeToExt[$realMime])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Security Policy Violation: Uploaded receipt format is invalid or corrupted.',
                ]);
            }
            $forcedExt = $mimeToExt[$realMime];
            $receiptName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $forcedExt;
            $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'receipts';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0750, true);
            }
            $receiptFile->move($targetDir, $receiptName);
        }

        $costPerLiter = $liters > 0 ? round($cost / $liters, 2) : 0;
        $receiptNo = 'REC-' . date('Ymd') . '-' . rand(100, 999);

        $data = [
            'receipt_no'     => $receiptNo,
            'vehicle_id'     => $vehicleId,
            'driver_id'      => $driverId,
            'fuel_date'      => date('Y-m-d'),
            'odometer_km'    => $odo,
            'liters'         => $liters,
            'cost_per_liter' => $costPerLiter,
            'total_cost'     => $cost,
            'fuel_station'   => $station ?: 'Commercial Gas Station',
            'notes'          => $receiptName ? "Receipt uploaded: {$receiptName}" : 'PWA Mobile Log',
        ];

        $fuelModel->insert($data);

        // Update vehicle state
        $vehicle = $vehicleModel->find($vehicleId);
        $newOdo = ($odo > 0) ? $odo : ($vehicle ? (float)$vehicle['odometer_km'] : 0.00);
        $vehicleModel->update($vehicleId, [
            'odometer_km'        => $newOdo,
            'current_fuel_level' => 100.00,
        ]);

        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        $isDriverView = str_contains($referer, 'driver/trips');
        if ($isDriverView || (!$this->request->isAJAX() && !$this->request->is('json') && strpos($this->request->getHeaderLine('Accept'), 'application/json') === false)) {
            return redirect()->to('/driver/trips')->with('success', "Fuel receipt {$receiptNo} logged successfully. Vehicle refueled to 100%.");
        }

        return $this->response->setJSON([
            'status'      => 'success',
            'message'     => 'Fuel purchase logged successfully.',
            'receipt_no'  => $receiptNo,
            'odometer_km' => $newOdo,
            'file'        => $receiptName,
        ]);
    }
}
