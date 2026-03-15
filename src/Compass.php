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
     * @param int $fontSize Font size for labels
     * @param string $fontColor Hex color
     */
    public function __construct(LatLng $center, float $size, int $fontSize = 30, string $fontColor = '000000')
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

        $dImage->drawCircle($center->getX(), $center->getY(), $size * 2, $this->fontColor);
        $dImage->drawCircle($center->getX(), $center->getY(), ($size - ($this->strokeWeight * 3)) * 2, 'ffffffff');

        $dImage->drawCircle($center->getX(), $center->getY(), ($size / 30) * 2, $this->fontColor);
        $dImage->drawCircle($center->getX(), $center->getY(), (($size / 30) - ($this->strokeWeight * 3)) * 2, 'ffffffff');

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
