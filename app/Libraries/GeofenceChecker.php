<?php

namespace App\Libraries;

class GeofenceChecker
{
    protected array $geofences = [
        [
            'name'       => 'PIA Central Office Compound (Visayas Ave, QC)',
            'center_lat' => 14.657200,
            'center_lng' => 121.045000,
            'radius_km'  => 2.5,
            'type'       => 'compound'
        ],
        [
            'name'       => 'Malacañang / PCO Press Complex (Manila)',
            'center_lat' => 14.594200,
            'center_lng' => 120.994600,
            'radius_km'  => 3.0,
            'type'       => 'government'
        ],
        [
            'name'       => 'Manila Port / Intramuros Government Zone',
            'center_lat' => 14.583333,
            'center_lng' => 120.966667,
            'radius_km'  => 5.0,
            'type'       => 'hub'
        ],
        [
            'name'       => 'Clark Regional Government Center',
            'center_lat' => 15.185500,
            'center_lng' => 120.540600,
            'radius_km'  => 6.0,
            'type'       => 'regional'
        ],
        [
            'name'       => 'CALABARZON Regional Hub (Laguna)',
            'center_lat' => 14.282900,
            'center_lng' => 121.077200,
            'radius_km'  => 4.5,
            'type'       => 'regional'
        ],
    ];

    public function check(float $lat, float $lng): ?array
    {
        foreach ($this->geofences as $fence) {
            $dist = $this->distanceKm($lat, $lng, $fence['center_lat'], $fence['center_lng']);
            if ($dist <= $fence['radius_km']) {
                return array_merge($fence, ['distance_from_center_km' => round($dist, 2)]);
            }
        }
        return null;
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(1, max(-1, $dist)));
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515 * 1.609344;
    }
}
