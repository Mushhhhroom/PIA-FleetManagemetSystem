<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;

class RouteService
{
    protected string $osrmUrl;

    public function __construct()
    {
        $this->osrmUrl = env('OSRM_URL', 'https://router.project-osrm.org/route/v1/driving/');
    }

    /**
     * Get route distance and coordinates from OSRM or fallback
     */
    public function getRoute(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $client = \Config\Services::curlrequest(['timeout' => 6, 'connect_timeout' => 4]);

        try {
            $url = $this->osrmUrl . "{$originLng},{$originLat};{$destLng},{$destLat}?overview=simplified&geometries=geojson";
            $response = $client->get($url, ['headers' => ['User-Agent' => 'PIA-AFMS/2.1']]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                if (!empty($data['routes'][0])) {
                    $route = $data['routes'][0];
                    return [
                        'distance_km' => round($route['distance'] / 1000, 2),
                        'duration_min'=> round($route['duration'] / 60, 1),
                        'geometry'    => $route['geometry']['coordinates'] ?? [],
                        'source'      => 'osrm',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback to Great-Circle curvature calculation
        }

        // Great circle distance with 1.3 highway tortuosity factor
        $theta = $originLng - $destLng;
        $dist = sin(deg2rad($originLat)) * sin(deg2rad($destLat)) + cos(deg2rad($originLat)) * cos(deg2rad($destLat)) * cos(deg2rad($theta));
        $dist = acos(min(1, max(-1, $dist)));
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $km = round($miles * 1.609344 * 1.3, 1);

        return [
            'distance_km'  => $km,
            'duration_min' => round(($km / 50) * 60), // approx 50 km/h average commercial speed
            'geometry'     => [
                [$originLng, $originLat],
                [round(($originLng + $destLng) / 2, 6), round(($originLat + $destLat) / 2, 6)],
                [$destLng, $destLat]
            ],
            'source'       => 'fallback',
        ];
    }
}
