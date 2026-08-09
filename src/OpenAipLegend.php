<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Interfaces\Draw;
use Ycdev\OsmStaticAero\Utils\OpenAipSymbols;

/**
 * Bandeau de legende OpenAIP, cale en bas de la carte.
 *
 * La hauteur se declare en pourcentage de la hauteur de l'image : c'est le seul
 * reglage necessaire pour passer d'un A4 a un A0. Tout le reste — nombre de
 * colonnes, taille de police, taille des vignettes — en decoule, ce qui evite
 * d'avoir a remettre l'echelle a la main format par format.
 *
 * Le bandeau se superpose a la carte, il ne la retaille pas : prevoyez la
 * hauteur en consequence lors du cadrage.
 *
 * Usage :
 *
 *     $map->draw()->addDraw(new OpenAipLegend(15.0));
 *
 * @package Ycdev\OsmStaticAero
 * @see OpenAipSymbology Table des symboles, derivee du style publie par OpenAIP
 */
class OpenAipLegend implements Draw
{
    const ALIGN_BOTTOM = 'bottom';
    const ALIGN_TOP = 'top';

    /**
     * Taille de reference utilisee pour mesurer les libelles. Les largeurs
     * obtenues sont proportionnelles a la taille de police, une seule mesure
     * suffit donc pour evaluer toutes les mises en page candidates.
     */
    const MEASURE_FONT_SIZE = 100.0;

    /**
     * @var float Hauteur du bandeau, en pourcentage de la hauteur de la carte
     */
    private $heightPercent;

    /**
     * @var string Bord de la carte ou caler le bandeau
     */
    private $alignment = self::ALIGN_BOTTOM;

    /**
     * @var string Fond du bandeau, en RRGGBBAA (AA : 00 opaque, FF transparent)
     */
    private $backgroundColor = 'ffffff00';

    /**
     * @var string|null Filet separant le bandeau de la carte, null pour aucun
     */
    private $borderColor = '00000000';

    /**
     * @var string Couleur des libelles
     */
    private $fontColor = '000000';

    /**
     * @var string|null Titre du bandeau, null pour aucun
     */
    private $title = 'LEGENDE OpenAIP';

    /**
     * @var array[]|null Groupes affiches, null pour la table complete
     */
    private $groups = null;

    /**
     * @var int Nombre maximal de colonnes explorees par la mise en page
     */
    private $maxColumns = 24;

    /**
     * @var string|null Mention de source, null pour la retirer. La licence des
     *                  symboles OpenAIP impose l'attribution.
     */
    private $attribution = OpenAipSymbols::ATTRIBUTION;

    /**
     * @var array Largeurs de texte deja mesurees, indexees par police et libelle
     */
    private $measureCache = [];

    /**
     * @param float $heightPercent Hauteur du bandeau, en pourcentage de la
     *                             hauteur de la carte (ex. 15.0 pour 15 %)
     */
    public function __construct(float $heightPercent = 15.0)
    {
        $this->setHeightPercent($heightPercent);
    }

    /**
     * @param float $heightPercent Hauteur du bandeau, en pourcentage. Bornee a
     *                             ]0, 100] : une valeur nulle ne dessinerait
     *                             rien et une valeur superieure deborderait.
     * @return $this
     */
    public function setHeightPercent(float $heightPercent): OpenAipLegend
    {
        $this->heightPercent = \max(0.5, \min(100.0, $heightPercent));
        return $this;
    }

    /**
     * @param string $alignment self::ALIGN_BOTTOM ou self::ALIGN_TOP
     * @return $this
     */
    public function setAlignment(string $alignment): OpenAipLegend
    {
        $this->alignment = ($alignment === self::ALIGN_TOP) ? self::ALIGN_TOP : self::ALIGN_BOTTOM;
        return $this;
    }

