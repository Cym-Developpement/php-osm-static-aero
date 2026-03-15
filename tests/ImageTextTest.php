<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageTextTest extends TestCase
{
    private function getFontPath(): string
    {
        return __DIR__ . '/../src/resources/font.ttf';
    }

    public function testWriteTextAndGetBoundingBoxKeys()
    {
        $fontPath = $this->getFontPath();
        if (!file_exists($fontPath)) {
            $this->markTestSkipped('Font file not found');
        }

        $img = Image::newCanvas(200, 100);
        $bbox = $img->writeTextAndGetBoundingBox(
            'Hello',
            $fontPath,
            12,
            '000000',
            10,
            50,
            Image::ALIGN_LEFT,
            Image::ALIGN_TOP
        );

        $this->assertArrayHasKey('top-left', $bbox);
        $this->assertArrayHasKey('top-right', $bbox);
        $this->assertArrayHasKey('bottom-left', $bbox);
        $this->assertArrayHasKey('bottom-right', $bbox);
        $this->assertArrayHasKey('baseline', $bbox);

        $this->assertArrayHasKey('x', $bbox['top-left']);
        $this->assertArrayHasKey('y', $bbox['top-left']);
    }

    public function testWriteTextDoesNotCrash()
    {
        $fontPath = $this->getFontPath();
        if (!file_exists($fontPath)) {
            $this->markTestSkipped('Font file not found');
        }

        $img = Image::newCanvas(200, 100);
        $result = $img->writeText(
            'Test',
            $fontPath,
            14,
            'FF0000',
            Image::ALIGN_CENTER,
            Image::ALIGN_MIDDLE
        );
        $this->assertInstanceOf(Image::class, $result);
        $this->assertTrue($img->isImageDefined());
    }
}
