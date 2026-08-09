
# Line

Ycdev\OsmStaticAero\Line draw line on the map.

* Full name: `\Ycdev\OsmStaticAero\Line`
* This class implements: \Ycdev\OsmStaticAero\Interfaces\Draw

## Methods

- [__construct](#-__construct)
- [addPoint](#-addpoint)
- [draw](#-draw)
- [getBoundingBox](#-getboundingbox)

### ->__construct

Line constructor.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `color` | **string** | Hexadecimal string color |
| `weight` | **int** | pixel weight of the line |

---
### ->addPoint

Add a latitude and longitude to the multi-points line

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `latLng` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude to add |

#### Return Value:

 **$this** : Fluent interface

---
### ->draw

Draw the line on the map image.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `image` | **\Ycdev\OsmStaticAero\Image** | The map image |
| `mapData` | **\Ycdev\OsmStaticAero\MapData** | Bounding box of the map |

#### Return Value:

 **$this** : Fluent interface

---
### ->getBoundingBox

Get bounding box of the shape

#### Return Value:

 **\Ycdev\OsmStaticAero\LatLng[]** :

---
