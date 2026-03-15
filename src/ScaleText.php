<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Interfaces\Draw;
use Ycdev\OsmStaticAero\Utils\GeographicConverter;

/**
 * Ycdev\OsmStaticAero\ScaleText renders a scale bar with distance and ratio on the map.
 *
 * @package Ycdev\OsmStaticAero
 */
class ScaleText implements Draw
{
    /**
     * @var LatLng
     */
    private $center;
    /**
     * @var float
     */
    private $meters;
    /**
     * @var string
     */
    private $color;

    /**
     * @param LatLng $center Position of the scale bar
     * @param float $meters Length of the scale bar in meters
     * @param string $color Hex color
     */
    public function __construct(LatLng $center, float $meters = 5000, string $color = '991410')
    {
        $this->center = $center;
        $this->meters = $meters;
        $this->color = $color;
    }

    /**
     * @param Image $image The map image
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): ScaleText
    {
        $start = $mapData->convertLatLngToPxPosition($this->center);
        $end = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($this->center, $this->meters, 90));
        $startVert = GeographicConverter::metersToLatLng($this->center, ($this->meters / 10.0), 360);
        $endVert = GeographicConverter::metersToLatLng($this->center, ($this->meters / 10.0), 180);
        $middleText = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($this->center, ($this->meters / 2), 90));

        $image->drawLine($start->getX(), $start->getY(), $end->getX(), $end->getY(), 12, $this->color);

        $image->drawLine(
            $mapData->convertLatLngToPxPosition($startVert)->getX(),
            $mapData->convertLatLngToPxPosition($startVert)->getY(),
            $mapData->convertLatLngToPxPosition($endVert)->getX(),
            $mapData->convertLatLngToPxPosition($endVert)->getY(),
            10,
            $this->color
        );

        $startVert2 = GeographicConverter::metersToLatLng($startVert, $this->meters, 90);
        $endVert2 = GeographicConverter::metersToLatLng($endVert, $this->meters, 90);

        $image->drawLine(
            $mapData->convertLatLngToPxPosition($startVert2)->getX(),
            $mapData->convertLatLngToPxPosition($startVert2)->getY(),
            $mapData->convertLatLngToPxPosition($endVert2)->getX(),
            $mapData->convertLatLngToPxPosition($endVert2)->getY(),
            10,
            $this->color
        );

        $boldFont = __DIR__ . '/resources/CascadiaCode-Bold.ttf';
        $lightFont = __DIR__ . '/resources/CascadiaCode-Light.ttf';

        $distanceText = \intval($this->meters) . 'm';
        $distanceY = $mapData->convertLatLngToPxPosition($startVert)->getY() + 20;

        $image->writeText($distanceText, $boldFont, 40, 'ffffff', $middleText->getX(), $distanceY);
        $image->writeText($distanceText, $lightFont, 40, $this->color, $middleText->getX(), $distanceY);

        $scaleText = $this->getScaleOneBy($mapData);
        $scaleY = $mapData->convertLatLngToPxPosition($endVert)->getY() - 20;

        $image->writeText($scaleText, $boldFont, 40, 'ffffff', $middleText->getX(), $scaleY);
        $image->writeText($scaleText, $lightFont, 40, $this->color, $middleText->getX(), $scaleY);

        return $this;
    }

    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        return [$this->center, $this->center];
    }

    /**
     * Calculate and format the scale ratio
     * @param MapData $mapData
     * @return string
     */
    public function getScaleOneBy(MapData $mapData): string
    {
        $scale = $mapData->getScale() / 10000;
        $scale = \intval($scale) * 10000;
        return '&asymp; 1:' . \number_format($scale, 0, '.', ' ');
    }
}
