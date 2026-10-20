<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\TripTicketModel;
use App\Models\TelemetryModel;
use App\Libraries\RouteService;
use App\Libraries\GeofenceChecker;

class ApiController extends BaseController
{
    /**
     * Helper to return standard JSON with universal CORS headers
     */
    protected function corsResponse(array $data, int $statusCode = 200)
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
            ->setStatusCode($statusCode)
            ->setJSON($data);
    }

    /**
     * Preflight CORS handler for external mapping apps / GIS integrations
     */
    public function optionsHandler()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
            ->setStatusCode(200);
    }

    /**
     * GET /api/v1/vehicles
     * Returns fleet asset registry
     */
    public function getVehicles()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->findAll();

        return $this->corsResponse([
            'status' => 'success',
            'count'  => count($vehicles),
            'data'   => $vehicles,
        ]);
    }

    /**
     * GET /api/v1/vehicles/(:num)
     * Returns individual vehicle details
     */
    public function getVehicle($id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($id);

        if (!$vehicle) {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Vehicle not found.',
            ], 404);
        }

        return $this->corsResponse([
            'status' => 'success',
            'data'   => $vehicle,
        ]);
    }

    /**
     * GET /api/v1/tracking or /api/v1/map
     * Core Real-Time Mapping Telematics API
     * Returns all fleet units with live coordinates, speed, fuel, driver, active e-DTT, and GeoJSON collection
     */
    public function getLiveTracking()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->select('vehicles.*,
                                          CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name,
                                          drivers.phone AS driver_phone,
                                          drivers.license_type,
                                          trip_tickets.id AS active_ticket_id,
                                          trip_tickets.ticket_serial_no AS trip_number,
                                          trip_tickets.authorized_destination AS destination_address,
                                          trip_tickets.authorized_purpose')
                                ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                                ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                                ->groupBy('vehicles.id')
                                ->findAll();

        $geofenceChecker = new GeofenceChecker();
        $features = [];

        foreach ($vehicles as &$v) {
            $lat = (float)($v['current_latitude'] ?? 0);
            $lng = (float)($v['current_longitude'] ?? 0);

            $fence = ($lat != 0 && $lng != 0) ? $geofenceChecker->check($lat, $lng) : null;
            $v['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';

            if ($lat != 0 && $lng != 0) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$lng, $lat],
                    ],
                    'properties' => [
                        'vehicle_id'   => (int)$v['id'],
                        'vehicle_code' => $v['vehicle_code'],
                        'plate_number' => $v['plate_number'],
                        'make_model'   => $v['make'] . ' ' . $v['model'],
                        'status'       => $v['status'],
                        'speed_kmh'    => (float)$v['current_speed'],
                        'fuel_level'   => (float)$v['current_fuel_level'],
                        'driver_name'  => $v['driver_name'] ?: 'Unassigned',
                        'driver_phone' => $v['driver_phone'] ?: '',
                        'geofence'     => $v['current_geofence'],
                        'trip_number'  => $v['trip_number'] ?: null,
                    ]
                ];
            }
        }

        return $this->corsResponse([
            'status'    => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'count'     => count($vehicles),
            'vehicles'  => $vehicles,
            'geojson'   => [
                'type'     => 'FeatureCollection',
                'features' => $features,
            ],
        ]);
    }

    /**
     * GET /api/v1/tracking/(:num)
     * Returns tracking state for a specific vehicle
     */
    public function getVehicleTracking($id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->select('vehicles.*,
                                         CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name,
                                         drivers.phone AS driver_phone,
                                         trip_tickets.id AS active_ticket_id,
                                         trip_tickets.ticket_serial_no AS trip_number,
                                         trip_tickets.authorized_destination AS destination_address,
                                         trip_tickets.authorized_purpose')
                               ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                               ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                               ->where('vehicles.id', (int)$id)
                               ->first();

        if (!$vehicle) {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => "Vehicle #{$id} not found.",
            ], 404);
        }

        $lat = (float)($vehicle['current_latitude'] ?? 0);
        $lng = (float)($vehicle['current_longitude'] ?? 0);
        $geofenceChecker = new GeofenceChecker();
        $fence = ($lat != 0 && $lng != 0) ? $geofenceChecker->check($lat, $lng) : null;
        $vehicle['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';

        return $this->corsResponse([
            'status'    => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'vehicle'   => $vehicle,
        ]);
    }

    /**
     * GET /api/v1/tracking/(:num)/trail
     * Returns recent GPS breadcrumb trail coordinates for drawing polyline paths on the map
     */
    public function getVehicleTrail($id)
    {
        $limit = min(200, max(5, (int)($this->request->getGet('limit') ?: 60)));
        $telemetryModel = new TelemetryModel();

        $crumbs = $telemetryModel->where('vehicle_id', (int)$id)
                                ->orderBy('id', 'DESC')
                                ->findAll($limit);

        $crumbs = array_reverse($crumbs);

        $leafletCoords = [];
        $geoJsonCoords = [];

        foreach ($crumbs as $c) {
            $lat = (float)$c['latitude'];
            $lng = (float)$c['longitude'];
            if ($lat != 0 && $lng != 0) {
                $leafletCoords[] = [$lat, $lng];
                $geoJsonCoords[] = [$lng, $lat];
            }
        }

        return $this->corsResponse([
            'status'      => 'success',
            'vehicle_id'  => (int)$id,
            'point_count' => count($crumbs),
            'coordinates' => $leafletCoords,
            'geojson'     => [
                'type'        => 'Feature',
                'geometry'    => [
                    'type'        => 'LineString',
                    'coordinates' => $geoJsonCoords,
                ],
                'properties'  => [
                    'vehicle_id' => (int)$id,
                    'timestamp'  => date('Y-m-d H:i:s'),
                ]
            ],
            'points'      => $crumbs,
        ]);
    }

    /**
     * GET /api/v1/telemetry
     * Returns raw telemetry events with optional filters (?vehicle_id=, ?trip_id=, ?limit=)
     */
    public function getTelemetry()
    {
        $telemetryModel = new TelemetryModel();
        $builder = $telemetryModel->orderBy('id', 'DESC');

        if ($vehicleId = $this->request->getGet('vehicle_id')) {
            $builder->where('vehicle_id', (int)$vehicleId);
        }
        if ($tripId = $this->request->getGet('trip_id')) {
            $builder->where('trip_id', (int)$tripId);
        }

        $limit = min(200, max(1, (int)($this->request->getGet('limit') ?: 50)));
        $telemetry = $builder->findAll($limit);

        return $this->corsResponse([
            'status' => 'success',
            'count'  => count($telemetry),
            'data'   => $telemetry,
        ]);
    }

    /**
     * POST /api/v1/telemetry
     * Ingests GPS telemetry coordinate ping safely
     */
    public function ingestTelemetry()
    {
        $json = null;
        $raw = $this->request->getBody();
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }
        if (empty($json)) {
            $json = $this->request->getPost();
        }
        if (empty($json)) {
            $json = $this->request->getGet();
        }

        if (empty($json['vehicle_id']) || !isset($json['latitude']) || !isset($json['longitude'])) {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Missing vehicle_id, latitude, or longitude.',
            ], 400);
        }

        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($json['vehicle_id']);
        if (!$vehicle) {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Vehicle does not exist in registry.',
            ], 404);
        }

        $telemetryModel = new TelemetryModel();
        $telemetryData = [
            'vehicle_id'      => (int)$json['vehicle_id'],
            'trip_id'         => !empty($json['trip_id']) ? (int)$json['trip_id'] : null,
            'latitude'        => (float)$json['latitude'],
            'longitude'       => (float)$json['longitude'],
            'speed_kmh'       => (float)($json['speed_kmh'] ?? 0.00),
            'heading'         => (float)($json['heading'] ?? 0.00),
            'fuel_level'      => (float)($json['fuel_level'] ?? $vehicle['current_fuel_level']),
            'engine_status'   => $json['engine_status'] ?? 'running',
            'battery_voltage' => isset($json['battery_voltage']) ? (float)$json['battery_voltage'] : null,
            'engine_temp_c'   => isset($json['engine_temp_c']) ? (float)$json['engine_temp_c'] : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $telemetryModel->insert($telemetryData);

        $vehicleModel->update($json['vehicle_id'], [
            'current_latitude'   => $telemetryData['latitude'],
            'current_longitude'  => $telemetryData['longitude'],
            'current_speed'      => $telemetryData['speed_kmh'],
            'current_fuel_level' => $telemetryData['fuel_level'],
            'engine_status'      => $telemetryData['engine_status'],
        ]);

        return $this->corsResponse([
            'status'  => 'success',
            'message' => 'Telemetry coordinate ingested.',
            'data'    => $telemetryData,
        ]);
    }

    /**
     * GET or POST /api/v1/route
     * Computes road route geometry and distance between origin and destination coordinates
     */
    public function calculateRoute()
    {
        $originLat = $this->request->getVar('origin_lat');
        $originLng = $this->request->getVar('origin_lng');
        $destLat   = $this->request->getVar('destination_lat') ?? $this->request->getVar('dest_lat');
        $destLng   = $this->request->getVar('destination_lng') ?? $this->request->getVar('dest_lng');

        if (!isset($originLat) || !isset($originLng) || !isset($destLat) || !isset($destLng)) {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Required query or body parameters: origin_lat, origin_lng, destination_lat, destination_lng.',
            ], 400);
        }

        $routeService = new RouteService();
        $route = $routeService->getRoute((float)$originLat, (float)$originLng, (float)$destLat, (float)$destLng);

        return $this->corsResponse([
            'status'       => 'success',
            'distance_km'  => $route['distance_km'],
            'duration_min' => $route['duration_min'],
            'source'       => $route['source'],
            'geojson'      => [
                'type'        => 'Feature',
                'geometry'    => [
                    'type'        => 'LineString',
                    'coordinates' => $route['geometry'] ?? [],
                ],
                'properties'  => [
                    'distance_km'  => $route['distance_km'],
                    'duration_min' => $route['duration_min'],
                ]
            ],
            'coordinates'  => $route['geometry'] ?? [],
        ]);
    }

    /**
     * POST /api/v1/tracking/simulate-step
     * Simulates forward motion along active transit routes for testing & demonstration
     */
    public function simulateStep()
    {
        $vehicleModel   = new VehicleModel();
        $telemetryModel = new TelemetryModel();
        $ticketModel    = new TripTicketModel();

        $activeTickets = $ticketModel->whereIn('status', ['departed', 'arrived_dest', 'departed_dest'])->findAll();
        if (empty($activeTickets)) {
            $activeVehicles = $vehicleModel->where('status', 'in_transit')->findAll();
            foreach ($activeVehicles as $v) {
                $activeTickets[] = ['id' => null, 'vehicle_id' => $v['id']];
            }
        }

        $updatedCount = 0;
        foreach ($activeTickets as $tk) {
            $vehicle = $vehicleModel->find($tk['vehicle_id']);
            if (!$vehicle) continue;

            $curLat = (float)$vehicle['current_latitude'];
            $curLng = (float)$vehicle['current_longitude'];

            $dLat = ((rand(-15, 20)) / 10000);
            $dLng = ((rand(-15, 20)) / 10000);

            $newLat   = round($curLat + $dLat, 7);
            $newLng   = round($curLng + $dLng, 7);
            $newSpeed = rand(35, 68) + (rand(0, 9) / 10);
            $newFuel  = max(10, round($vehicle['current_fuel_level'] - 0.2, 2));
            $newOdo   = round($vehicle['odometer_km'] + 1.2, 2);

            $vehicleModel->update($vehicle['id'], [
                'current_latitude'   => $newLat,
                'current_longitude'  => $newLng,
                'current_speed'      => $newSpeed,
                'current_fuel_level' => $newFuel,
                'odometer_km'        => $newOdo,
                'engine_status'      => 'running',
            ]);

            $telemetryModel->insert([
                'vehicle_id'      => $vehicle['id'],
                'trip_id'         => $tk['id'],
                'latitude'        => $newLat,
                'longitude'       => $newLng,
                'speed_kmh'       => $newSpeed,
                'heading'         => rand(0, 360),
                'fuel_level'      => $newFuel,
                'engine_status'   => 'running',
                'battery_voltage' => 14.1,
                'engine_temp_c'   => rand(86, 92),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $updatedCount++;
        }

        return $this->corsResponse([
            'status'        => 'success',
            'message'       => "Telemetry simulated for {$updatedCount} vehicle(s) in transit.",
            'updated_count' => $updatedCount,
            'timestamp'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /api/v1/trips/active
     * Returns official Driver's Trip Tickets currently in transit
     */
    public function getActiveTrips()
    {
        $ticketModel = new TripTicketModel();
        $tickets = $ticketModel->getTicketsWithDetails(['status' => 'departed']);

        return $this->corsResponse([
            'status' => 'success',
            'count'  => count($tickets),
            'data'   => $tickets,
        ]);
    }
}
