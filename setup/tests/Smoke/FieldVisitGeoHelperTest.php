<?php
declare(strict_types=1);

use App\Helpers\FieldVisitGeoHelper;
use PHPUnit\Framework\TestCase;

final class FieldVisitGeoHelperTest extends TestCase
{
    public function testHaversineZeroDistance(): void
    {
        $d = FieldVisitGeoHelper::haversineDistanceMeters(38.4, 27.1, 38.4, 27.1);
        self::assertLessThan(0.01, $d);
    }

    public function testHaversineKnownDistance(): void
    {
        $d = FieldVisitGeoHelper::haversineDistanceMeters(38.4192, 27.1287, 38.4237, 27.1428);
        self::assertGreaterThan(1000, $d);
        self::assertLessThan(2000, $d);
    }

    public function testGeofenceInside(): void
    {
        $status = FieldVisitGeoHelper::geofenceStatus(38.4, 27.1, ['lat' => 38.4001, 'lon' => 27.1001], 500);
        self::assertFalse($status['outside']);
        self::assertNotNull($status['distance_m']);
    }

    public function testGeofenceOutside(): void
    {
        $status = FieldVisitGeoHelper::geofenceStatus(38.5, 27.2, ['lat' => 38.4, 'lon' => 27.1], 500);
        self::assertTrue($status['outside']);
    }

    public function testAccuracyAcceptable(): void
    {
        self::assertTrue(FieldVisitGeoHelper::isAccuracyAcceptable(50.0, 100));
        self::assertFalse(FieldVisitGeoHelper::isAccuracyAcceptable(150.0, 100));
        self::assertTrue(FieldVisitGeoHelper::isAccuracyAcceptable(null, 100));
    }

    public function testParseCoordsString(): void
    {
        $parsed = FieldVisitGeoHelper::parseCoordsString('38.419200,27.128700');
        self::assertNotNull($parsed);
        self::assertEqualsWithDelta(38.4192, $parsed['lat'], 0.0001);
    }
}
