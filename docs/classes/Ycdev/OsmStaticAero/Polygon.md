
# Polygon

Ycdev\OsmStaticAero\Polygon draw polygon on the map.

* Full name: `\Ycdev\OsmStaticAero\Polygon`
* This class implements: \Ycdev\OsmStaticAero\Interfaces\Draw

## Methods

- [__construct](#-__construct)
- [addPoint](#-addpoint)
- [draw](#-draw)
- [getBoundingBox](#-getboundingbox)

### ->__construct

Polygon constructor.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `strokeColor` | **string** | Hexadecimal string color |
| `strokeWeight` | **int** | pixel weight of the line |
| `fillColor` | **string** | Hexadecimal string color |

---
### ->addPoint

Add a latitude and longitude to the polygon

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `latLng` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude to add |

#### Return Value:

 **$this** : Fluent interface

---
### ->draw

Draw the polygon on the map image.

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
