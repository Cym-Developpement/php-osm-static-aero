<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageOutputTest extends TestCase
{
    public function testSavePNG()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_test_') . '.png';
        $img = Image::newCanvas(50, 50);
        $img->drawRectangle(0, 0, 50, 50, 'FF0000');

        $result = $img->savePNG($tmp);
        $this->assertTrue($result);
        $this->assertFileExists($tmp);
        $this->assertGreaterThan(0, filesize($tmp));

        // Verify it's a valid PNG
        $info = getimagesize($tmp);
        $this->assertEquals(3, $info[2]); // IMAGETYPE_PNG

        unlink($tmp);
    }

    public function testSaveJPG()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_test_') . '.jpg';
        $img = Image::newCanvas(50, 50);

        $result = $img->saveJPG($tmp, 80);
        $this->assertTrue($result);
        $this->assertFileExists($tmp);

        unlink($tmp);
    }

    public function testSaveGIF()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_test_') . '.gif';
        $img = Image::newCanvas(50, 50);

        $result = $img->saveGIF($tmp);
        $this->assertTrue($result);
        $this->assertFileExists($tmp);

        unlink($tmp);
    }

    public function testGetDataPNG()
    {
        $img = Image::newCanvas(50, 50);
        $data = $img->getDataPNG();
        $this->assertNotEmpty($data);
        // PNG signature
        $this->assertStringStartsWith("\x89PNG", $data);
    }

    public function testGetDataJPG()
    {
        $img = Image::newCanvas(50, 50);
        $data = $img->getDataJPG();
        $this->assertNotEmpty($data);
    }

    public function testGetDataGIF()
    {
        $img = Image::newCanvas(50, 50);
        $data = $img->getDataGIF();
        $this->assertNotEmpty($data);
        $this->assertStringStartsWith('GIF', $data);
    }
}