    /**
     * @param string $backgroundColor Fond du bandeau, en RRGGBB ou RRGGBBAA
     * @param string|null $borderColor Filet de separation, null pour aucun
     * @return $this
     */
    public function setBackground(string $backgroundColor, $borderColor = '00000000'): OpenAipLegend
    {
        $this->backgroundColor = $backgroundColor;
        $this->borderColor = $borderColor;
        return $this;
    }

    /**
     * @param string $fontColor Couleur des libelles, en RRGGBB
     * @return $this
     */
    public function setFontColor(string $fontColor): OpenAipLegend
    {
        $this->fontColor = $fontColor;
        return $this;
    }

    /**
     * @param string|null $title Titre du bandeau, null pour n'en afficher aucun
     * @return $this
     */
    public function setTitle($title): OpenAipLegend
    {
        $this->title = ($title === '') ? null : $title;
        return $this;
    }

    /**
     * @param string|null $attribution Mention de source, null pour la retirer
     * @return $this
     */
    public function setAttribution($attribution): OpenAipLegend
    {
        $this->attribution = ($attribution === '') ? null : $attribution;
        return $this;
    }

    /**
     * Plafonne le nombre de colonnes explorees.
     *
     * La mise en page choisit d'elle-meme le decoupage le plus lisible ; ce
     * reglage sert a imposer un bandeau plus dense ou, au contraire, a interdire
     * des colonnes trop etroites.
     *
     * @param int $maxColumns
     * @return $this
     */
    public function setMaxColumns(int $maxColumns): OpenAipLegend
    {
        $this->maxColumns = \max(1, $maxColumns);
        return $this;
    }

    /**
     * Restreint la legende a certains groupes de la table.
     *
     * @param array[]|null $groups Groupes au format OpenAipSymbology::groups(),
     *                             null pour revenir a la table complete
     * @return $this
     */
    public function setGroups($groups): OpenAipLegend
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Ne conserve que les groupes dont le titre figure dans la liste.
     *
     * @param string[] $titles
     * @return $this
     */
    public function onlyGroups(array $titles): OpenAipLegend
    {
        $kept = [];

        foreach (OpenAipSymbology::groups() as $group) {
            if (\in_array($group['title'], $titles, true)) {
                $kept[] = $group;
            }
        }

        return $this->setGroups($kept);
    }

    /**
     * Groupes effectivement affiches.
     *
     * @return array[]
     */
    public function getGroups(): array
    {
        return $this->groups === null ? OpenAipSymbology::groups() : $this->groups;
    }

