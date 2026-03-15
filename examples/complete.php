<?php
// Les cartes papier haute résolution nécessitent plus de mémoire
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../vendor/autoload.php';

use Ycdev\PaperSize\PaperSize;
use Ycdev\OsmStaticAero\Circle;
use Ycdev\OsmStaticAero\Compass;
use Ycdev\OsmStaticAero\LatLng;
use Ycdev\OsmStaticAero\Legend;
use Ycdev\OsmStaticAero\PaperMap;
use Ycdev\OsmStaticAero\ScaleText;
use Ycdev\OsmStaticAero\Text;
use Ycdev\OsmStaticAero\TileLayer;
use Ycdev\OsmStaticAero\Utils\GeographicConverter;

// =====================================================================
// Exemple complet : carte aéronautique papier avec aérodromes,
// cercles de distance, rose des vents, échelle et légende
// Adapté de glider-map/map.php
// =====================================================================

$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// --- Centre de la carte ---
$center     = new LatLng(46.9626265718427, -0.15698199058019643);
$nameCenter = "Thouars LFCT";

// --- Récupération des aérodromes via OpenAIP ---
$openaipAirfield = "https://api.core.openaip.net/api/airports?page=1&limit=100&pos=46.9626265718427%2C-0.15698199058019643&dist=90000&type=2&sortBy=name&sortDesc=true&searchOptLwc=false&apiKey=b85c3693887f9070b9603162d49d9cd2";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $openaipAirfield);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Erreur cURL : ' . curl_error($ch) . PHP_EOL;
    exit();
}

curl_close($ch);

$airfield  = json_decode($response);
$totalText = "\n# AERODROMES A PROXIMITE :\n\n";

if (isset($airfield->items)) {
    $compositionLabels = [
        0  => "Asphalte",
        1  => "Béton",
        2  => "Herbe",
        3  => "Sable",
        4  => "Eau",
        5  => "Bitume ou ciment-terre",
        6  => "Brique",
        7  => "Macadam ou tarmac (roche concassée liée à l'eau)",
        8  => "Pierre",
        9  => "Corail",
        10 => "Argile",
        11 => "Latérite (argile ferrugineuse tropicale)",
        12 => "Gravier",
        13 => "Terre",
        14 => "Glace",
        15 => "Neige",
        16 => "Stratifié protecteur (généralement en caoutchouc)",
        17 => "Métal",
        18 => "Tapis d'atterrissage portable (aluminium)",
        19 => "Plaques d'acier perforées",
        20 => "Bois",
        21 => "Mélange non bitumineux",
        22 => "Inconnu",
    ];

    foreach ($airfield->items as $key => $value) {
        $txt = '## ' . $value->name . ' ' . ((isset($value->icaoCode)) ? ('(' . $value->icaoCode . ')') : '') . " : \n";
        $txt .= (isset($value->frequencies)) ? $value->frequencies[0]->value . "Mhz - " : "";
        $allRunways = [];
        $width      = 0;
        $used       = [];

        foreach ($value->runways as $i => $runway) {
            if (!isset($runway->operations) || $runway->operations != 0) {
                continue;
            }
            if (in_array($i, $used)) {
                continue;
            }

            if (isset($runway->surface)) {
                if (is_array($runway->surface)) {
                    $comp            = isset($runway->surface['composition']) ? $runway->surface['composition'] : 22;
                    $compositionCode = is_array($comp) ? (isset($comp[0]) ? $comp[0] : 22) : $comp;
                } else {
                    $comp            = isset($runway->surface->composition) ? $runway->surface->composition : 22;
                    $compositionCode = is_array($comp) ? (isset($comp[0]) ? $comp[0] : 22) : $comp;
                }
            } else {
                $compositionCode = 22;
            }
            $compositionText = isset($compositionLabels[$compositionCode]) ? $compositionLabels[$compositionCode] : "Inconnu";
            $foundPair       = false;
            $d1              = intval($runway->designator);

            foreach ($value->runways as $j => $other) {
                if (!isset($other->operations) || $other->operations != 0) {
                    continue;
                }
                if ($i !== $j && !in_array($j, $used)) {
                    $d2 = intval($other->designator);
                    if (abs($d1 - $d2) == 18) {
                        if ($runway->dimension->length->value == $other->dimension->length->value) {
                            $lengthStr = $runway->dimension->length->value . " m";
                        } else {
                            $lengthStr = $runway->dimension->length->value . " m/" . $other->dimension->length->value . " m";
                        }
                        $allRunways[] = $runway->designator . "/" . $other->designator . " (" . $lengthStr . ", " . $compositionText . ")";
                        $used[]       = $i;
                        $used[]       = $j;
                        $foundPair    = true;
                        break;
                    }
                }
            }
            if (!$foundPair) {
                $allRunways[] = $runway->designator . " (" . $runway->dimension->length->value . " m, " . $compositionText . ")";
                $used[]       = $i;
            }
            if ($width == 0) {
                $width = $runway->dimension->width->value;
            }
        }

        $txt .= implode(' / ', $allRunways) . " largeur : $width m \n";
        $distanceToCenter = intval(GeographicConverter::latLngToMeters($center, new LatLng($value->geometry->coordinates[1], $value->geometry->coordinates[0])) / 100) / 10;
        if ($distanceToCenter > 1.0) {
            $txt .= "Distance de $nameCenter : $distanceToCenter Km / " . number_format($distanceToCenter / 1.852, 1) . " Nm\n";
        }

        $txt .= "\n";
        $totalText .= $txt;
    }
} else {
    $totalText .= "(Données aérodromes non disponibles)\n\n";
}

