import numpy as np
import pandas as pd

from pulse_pipeline.tiles import hex_tiles


def _points(n=40, seed=3):
    rng = np.random.default_rng(seed)
    return pd.DataFrame({"psgc": [f"P{i:03d}" for i in range(n)], "lat": 8 + rng.random(n) * 2, "lon": 125.2 + rng.random(n) * 1.2})


def test_one_tile_per_municipality():
    tiles = hex_tiles(_points())
    assert len(tiles) == 40 and tiles.psgc.is_unique and set(tiles.psgc) == set(_points().psgc)
    assert (tiles.geometry.geom_type == "Polygon").all() and tiles.geometry.is_valid.all()


def test_tiles_do_not_overlap():
    tiles = hex_tiles(_points())
    geoms = list(tiles.geometry)
    worst = max(geoms[i].intersection(geoms[j]).area for i in range(len(geoms)) for j in range(i + 1, len(geoms)))
    assert worst < 1e-9


def test_tiles_stay_near_the_real_location():
    pts = _points()
    tiles = hex_tiles(pts, radius=0.075).set_index("psgc")
    spacing = 0.075 * 3**0.5
    for p in pts.itertuples():
        c = tiles.loc[p.psgc].geometry.centroid
        assert np.hypot(c.x - p.lon, c.y - p.lat) < 4 * spacing


def test_crowded_points_get_distinct_cells():
    pts = pd.DataFrame({"psgc": [f"C{i}" for i in range(10)], "lat": [9.0] * 10, "lon": [125.5] * 10})
    tiles = hex_tiles(pts)
    assert len({(round(g.centroid.x, 6), round(g.centroid.y, 6)) for g in tiles.geometry}) == 10


def test_deterministic():
    a, b = hex_tiles(_points()), hex_tiles(_points())
    assert [g.wkt for g in a.geometry] == [g.wkt for g in b.geometry]
