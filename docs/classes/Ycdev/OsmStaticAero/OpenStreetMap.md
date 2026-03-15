
# OpenStreetMap

Ycdev\OsmStaticAero\OpenStreetMap is a PHP library created for easily getting static images from OpenStreetMap with markers, lines, polygons and circles.

* Full name: `\Ycdev\OsmStaticAero\OpenStreetMap`

## Methods

- *(static)* [createFromLatLngZoom](#createfromlatlngzoom)
- *(static)* [createFromBoundingBox](#createfromboundingbox)
- [__construct](#-__construct)
- [addLayer](#-addlayer)
- [addMarkers](#-addmarkers)
- [addDraw](#-adddraw)
- [fitToDraws](#-fittodraws)
- [fitToMarkers](#-fittomarkers)
- [fitToMarkersAndDraws](#-fittomarkersanddraws)
- [fitToPoints](#-fittopoints)
- [getMapData](#-getmapdata)
- [getImage](#-getimage)

### ::createFromLatLngZoom

Create new instance of OpenStreetMap.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `centerMap` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the map center |
| `zoom` | **int** | Zoom |
| `imageWidth` | **int** | Width of the generated map image |
| `imageHeight` | **int** | Height of the generated map image |
| `tileLayer` | **\Ycdev\OsmStaticAero\TileLayer** | Tile server configuration, defaults to OpenStreetMaps tile server |
| `tileSize` | **int** | Tile size in pixels |

#### Return Value:

 **\Ycdev\OsmStaticAero\OpenStreetMap** :

---
### ::createFromBoundingBox

Create new instance of OpenStreetMap.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `topLeft` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the map top left |
| `bottomRight` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the map bottom right |
| `padding` | **int** | Padding to add before top left and after bottom right position. |
| `imageWidth` | **int** | Width of the generated map image |
| `imageHeight` | **int** | Height of the generated map image |
| `tileLayer` | **\Ycdev\OsmStaticAero\TileLayer** | Tile server configuration, defaults to OpenStreetMaps tile server |
| `tileSize` | **int** | Tile size in pixels |

#### Return Value:

 **\Ycdev\OsmStaticAero\OpenStreetMap** :

---
### ->__construct

OpenStreetMap constructor.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `centerMap` | **\Ycdev\OsmStaticAero\LatLng** | Latitude and longitude of the map center |
| `zoom` | **int** | Zoom |
| `imageWidth` | **int** | Width of the generated map image |
| `imageHeight` | **int** | Height of the generated map image |
| `tileLayer` | **\Ycdev\OsmStaticAero\TileLayer** | Tile server configuration, defaults to OpenStreetMaps tile server |
| `tileSize` | **int** | Tile size in pixels |

---
### ->addLayer

Add tile layer to the map.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `layer` | **\Ycdev\OsmStaticAero\TileLayer** | An instance of TileLayer |

#### Return Value:

 **$this** : Fluent interface

---
### ->addMarkers

Add markers on the map.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `markers` | **\Ycdev\OsmStaticAero\Markers** | An instance of Markers |

#### Return Value:

 **$this** : Fluent interface

---
### ->addDraw

Add a draw on the map.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `draw` | **\Ycdev\OsmStaticAero\Interfaces\Draw** | An instance of Draw |

#### Return Value:

 **$this** : Fluent interface

---
### ->fitToDraws

Fit map to draws.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `padding` | **int** | Padding in pixel |

#### Return Value:

 **$this** : Fluent interface

---
### ->fitToMarkers

Fit map to markers.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `padding` | **int** | Padding in pixel |

#### Return Value:

 **$this** : Fluent interface

---
### ->fitToMarkersAndDraws

Fit map to draws and markers.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `padding` | **int** | Padding in pixel |

#### Return Value:

 **$this** : Fluent interface

---
### ->fitToPoints

Fit map to an array of points.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `points` | **\Ycdev\OsmStaticAero\LatLng[]** | LatLng points |
| `padding` | **int** | Padding in pixel |

#### Return Value:

 **$this** : Fluent interface

---
### ->getMapData

Get data about the generated map (bounding box, size, OSM tile ids...).

#### Return Value:

 **\Ycdev\OsmStaticAero\MapData** : data about the generated map

---
### ->getImage

Get the map image with markers and lines.

#### Return Value:

 **\Ycdev\OsmStaticAero\Image** : An instance of Image

---