// --- Table de finesse ---
$finess = "

#Finesse :
---  |  5 Km |  10 Km |  15 Km |  20 Km |  25 Km |  30 Km |  40 Km |  45 Km |
50   | 100 m |  200 m |  300 m |  400 m |  500 m |  600 m |  800 m |  900 m |
45   | 115 m |  225 m |  335 m |  445 m |  555 m |  670 m |  890 m | 1000 m |
40   | 125 m |  250 m |  375 m |  500 m |  625 m |  750 m | 1000 m | 1125 m |
35   | 150 m |  300 m |  430 m |  580 m |  720 m |  860 m | 1150 m | 1290 m |
30   | 170 m |  340 m |  500 m |  670 m |  840 m | 1000 m | 1340 m | 1500 m |
25   | 200 m |  400 m |  600 m |  800 m | 1000 m | 1200 m | 1600 m | 1800 m |
20   | 250 m |  500 m |  750 m | 1000 m | 1250 m | 1500 m | 2000 m | 2250 m |
15   | 335 m |  670 m | 1000 m | 1335 m | 1670 m | 2000 m | 2670 m | 3000 m |
-----------------------------------------------------------------------------
10   | 500 m | 1000 m | 1500 m | 2000 m | 2500 m | 3000 m | 4000 m | 4500 m |



#Fréquence vol à voile : 122.655 Mhz






















";

$totalText .= $finess;

// --- Configurations de formats disponibles ---
$doubleA0 = [1189, 1682];

$config = [
    'DA0'    => [
        'zoom'     => 12,
        'format'   => $doubleA0,
        'fontSize' => 15,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
    'A0'     => [
        'zoom'     => 12,
        'format'   => PaperSize::A0,
        'fontSize' => 30,
        'factor'   => 2.0,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
    'A0ZOOM' => [
        'zoom'     => 13,
        'format'   => PaperSize::A0,
        'fontSize' => 30,
        'factor'   => 2.0,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
    'A1'     => [
        'zoom'     => 12,
        'format'   => PaperSize::A1,
        'fontSize' => 20,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
    'A3'     => [
        'zoom'     => 12,
        'format'   => PaperSize::A3,
        'fontSize' => 30,
        'factor'   => 2.0,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
    'topo'   => [
        'zoom'     => 14,
        'format'   => PaperSize::A3,
        'fontSize' => 30,
        'factor'   => 2.0,
        'layers'   => [TileLayer::OPENTOPO, TileLayer::OPENAIP],
    ],
    'A4'     => [
        'zoom'     => 12,
        'format'   => PaperSize::A4,
        'fontSize' => 10,
        'layers'   => [TileLayer::OSMFR, TileLayer::OPENAIP],
    ],
];

// --- Sélection du format ---
$idConfig   = 'A0';
$format     = $config[$idConfig]['format'];
$zoom       = $config[$idConfig]['zoom'];
$tileLayers = $config[$idConfig]['layers'];
$logo       = null; // Mettre un chemin vers un logo PNG si besoin (ex: __DIR__ . '/cvvt-ecusson.png')

// --- Création de la carte papier ---
$map = new PaperMap(
    PaperSize::landscape($format),
    GeographicConverter::metersToLatLng(GeographicConverter::metersToLatLng($center, 5000.0, 90), 10000.0, 0),
    ['zoom' => $zoom, 'factor' => 2.0],
    $tileLayers
);

// --- Dessins sur la carte ---
$map->draw()->addDraw(
    (new Circle($center, '3e43ff1a', 4, '3e43ff99', true))
        ->setRadius($map->dist('30Km'))
)->addDraw(
    (new Circle($center, '3e43ff1a', 4, '3e43ff99', true))
        ->setRadius($map->dist('15Km'))
)->addDraw(
    (new Circle($center, '3e43ff1a', 4, '3e43ff99', true))
        ->setRadius($map->dist('5Km'))
)->addDraw(
    (new Text(GeographicConverter::metersToLatLng($center, $map->dist('29Km'), 360), '30Km', 55, '3e43ff00'))
)->addDraw(
    (new Text(GeographicConverter::metersToLatLng($center, $map->dist('14Km'), 360), '15Km', 55, '3e43ff00'))
)->addDraw(
    (new Text(GeographicConverter::metersToLatLng($center, $map->dist('4Km'), 360), '5Km', 55, '3e43ff00'))
)->addDraw(
    (new Legend(Legend::ALIGN_RIGHT, $totalText, ($config[$idConfig]['fontSize'] - 5), '000000', 'ffffff', ($config[$idConfig]['fontSize'] + 7), $logo, 'Thouars LFCT'))
)->addDraw(
    (new ScaleText(GeographicConverter::metersToLatLng(GeographicConverter::metersToLatLng($center, 0000.0, 180), 77000.0, 90), 10000))
)->addDraw(
    (new Compass($center, 10000, 15, '00000080'))
);

// --- Sauvegarde ---
$outputPath = $outputDir . "/complete_$idConfig.png";
$map->saveImage($outputPath);

echo "Carte complète sauvegardée dans : $outputPath" . PHP_EOL;
