import geopandas as gpd
import numpy as np
import pandas as pd
from scipy.optimize import linear_sum_assignment
from shapely.geometry import Polygon

SQRT3 = 3**0.5


def hex_tiles(munis: pd.DataFrame, radius: float = 0.075, gap: float = 0.94) -> gpd.GeoDataFrame:
    """One hexagon per municipality, each placed on a hex grid as near as possible to its real location.

    A tile map is deliberately not geography: every municipality gets the same area, so small ones
    (islands, cities) are as visible as large ones. Coordinates are lon/lat degrees; longitude is
    scaled by cos(latitude) while laying out so hexagons are regular on the ground. `radius` is the
    hexagon circumradius in degrees of latitude and `gap` shrinks each tile slightly to leave a seam.
    """
    lat0 = float(np.mean(munis["lat"]))
    kx = np.cos(np.radians(lat0))
    pts = np.column_stack([munis["lon"].to_numpy(float) * kx, munis["lat"].to_numpy(float)])

    # Candidate cell centres (pointy-top hexagons on an axial grid) covering the points with a margin.
    margin = radius * 6
    lo, hi = pts.min(axis=0) - margin, pts.max(axis=0) + margin
    cells = []
    r_max = int((hi[1] - lo[1]) / (1.5 * radius)) + 1
    for r in range(r_max + 1):
        q_min = int(np.floor((lo[0] - SQRT3 * radius * r / 2) / (SQRT3 * radius)))
        q_max = int(np.ceil((hi[0] - SQRT3 * radius * r / 2) / (SQRT3 * radius)))
        for q in range(q_min, q_max + 1):
            cells.append((SQRT3 * radius * (q + r / 2), lo[1] + 1.5 * radius * r))
    cells = np.array(cells)

    cost = ((pts[:, None, :] - cells[None, :, :]) ** 2).sum(axis=2)
    rows, cols = linear_sum_assignment(cost)
    order = np.argsort(rows)
    chosen = cells[cols[order]]

    angles = np.radians(60 * np.arange(6) + 30)
    shape = radius * gap
    polys = [
        Polygon([((cx + shape * np.cos(a)) / kx, cy + shape * np.sin(a)) for a in angles])
        for cx, cy in chosen
    ]
    return gpd.GeoDataFrame({"psgc": munis["psgc"].to_numpy()}, geometry=polys, crs="EPSG:4326")
