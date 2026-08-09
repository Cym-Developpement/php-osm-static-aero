
# Markers

Ycdev\OsmStaticAero\Markers display markers on the map.

* Full name: `\Ycdev\OsmStaticAero\Markers`

## Constants

| Constant | Value |
|:---      |:---   |
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_LEFT`|&#039;left&#039;|
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_CENTER`|&#039;center&#039;|
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_RIGHT`|&#039;right&#039;|
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_TOP`|&#039;top&#039;|
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_MIDDLE`|&#039;middle&#039;|
|`\Ycdev\OsmStaticAero\Markers::ANCHOR_BOTTOM`|&#039;bottom&#039;|

## Methods

- [__construct](#-__construct)
- [addMarker](#-addmarker)
- [setAnchor](#-setanchor)
- [draw](#-draw)
- [getBoundingBox](#-getboundingbox)

### ->__construct

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `pathImage` | **string** | Path to marker image |

---
### ->addMarker

Add a marker on the map.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `coordinate` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the marker |

#### Return Value:

 **$this** : Fluent interface

---
### ->setAnchor

Define the anchor point of the image marker.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `horizontalAnchor` | **int&#124;string** | Horizontal anchor in pixel or you can use `Markers::ANCHOR_LEFT`, `Markers::ANCHOR_CENTER`, `Markers::ANCHOR_RIGHT` |
| `verticalAnchor` | **int&#124;string** | Vertical anchor in pixel or you can use `Markers::ANCHOR_TOP`, `Markers::ANCHOR_MIDDLE`, `Markers::ANCHOR_BOTTOM` |

#### Return Value:

 **$this** : Fluent interface

---
### ->draw

Draw markers on the image map.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `image` | **\Ycdev\OsmStaticAero\Image** | The map image |
| `mapData` | **\Ycdev\OsmStaticAero\MapData** | Bounding box of the map |

#### Return Value:

 **$this** : Fluent interface

---
### ->getBoundingBox

Get bounding box of markers

#### Return Value:

 **\Ycdev\OsmStaticAero\LatLng[]** :

---
