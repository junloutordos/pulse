Reference data for Caraga (73 cities and municipalities).

- Codes and names: Philippine Standard Geographic Code (PSGC), 10-digit.
- Polygons: altcoder/philippines-psgc-shapefiles (MIT licence), built from public data and updated with PSA PSGC changes
  (the repository states it reflects the PSGC as of 31 December 2023). Simplified for the web.
- `boundaries.geojson` has two features per municipality: `shape=boundary` (official polygon) and `shape=tile`
  (an equal-area hexagon placed near the real location, for the tile map).
- Check the quarter against the current PSA PSGC publication before relying on it for real data.

Rebuild with `pipeline/scripts/build_reference.py` (see its docstring).
