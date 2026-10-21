<?php

declare(strict_types=1);

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
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-Requested-With, Accept, X-CSRF-TOKEN')
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
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-Requested-With, Accept, X-CSRF-TOKEN')
            ->setStatusCode(200);
    }

    /**
     * Extracts an optional API key from X-API-Key header, Authorization Bearer, or query param.
     */
    protected function extractApiKey(): ?string
    {
        $headerKey = $this->request->getHeaderLine('X-API-Key');
        if ($headerKey !== '') {
            return trim($headerKey);
        }

        $authHeader = $this->request->getHeaderLine('Authorization');
        if ( str_starts_with(strtolower($authHeader), 'bearer ')) {
            return trim(substr($authHeader, 7));
        }

        $queryKey = $this->request->getVar('api_key') ?? $this->request->getVar('key');
        return is_string($queryKey) && $queryKey !== '' ? trim($queryKey) : null;
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
     * Builds enriched fleet tracking array with geofence, resolved destination coordinates,
     * and compass bearing for both Web Radar and REST API consumers.
     *
     * @return array{vehicles: list<array<string, mixed>>, features: list<array<string, mixed>>}
     */
    public function buildFleetTrackingPayload(): array
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->select('vehicles.*,
                                          COALESCE(NULLIF(CONCAT(d1.first_name, \' \', d1.last_name), \' \'), NULLIF(CONCAT(d2.first_name, \' \', d2.last_name), \' \')) AS driver_name,
                                          COALESCE(d1.phone, d2.phone) AS driver_phone,
                                          COALESCE(d1.license_type, d2.license_type) AS license_type,
                                          trip_tickets.id AS active_ticket_id,
                                          trip_tickets.ticket_serial_no AS trip_number,
                                          trip_tickets.status AS ticket_status,
                                          trip_tickets.authorized_destination AS destination_address,
                                          trip_tickets.authorized_purpose')
                                ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                                ->join('drivers d1', 'd1.id = vehicles.current_driver_id', 'left')
                                ->join('drivers d2', 'd2.id = trip_tickets.driver_id', 'left')
                                ->groupBy('vehicles.id')
                                ->findAll();

        $geofenceChecker = new GeofenceChecker();
        $routeService    = new RouteService();
        $features        = [];

        foreach ($vehicles as &$v) {
            $lat = (float) ($v['current_latitude'] ?? 0);
            $lng = (float) ($v['current_longitude'] ?? 0);
            if ($lat == 0.0 || $lng == 0.0) {
                $lat = RouteService::ORIGIN_LAT;
                $lng = RouteService::ORIGIN_LNG;
                $v['current_latitude']  = $lat;
                $v['current_longitude'] = $lng;
            }

            $fence = $geofenceChecker->check($lat, $lng);
            $v['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';
            $v['origin_lat']       = RouteService::ORIGIN_LAT;
            $v['origin_lng']       = RouteService::ORIGIN_LNG;

            if (!empty($v['destination_address'])) {
                $destGeo = $routeService->geocodeDestination((string) $v['destination_address']);
                $v['destination_lat'] = $destGeo['lat'];
                $v['destination_lng'] = $destGeo['lng'];
                $v['heading']         = $routeService->computeBearing($lat, $lng, $destGeo['lat'], $destGeo['lng']);
                $v['remaining_km']    = round($routeService->computeHaversineKm($lat, $lng, $destGeo['lat'], $destGeo['lng']) * 1.25, 1);
            } else {
                $v['destination_lat'] = null;
                $v['destination_lng'] = null;
                $v['heading']         = 0.0;
                $v['remaining_km']    = 0.0;
            }

            $features[] = [
                'type'     => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [$lng, $lat],
                ],
                'properties' => [
                    'vehicle_id'      => (int) $v['id'],
                    'vehicle_code'    => $v['vehicle_code'],
                    'plate_number'    => $v['plate_number'],
                    'make_model'      => $v['make'] . ' ' . $v['model'],
                    'status'          => $v['status'],
                    'speed_kmh'       => (float) $v['current_speed'],
                    'heading'         => (float) $v['heading'],
                    'fuel_level'      => (float) $v['current_fuel_level'],
                    'driver_name'     => $v['driver_name'] ?: 'Unassigned',
                    'driver_phone'    => $v['driver_phone'] ?: '',
                    'geofence'        => $v['current_geofence'],
                    'trip_number'     => $v['trip_number'] ?: null,
                    'destination'     => $v['destination_address'] ?: null,
                    'destination_lat' => $v['destination_lat'],
                    'destination_lng' => $v['destination_lng'],
                    'remaining_km'    => $v['remaining_km'],
                ],
            ];
        }
        unset($v);

        return [
            'vehicles' => $vehicles,
            'features' => $features,
        ];
    }

    /**
     * GET /api/v1/tracking or /api/v1/map
     * Core Real-Time Mapping Telematics API
     * Returns all fleet units with live coordinates, speed, fuel, driver, active e-DTT, and GeoJSON collection
     */
    public function getLiveTracking()
    {
        $payload      = $this->buildFleetTrackingPayload();
        $routeService = new RouteService();

        return $this->corsResponse([
            'status'     => 'success',
            'timestamp'  => date('Y-m-d H:i:s'),
            'count'      => count($payload['vehicles']),
            'api_status' => $routeService->getApiStatus(),
            'vehicles'   => $payload['vehicles'],
            'geojson'    => [
                'type'     => 'FeatureCollection',
                'features' => $payload['features'],
            ],
        ]);
    }

    /**
     * GET /api/v1/tracking/(:num)
     * Returns tracking state and active road route for a specific vehicle
     */
    public function getVehicleTracking($id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->select('vehicles.*,
                                         CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                                         drivers.phone AS driver_phone,
                                         trip_tickets.id AS active_ticket_id,
                                         trip_tickets.ticket_serial_no AS trip_number,
                                         trip_tickets.status AS ticket_status,
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

        $lat = (float) ($vehicle['current_latitude'] ?? 0);
        $lng = (float) ($vehicle['current_longitude'] ?? 0);
        if ($lat == 0.0 || $lng == 0.0) {
            $lat = RouteService::ORIGIN_LAT;
            $lng = RouteService::ORIGIN_LNG;
        }

        $geofenceChecker = new GeofenceChecker();
        $routeService    = new RouteService();
        $fence = $geofenceChecker->check($lat, $lng);
        $vehicle['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';
        $vehicle['origin_lat']       = RouteService::ORIGIN_LAT;
        $vehicle['origin_lng']       = RouteService::ORIGIN_LNG;

        $activeRoute = null;
        if (!empty($vehicle['destination_address'])) {
            $destGeo = $routeService->geocodeDestination((string) $vehicle['destination_address']);
            $vehicle['destination_lat'] = $destGeo['lat'];
            $vehicle['destination_lng'] = $destGeo['lng'];
            $activeRoute = $routeService->getRoute($lat, $lng, $destGeo['lat'], $destGeo['lng'], true);
            $vehicle['heading'] = $activeRoute['bearing_deg'];
        }

        return $this->corsResponse([
            'status'       => 'success',
            'timestamp'    => date('Y-m-d H:i:s'),
            'vehicle'      => $vehicle,
            'active_route' => $activeRoute,
        ]);
    }

    /**
     * GET /api/v1/tracking/(:num)/trail
     * Returns recent GPS breadcrumb trail coordinates AND active planned road route
     * for drawing both historical trail and forward navigation polylines on the map.
     */
    public function getVehicleTrail($id)
    {
        $limit          = min(200, max(5, (int) ($this->request->getGet('limit') ?: 60)));
        $telemetryModel = new TelemetryModel();
        $vehicleModel   = new VehicleModel();
        $routeService   = new RouteService();

        $vehicle = $vehicleModel->select('vehicles.*,
                                         trip_tickets.id AS active_ticket_id,
                                         trip_tickets.ticket_serial_no AS trip_number,
                                         trip_tickets.status AS ticket_status,
                                         trip_tickets.authorized_destination AS destination_address')
                                ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                                ->where('vehicles.id', (int) $id)
                                ->first();

        $crumbs = $telemetryModel->where('vehicle_id', (int) $id)
                                ->orderBy('id', 'DESC')
                                ->findAll($limit);

        $crumbs = array_reverse($crumbs);

        $leafletCoords = [];
        $geoJsonCoords = [];

        foreach ($crumbs as $c) {
            $lat = (float) $c['latitude'];
            $lng = (float) $c['longitude'];
            if ($lat != 0 && $lng != 0) {
                $leafletCoords[] = [$lat, $lng];
                $geoJsonCoords[] = [$lng, $lat];
            }
        }

        // Also compute the road-snapped planned route if the vehicle has an active ticket destination
        $plannedRoute = null;
        if ($vehicle && !empty($vehicle['destination_address'])) {
            $curLat  = (float) ($vehicle['current_latitude'] ?: RouteService::ORIGIN_LAT);
            $curLng  = (float) ($vehicle['current_longitude'] ?: RouteService::ORIGIN_LNG);
            $destGeo = $routeService->geocodeDestination((string) $vehicle['destination_address']);
            $route   = $routeService->getRoute($curLat, $curLng, $destGeo['lat'], $destGeo['lng'], true);

            $plannedRoute = [
                'origin_lat'          => RouteService::ORIGIN_LAT,
                'origin_lng'          => RouteService::ORIGIN_LNG,
                'origin_label'        => RouteService::ORIGIN_LABEL,
                'current_lat'         => $curLat,
                'current_lng'         => $curLng,
                'destination_lat'     => $destGeo['lat'],
                'destination_lng'     => $destGeo['lng'],
                'destination_address' => $vehicle['destination_address'],
                'distance_km'         => $route['distance_km'],
                'duration_min'        => $route['duration_min'],
                'bearing_deg'         => $route['bearing_deg'],
                'leaflet_coords'      => $route['leaflet_coords'],
                'steps'               => $route['steps'],
                'source'              => $route['source'],
            ];
        }

        return $this->corsResponse([
            'status'        => 'success',
            'vehicle_id'    => (int) $id,
            'point_count'   => count($crumbs),
            'coordinates'   => $leafletCoords,
            'planned_route' => $plannedRoute,
            'geojson'       => [
                'type'     => 'Feature',
                'geometry' => [
                    'type'        => 'LineString',
                    'coordinates' => $geoJsonCoords,
                ],
                'properties' => [
                    'vehicle_id' => (int) $id,
                    'timestamp'  => date('Y-m-d H:i:s'),
                ],
            ],
            'points'        => $crumbs,
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
        $routeService = new RouteService();
        $hasSession   = (bool) (session()->get('logged_in') || session()->get('user_id'));
        if (!$routeService->validateTelematicsRequest($this->extractApiKey(), $hasSession)) {
            return $this->corsResponse([
                'status'  => 'error',
                'code'    => 'INVALID_API_KEY',
                'message' => 'Invalid or missing X-API-Key header for external telemetry ingestion.',
            ], 401);
        }

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
     * Computes road-snapped route geometry, turn-by-turn navigation steps, ETA, and distance.
     * Supports both explicit coordinates (dest_lat/dest_lng) AND text destination geocoding (?destination=...).
     * Origin defaults to PIA Central Office Motorpool (14.657200, 121.045000) when omitted.
     */
    public function calculateRoute()
    {
        $routeService = new RouteService();

        $originLat   = $this->request->getVar('origin_lat');
        $originLng   = $this->request->getVar('origin_lng');
        $originQuery = trim((string) ($this->request->getVar('origin') ?? ''));

        if (($originLat === null || $originLng === null || $originLat === '' || $originLng === '') && $originQuery !== '') {
            $originGeo = $routeService->geocodeDestination($originQuery);
            $originLat = $originGeo['lat'];
            $originLng = $originGeo['lng'];
        }

        $originLat = ($originLat !== null && $originLat !== '') ? (float) $originLat : RouteService::ORIGIN_LAT;
        $originLng = ($originLng !== null && $originLng !== '') ? (float) $originLng : RouteService::ORIGIN_LNG;

        $destLat     = $this->request->getVar('destination_lat') ?? $this->request->getVar('dest_lat');
        $destLng     = $this->request->getVar('destination_lng') ?? $this->request->getVar('dest_lng');
        $destAddress = trim((string) ($this->request->getVar('destination') ?? $this->request->getVar('destination_address') ?? ''));
        $destSource  = 'coordinates';

        if (($destLat === null || $destLng === null || $destLat === '' || $destLng === '') && $destAddress !== '') {
            $destGeo    = $routeService->geocodeDestination($destAddress);
            $destLat    = $destGeo['lat'];
            $destLng    = $destGeo['lng'];
            $destSource = $destGeo['source'];
        }

        if ($destLat === null || $destLng === null || $destLat === '' || $destLng === '') {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Provide either destination_lat & destination_lng OR a destination address parameter.',
            ], 400);
        }

        $destLatF = (float) $destLat;
        $destLngF = (float) $destLng;
        $route    = $routeService->getRoute($originLat, $originLng, $destLatF, $destLngF, true);

        return $this->corsResponse([
            'status'         => 'success',
            'distance_km'    => $route['distance_km'],
            'duration_min'   => $route['duration_min'],
            'bearing_deg'    => $route['bearing_deg'],
            'source'         => $route['source'],
            'api_status'     => $routeService->getApiStatus(),
            'origin'         => [
                'lat'   => $originLat,
                'lng'   => $originLng,
                'label' => $originQuery !== '' ? $originQuery : RouteService::ORIGIN_LABEL,
            ],
            'destination'    => [
                'lat'            => $destLatF,
                'lng'            => $destLngF,
                'label'          => $destAddress !== '' ? $destAddress : "{$destLatF}, {$destLngF}",
                'geocode_source' => $destSource,
            ],
            'geojson'        => [
                'type'     => 'Feature',
                'geometry' => [
                    'type'        => 'LineString',
                    'coordinates' => $route['geometry'] ?? [],
                ],
                'properties' => [
                    'distance_km'  => $route['distance_km'],
                    'duration_min' => $route['duration_min'],
                    'bearing_deg'  => $route['bearing_deg'],
                ],
            ],
            'coordinates'    => $route['geometry'] ?? [],
            'leaflet_coords' => $route['leaflet_coords'] ?? [],
            'steps'          => $route['steps'] ?? [],
        ]);
    }

    /**
     * POST /api/v1/tracking/simulate-step
     * Advances active vehicles accurately along their OSRM road route toward their
     * authorized destination (replaces random-walk coordinate drift).
     */
    public function simulateStep()
    {
        $vehicleModel   = new VehicleModel();
        $telemetryModel = new TelemetryModel();
        $ticketModel    = new TripTicketModel();
        $routeService   = new RouteService();

        $activeTickets = $ticketModel->whereIn('status', ['departed', 'arrived_dest', 'departed_dest', 'issued'])->findAll();
        if (empty($activeTickets)) {
            $activeVehicles = $vehicleModel->whereIn('status', ['in_transit', 'active'])->findAll(3);
            foreach ($activeVehicles as $v) {
                $activeTickets[] = [
                    'id'                     => null,
                    'vehicle_id'             => $v['id'],
                    'authorized_destination' => 'Malacañang Palace Press Briefing Room, Manila',
                ];
            }
        }

        $updatedCount = 0;
        $movements    = [];

        foreach ($activeTickets as $tk) {
            $vehicle = $vehicleModel->find($tk['vehicle_id']);
            if (!$vehicle) {
                continue;
            }

            $curLat      = (float) ($vehicle['current_latitude'] ?: RouteService::ORIGIN_LAT);
            $curLng      = (float) ($vehicle['current_longitude'] ?: RouteService::ORIGIN_LNG);
            $destAddress = (string) ($tk['authorized_destination'] ?? 'Malacañang Palace, Manila');

            $step = $routeService->advanceAlongRoute($curLat, $curLng, $destAddress);

            $newLat   = $step['latitude'];
            $newLng   = $step['longitude'];
            $newSpeed = $step['speed_kmh'];
            $heading  = $step['heading'];
            $distStep = $step['distance_step_km'];
            $newFuel  = max(10.0, round((float) $vehicle['current_fuel_level'] - 0.15, 2));
            $newOdo   = round((float) $vehicle['odometer_km'] + $distStep, 2);

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
                'heading'         => $heading,
                'fuel_level'      => $newFuel,
                'engine_status'   => 'running',
                'battery_voltage' => 14.1,
                'engine_temp_c'   => mt_rand(86, 92),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $movements[] = [
                'vehicle_id'   => (int) $vehicle['id'],
                'vehicle_code' => $vehicle['vehicle_code'],
                'latitude'     => $newLat,
                'longitude'    => $newLng,
                'heading'      => $heading,
                'speed_kmh'    => $newSpeed,
                'remaining_km' => $step['remaining_km'],
                'destination'  => $destAddress,
            ];

            $updatedCount++;
        }

        return $this->corsResponse([
            'status'        => 'success',
            'message'       => "Road-snapped telemetry advanced for {$updatedCount} vehicle(s) in transit.",
            'updated_count' => $updatedCount,
            'movements'     => $movements,
            'timestamp'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /api/v1/map/config
     * Returns active mapping provider configuration and API key health status
     */
    public function getMapConfig()
    {
        $routeService = new RouteService();

        return $this->corsResponse([
            'status'     => 'success',
            'origin'     => [
                'lat'   => RouteService::ORIGIN_LAT,
                'lng'   => RouteService::ORIGIN_LNG,
                'label' => RouteService::ORIGIN_LABEL,
            ],
            'api_status' => $routeService->getApiStatus(),
        ]);
    }

    /**
     * GET /api/v1/geocode?q=...
     * Resolves a destination address or Philippine landmark into accurate coordinates
     */
    public function geocode()
    {
        $query = trim((string) ($this->request->getVar('q') ?? $this->request->getVar('destination') ?? $this->request->getVar('address') ?? ''));
        if ($query === '') {
            return $this->corsResponse([
                'status'  => 'error',
                'message' => 'Missing query parameter q or address.',
            ], 400);
        }

        $routeService = new RouteService();
        $resolved     = $routeService->geocodeDestination($query);

        return $this->corsResponse([
            'status' => 'success',
            'query'  => $query,
            'result' => $resolved,
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