    /**
     * @param Image $image
     * @param MapData $mapData
     * @return $this
     */
    public function draw(Image $image, MapData $mapData): OpenAipLegend
    {
        if (! $image->isImageDefined()) {
            return $this;
        }

        $width = $image->getWidth();
        $height = $image->getHeight();
        $bandHeight = (int) \round($height * $this->heightPercent / 100.0);

        if ($bandHeight < 8 || $width < 8) {
            return $this;
        }

        $bandTop = ($this->alignment === self::ALIGN_TOP) ? 0 : ($height - $bandHeight);
        $bandBottom = $bandTop + $bandHeight;
        $padding = (int) \max(4, \round($bandHeight * 0.05));

        $image->drawRectangle(0, $bandTop, $width - 1, $bandBottom - 1, $this->backgroundColor);

        if ($this->borderColor !== null) {
            $filet = (int) \max(1, \round($bandHeight * 0.012));
            $filetTop = ($this->alignment === self::ALIGN_TOP) ? ($bandBottom - $filet) : $bandTop;
            $image->drawRectangle(0, $filetTop, $width - 1, $filetTop + $filet, $this->borderColor);
        }

        $fontRegular = __DIR__ . '/resources/CascadiaCode-Light.ttf';
        $fontBold = __DIR__ . '/resources/SpaceMono-Bold.ttf';

        // Le titre et la mention de source encadrent la zone des colonnes : ils
        // sont retires de la hauteur utile avant tout calcul de mise en page.
        $titleFontSize = 0.0;
        $titleHeight = 0;
        if ($this->title !== null) {
            $titleFontSize = \max(7.0, $bandHeight * 0.085);
            $titleHeight = (int) \round($titleFontSize * 1.9);
        }

        $attributionFontSize = 0.0;
        $attributionHeight = 0;
        if ($this->attribution !== null) {
            $attributionFontSize = \max(5.0, $bandHeight * 0.042);
            $attributionHeight = (int) \round($attributionFontSize * 1.8);
        }

        $contentLeft = $padding;
        $contentWidth = $width - 2 * $padding;
        $contentTop = $bandTop + $padding + $titleHeight;
        $contentHeight = $bandHeight - 2 * $padding - $titleHeight - $attributionHeight;

        if ($contentHeight < 8 || $contentWidth < 8) {
            return $this;
        }

        if ($this->title !== null) {
            $image->writeText(
                $this->title,
                $fontBold,
                $titleFontSize,
                $this->fontColor,
                $contentLeft,
                $bandTop + $padding + $titleHeight / 2,
                Image::ALIGN_LEFT,
                Image::ALIGN_MIDDLE
            );
        }

        $rows = $this->buildRows();

        if ($rows === []) {
            return $this;
        }

        $layout = $this->fitLayout($rows, $contentWidth, $contentHeight, $fontRegular, $fontBold);

        if ($layout === null) {
            return $this;
        }

        $this->drawColumns($image, $layout, $contentLeft, $contentTop, $fontRegular, $fontBold);

        if ($this->attribution !== null) {
            $image->writeText(
                $this->attribution,
                $fontRegular,
                $attributionFontSize,
                $this->fontColor,
                $width - $padding,
                $bandBottom - $padding - $attributionHeight / 2,
                Image::ALIGN_RIGHT,
                Image::ALIGN_MIDDLE
            );
        }

        return $this;
    }

    /**
     * Aplatit les groupes en une suite de lignes : un en-tete par groupe, puis
     * ses entrees.
     *
     * @return array[]
     */
    private function buildRows(): array
    {
        $rows = [];

        foreach ($this->getGroups() as $group) {
            if (empty($group['entries'])) {
                continue;
            }

            $rows[] = ['kind' => 'header', 'label' => $group['title']];

            foreach ($group['entries'] as $entry) {
                $entry['kind'] = 'entry';
                $rows[] = $entry;
            }
        }

        return $rows;
    }

