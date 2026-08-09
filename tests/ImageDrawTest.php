<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageDrawTest extends TestCase
{
    public function testDrawLine()
    {
        $img = Image::newCanvas(100, 100);
        $result = $img->drawLine(10, 10, 90, 90, 2, 'FF0000');
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }

    public function testDrawPolygonTriangle()
    {
        $img = Image::newCanvas(100, 100);
        $result = $img->drawPolygon([10, 10, 90, 10, 50, 90], '00FF00');
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }

    public function testDrawCircle()
    {
        $img = Image::newCanvas(100, 100);
        $result = $img->drawCircle(50, 50, 40, '0000FF');
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }

    public function testDrawRectangle()
    {
        $img = Image::newCanvas(100, 100);
        $result = $img->drawRectangle(10, 10, 90, 90, 'FF0000');
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }

    public function testDrawLineWithAngle()
    {
        $img = Image::newCanvas(100, 100);
        $result = $img->drawLineWithAngle(50, 50, 45.0, 30.0, 2, '000000');
        $this->assertInstanceOf(Image::class, $result);
    }
}
