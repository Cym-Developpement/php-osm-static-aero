<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Interfaces\Draw;
use Ycdev\OsmStaticAero\Utils\GeographicConverter;

class Compass implements Draw
{
    /**
     * @var LatLng
     */
    private $center;
    /**
     * @var float
     */
    private $size;
    /**
     * @var int
     */
    private $fontSize;
    /**
     * @var string
     */
    private $fontColor;
    /**
     * @var int
     */
    private $strokeWeight = 1;

    /**
     * @param LatLng $center Center of the compass
     * @param float $size Size in meters
     * @param float $fontSize Font size for labels
     * @param string $fontColor Hex color
     */
    public function __construct(LatLng $center, float $size, float $fontSize = 30.0, string $fontColor = '000000')
    {
        $this->center = $center;
        $this->size = $size;
        $this->fontSize = $fontSize;
        $this->fontColor = $fontColor;
    }

    /**
     * @param Image $image The map image
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Compass
    {
        $center = $mapData->convertLatLngToPxPosition($this->center);
        $size = \intval($this->size / $mapData->getMetersByPx());

        $dImage = Image::newCanvas($image->getWidth(), $image->getHeight());

        $this->drawRing($dImage, $center->getX(), $center->getY(), $size, $this->strokeWeight * 3);
        $this->drawRing($dImage, $center->getX(), $center->getY(), $size / 30, $this->strokeWeight * 3);

        $centerCompassSize = \intval($size / 30);
        $dImage->drawLineWithAngle($center->getX(), $center->getY(), 0, $centerCompassSize, ($this->strokeWeight * 3), $this->fontColor);
        $dImage->drawLineWithAngle($center->getX(), $center->getY(), 90, $centerCompassSize, ($this->strokeWeight * 3), $this->fontColor);
        $dImage->drawLineWithAngle($center->getX(), $center->getY(), 180, $centerCompassSize, ($this->strokeWeight * 3), $this->fontColor);
        $dImage->drawLineWithAngle($center->getX(), $center->getY(), 270, $centerCompassSize, ($this->strokeWeight * 3), $this->fontColor);

        $marks = [
            ['length' => 225, 'stroke' => ($this->strokeWeight * 2), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 350, 'stroke' => ($this->strokeWeight * 3), 'drawNumber' => true],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
            ['length' => 125, 'stroke' => ($this->strokeWeight), 'drawNumber' => false],
        ];

        for ($i = 5; $i < 365; $i += 10) {
            foreach ($marks as $offset => $elem) {
                $this->drawMark($dImage, $mapData, ($i + $offset), $elem['length'], $elem['stroke'], $elem['drawNumber']);
            }
        }

        $image->pasteOn($dImage, 0, 0);
        return $this;
    }

    /**
     * Draw a ring : a filled disc whose middle is erased back to transparent.
     *
     * The erasing circle is skipped when the ring is thicker than the radius —
     * which happens on a small compass, where GD would be handed a negative
     * diameter and throw. The ring then degrades to a solid dot.
     *
     * @param Image $dImage Layer being drawn on
     * @param int $x Horizontal center in pixels
     * @param int $y Vertical center in pixels
     * @param float $radius Outer radius in pixels
     * @param float $thickness Ring thickness in pixels
     */
    private function drawRing(Image $dImage, int $x, int $y, float $radius, float $thickness): void
    {
        if ($radius <= 0) {
            return;
        }

        $dImage->drawCircle($x, $y, (int) \round($radius * 2), $this->fontColor);

        $innerRadius = $radius - $thickness;
        if ($innerRadius > 0) {
            $dImage->drawCircle($x, $y, (int) \round($innerRadius * 2), 'ffffffff');
        }
    }

    /**
     * @param Image $dImage
     * @param MapData $mapData
     * @param float $angle
     * @param float $length
     * @param int $stroke
     * @param bool $drawNumber
     */
    private function drawMark(Image &$dImage, MapData $mapData, float $angle, float $length, int $stroke, bool $drawNumber): void
    {
        $start = GeographicConverter::metersToLatLng($this->center, $this->size, $angle);
        $end = GeographicConverter::metersToLatLng($start, $length, $angle);

        $dImage->drawLine(
            $mapData->convertLatLngToPxPosition($start)->getX(),
            $mapData->convertLatLngToPxPosition($start)->getY(),
            $mapData->convertLatLngToPxPosition($end)->getX(),
            $mapData->convertLatLngToPxPosition($end)->getY(),
            $stroke,
            $this->fontColor
        );

        if ($drawNumber) {
            $centerText = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($end, $length, $angle));
            $dImage->writeText(\strval($angle), __DIR__ . '/resources/CascadiaCode-Bold.ttf', $this->fontSize, 'ffffff', $centerText->getX(), $centerText->getY(), Image::ALIGN_CENTER, Image::ALIGN_MIDDLE, (360 - $angle), 0);
            $dImage->writeText(\strval($angle), __DIR__ . '/resources/CascadiaCode-Light.ttf', $this->fontSize, $this->fontColor, $centerText->getX(), $centerText->getY(), Image::ALIGN_CENTER, Image::ALIGN_MIDDLE, (360 - $angle), 0);
        }
    }

    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        $distance = $this->size * 1.4142;
        return [
            GeographicConverter::metersToLatLng($this->center, $distance, 315),
            GeographicConverter::metersToLatLng($this->center, $distance, 135),
        ];
    }
}
