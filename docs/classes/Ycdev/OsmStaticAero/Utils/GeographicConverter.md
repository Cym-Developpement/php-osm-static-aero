
# GeographicConverter

* Full name: `\Ycdev\OsmStaticAero\Utils\GeographicConverter`

## Methods

- *(static)* [earthRadiusAtLatitude](#earthradiusatlatitude)
- *(static)* [metersToLatLng](#meterstolatlng)
- *(static)* [latLngToMeters](#latlngtometers)
- *(static)* [getCenter](#getcenter)

### ::earthRadiusAtLatitude

Calculate the earth radius at the given latitude.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `lat` | **float** |  |

#### Return Value:

 **float** :

---
### ::metersToLatLng

Convert distance and angle from a point to latitude and longitude.
0 : top, 90 : right; 180 : bottom, 270 : left

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `from` | **\Ycdev\OsmStaticAero\LatLng** | Starting coordinate |
| `distance` | **float** | Distance in meters |
| `angle` | **float** | Angle in degrees |

#### Return Value:

 **\Ycdev\OsmStaticAero\LatLng** :

---
### ::latLngToMeters

Get distance in meters between two points.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `from` | **\Ycdev\OsmStaticAero\LatLng** | Starting coordinate |
| `end` | **\Ycdev\OsmStaticAero\LatLng** | Ending coordinate |

#### Return Value:

 **float** :

---
### ::getCenter

Get center between two coordinates.

* This method is **static**.

#### Parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `point1` | **\Ycdev\OsmStaticAero\LatLng** | First coordinate |
| `point2` | **\Ycdev\OsmStaticAero\LatLng** | Second coordinate |

#### Return Value:

 **\Ycdev\OsmStaticAero\LatLng** : midpoint between the given coordinates

---