    /**
     * Cherche le nombre de colonnes qui autorise la plus grande police.
     *
     * Ajouter une colonne raccourcit les colonnes, donc grandit les lignes et la
     * police ; mais cela retrecit aussi la largeur disponible pour les libelles,
     * qui finit par imposer une police plus petite. L'optimum est entre les
     * deux, on le trouve en enumerant.
     *
     * @param array[] $rows
     * @param int $contentWidth
     * @param int $contentHeight
     * @param string $fontRegular
     * @param string $fontBold
     * @return array|null
     */
    private function fitLayout(array $rows, int $contentWidth, int $contentHeight, string $fontRegular, string $fontBold)
    {
        $best = null;

        for ($columns = 1; $columns <= $this->maxColumns; $columns++) {
            $rowsPerColumn = (int) \ceil(\count($rows) / $columns);
            $distribution = null;

            // Un en-tete de groupe seul en bas de colonne est repousse a la
            // colonne suivante, ce qui peut faire deborder : on rallonge alors
            // les colonnes jusqu'a ce que tout rentre.
            for ($attempt = 0; $attempt < 4; $attempt++) {
                $candidate = $this->distribute($rows, $rowsPerColumn);

                if (\count($candidate) <= $columns) {
                    $distribution = $candidate;
                    break;
                }

                $rowsPerColumn++;
            }

            if ($distribution === null) {
                continue;
            }

            // La hauteur de ligne se cale sur la colonne reellement la plus
            // longue, pas sur le quota vise : les en-tetes repousses en raccourcissent
            // certaines, et diviser par le quota laisserait un blanc en bas du
            // bandeau avec des lignes plus serrees que necessaire.
            $longestColumn = 0;
            foreach ($distribution as $column) {
                $longestColumn = \max($longestColumn, \count($column));
            }

            if ($longestColumn === 0) {
                continue;
            }

            $rowHeight = $contentHeight / $longestColumn;

            if ($rowHeight < 4) {
                continue;
            }

            $swatchWidth = $rowHeight * 2.4;
            $gap = $rowHeight * 0.45;
            $columnGap = $rowHeight * 0.7;
            $columnWidth = ($contentWidth - ($columns - 1) * $columnGap) / $columns;
            $labelWidth = $columnWidth - $swatchWidth - $gap;

            if ($labelWidth <= 0) {
                continue;
            }

            // Contrainte verticale d'un cote, largeur des libelles de l'autre :
            // la police retenue est la plus petite des deux.
            $fontSize = $rowHeight * 0.52;
            $fontSize = \min($fontSize, static::MEASURE_FONT_SIZE * $labelWidth / \max(1.0, $this->widestEntry($rows, $fontRegular)));
            // Les en-tetes disposent de toute la largeur de colonne, pas
            // seulement de la zone des libelles.
            $fontSize = \min($fontSize, static::MEASURE_FONT_SIZE * $columnWidth / \max(1.0, $this->widestHeader($rows, $fontBold)));

            if ($fontSize < 4) {
                continue;
            }

            if ($best === null || $fontSize > $best['fontSize']) {
                $best = [
                    'columns'      => $distribution,
                    'columnCount'  => $columns,
                    'rowHeight'    => $rowHeight,
                    'columnWidth'  => $columnWidth,
                    'columnGap'    => $columnGap,
                    'swatchWidth'  => $swatchWidth,
                    'gap'          => $gap,
                    'fontSize'     => $fontSize,
                ];
            }
        }

        return $best;
    }

    /**
     * Repartit les lignes en colonnes, sans jamais laisser un en-tete de groupe
     * en derniere ligne d'une colonne.
     *
     * @param array[] $rows
     * @param int $rowsPerColumn
     * @return array[] Liste de colonnes, chacune etant une liste de lignes
     */
    private function distribute(array $rows, int $rowsPerColumn): array
    {
        $columns = [];
        $current = [];

        foreach ($rows as $row) {
            $isOrphanHeader = $row['kind'] === 'header' && \count($current) === $rowsPerColumn - 1;

            if (\count($current) >= $rowsPerColumn || $isOrphanHeader) {
                $columns[] = $current;
                $current = [];
            }

            $current[] = $row;
        }

        if ($current !== []) {
            $columns[] = $current;
        }

        return $columns;
    }

    /**
     * @param array[] $rows
     * @param string $font
     * @return float Largeur du plus long libelle d'entree, a MEASURE_FONT_SIZE
     */
    private function widestEntry(array $rows, string $font): float
    {
        $widest = 1.0;

        foreach ($rows as $row) {
            if ($row['kind'] === 'entry') {
                $widest = \max($widest, $this->measure($row['label'], $font));
            }
        }

        return $widest;
    }

    /**
     * @param array[] $rows
     * @param string $font
     * @return float Largeur du plus long en-tete, a MEASURE_FONT_SIZE
     */
    private function widestHeader(array $rows, string $font): float
    {
        $widest = 1.0;

        foreach ($rows as $row) {
            if ($row['kind'] === 'header') {
                $widest = \max($widest, $this->measure($row['label'], $font));
            }
        }

        return $widest;
    }

