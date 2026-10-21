<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\RouteService;

class TrackingController extends BaseController
{
    public function index()
    {
        $apiController = new ApiController();
        $apiController->initController($this->request, $this->response, $this->logger);
        $payload      = $apiController->buildFleetTrackingPayload();
        $routeService = new RouteService();

        return view('tracking/index', [
            'title'     => 'Real-Time Fleet Telematics & GPS Tracking',
            'vehicles'  => $payload['vehicles'],
            'apiStatus' => $routeService->getApiStatus(),
        ]);
    }

    public function liveData()
    {
        $apiController = new ApiController();
        $apiController->initController($this->request, $this->response, $this->logger);
        return $apiController->getLiveTracking();
    }

    /**
     * Simulation action: advances vehicles that are in transit along their road route
     */
    public function simulateStep()
    {
        $apiController = new ApiController();
        $apiController->initController($this->request, $this->response, $this->logger);
        return $apiController->simulateStep();
    }
}

