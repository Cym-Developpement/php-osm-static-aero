<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\LatLng;
use Ycdev\OsmStaticAero\MapData;
use Ycdev\OsmStaticAero\ScaleText;
use Ycdev\OsmStaticAero\XY;

/**
 * @requires extension gd
 */
class ScaleTextTest extends TestCase
{
    private function mapData(int $zoom = 12): MapData
    {
        return new MapData(new LatLng(46.9626265718427, -0.15698199058019643), $zoom, new XY(2000, 1400), 256, 1.0);
    }

    public function testScaleRatioIsAnnouncedForThreeHundredDpiByDefault()
    {
        $scale = new ScaleText(new LatLng(46.9626265718427, -0.15698199058019643), 10000);

        $this->assertMatchesRegularExpression('/^≈ 1:[\d ]+$/u', $scale->getScaleOneBy($this->mapData()));
    }

    /**
     * A nombre de pixels egal, une resolution deux fois plus basse etale la
     * carte sur deux fois plus de papier : le rapport annonce est divise par
     * deux. C'est ce qui permet a un apercu reduit d'annoncer le rapport de la
     * carte finale, en declarant 300 / facteur de reduction.
     */
    public function testHalvingTheDpiHalvesTheAnnouncedRatio()
    {
        $origin = new LatLng(46.9626265718427, -0.15698199058019643);
        $mapData = $this->mapData();

        $reference = $this->ratio((new ScaleText($origin, 10000))->getScaleOneBy($mapData));
        $halved = $this->ratio((new ScaleText($origin, 10000))->setDpi(150)->getScaleOneBy($mapData));

        // L'arrondi a deux chiffres significatifs autorise un leger ecart.
        $this->assertEqualsWithDelta(0.5, $halved / $reference, 0.02);
    }

    public function testANonPositiveDpiFallsBackToTheDefault()
    {
        $origin = new LatLng(46.9626265718427, -0.15698199058019643);
        $mapData = $this->mapData();

        $this->assertSame(
            (new ScaleText($origin, 10000))->getScaleOneBy($mapData),
            (new ScaleText($origin, 10000))->setDpi(0)->getScaleOneBy($mapData)
        );
    }

    private function ratio(string $text): float
    {
        return (float) str_replace([' ', ' '], '', substr($text, strpos($text, ':') + 1));
    }
}
