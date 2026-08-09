
# Geometry2D

Ycdev\OsmStaticAero\Geometry2D provides static utility methods for 2D geometry calculations.

* Full name: `\Ycdev\OsmStaticAero\Geometry2D`

## Methods

- *(static)* [degrees0to360](#degrees0to360)
- *(static)* [getDstXY](#getdstxy)
- *(static)* [getDstXYRounded](#getdstxyrounded)
- *(static)* [getAngleAndLengthFromPoints](#getangleandlengthfrompoints)

### ::degrees0to360

Normalize an angle to 0-360 range.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `angle` | **float** | Angle in degrees |

#### Return Value:

 **float** : Normalized angle (0 <= angle < 360)

---
### ::getDstXY

Calculate destination coordinates from origin, angle and distance.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `originX` | **float** | Origin X |
| `originY` | **float** | Origin Y |
| `angle` | **float** | Counterclockwise angle in degrees |
| `length` | **float** | Distance |

#### Return Value:

 **array** : `['x' => float, 'y' => float]`

---
### ::getDstXYRounded

Same as `getDstXY` but with rounded coordinates.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `originX` | **float** | Origin X |
| `originY` | **float** | Origin Y |
| `angle` | **float** | Counterclockwise angle in degrees |
| `length` | **float** | Distance |

#### Return Value:

 **array** : `['x' => float, 'y' => float]` (rounded)

---
### ::getAngleAndLengthFromPoints

Calculate angle and distance between two points.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `originX` | **float** | Origin X |
| `originY` | **float** | Origin Y |
| `dstX` | **float** | Destination X |
| `dstY` | **float** | Destination Y |

#### Return Value:

 **array** : `['angle' => float, 'length' => float]`

---