    /**
     * Largeur d'un texte a MEASURE_FONT_SIZE, mesuree une seule fois.
     *
     * @param string $text
     * @param string $font
     * @return float
     */
    private function measure(string $text, string $font): float
    {
        $key = $font . "\0" . $text;

        if (! isset($this->measureCache[$key])) {
            $canvas = Image::newCanvas(1, 1);
            $bbox = $canvas->writeTextAndGetBoundingBox(
                $text,
                $font,
                static::MEASURE_FONT_SIZE,
                '000000',
                0,
                0,
                Image::ALIGN_LEFT,
                Image::ALIGN_MIDDLE
            );
            $canvas->destroy();

            $this->measureCache[$key] = empty($bbox)
                ? \strlen($text) * static::MEASURE_FONT_SIZE * 0.6
                : \abs($bbox['bottom-right']['x'] - $bbox['top-left']['x']);
        }

        return $this->measureCache[$key];
    }

    /**
     * @param Image $image
     * @param array $layout
     * @param int $contentLeft
     * @param int $contentTop
     * @param string $fontRegular
     * @param string $fontBold
     * @return void
     */
    private function drawColumns(Image $image, array $layout, int $contentLeft, int $contentTop, string $fontRegular, string $fontBold)
    {
        foreach ($layout['columns'] as $columnIndex => $column) {
            $columnLeft = $contentLeft + $columnIndex * ($layout['columnWidth'] + $layout['columnGap']);

            foreach ($column as $rowIndex => $row) {
                $rowTop = $contentTop + $rowIndex * $layout['rowHeight'];
                $centerY = $rowTop + $layout['rowHeight'] / 2;

                if ($row['kind'] === 'header') {
                    $image->writeText(
                        $row['label'],
                        $fontBold,
                        $layout['fontSize'],
                        $this->fontColor,
                        (int) \round($columnLeft),
                        $centerY,
                        Image::ALIGN_LEFT,
                        Image::ALIGN_MIDDLE
                    );

                    // Filet sous l'en-tete, sur la largeur de la colonne.
                    $underline = (int) \round($rowTop + $layout['rowHeight'] * 0.92);
                    $image->drawRectangle(
                        (int) \round($columnLeft),
                        $underline,
                        (int) \round($columnLeft + $layout['columnWidth']),
                        $underline + (int) \max(1, \round($layout['fontSize'] * 0.07)),
                        $this->fontColor
                    );
                    continue;
                }

                $this->drawSwatch($image, $row, $columnLeft, $centerY, $layout);

                $image->writeText(
                    $row['label'],
                    $fontRegular,
                    $layout['fontSize'],
                    $this->fontColor,
                    (int) \round($columnLeft + $layout['swatchWidth'] + $layout['gap']),
                    $centerY,
                    Image::ALIGN_LEFT,
                    Image::ALIGN_MIDDLE
                );
            }
        }
    }

