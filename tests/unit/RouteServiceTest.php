<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\RouteService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RouteServiceTest extends CIUnitTestCase
{
    private RouteService $routeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routeService = new RouteService();
    }

    public function testApiKeyValidationGracefullyHandlesEmptyAndPlaceholders(): void
    {
        $this->assertFalse($this->routeService->hasValidKey(''));
        $this->assertFalse($this->routeService->hasValidKey(null));
        $this->assertFalse($this->routeService->hasValidKey('YOUR_API_KEY'));
        $this->assertFalse($this->routeService->hasValidKey('change-me'));
        $this->assertTrue($this->routeService->hasValidKey('AIzaSyD-ExampleValidKeyString123456'));

        $status = $this->routeService->getApiStatus();
        $this->assertSame('none (keyless fallback active)', $status['api_key_errors']);
        $this->assertNotEmpty($status['active_provider']);
    }

    public function testGeocodeDestinationResolvesPhilippineLandmarksAccurately(): void
    {
        $malacanang = $this->routeService->geocodeDestination('Malacañang Palace Press Briefing Room, Manila');
        $this->assertSame('pia_gazetteer', $malacanang['source']);
        $this->assertEqualsWithDelta(14.5941, $malacanang['lat'], 0.01);
        $this->assertEqualsWithDelta(120.9946, $malacanang['lng'], 0.01);

        $senate = $this->routeService->geocodeDestination('Senate of the Philippines, GSIS Bldg, Pasay');
        $this->assertSame('pia_gazetteer', $senate['source']);
        $this->assertEqualsWithDelta(14.5378, $senate['lat'], 0.01);
        $this->assertEqualsWithDelta(120.9818, $senate['lng'], 0.01);

        $rawCoords = $this->routeService->geocodeDestination('14.657200, 121.045000');
        $this->assertSame('coordinates', $rawCoords['source']);
        $this->assertSame(14.6572, $rawCoords['lat']);
        $this->assertSame(121.045, $rawCoords['lng']);
    }

    public function testAdvanceAlongRouteMovesTowardDestinationWithValidCompassBearing(): void
    {
        $step = $this->routeService->advanceAlongRoute(
            RouteService::ORIGIN_LAT,
            RouteService::ORIGIN_LNG,
            'Malacañang Palace, Manila'
        );

        $this->assertArrayHasKey('latitude', $step);
        $this->assertArrayHasKey('longitude', $step);
        $this->assertArrayHasKey('heading', $step);
        $this->assertArrayHasKey('speed_kmh', $step);
        $this->assertGreaterThanOrEqual(0.0, $step['heading']);
        $this->assertLessThan(360.0, $step['heading']);
        $this->assertGreaterThan(0.0, $step['distance_step_km']);
    }
}
