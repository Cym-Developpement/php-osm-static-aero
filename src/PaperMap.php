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
        // PaperSize::px() rend des millimetres convertis, donc fractionnaires :
        // les tailles en pixels sont arrondies avant d'etre passees aux couches,
        // qui les attendent entieres.
        $this->size = \array_map([$this, 'toPx'], PaperSize::px($size));
        $this->bordurePx = $this->toPx(PaperSize::px([$this->options['bordure'], 0])[0]);
        $this->layerSize = [($this->size[0] - (2 * $this->bordurePx)), ($this->size[1] - (2 * $this->bordurePx))];
        $drawLayer = new OpenStreetMap($this->center, $this->options['zoom'], $this->layerSize[0], $this->layerSize[1], false, $this->toPx(256 * $this->options['factor']));

        $this->layerSize = [
            $this->toPx($this->layerSize[0] / $this->options['factor']),
            $this->toPx($this->layerSize[1] / $this->options['factor']),
        ];

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
     * Arrondit une dimension en pixels.
     *
     * @param float|int $value
     * @return int
     */
    private function toPx($value): int
    {
        return (int) \round($value);
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
     * Ajoute en bas de la carte le bandeau de legende OpenAIP.
     *
     * La hauteur se declare en pourcentage de la hauteur de la carte : c'est le
     * seul reglage a revoir d'un format a l'autre, la mise en page interne s'y
     * adapte seule. Le bandeau se superpose a la carte plutot que de la
     * retailler, comme les autres surcouches.
     *
     * L'objet est retourne pour permettre d'affiner l'apparence :
     *
     *     $map->addOpenAipLegend(12.0)->setTitle('Legende')->setBackground('ffffff19');
     *
     * @param float $heightPercent Hauteur du bandeau, en pourcentage
     * @return OpenAipLegend Le bandeau ajoute, pour chainage
     */
    public function addOpenAipLegend(float $heightPercent = 15.0): OpenAipLegend
    {
        $legend = new OpenAipLegend($heightPercent);
        $this->draw()->addDraw($legend);

        return $legend;
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
                // Reaffecter suffit a liberer l'original : depuis PHP 8.0 une
                // GdImage est un objet compte par references, et imagedestroy()
                // n'a plus d'effet — il est deprecie en 8.5.
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

            // Une couche A0 pese plusieurs centaines de Mo : on relache tout de
            // suite plutot que d'attendre la fin de l'iteration suivante.
            unset($gdImage);
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
        unset($img);
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
