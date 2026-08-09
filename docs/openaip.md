# OpenAIP

[OpenAIP](https://www.openaip.net) is the aeronautical data source used by this
library. It exposes two independent services, both requiring an API key:

| Service | Host | Used for |
|---|---|---|
| Tile server (TMS) | `api.tiles.openaip.net` | the airspace overlay, via `TileLayer::OPENAIP` |
| Core REST API | `api.core.openaip.net` | airfield data (runways, frequencies, coordinates) |

The library only talks to the tile server. Querying the REST API is left to the
calling application — see `examples/complete.php`.

## API key

Both services reject unauthenticated requests. Register on openaip.net to obtain
a key, then pass it in the `apiKey` query parameter.

`TileLayer::OPENAIP` carries an `{apiKey}` placeholder, never a key. Supply
yours in whichever way suits the host application:

```php
use Ycdev\OsmStaticAero\TileLayer;

// Once at boot — the usual choice for an application
TileLayer::$openaipApiKey = $yourKey;

// Or from the environment, without touching the code
putenv('OPENAIP_API_KEY=' . $yourKey);

// Or explicitly, when a single layer needs a distinct key
$map->addLayer(TileLayer::openaip($yourKey));
```

Resolution order is the static property, then the `OPENAIP_API_KEY` environment
variable. `TileLayer::resolveApiKey()` returns the effective key, or `null`, and
is worth reusing for your own REST calls so both go through the same setting.

The placeholder is substituted **in the constructor**, so a missing key throws an
`InvalidArgumentException` before the first request. That matters here: an A0
sheet would otherwise emit several hundred `401` responses, and the cause would
have to be inferred from the failure log.

The key ends up in the tile URL, which is also the disk cache key — so cached
file names contain it. Keep the cache directory out of version control.
`Image::logTileFailure()` masks it before writing to the log.

## Rate limiting and transient failures

The documentation states that exceeding the rate limit returns **HTTP 429**, and
recommends reducing the request rate or fronting the service with your own cache.

In practice, throttling has also been observed as **HTTP 502**. Measured on a
single host:

```
60 requests, no pause (7.4 req/s)   ->  60 × HTTP 502
the same tiles, 700 ms apart        ->  HTTP 200
30 requests on a fresh area, later  ->  30 × HTTP 200   (not reproducible)
```

These failures are intermittent, not deterministic. An A0 sheet at zoom 12 issues
roughly 560 tile requests per layer, so a whole map is well over a thousand — the
odds of hitting one are high.

`Image::curl()` handles this:

- up to `Image::$tileMaxAttempts` attempts (3 by default), waiting 500 ms then 1 s
- only retryable failures are retried: cURL errors, 429, 5xx, and 200 responses
  whose body is not an image. A 204, 401, 403 or 404 fails immediately — the
  server answered clearly and insisting would only add load
- **failed responses are never cached**, so a re-run retries only what failed
- every failure is written to `Image::$tileLogFile` and to STDERR under CLI

Without this, a failed tile produced a transparent hole and no trace at all:
`data()` fails, `resetFields()` empties the object and `pasteOn()` returns early
on `isImageDefined()`.

## Subdomains

The OpenAIP documentation publishes the tile URL with a subdomain placeholder,
`https://{s}.api.tiles.openaip.net/...`, and `TileLayer` supports `{s}`.

`TileLayer::OPENAIP` deliberately does **not** use it. Subdomain sharding works
around a browser's per-host connection limit; this client is sequential, so it
gains nothing — measured 25/25 successful requests in a row on a single host with
no pause. Meanwhile, adding `{s}` changes every tile URL, and therefore every
cache key, invalidating the whole tile cache.

Use `{s}` only if you fetch tiles concurrently.

## Known raster rendering defect at low zoom

OpenAIP renders its raster tiles from vector data, and the raster renderer drops
geometry on some zoom-12 tiles.

Documented case — tile `12/2088/1423`, near Saint-Florentin (LFGP):

```
12/2087/1423   3072 px of line, x[0..255] y[201..212]   full-width boundary
12/2088/1423   1792 px of line, x[249..255] y[0..255]   only a vertical tick
```

The TMA boundary reaches the right edge of one tile and nothing continues it in
the next. The zone fill is present; the outline is missing. The vector tile for
the same coordinates (`.pbf`, same endpoint) **does contain the polygon**, with a
vertex at y = 3394/4096 — exactly the 212 px where the neighbouring raster tile
draws its line. The same corner renders correctly at zoom 13.

Extent measured over one A0 sheet, comparing every pair of adjacent tiles:

```
560 tiles analysed, 24 boundary discontinuities out of 1092 shared edges  (2%)
```

There is no raster workaround. `@2x`, `scale=2`, `tileSize=512`, a cache-busting
parameter and the `a/b/c` subdomains all return a byte-identical tile.

This is why the openaip.net map looks correct: the website downloads the vector
tiles and renders them in the browser. Reproducing that would mean implementing
an MVT renderer and OpenAIP's cartographic style — labels along curved
boundaries being the hard part.

If the defect matters for your use case, render one zoom level deeper and
downscale, at the cost of four times as many tiles and half-size labels.

## Caching

`Image` caches every successful tile under `Image::$cacheDirectory`
(`.tiles_cache` by default), keyed by the full URL. Before caching, the response
is checked against the PNG, JPEG, GIF and WEBP magic bytes: an HTML error page
served with HTTP 200 must never end up on disk under a `.png` name.

Honour the service: cache aggressively and do not re-download a map you already
have.

## Attribution

OpenAIP data is published under its own terms and requires attribution.
`TileLayer::OPENAIP` carries `© OpenAIP contributors`, drawn automatically by
`OpenStreetMap::getImage()`.

`PaperMap` disables that automatic attribution, so **printed output must carry
the notice by other means** — a line in the `Legend` block, for instance. This
applies to OpenStreetMap and OpenTopoMap too.
