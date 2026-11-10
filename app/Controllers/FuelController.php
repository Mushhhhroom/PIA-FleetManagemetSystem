<?php

namespace App\Controllers;

use App\Models\FuelLogModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;

class FuelController extends BaseController
{
    public function index()
    {
        $fuelModel = new FuelLogModel();
        $logs = $fuelModel->getLogsWithDetails();

        $stats = $fuelModel->selectSum('total_cost', 'total_spend')
                           ->selectSum('liters', 'total_liters')
                           ->first();

        return view('fuel/index', [
            'title'       => 'Fuel & Energy Management',
            'logs'        => $logs,
            'totalSpend'  => $stats['total_spend'] ?? 0,
            'totalLiters' => $stats['total_liters'] ?? 0,
        ]);
    }

    public function create()
    {
        $vehicleModel = new VehicleModel();
        $driverModel = new DriverModel();

        return view('fuel/form', [
            'title'    => 'Log Fuel Transaction',
            'vehicles' => $vehicleModel->findAll(),
            'drivers'  => $driverModel->findAll(),
        ]);
    }

    public function store()
    {
        $fuelModel = new FuelLogModel();
        $vehicleModel = new VehicleModel();

        $rules = [
            'vehicle_id' => 'required|numeric',
            'fuel_date'  => 'required|valid_date',
            'liters'     => 'required|numeric',
            'total_cost' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $liters = (float)$this->request->getPost('liters');
        $totalCost = (float)$this->request->getPost('total_cost');
        $costPerLiter = $liters > 0 ? round($totalCost / $liters, 2) : 0;
        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $driverId = $this->request->getPost('driver_id') ? (int)$this->request->getPost('driver_id') : null;
        $odo = (float)$this->request->getPost('odometer_km');

        $receiptFile = $this->request->getFile('receipt_image');
        $receiptName = null;
        if ($receiptFile && $receiptFile->isValid() && !$receiptFile->hasMoved()) {
            if ($receiptFile->getSizeByUnit('kb') > 5120) {
                return redirect()->back()->withInput()->with('error', 'Receipt file size must not exceed 5 MB.');
            }
            $clientExt = strtolower($receiptFile->getClientExtension());
            if (!in_array($clientExt, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return redirect()->back()->withInput()->with('error', 'Security Policy Violation: Only authentic image (.jpg, .png) or PDF receipts are allowed.');
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($receiptFile->getTempName());
            $mimeToExt = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
            ];
            if (!isset($mimeToExt[$realMime])) {
                return redirect()->back()->withInput()->with('error', 'Security Policy Violation: Invalid receipt image format.');
            }
            $forcedExt = $mimeToExt[$realMime];
            $receiptName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $forcedExt;
            $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'receipts';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0750, true);
            }
            $receiptFile->move($targetDir, $receiptName);
        }

        $notes = trim($this->request->getPost('notes'));
        if ($receiptName) {
            $notes = ($notes ? $notes . ' | ' : '') . "Receipt: {$receiptName}";
        }

        $data = [
            'receipt_no'     => trim($this->request->getPost('receipt_no')) ?: ('REC-' . rand(10000, 99999)),
            'vehicle_id'     => $vehicleId,
            'driver_id'      => $driverId,
            'fuel_date'      => $this->request->getPost('fuel_date'),
            'odometer_km'    => $odo,
            'liters'         => $liters,
            'cost_per_liter' => $costPerLiter,
            'total_cost'     => $totalCost,
            'fuel_station'   => trim($this->request->getPost('fuel_station')),
            'notes'          => $notes,
        ];

        $fuelModel->insert($data);

        // Update vehicle odometer and fuel level to 100%
        if ($odo > 0) {
            $vehicleModel->update($vehicleId, [
                'odometer_km'        => $odo,
                'current_fuel_level' => 100.00,
            ]);
        } else {
            $vehicleModel->update($vehicleId, [
                'current_fuel_level' => 100.00,
            ]);
        }

        return redirect()->to('/fuel')->with('success', 'Fuel purchase logged successfully.');
    }

    public function viewReceipt(string $filename)
    {
        $safeName = basename($filename);
        $path = WRITEPATH . 'uploads/receipts/' . $safeName;
        if (!file_exists($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Receipt image not found.');
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        return $this->response->setHeader('Content-Type', $mime)->setBody(file_get_contents($path));
    }
}
