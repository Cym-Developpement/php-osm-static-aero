<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageCompositeTest extends TestCase
{
    public function testPasteOnCenter()
    {
        $canvas = Image::newCanvas(100, 100);
        $overlay = Image::newCanvas(50, 50);
        $overlay->drawRectangle(0, 0, 50, 50, 'FF0000');

        $result = $canvas->pasteOn($overlay, Image::ALIGN_CENTER, Image::ALIGN_MIDDLE);
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($canvas->isImageDefined());
    }

    public function testPasteOnPosition()
    {
        $canvas = Image::newCanvas(100, 100);
        $overlay = Image::newCanvas(20, 20);

        $result = $canvas->pasteOn($overlay, 10, 10);
        $this->assertInstanceOf(Image::class, $result);
    }

    public function testPasteOnAlignments()
    {
        $canvas = Image::newCanvas(100, 100);
        $overlay = Image::newCanvas(20, 20);

        $canvas->pasteOn($overlay, Image::ALIGN_LEFT, Image::ALIGN_TOP);
        $canvas->pasteOn($overlay, Image::ALIGN_RIGHT, Image::ALIGN_BOTTOM);
        $this->assertTrue($canvas->isImageDefined());
    }

    public function testSetOpacity()
    {
        $img = Image::newCanvas(50, 50);
        $img->drawRectangle(0, 0, 50, 50, 'FF0000');
        $result = $img->setOpacity(0.5);
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }
}