    /**
     * Vignette d'une entree, calee a gauche de son libelle.
     *
     * @param Image $image
     * @param array $entry
     * @param float $left
     * @param float $centerY
     * @param array $layout
     * @return void
     */
    private function drawSwatch(Image $image, array $entry, float $left, float $centerY, array $layout)
    {
        $type = isset($entry['type']) ? $entry['type'] : 'airspace';

        if ($type === 'icon') {
            $this->drawIcon($image, $entry, $left, $centerY, $layout);
            return;
        }

        $boxHeight = $layout['rowHeight'] * 0.62;
        $boxLeft = (int) \round($left);
        $boxRight = (int) \round($left + $layout['swatchWidth']);
        $boxTop = (int) \round($centerY - $boxHeight / 2);
        $boxBottom = (int) \round($centerY + $boxHeight / 2);
        $weight = (int) \max(1, \round($boxHeight * 0.12));

        if (isset($entry['fill'])) {
            $alpha = isset($entry['fillAlpha']) ? $entry['fillAlpha'] : 1.0;
            $image->drawRectangle($boxLeft, $boxTop, $boxRight, $boxBottom, $this->rgba($entry['fill'], $alpha));
        }

        // Bande interieure : sur la carte, OpenAIP la dessine en retrait du
        // contour (couches *_offset). On la restitue en anneau, ce qui la
        // distingue d'un remplissage plein.
        if (isset($entry['band'])) {
            $band = $entry['band'];
            // Bornee a la moitie de la hauteur interieure : au-dela les deux
            // brins de l'anneau se recouvriraient et la bande deviendrait un
            // aplat plein.
            $thickness = (int) \max(1, \min(\round($boxHeight * 0.26), \floor(($boxHeight - 2 * $weight) / 2)));
            $alpha = isset($band['alpha']) ? $band['alpha'] : 1.0;
            $color = $this->rgba($band['color'], $alpha);
            $mode = isset($band['mode']) ? $band['mode'] : 'solid';

            // L'anneau est rentre de l'epaisseur du contour, comme sur la carte
            // ou les couches *_offset sont en retrait du trait. Sans ce retrait,
            // une bande de la meme teinte que le contour comblerait les blancs
            // d'un trait tirete et TRA deviendrait indiscernable de TSA.
            $innerLeft = $boxLeft + $weight;
            $innerTop = $boxTop + $weight;
            $innerRight = $boxRight - $weight;
            $innerBottom = $boxBottom - $weight;

            $ring = [
                [$innerLeft, $innerTop, $innerRight, $innerTop + $thickness],
                [$innerLeft, $innerBottom - $thickness, $innerRight, $innerBottom],
                [$innerLeft, $innerTop, $innerLeft + $thickness, $innerBottom],
                [$innerRight - $thickness, $innerTop, $innerRight, $innerBottom],
            ];

            foreach ($ring as $rect) {
                if ($mode === 'hatch') {
                    // Le pas des hachures se cale sur la vignette, pas sur
                    // l'epaisseur de l'anneau : sinon un anneau fin produirait
                    // des traits si serres qu'ils se liraient comme un aplat.
                    $this->hatchRectangle($image, $rect[0], $rect[1], $rect[2], $rect[3], $color, (int) \round($boxHeight));
                } else {
                    $image->drawRectangle($rect[0], $rect[1], $rect[2], $rect[3], $color);
                }
            }
        }

        $strokeAlpha = isset($entry['strokeAlpha']) ? $entry['strokeAlpha'] : 1.0;
        $stroke = $this->rgba(isset($entry['stroke']) ? $entry['stroke'] : '000000', $strokeAlpha);
        $dash = isset($entry['dash']) ? $entry['dash'] : null;

        $this->outlineRectangle($image, $boxLeft, $boxTop, $boxRight, $boxBottom, $weight, $stroke, $dash);
    }

    /**
     * Pictogramme OpenAIP, rasterise a la hauteur de la ligne et centre dans la
     * zone de vignette. Une entree dont le symbole est indisponible reste
     * lisible : seul le libelle s'affiche, precede d'un reperage discret.
     *
     * @param Image $image
     * @param array $entry
     * @param float $left
     * @param float $centerY
     * @param array $layout
     * @return void
     */
    private function drawIcon(Image $image, array $entry, float $left, float $centerY, array $layout)
    {
        $iconHeight = (int) \max(4, \round($layout['rowHeight'] * 0.78));
        $path = OpenAipSymbols::pngPath($entry['icon'], $iconHeight);

        if ($path === null) {
            $tick = (int) \max(1, \round($layout['rowHeight'] * 0.06));
            $image->drawRectangle(
                (int) \round($left + $layout['swatchWidth'] / 2 - $tick),
                (int) \round($centerY - $tick),
                (int) \round($left + $layout['swatchWidth'] / 2 + $tick),
                (int) \round($centerY + $tick),
                $this->rgba($this->fontColor, 0.35)
            );
            return;
        }

        $icon = Image::fromPath($path);

        if (! $icon->isImageDefined()) {
            return;
        }

        $image->pasteOn(
            $icon,
            (int) \round($left + ($layout['swatchWidth'] - $icon->getWidth()) / 2),
            (int) \round($centerY - $icon->getHeight() / 2)
        );

        $icon->destroy();
    }

