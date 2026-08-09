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

A drawing anchored in pixels rather than on the ground — an aligned `Legend` — returns an empty bounding box and is simply ignored by these methods. If no drawing contributes any point, the map is left untouched.

## Aeronautical features

Everything below is specific to this fork and has no equivalent upstream.

### Scale bar — `ScaleText`

Draws a horizontal scale bar of a known ground length, with a tick at each end, the distance written underneath and the map ratio written above.

```php
use \Ycdev\OsmStaticAero\ScaleText;

// A 10 km bar, anchored at the given position, drawn in dark red
$map->addDraw(new ScaleText($position, 10000, '991410'));
```

| Parameter | Default | Description |
|---|---|---|
| `$center` | — | `LatLng` of the left end of the bar |
| `$meters` | `5000` | Ground length of the bar, in meters |
| `$color` | `'991410'` | Hex color, without `#` |

The bar grows eastwards from `$center`, and its end ticks span one tenth of its length on each side. Both labels are drawn twice — white underneath, then colored — so they stay readable over a busy map.

Four fluent settings adapt it to the sheet:

| Method | Default | Description |
|---|---|---|
| `setFontSize(int)` | `40` | Label size in pixels. Line weights derive from it, so this is the only scaling knob. |
| `setBackground(?string, int)` | none | Panel behind the bar, `RRGGBBAA` with `00` opaque, plus a padding in pixels. |
| `setPosition(string, float)` | free | Anchors the bar to a corner (`ALIGN_BOTTOM_LEFT`…) at a margin in millimeters, resolved at draw time. |
| `setDpi(float)` | `300` | Resolution the image will actually be reproduced at. |

`setDpi()` matters when rendering a thumbnail: an image drawn at half the size is the same map reproduced smaller, so declaring `300 / reduction` makes the bar announce the ratio and the margin of the full-size sheet instead of those of the thumbnail.

The ratio comes from `getScaleOneBy()`, deduced from the length actually drawn rather than from `MapData::getScale()` — the latter uses the top-left latitude, which drifts about 1 % over an A0. It rounds to two significant digits and returns a string like `≈ 1:250 000` — the UTF-8 character, not the `&asymp;` HTML entity, which GD would draw literally. You can call it on its own if you only need the value:

```php
echo (new ScaleText($position))->getScaleOneBy($map->getMapData());
```

The underlying figures are available directly on `MapData`:

- `getMetersByPx(): float` — ground meters covered by one pixel, at the map's top latitude.
- `getScale(): float` — the scale denominator, **assuming a 300 dpi output**. At another resolution this value is wrong; recompute it from `getMetersByPx()`.
- `convertPxPositionToLatLng(XY $xy): LatLng` — the reverse of `convertLatLngToPxPosition()`.

### Paper output — `PaperMap`

