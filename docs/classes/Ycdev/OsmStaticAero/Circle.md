
# Circle

Ycdev\OsmStaticAero\Circle draw circle on the map.

* Full name: `\Ycdev\OsmStaticAero\Circle`
* This class implements: \Ycdev\OsmStaticAero\Interfaces\Draw

## Methods

- [__construct](#-__construct)
- [setEdgePoint](#-setedgepoint)
- [setRadius](#-setradius)
- [draw](#-draw)
- [getBoundingBox](#-getboundingbox)

### ->__construct

Circle constructor.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `center` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the circle center |
| `strokeColor` | **string** | Hexadecimal string color |
| `strokeWeight` | **int** | pixel weight of the line |
| `fillColor` | **string** | Hexadecimal string color |

---
### ->setEdgePoint

Set a latitude and longitude to define the radius of the circle.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `edge` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the edge point of a circle |

#### Return Value:

 **$this** : Fluent interface

---
### ->setRadius

Set the radius of the circle in meters.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `radius` | **float** | radius of a circle in meters |

#### Return Value:

 **$this** : Fluent interface

---
### ->draw

Draw the circle on the map image.

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
