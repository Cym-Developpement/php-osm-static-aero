<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\PaperSize\PaperSize;

class PaperMap
{
    /**
     * @var array
     */
    private $size = [841, 1189];
    /**
     * @var array
     */
    private $layerSize = [841, 1189];
    /**
     * @var array
     */
    private $tileLayers = [];
    /**
     * @var OpenStreetMap[]
     */
    private $mapLayers = [];
    /**
     * @var array
     */
    private $mapImage = [];
    /**
     * @var LatLng
     */
    private $center;
    /**
     * @var int
     */
    private $bordurePx;
    /**
     * @var array
     */
    private $options = [
        'zoom' => 12,
        'bordure' => 10,
        'legend' => false,
        'legendBackground' => 'ffffff',
        'legendLogo' => null,
        'legendTitle' => '',
        'factor' => 1.0,
    ];

    /**
     * @param array $size Paper size in mm (e.g. PaperSize::A4)
     * @param LatLng $center Center of the map
     * @param array $options Map options
     * @param array $tileLayers Array of tile layer configurations
     */
    public function __construct(array $size, LatLng $center, $options = [], array $tileLayers = [])
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
        $this->center = $center;
        $this->size = PaperSize::px($size);
        $this->bordurePx = PaperSize::px([$this->options['bordure'], 0])[0];
        $this->layerSize = [($this->size[0] - (2 * $this->bordurePx)), ($this->size[1] - (2 * $this->bordurePx))];
        $drawLayer = new OpenStreetMap($this->center, $this->options['zoom'], $this->layerSize[0], $this->layerSize[1], false, (256 * $this->options['factor']));

        $this->layerSize = [($this->size[0] - (2 * $this->bordurePx)) / $this->options['factor'], ($this->size[1] - (2 * $this->bordurePx)) / $this->options['factor']];

        foreach ($tileLayers as $tileLayer) {
            if (\is_string($tileLayer) && \defined('Ycdev\\OsmStaticAero\\TileLayer::' . $tileLayer)) {
                $tileLayer = \constant('Ycdev\\OsmStaticAero\\TileLayer::' . $tileLayer);
            }

            if (\is_array($tileLayer)) {
                $tileLayer = new TileLayer($tileLayer[1], $tileLayer[2]);
            } else {
                $tileLayer = TileLayer::defaultTileLayer();
            }

            $this->mapLayers[] = new OpenStreetMap($this->center, $this->options['zoom'], $this->layerSize[0], $this->layerSize[1], $tileLayer, 256, 1.0, false);
        }
        $this->mapLayers[] = $drawLayer;
    }

    /**
     * Get the draw layer OpenStreetMap instance
     * @return OpenStreetMap
     */
    public function draw(): OpenStreetMap
    {
        return $this->mapLayers[\count($this->mapLayers) - 1];
    }

    /**
     * Generate the composite image
     * @return resource|\GdImage
     */
    public function getImage()
    {
        $dest_image = \imagecreatetruecolor($this->size[0], $this->size[1]);
        \imagesavealpha($dest_image, true);
        $trans_background = \imagecolorallocatealpha($dest_image, 255, 255, 255, 0);
        \imagefill($dest_image, 0, 0, $trans_background);

        $layerCount = \count($this->mapLayers);

        foreach ($this->mapLayers as $key => $layer) {
            $layerImage = $layer->getImage();
            $gdImage = \imagecreatefromstring($layerImage->getDataPNG());
            $layerImage->destroy();

            if ($this->options['factor'] !== 1.0 && $key !== ($layerCount - 1)) {
                $scaled = \imagescale($gdImage, (\imagesx($gdImage) * $this->options['factor']));
                \imagedestroy($gdImage);
                $gdImage = $scaled;
            }

            \imagecopy(
                $dest_image,
                $gdImage,
                $this->bordurePx,
                $this->bordurePx,
                0,
                0,
                \imagesx($gdImage),
                \imagesy($gdImage)
            );
            \imagedestroy($gdImage);
        }

        return $dest_image;
    }

    /**
     * Save the map image to a PNG file
     * @param string $path File path
     */
    public function saveImage(string $path): void
    {
        $img = $this->getImage();
        \imagepng($img, $path);
        \imagedestroy($img);
    }

    /**
     * Parse a distance string to meters
     * @param string|float $distance Distance string (e.g. "5km", "500m")
     * @return float Distance in meters
     */
    public function dist($distance): float
    {
        $distance = \strtolower($distance);
        $unit = 1.0;
        $floatDistance = \floatval($distance);
        if (\substr($distance, -2) == 'km') {
            $unit = 1000.0;
            $floatDistance = \floatval(\str_replace('km', '', $distance));
        } elseif (\substr($distance, -1) == 'm') {
            $unit = 1.0;
            $floatDistance = \floatval(\str_replace('m', '', $distance));
        }
        return ($floatDistance * $unit);
    }
}
