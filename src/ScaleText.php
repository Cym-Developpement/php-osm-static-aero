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
    const ALIGN_TOP_LEFT = 'top-left';
    const ALIGN_TOP_RIGHT = 'top-right';
    const ALIGN_BOTTOM_LEFT = 'bottom-left';
    const ALIGN_BOTTOM_RIGHT = 'bottom-right';

    /**
     * Resolution de sortie, en points par pouce. PaperSize::px() convertit les
     * millimetres en pixels a cette meme valeur : le rapport d'echelle annonce
     * n'est juste que si les deux concordent.
     */
    const DPI = 300;

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
     * @var string|null Couleur du fond, null pour aucun fond
     */
    private $backgroundColor = null;
    /**
     * @var int Marge autour du contenu, en pixels
     */
    private $backgroundPadding = 30;
    /**
     * @var int Taille de police des libelles, seul reglage d'echelle
     */
    private $fontSize = 40;
    /**
     * @var string|null Coin de la carte ou caler la barre
     */
    private $alignment = null;
    /**
     * @var float Marge au bord de la carte, en millimetres
     */
    private $marginMm = 10.0;

    /**
     * @var float Resolution de sortie effective, en points par pouce
     */
    private $dpi = self::DPI;

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
     * Ajoute un fond derriere la barre, pour la rendre lisible ou qu'elle soit
     * posee sur la carte.
     *
     * La convention de couleur de cette bibliotheque est RRGGBBAA avec 00
     * opaque et FF transparent : 'ffffff19' donne le meme blanc legerement
     * translucide que la legende.
     *
     * @param string|null $color Couleur du fond, null pour aucun fond
     * @param int $padding Marge autour du contenu, en pixels
     * @return $this Fluent interface
     */
    public function setBackground($color = 'ffffff19', int $padding = 30)
    {
        $this->backgroundColor = $color;
        $this->backgroundPadding = $padding;
        return $this;
    }

    /**
     * Taille de police des deux libelles. Les epaisseurs de trait en derivent,
     * ce qui fait de ce reglage le seul facteur d'echelle de la classe.
     *
     * @param int $fontSize
     * @return $this Fluent interface
     */
    public function setFontSize(int $fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Resolution a laquelle l'image sera reproduite, si elle differe des 300
     * DPI de PaperSize::px().
     *
     * Sert a produire un apercu : une image rendue a taille reduite represente
     * la meme carte imprimee plus petit. En annoncant la resolution effective
     * — 300 / facteur de reduction — la barre affiche le rapport d'echelle et
     * la marge de la carte finale, et non ceux de la vignette.
     *
     * @param float $dpi
     * @return $this Fluent interface
     */
    public function setDpi(float $dpi)
    {
        $this->dpi = $dpi > 0 ? $dpi : self::DPI;
        return $this;
    }

    /**
     * Cale la barre dans un coin de la carte, a une marge donnee du bord.
     *
     * La position exacte n'est resolue qu'au trace, quand MapData est connu.
     * Remplace avantageusement une position calculee en metres depuis le
     * centre, qui dependrait du format et du zoom.
     *
     * @param string $alignment Une des constantes ALIGN_...
     * @param float $marginMm Marge au bord de la carte, en millimetres
     * @return $this Fluent interface
     */
    public function setPosition(string $alignment, float $marginMm = 10.0)
    {
        $this->alignment = $alignment;
        $this->marginMm = $marginMm;
        return $this;
    }

    /**
     * @param Image $image The map image
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): ScaleText
    {
        $origin = $this->alignment !== null ? $this->alignedPosition($mapData) : $this->center;

        $start = $mapData->convertLatLngToPxPosition($origin);
        $end = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($origin, $this->meters, 90));
        $startVert = GeographicConverter::metersToLatLng($origin, ($this->meters / 10.0), 360);
        $endVert = GeographicConverter::metersToLatLng($origin, ($this->meters / 10.0), 180);
        $middleText = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($origin, ($this->meters / 2), 90));

        $topPx = $mapData->convertLatLngToPxPosition($startVert);
        $bottomPx = $mapData->convertLatLngToPxPosition($endVert);

        $distanceText = \intval($this->meters) . 'm';
        $scaleText = $this->getScaleOneBy($mapData);

        // Les libelles sont places par rapport a l'axe de la barre, avec un
        // decalage proportionnel : un ecart fixe en pixels les faisait se
        // chevaucher des que les montants devenaient courts, c'est-a-dire des
        // qu'on reduisait le format ou le zoom.
        $halfHeight = \abs($bottomPx->getY() - $topPx->getY()) / 2;
        $offset = \max($halfHeight * 0.55, $this->fontSize * 0.85);
        $distanceY = (int) \round($start->getY() - $offset);
        $scaleY = (int) \round($start->getY() + $offset);

        // Fond dessine en premier, sous tout le reste.
        if ($this->backgroundColor !== null) {
            $textWidth = \max($this->textWidth($distanceText), $this->textWidth($scaleText));
            $halfText = \intval($textWidth / 2) + \intval($this->fontSize / 4);
            $pad = $this->backgroundPadding;
            $halfLine = $this->fontSize * 0.7;

            $image->drawRectangle(
                (int) \round(\min($start->getX(), $middleText->getX() - $halfText) - $pad),
                (int) \round(\min($topPx->getY(), $distanceY - $halfLine) - $pad),
                (int) \round(\max($end->getX(), $middleText->getX() + $halfText) + $pad),
                (int) \round(\max($bottomPx->getY(), $scaleY + $halfLine) + $pad),
                $this->backgroundColor
            );
        }

        // Epaisseurs derivees de la police : 12 et 10 px pour la valeur
        // d'origine de 40, mais proportionnees des que le format change.
        $barWeight = \max(1, (int) \round($this->fontSize * 0.30));
        $tickWeight = \max(1, (int) \round($this->fontSize * 0.25));

        $image->drawLine($start->getX(), $start->getY(), $end->getX(), $end->getY(), $barWeight, $this->color);
        $image->drawLine($topPx->getX(), $topPx->getY(), $bottomPx->getX(), $bottomPx->getY(), $tickWeight, $this->color);

        $startVert2 = GeographicConverter::metersToLatLng($startVert, $this->meters, 90);
        $endVert2 = GeographicConverter::metersToLatLng($endVert, $this->meters, 90);

        $image->drawLine(
            $mapData->convertLatLngToPxPosition($startVert2)->getX(),
            $mapData->convertLatLngToPxPosition($startVert2)->getY(),
            $mapData->convertLatLngToPxPosition($endVert2)->getX(),
            $mapData->convertLatLngToPxPosition($endVert2)->getY(),
            $tickWeight,
            $this->color
        );

        $boldFont = __DIR__ . '/resources/CascadiaCode-Bold.ttf';
        $lightFont = __DIR__ . '/resources/CascadiaCode-Light.ttf';

        // Chaque libelle est double : blanc dessous pour le detacher du fond de
        // carte, couleur au-dessus.
        $image->writeText($distanceText, $boldFont, $this->fontSize, 'ffffff', $middleText->getX(), $distanceY);
        $image->writeText($distanceText, $lightFont, $this->fontSize, $this->color, $middleText->getX(), $distanceY);
        $image->writeText($scaleText, $boldFont, $this->fontSize, 'ffffff', $middleText->getX(), $scaleY);
        $image->writeText($scaleText, $lightFont, $this->fontSize, $this->color, $middleText->getX(), $scaleY);

        return $this;
    }

    /**
     * Convertit l'alignement demande en coordonnee geographique de l'extremite
     * gauche de la barre.
     *
     * @param MapData $mapData
     * @return LatLng
     */
    private function alignedPosition(MapData $mapData): LatLng
    {
        $size = $mapData->getOutputSize();
        $margin = $this->marginMm * $this->dpi / 25.4;
        $pad = $this->backgroundColor !== null ? $this->backgroundPadding : 0;

        // Demi-hauteur de la barre : les montants s'etendent de meters/10 de
        // part et d'autre de l'axe.
        $halfHeight = ($this->meters / 10.0) / $mapData->getMetersByPx();
        // Longueur de la barre, pour les alignements a droite.
        $length = $this->meters / $mapData->getMetersByPx();

        $x = $margin + $pad;
        $y = $margin + $pad + $halfHeight;

        if ($this->alignment === self::ALIGN_TOP_RIGHT || $this->alignment === self::ALIGN_BOTTOM_RIGHT) {
            $x = $size->getX() - $margin - $pad - $length;
        }
        if ($this->alignment === self::ALIGN_BOTTOM_LEFT || $this->alignment === self::ALIGN_BOTTOM_RIGHT) {
            $y = $size->getY() - $margin - $pad - $halfHeight;
        }

        return $mapData->convertPxPositionToLatLng(new XY((int) \round($x), (int) \round($y)));
    }

    /**
     * Largeur d'un libelle, mesuree sans l'afficher.
     *
     * @param string $text
     * @return int
     */
    private function textWidth(string $text): int
    {
        $bbox = Image::newCanvas(1, 1)->writeTextAndGetBoundingBox(
            $text,
            __DIR__ . '/resources/CascadiaCode-Bold.ttf',
            $this->fontSize,
            '000000',
            0,
            0,
            Image::ALIGN_LEFT,
            Image::ALIGN_MIDDLE
        );

        return (int) \abs($bbox['bottom-right']['x'] - $bbox['top-left']['x']);
    }

    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        // La barre s'etend vers l'est et de meters/10 de part et d'autre.
        return [
            GeographicConverter::metersToLatLng($this->center, $this->meters / 10.0, 360),
            GeographicConverter::metersToLatLng(
                GeographicConverter::metersToLatLng($this->center, $this->meters, 90),
                $this->meters / 10.0,
                180
            ),
        ];
    }

    /**
     * Rapport d'echelle, deduit de la longueur reellement tracee.
     *
     * Se fonder sur la barre dessinee plutot que sur MapData::getScale() evite
     * deux ecueils : getScale() prend la latitude du coin haut-gauche, ce qui
     * decale le rapport de ~1 % sur une carte A0, et le resultat annonce ne
     * correspondrait alors plus a la barre imprimee juste a cote.
     *
     * @param MapData $mapData
     * @return string
     */
    public function getScaleOneBy(MapData $mapData): string
    {
        // Meme origine que le trace : la longueur en pixels d'une distance
        // donnee varie avec la latitude.
        $origin = $this->alignment !== null ? $this->alignedPosition($mapData) : $this->center;

        $start = $mapData->convertLatLngToPxPosition($origin);
        $end = $mapData->convertLatLngToPxPosition(GeographicConverter::metersToLatLng($origin, $this->meters, 90));

        $pixels = \abs($end->getX() - $start->getX());

        if ($pixels <= 0) {
            return '';
        }

        // Longueur de la barre sur le papier, en metres.
        $paper = $pixels * 0.0254 / $this->dpi;
        $scale = $this->meters / $paper;

        // Arrondi a deux chiffres significatifs : donne 1:150 000 comme
        // 1:38 000 sans perdre un ordre de grandeur, la ou un arrondi fixe a
        // 10 000 fausserait les grandes echelles.
        $exponent = (int) \floor(\log10($scale)) - 1;
        $rounded = \round($scale / \pow(10, $exponent)) * \pow(10, $exponent);

        return '≈ 1:' . \number_format($rounded, 0, '.', ' ');
    }
}
