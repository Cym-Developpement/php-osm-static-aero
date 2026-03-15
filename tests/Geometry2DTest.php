<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Geometry2D;

class Geometry2DTest extends TestCase
{
    public function testDegrees0to360NegativeAngle()
    {
        $this->assertEqualsWithDelta(350.0, Geometry2D::degrees0to360(-10), 0.0001);
        $this->assertEqualsWithDelta(0.0, Geometry2D::degrees0to360(-360), 0.0001);
        $this->assertEqualsWithDelta(180.0, Geometry2D::degrees0to360(-180), 0.0001);
    }

    public function testDegrees0to360OverflowAngle()
    {
        $this->assertEqualsWithDelta(10.0, Geometry2D::degrees0to360(370), 0.0001);
        $this->assertEqualsWithDelta(0.0, Geometry2D::degrees0to360(360), 0.0001);
        $this->assertEqualsWithDelta(90.0, Geometry2D::degrees0to360(450), 0.0001);
    }

    public function testDegrees0to360ExactValues()
    {
        $this->assertEqualsWithDelta(0.0, Geometry2D::degrees0to360(0), 0.0001);
        $this->assertEqualsWithDelta(90.0, Geometry2D::degrees0to360(90), 0.0001);
        $this->assertEqualsWithDelta(180.0, Geometry2D::degrees0to360(180), 0.0001);
        $this->assertEqualsWithDelta(270.0, Geometry2D::degrees0to360(270), 0.0001);
    }

    public function testGetDstXYAngle0()
    {
        $result = Geometry2D::getDstXY(0, 0, 0, 100);
        $this->assertEqualsWithDelta(100, $result['x'], 0.001);
        $this->assertEqualsWithDelta(0, $result['y'], 0.001);
    }

    public function testGetDstXYAngle90()
    {
        $result = Geometry2D::getDstXY(0, 0, 90, 100);
        $this->assertEqualsWithDelta(0, $result['x'], 0.001);
        $this->assertEqualsWithDelta(-100, $result['y'], 0.001);
    }

    public function testGetDstXYAngle180()
    {
        $result = Geometry2D::getDstXY(0, 0, 180, 100);
        $this->assertEqualsWithDelta(-100, $result['x'], 0.001);
        $this->assertEqualsWithDelta(0, $result['y'], 0.001);
    }

    public function testGetDstXYAngle270()
    {
        $result = Geometry2D::getDstXY(0, 0, 270, 100);
        $this->assertEqualsWithDelta(0, $result['x'], 0.001);
        $this->assertEqualsWithDelta(100, $result['y'], 0.001);
    }

    public function testGetDstXYRounded()
    {
        $result = Geometry2D::getDstXYRounded(0, 0, 45, 100);
        $this->assertIsFloat($result['x']);
        $this->assertIsFloat($result['y']);
        $this->assertEquals(\round($result['x']), $result['x']);
        $this->assertEquals(\round($result['y']), $result['y']);
    }

    public function testGetAngleAndLengthRoundTrip()
    {
        $originX = 10;
        $originY = 20;
        $dstX = 50;
        $dstY = 60;

        $result = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        $this->assertArrayHasKey('angle', $result);
        $this->assertArrayHasKey('length', $result);

        $dst = Geometry2D::getDstXY($originX, $originY, $result['angle'], $result['length']);
        $this->assertEqualsWithDelta($dstX, $dst['x'], 0.001);
        $this->assertEqualsWithDelta($dstY, $dst['y'], 0.001);
    }

    public function testGetAngleAndLengthHorizontal()
    {
        $result = Geometry2D::getAngleAndLengthFromPoints(0, 0, 100, 0);
        // angle is 360 (equivalent to 0)
        $this->assertEqualsWithDelta(360, $result['angle'], 0.001);
        $this->assertEqualsWithDelta(100, $result['length'], 0.001);
    }

    public function testGetAngleAndLengthVertical()
    {
        $result = Geometry2D::getAngleAndLengthFromPoints(0, 0, 0, 100);
        $this->assertEqualsWithDelta(100, $result['length'], 0.001);
    }
}
