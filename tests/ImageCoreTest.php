<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageCoreTest extends TestCase
{
    public function testNewCanvasDimensions()
    {
        $img = Image::newCanvas(200, 100);
        $this->assertEquals(200, $img->getWidth());
        $this->assertEquals(100, $img->getHeight());
        $this->assertTrue($img->isImageDefined());
    }

    public function testResetCanvas()
    {
        $img = Image::newCanvas(100, 100);
        $img->resetCanvas(50, 50);
        $this->assertEquals(50, $img->getWidth());
        $this->assertEquals(50, $img->getHeight());
    }

    public function testFromData()
    {
        $src = Image::newCanvas(10, 10);
        $data = $src->getDataPNG();
        $img = Image::fromData($data);
        $this->assertEquals(10, $img->getWidth());
        $this->assertEquals(10, $img->getHeight());
        $this->assertTrue($img->isImageDefined());
    }

    public function testConstants()
    {
        $this->assertEquals('left', Image::ALIGN_LEFT);
        $this->assertEquals('center', Image::ALIGN_CENTER);
        $this->assertEquals('right', Image::ALIGN_RIGHT);
        $this->assertEquals('top', Image::ALIGN_TOP);
        $this->assertEquals('middle', Image::ALIGN_MIDDLE);
        $this->assertEquals('bottom', Image::ALIGN_BOTTOM);
    }

    public function testDestroy()
    {
        $img = Image::newCanvas(10, 10);
        $this->assertTrue($img->isImageDefined());
        $img->destroy();
        $this->assertFalse($img->isImageDefined());
    }

    public function testIsGdImage()
    {
        $img = Image::newCanvas(10, 10);
        $this->assertTrue(Image::isGdImage($img->getImage()));
        $this->assertFalse(Image::isGdImage(null));
        $this->assertFalse(Image::isGdImage('string'));
    }

    public function testClone()
    {
        $img = Image::newCanvas(50, 50);
        $clone = clone $img;
        $this->assertEquals($img->getWidth(), $clone->getWidth());
        $this->assertEquals($img->getHeight(), $clone->getHeight());
        $this->assertNotSame($img->getImage(), $clone->getImage());
    }

    public function testFromPath()
    {
        // Create a temp PNG file
        $tmp = tempnam(sys_get_temp_dir(), 'img_test_') . '.png';
        Image::newCanvas(30, 20)->savePNG($tmp);

        $img = Image::fromPath($tmp);
        $this->assertEquals(30, $img->getWidth());
        $this->assertEquals(20, $img->getHeight());
        $this->assertEquals(3, $img->getType()); // PNG

        unlink($tmp);
    }
}