    /**
     * Contour d'un rectangle, plein ou tirete.
     *
     * @param Image $image
     * @param int $left
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param int $weight
     * @param string $color
     * @param array|null $dash [longueur du trait, longueur du blanc], en
     *                         multiples de l'epaisseur
     * @return void
     */
    private function outlineRectangle(Image $image, int $left, int $top, int $right, int $bottom, int $weight, string $color, $dash)
    {
        $sides = [
            [$left, $top, $right, $top],
            [$right, $bottom, $left, $bottom],
            [$left, $bottom, $left, $top],
            [$right, $top, $right, $bottom],
        ];

        foreach ($sides as $side) {
            if ($dash === null) {
                $image->drawLine($side[0], $side[1], $side[2], $side[3], $weight, $color);
            } else {
                $this->dashedLine($image, $side[0], $side[1], $side[2], $side[3], $weight, $color, $dash[0] * $weight, $dash[1] * $weight);
            }
        }
    }

    /**
     * Segment tirete entre deux points.
     *
     * @param Image $image
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param int $weight
     * @param string $color
     * @param float $on Longueur du trait, en pixels
     * @param float $off Longueur du blanc, en pixels
     * @return void
     */
    private function dashedLine(Image $image, int $x1, int $y1, int $x2, int $y2, int $weight, string $color, float $on, float $off)
    {
        $length = \sqrt(($x2 - $x1) ** 2 + ($y2 - $y1) ** 2);

        if ($length < 1) {
            return;
        }

        $on = \max(1.0, $on);
        $off = \max(1.0, $off);
        $unitX = ($x2 - $x1) / $length;
        $unitY = ($y2 - $y1) / $length;

        for ($start = 0.0; $start < $length; $start += $on + $off) {
            $end = \min($start + $on, $length);

            $image->drawLine(
                (int) \round($x1 + $unitX * $start),
                (int) \round($y1 + $unitY * $start),
                (int) \round($x1 + $unitX * $end),
                (int) \round($y1 + $unitY * $end),
                $weight,
                $color
            );
        }
    }

    /**
     * Hachures a 45 degres bornees a un rectangle, comme les motifs
     * diagonal_lines_* du style OpenAIP.
     *
     * @param Image $image
     * @param int $left
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param string $color
     * @param int $reference Dimension servant a calibrer pas et epaisseur
     * @return void
     */
    private function hatchRectangle(Image $image, int $left, int $top, int $right, int $bottom, string $color, int $reference)
    {
        $weight = (int) \max(1, \round($reference * 0.09));
        $step = \max($weight * 3.0, 4.0);

        // Les hachures suivent y = x + c ; on fait varier c et on borne chaque
        // segment au rectangle, ce qui evite de deborder sur la vignette voisine.
        for ($c = $top - $right; $c <= $bottom - $left; $c += $step) {
            $startX = \max($left, $top - $c);
            $endX = \min($right, $bottom - $c);

            if ($endX <= $startX) {
                continue;
            }

            $image->drawLine(
                (int) \round($startX),
                (int) \round($startX + $c),
                (int) \round($endX),
                (int) \round($endX + $c),
                $weight,
                $color
            );
        }
    }

    /**
     * Convertit une couleur RRGGBB et une opacite en notation de la
     * bibliotheque, ou l'octet final vaut 00 pour un aplat opaque et FF pour un
     * aplat totalement transparent.
     *
     * @param string $color
     * @param float $alpha Opacite, de 0 (invisible) a 1 (opaque)
     * @return string
     */
    private function rgba(string $color, float $alpha): string
    {
        $color = \ltrim($color, '#');
        $alpha = \max(0.0, \min(1.0, $alpha));

        return \substr($color, 0, 6) . \sprintf('%02x', (int) \round((1.0 - $alpha) * 255));
    }

    /**
     * Le bandeau est cale sur l'image, pas sur des coordonnees geographiques :
     * il n'entre pas dans le calcul du cadrage.
     *
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        return [];
    }
}
