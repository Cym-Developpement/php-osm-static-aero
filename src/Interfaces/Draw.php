<?php

namespace Ycdev\OsmStaticAero\Interfaces;


use Ycdev\OsmStaticAero\MapData;
use Ycdev\OsmStaticAero\Image;

interface Draw
{
    public function getBoundingBox(): array;

    public function draw(Image $image, MapData $mapData);
}
