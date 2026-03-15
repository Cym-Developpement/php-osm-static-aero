<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ycdev\OsmStaticAero\LatLng;
use Ycdev\OsmStaticAero\OpenStreetMap;

$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$map = OpenStreetMap::createFromLatLngZoom(
    new LatLng(47.0117, 0.0856),
    14,
    800,
    600
);

$outputPath = $outputDir . '/simple.png';
$map->getImage()->savePNG($outputPath);

echo "Carte sauvegardée dans : $outputPath" . PHP_EOL;
