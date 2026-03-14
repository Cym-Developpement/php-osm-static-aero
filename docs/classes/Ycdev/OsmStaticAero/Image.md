
# Image

Ycdev\OsmStaticAero\Image is a GD-based image editing class for creating, drawing, compositing and exporting images. Includes tile caching for OpenStreetMap tiles.

* Full name: `\Ycdev\OsmStaticAero\Image`

## Constants

| Constant | Value |
|:---      |:---   |
|`ALIGN_LEFT`|'left'|
|`ALIGN_CENTER`|'center'|
|`ALIGN_RIGHT`|'right'|
|`ALIGN_TOP`|'top'|
|`ALIGN_MIDDLE`|'middle'|
|`ALIGN_BOTTOM`|'bottom'|

## Methods

### Creation / Destruction

- *(static)* [newCanvas](#newcanvas)
- [resetCanvas](#-resetcanvas)
- *(static)* [fromPath](#frompath)
- [path](#-path)
- *(static)* [fromData](#fromdata)
- [data](#-data)
- *(static)* [fromCurl](#fromcurl)
- [curl](#-curl)
- [destroy](#-destroy)

### Getters

- [getWidth](#-getwidth)
- [getHeight](#-getheight)
- [getType](#-gettype)
- [getImage](#-getimage)
- *(static)* [isGdImage](#isgdimage)
- [isImageDefined](#-isimagedefined)

### Drawing

- [drawLine](#-drawline)
- [drawLineWithAngle](#-drawlinewithangle)
- [drawPolygon](#-drawpolygon)
- [drawCircle](#-drawcircle)
- [drawRectangle](#-drawrectangle)

### Composition

- [pasteOn](#-pasteon)
- [pasteGdImageOn](#-pastegdimageon)
- [setOpacity](#-setopacity)

### Text

- [writeText](#-writetext)
- [writeTextAndGetBoundingBox](#-writetextandgetboundingbox)

### Output

- [savePNG](#-savepng) / [saveJPG](#-savejpg) / [saveGIF](#-savegif)
- [displayPNG](#-displaypng) / [displayJPG](#-displayjpg) / [displayGIF](#-displaygif)
- [getDataPNG](#-getdatapng) / [getDataJPG](#-getdatajpg) / [getDataGIF](#-getdatagif)

---

### ::newCanvas

Create a new image with transparent background.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `width` | **int** | Pixel width |
| `height` | **int** | Pixel height |

#### Return Value:

 **\Ycdev\OsmStaticAero\Image** :

---
### ->resetCanvas

Reset the image to a new transparent canvas.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `width` | **int** | Pixel width |
| `height` | **int** | Pixel height |

#### Return Value:

 **$this** : Fluent interface

---
### ::fromPath

Open image from local path.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `path` | **string** | Path to the image file |

#### Return Value:

 **\Ycdev\OsmStaticAero\Image** :

---
### ::fromData

Create an Image instance from raw image data.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `data` | **string** | Raw data of the image |

#### Return Value:

 **\Ycdev\OsmStaticAero\Image** :

---
### ::fromCurl

Open image from URL with cURL (with tile caching).

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `url` | **string** | URL of the image file |
| `curlOptions` | **array** | cURL options |
| `failOnError` | **bool** | If true, throw an exception on error |

#### Return Value:

 **\Ycdev\OsmStaticAero\Image** :

---
### ->drawLine

Draw a line from origin to destination.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `originX` | **int** | Start X |
| `originY` | **int** | Start Y |
| `dstX` | **int** | Destination X |
| `dstY` | **int** | Destination Y |
| `weight` | **int** | Line weight in pixels |
| `color` | **string** | Hexadecimal color |

#### Return Value:

 **$this** : Fluent interface

---
### ->drawPolygon

Draw a filled polygon.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `points` | **int[]** | Array of points [x1, y1, x2, y2, ...] |
| `color` | **string** | Hexadecimal color |
| `antialias` | **bool** | Enable antialiasing |

#### Return Value:

 **$this** : Fluent interface

---
### ->drawCircle

Draw a filled circle.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `posX` | **int** | X position |
| `posY` | **int** | Y position |
| `diameter` | **int** | Circle diameter |
| `color` | **string** | Hexadecimal color |
| `anchorX` | **string** | Horizontal anchor (`ALIGN_LEFT`, `ALIGN_CENTER`, `ALIGN_RIGHT`) |
| `anchorY` | **string** | Vertical anchor (`ALIGN_TOP`, `ALIGN_MIDDLE`, `ALIGN_BOTTOM`) |

#### Return Value:

 **$this** : Fluent interface

---
### ->drawRectangle

Draw a filled rectangle.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `left` | **int** | Left position |
| `top` | **int** | Top position |
| `right` | **int** | Right position |
| `bottom` | **int** | Bottom position |
| `color` | **string** | Hexadecimal color |

#### Return Value:

 **$this** : Fluent interface

---
### ->pasteOn

Paste an Image on this image.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `image` | **\Ycdev\OsmStaticAero\Image** | Image to paste |
| `posX` | **int&#124;string** | Position or alignment constant |
| `posY` | **int&#124;string** | Position or alignment constant |

#### Return Value:

 **$this** : Fluent interface

---
### ->setOpacity

Change the image opacity.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `opacity` | **float** | Opacity (0 to 1) |

#### Return Value:

 **$this** : Fluent interface

---
### ->writeText

Write text on the image.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `string` | **string** | Text to write |
| `fontPath` | **string** | Path to TTF font |
| `fontSize` | **float** | Font size |
| `color` | **string** | Hexadecimal color |
| `posX` | **float&#124;string** | Position or alignment |
| `posY` | **float&#124;string** | Position or alignment |
| `anchorX` | **float&#124;string** | Text anchor horizontal |
| `anchorY` | **float&#124;string** | Text anchor vertical |
| `rotation` | **float** | Rotation in degrees |
| `letterSpacing` | **float** | Letter spacing |

#### Return Value:

 **$this** : Fluent interface

---
### ->writeTextAndGetBoundingBox

Write text and return bounding box coordinates.

Same parameters as `writeText`.

#### Return Value:

 **array** : Keys: `top-left`, `top-right`, `bottom-left`, `bottom-right`, `baseline` (each with `x`, `y`)

---
### ->savePNG

Save to PNG file.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `path` | **string** | File path |

#### Return Value:

 **bool** : success

---
### ->saveJPG

Save to JPG file.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `path` | **string** | File path |
| `quality` | **int** | JPG quality (0-100) |

#### Return Value:

 **bool** : success

---
### ->getDataPNG

Get PNG raw data.

#### Return Value:

 **string** : PNG data

---
