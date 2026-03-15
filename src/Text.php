<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Interfaces\Draw;

class Text implements Draw
{
    /**
     * @var LatLng
     */
    private $center;
    /**
     * @var string
     */
    private $text;
    /**
     * @var int
     */
    private $fontSize;
    /**
     * @var string
     */
    private $fontColor;

    /**
     * @param LatLng $center Position of the text
     * @param string $text Text to display
     * @param int $fontSize Font size
     * @param string $fontColor Hex color
     */
    public function __construct(LatLng $center, string $text, int $fontSize = 30, string $fontColor = '000000')
    {
        $this->center = $center;
        $this->text = $text;
        $this->fontSize = $fontSize;
        $this->fontColor = $fontColor;
    }

    /**
     * @param Image $image The map image
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Text
    {
        $center = $mapData->convertLatLngToPxPosition($this->center);
        $image->writeText($this->text, __DIR__ . '/resources/CascadiaCode-Bold.ttf', $this->fontSize, 'ffffff', $center->getX(), $center->getY());
        $image->writeText($this->text, __DIR__ . '/resources/CascadiaCode-Light.ttf', $this->fontSize, $this->fontColor, $center->getX(), $center->getY(), Image::ALIGN_CENTER, Image::ALIGN_MIDDLE, 0, 0);

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
}
