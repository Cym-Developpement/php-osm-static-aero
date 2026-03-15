[![GitHub license](https://img.shields.io/github/license/DantSu/php-osm-static-api.svg)](https://github.com/DantSu/php-osm-static-api/blob/master/LICENSE)

# PHP OSM Static Aero

PHP library to generate static aeronautical maps from OpenStreetMap with markers, lines, circles and polygons.

This project is a fork of [php-osm-static-api](https://github.com/DantSu/php-osm-static-api) by [Franck ALARY (DantSu)](https://github.com/DantSu), adapted for aeronautical paper map generation.

This project uses the [Tile Server](https://wiki.openstreetmap.org/wiki/Tile_servers) of the OpenStreetMap Foundation which runs entirely on donated resources, see [Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/) for more information.

## Requirements

- PHP >= 7.0
- Extension `gd`
- Extension `curl`

## Installation

```cmd
composer require ycdev/php-osm-static-aero
```

## How to use

### Generate OpenStreetMap static image with markers and polygon :

```php
use \Ycdev\OsmStaticAero\OpenStreetMap;
use \Ycdev\OsmStaticAero\LatLng;
use \Ycdev\OsmStaticAero\Polygon;
use \Ycdev\OsmStaticAero\Markers;

\header('Content-type: image/png');
(new OpenStreetMap(new LatLng(44.351933, 2.568113), 17, 600, 400))
    ->addMarkers(
        (new Markers(__DIR__ . '/resources/marker.png'))
            ->setAnchor(Markers::ANCHOR_CENTER, Markers::ANCHOR_BOTTOM)
            ->addMarker(new LatLng(44.351933, 2.568113))
            ->addMarker(new LatLng(44.351510, 2.570020))
            ->addMarker(new LatLng(44.351873, 2.566250))
    )
    ->addDraw(
        (new Polygon('FF0000', 2, 'FF0000DD'))
            ->addPoint(new LatLng(44.351172, 2.571092))
            ->addPoint(new LatLng(44.352097, 2.570045))
            ->addPoint(new LatLng(44.352665, 2.568107))
            ->addPoint(new LatLng(44.352887, 2.566503))
            ->addPoint(new LatLng(44.352806, 2.565972))
            ->addPoint(new LatLng(44.351517, 2.565672))
    )
    ->getImage()
    ->displayPNG();
```

### Align and zoom the map to drawings and markers :

- `->fitToDraws(int $padding = 0)`
- `->fitToMarkers(int $padding = 0)`
- `->fitToMarkersAndDraws(int $padding = 0)`
- `->fitToPoints(LatLng[] $points, int $padding = 0)`

`$padding` sets the amount of padding in the borders of the map that shouldn't be accounted for when setting the view to fit bounds. This can be positive or negative according to your needs.

## Documentation

| Class | Description |
|---|---|
| [Circle](./docs/classes/Ycdev/OsmStaticAero/Circle.md) | Draw circle on the map. |
| [Geometry2D](./docs/classes/Ycdev/OsmStaticAero/Geometry2D.md) | 2D geometry utility methods. |
| [Image](./docs/classes/Ycdev/OsmStaticAero/Image.md) | GD-based image editing with tile caching. |
| [LatLng](./docs/classes/Ycdev/OsmStaticAero/LatLng.md) | Define latitude and longitude for map, lines, markers. |
| [Line](./docs/classes/Ycdev/OsmStaticAero/Line.md) | Draw line on the map. |
| [MapData](./docs/classes/Ycdev/OsmStaticAero/MapData.md) | Convert latitude and longitude to image pixel position. |
| [Markers](./docs/classes/Ycdev/OsmStaticAero/Markers.md) | Display markers on the map. |
| [OpenStreetMap](./docs/classes/Ycdev/OsmStaticAero/OpenStreetMap.md) | Main class to generate static map images. |
| [Polygon](./docs/classes/Ycdev/OsmStaticAero/Polygon.md) | Draw polygon on the map. |
| [TileLayer](./docs/classes/Ycdev/OsmStaticAero/TileLayer.md) | Define tile server url and related configuration. |
| [XY](./docs/classes/Ycdev/OsmStaticAero/XY.md) | Define X and Y pixel position for map, lines, markers. |

## Credits

This project is based on [php-osm-static-api](https://github.com/DantSu/php-osm-static-api) originally created by [Franck ALARY (DantSu)](https://github.com/DantSu), licensed under the [MIT License](./LICENSE).

## Contributing

Please fork this repository and contribute back using pull requests.

Any contributions, large or small, major features, bug fixes, are welcomed and appreciated but will be thoroughly reviewed.
