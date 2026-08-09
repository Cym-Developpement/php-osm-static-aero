<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Image;

/**
 * Ycdev\OsmStaticAero\TileLayer define tile server url and related configuration
 *
 * @package Ycdev\OsmStaticAero
 * @author Stephan Strate <hello@stephan.codes>
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class TileLayer
{
    const DEFAULT = ['default', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', '© OpenStreetMap contributors'];
    const OPENAIP = ['openaip', 'https://api.tiles.openaip.net/api/data/openaip/{z}/{x}/{y}.png?apiKey={apiKey}', '© OpenAIP contributors'];
    const OSMFR = ['openstreetmapfr', 'https://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', '© OpenStreetMap contributors'];
    const OPENTOPO = ['opentopomap', 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', 'Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)'];

    /**
     * Nom de la variable d'environnement consultee a defaut de cle explicite.
     */
    const OPENAIP_ENV = 'OPENAIP_API_KEY';

    /**
     * Cle d'API OpenAIP, a definir une fois au demarrage de l'application.
     *
     * A defaut, la variable d'environnement OPENAIP_API_KEY est consultee.
     *
     * @var string|null
     */
    public static $openaipApiKey = null;

    /**
     * Default tile server. OpenStreetMaps with related attribution text
     * @return TileLayer default tile server
     */
    public static function defaultTileLayer(): TileLayer
    {
        return new TileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', '© OpenStreetMap contributors');
    }

    /**
     * Couche OpenAIP prete a l'emploi, avec une cle d'API explicite.
     *
     * @param string|null $apiKey Cle d'API ; null pour utiliser la cle globale
     *                            ou la variable d'environnement
     * @return TileLayer
     * @throws \InvalidArgumentException si aucune cle n'est disponible
     */
    public static function openaip(?string $apiKey = null): TileLayer
    {
        $url = self::OPENAIP[1];

        if ($apiKey !== null) {
            $url = \str_replace('{apiKey}', \rawurlencode($apiKey), $url);
        }

        // Sans cle explicite, le constructeur resout {apiKey} lui-meme.
        return new TileLayer($url, self::OPENAIP[2]);
    }

    /**
     * Cle d'API OpenAIP effective : propriete statique, puis environnement.
     *
     * @return string|null
     */
    public static function resolveApiKey()
    {
        if (static::$openaipApiKey !== null && static::$openaipApiKey !== '') {
            return static::$openaipApiKey;
        }

        $env = \getenv(self::OPENAIP_ENV);

        return ($env !== false && $env !== '') ? $env : null;
    }

    /**
     * @var string Tile server url, defaults to OpenStreetMap tile server
     */
    protected $url;

    /**
     * @var string Tile server attribution according to license
     */
    protected $attributionText;

    /**
     * @var string[] Tile server subdomains
     */
    protected $subdomains;

    /**
     * @var float Opacity
     */
    protected $opacity = 1;

    /*
     * @var int Max zoom value
     */
    protected $maxZoom = 20;

    /*
     * @var int Min zoom value
     */
    protected $minZoom = 0;


    /**
     * @array $curlOptions Array of curl options
     */
    protected $curlOptions = [];

    /**
     * @bool $failCurlOnError If true, curl will throw an exception on error.
     */
    protected $failCurlOnError = false;

    /**
     * TileLayer constructor
     * @param string $url tile server url with placeholders (`x`, `y`, `z`, `r`, `s`)
     * @param string $attributionText tile server attribution text
     * @param string $subdomains tile server subdomains
     * @param array $curlOptions Array of curl options
     * @param bool $failCurlOnError If true, curl will throw an exception on error.
     */
    public function __construct(string $url, string $attributionText, string $subdomains = 'abc', array $curlOptions = [], bool $failCurlOnError = false)
    {
        // {apiKey} est resolu ici, pas au telechargement : une cle absente doit
        // arreter le programme avant la premiere requete, pas produire des
        // centaines de tuiles en 401 dont il faudrait ensuite deduire la cause.
        if (\strpos($url, '{apiKey}') !== false) {
            $apiKey = static::resolveApiKey();

            if ($apiKey === null) {
                throw new \InvalidArgumentException(
                    'Cette couche exige une cle d\'API OpenAIP. Renseignez '
                    . 'TileLayer::$openaipApiKey, la variable d\'environnement '
                    . self::OPENAIP_ENV . ', ou appelez TileLayer::openaip($cle).'
                );
            }

            $url = \str_replace('{apiKey}', \rawurlencode($apiKey), $url);
        }

        $this->url = $url;
        $this->attributionText = $attributionText;
        $this->subdomains = \str_split($subdomains);
        $this->curlOptions = $curlOptions;
        $this->failCurlOnError = $failCurlOnError;
    }

    /**
     * Set opacity of the layer
     * @param float $opacity Opacity value (0 to 1)
     * @return $this Fluent interface
     */
    public function setOpacity(float $opacity)
    {
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * Set a max zoom value
     * @param int $maxZoom
     * @return $this Fluent interface
     */
    public function setMaxZoom(int $maxZoom)
    {
        $this->maxZoom = $maxZoom;
        return $this;
    }

    /**
     * Get max zoom value
     * @return int
     */
    public function getMaxZoom(): int
    {
        return $this->maxZoom;
    }

    /**
     * Set a min zoom value
     * @param int $minZoom
     * @return $this Fluent interface
     */
    public function setMinZoom(int $minZoom)
    {
        $this->minZoom = $minZoom;
        return $this;
    }

    /**
     * Get min zoom value
     * @return int
     */
    public function getMinZoom(): int
    {
        return $this->minZoom;
    }

    /**
     * Check if zoom value is between min zoom and max zoom
     * @param int $zoom Zoom value to be checked
     * @return int
     */
    public function checkZoom(int $zoom): int
    {
        return \min(\max($zoom, $this->minZoom), $this->maxZoom);
    }

    /**
     * Get tile url for coordinates and zoom level
     * @param int $x x coordinate
     * @param int $y y coordinate
     * @param int $z zoom level
     * @return string tile url
     */
    public function getTileUrl(int $x, int $y, int $z): string
    {
        return \str_replace(
            ['{r}', '{s}', '{x}', '{y}', '{z}'],
            ['', $this->getSubdomain($x, $y), $x, $y, $z],
            $this->url
        );
    }

    /**
     * Select subdomain of tile server to prevent rate limiting on remote server
     * @param int $x x coordinate
     * @param int $y y coordinate
     * @return string selected subdomain
     * @see https://github.com/Leaflet/Leaflet/blob/main/src/layer/tile/TileLayer.js#L233 Leaflet implementation
     */
    protected function getSubdomain(int $x, int $y): string
    {
        return $this->subdomains[\abs($x + $y) % \sizeof($this->subdomains)];
    }

    /**
     * Get attribution text
     * @return string Attribution text
     */
    public function getAttributionText(): string
    {
        return $this->attributionText;
    }

    /**
     * Get an image tile
     * @param float $x
     * @param float $y
     * @param int $z
     * @param int $tileSize
     * @return Image Image instance containing the tile
     * @throws \Exception
     */
    public function getTile(float $x, float $y, int $z, int $tileSize): Image
    {
        if($this->opacity == 0) {
            return Image::newCanvas($tileSize, $tileSize);
        }

        $tile = Image::fromCurl($this->getTileUrl($x, $y, $z),$this->curlOptions, $this->failCurlOnError);

        if($this->opacity > 0 && $this->opacity < 1) {
            $tile->setOpacity($this->opacity);
        }

        return $tile;
    }
}