Composes several tile layers plus one drawing layer into a single image sized for a physical paper format, using [`ycdev/paper-size`](https://github.com/ycdev/paper-size).

```php
use \Ycdev\PaperSize\PaperSize;
use \Ycdev\OsmStaticAero\PaperMap;
use \Ycdev\OsmStaticAero\TileLayer;

$map = new PaperMap(
    PaperSize::landscape(PaperSize::A3),
    $center,
    ['zoom' => 11, 'factor' => 2.0],
    [TileLayer::OSMFR, TileLayer::OPENAIP]
);

$map->draw()->addDraw(/* ... */);
$map->saveImage(__DIR__ . '/carte.png');
```

| Option | Default | Description |
|---|---|---|
| `zoom` | `12` | Tile zoom level |
| `bordure` | `10` | Margin around the map, in millimeters |
| `factor` | `1.0` | Oversampling of the drawing layer — `2.0` doubles its resolution so lines and labels stay crisp in print |

`$tileLayers` accepts `TileLayer` presets (see below), raw `[name, url, attribution]` arrays, or the name of a preset as a string.

- `draw(): OpenStreetMap` — the tile-less top layer every drawing should be added to.
- `getImage()` — the composited GD image resource.
- `saveImage(string $path): void` — writes it as PNG.
- `dist($distance): float` — parses `'30Km'` or `'500m'` into meters, handy for radii.

The `legend`, `legendBackground`, `legendLogo` and `legendTitle` keys are accepted but currently unread — add a `Legend` drawing instead.

### Compass rose — `Compass`

```php
$map->addDraw(new Compass($center, 10000, 15, '00000080'));
```

`new Compass(LatLng $center, float $size, int $fontSize = 30, string $fontColor = '000000')` — `$size` is the dial radius in **meters**, so the rose scales with the terrain rather than with the zoom. Graduations are drawn every degree, with a longer, labelled mark every ten.

### Legend block — `Legend`

```php
$map->addDraw(new Legend(Legend::ALIGN_RIGHT, $text, 25, '000000', 'ffffff', 32, $logoPath, 'Thouars LFCT'));
```

The first argument is either a `LatLng` — the legend is then anchored on the ground — or one of `Legend::ALIGN_LEFT`, `ALIGN_RIGHT`, `ALIGN_TOP`, `ALIGN_BOTTOM` to pin it to an edge of the image.

The text supports a few line prefixes:

| Prefix | Effect |
|---|---|
| `#` | Heading, twice the base font size |
| `##` | Sub-heading, 1.5× the base font size |
| `>>` | Center the line inside the block |

Prefixes combine, `>>` first: `>> ## Sub-heading, centered`.

### Text label — `Text`

`new Text(LatLng $center, string $text, int $fontSize = 30, string $fontColor = '000000')` writes a label anchored on the ground, doubled in white underneath for legibility.

### Aeronautical zone circles — `Circle`

The constructor takes a fifth argument:

```php
new Circle($center, '3e43ff1a', 4, '3e43ff99', true);
```

With `$aeroZoneStyle = true` only a thick ring of fill color is kept along the edge, leaving the middle transparent — the usual way of drawing an airspace boundary without hiding the chart underneath.

### Rectangles — `Polygon`

`rectangle(LatLng $start, float $width, float $height): Polygon` adds the four corners of a rectangle given its top-left corner and its size in meters.

### Tile server presets — `TileLayer`

Ready-to-use `[name, url, attribution]` triplets: `TileLayer::DEFAULT`, `TileLayer::OSMFR`, `TileLayer::OPENTOPO` and `TileLayer::OPENAIP` (aeronautical overlay).

> `TileLayer::OPENAIP` embeds a shared API key so the examples run out of the box. Replace it with your own before any real use — see the [OpenAIP guide](./docs/openaip.md), which also covers rate limiting, retries and a known rendering defect at low zoom.

### Maps without tiles

Passing `false` instead of a `TileLayer` builds a transparent map that downloads nothing — the drawing layer of `PaperMap` works this way. The constructor also takes an oversampling factor and an attribution switch:

```php
new OpenStreetMap($center, $zoom, $width, $height, false, 256, 2.0, false);
//                                                 ^^^^^       ^^^  ^^^^^
//                                                 no tiles    factor, no attribution
```

### Tile caching — `Image`

Downloaded tiles are cached on disk under `<cache directory>/<host>/<path>/` and expire after 7 days. Empty or stale files are dropped on read.

The cache root is the static `Image::$cacheDirectory`, `.tiles_cache` by default. It is created recursively on first write. Being relative, the default depends on the working directory of the script — an application serving HTTP requests should set an absolute path once, at boot:

```php
Image::$cacheDirectory = '/var/www/storage/tiles-cache';
Image::$tileLogFile    = '/var/www/storage/logs/tiles-errors.log';
```

## Documentation

| Class | Description |
|---|---|
| [Circle](./docs/classes/Ycdev/OsmStaticAero/Circle.md) | Draw circle on the map, optionally as an airspace ring. |
| [Draw](./docs/classes/Ycdev/OsmStaticAero/Interfaces/Draw.md) | Interface implemented by every drawable. |
| [GeographicConverter](./docs/classes/Ycdev/OsmStaticAero/Utils/GeographicConverter.md) | Distance and bearing helpers between coordinates. |
| [Geometry2D](./docs/classes/Ycdev/OsmStaticAero/Geometry2D.md) | 2D geometry utility methods. |
| [Image](./docs/classes/Ycdev/OsmStaticAero/Image.md) | GD-based image editing with tile caching. |
| [LatLng](./docs/classes/Ycdev/OsmStaticAero/LatLng.md) | Define latitude and longitude for map, lines, markers. |
| [Line](./docs/classes/Ycdev/OsmStaticAero/Line.md) | Draw line on the map. |
| [MapData](./docs/classes/Ycdev/OsmStaticAero/MapData.md) | Convert latitude and longitude to image pixel position, and compute the map scale. |
| [Markers](./docs/classes/Ycdev/OsmStaticAero/Markers.md) | Display markers on the map. |
| [OpenStreetMap](./docs/classes/Ycdev/OsmStaticAero/OpenStreetMap.md) | Main class to generate static map images. |
| [Polygon](./docs/classes/Ycdev/OsmStaticAero/Polygon.md) | Draw polygon on the map. |
| [TileLayer](./docs/classes/Ycdev/OsmStaticAero/TileLayer.md) | Define tile server url and related configuration. |
| [XY](./docs/classes/Ycdev/OsmStaticAero/XY.md) | Define X and Y pixel position for map, lines, markers. |

Classes added by this fork. They have no generated API page yet — see [Aeronautical features](#aeronautical-features) above.

| Class | Description |
|---|---|
| Compass | Draw a graduated compass rose sized in meters. |
| Legend | Draw a legend block, pinned to an edge or anchored on the ground. |
| PaperMap | Compose tile layers and drawings into a paper-sized image. |
| ScaleText | Draw a scale bar with its distance and its `1:x` ratio. |
| Text | Write a text label anchored on the ground. |
| VacChart | Fetch official French VAC charts from the SIA eAIP. |

### Data sources

| Guide | Contents |
|---|---|
| [OpenAIP](./docs/openaip.md) | API key, rate limiting and transient failures, subdomains, a known raster rendering defect at low zoom, caching and attribution. |

## Examples

Runnable scripts are in [`examples/`](./examples) : [`simple.php`](./examples/simple.php) for a plain map, [`complete.php`](./examples/complete.php) for a full aeronautical paper chart with airfields, distance rings, compass, scale bar and legend.

## Credits

This project is based on [php-osm-static-api](https://github.com/DantSu/php-osm-static-api) originally created by [Franck ALARY (DantSu)](https://github.com/DantSu), licensed under the [MIT License](./LICENSE).

## Contributing

Please fork this repository and contribute back using pull requests.

Any contributions, large or small, major features, bug fixes, are welcomed and appreciated but will be thoroughly reviewed.
