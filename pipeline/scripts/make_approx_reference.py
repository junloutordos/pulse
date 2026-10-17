"""Builds an APPROXIMATE Caraga reference for development and the synthetic demo.

Municipality shapes are Voronoi cells around approximate town centers, clipped to a smoothed province outline.
Codes use a placeholder prefix (99) and are NOT official PSGC codes. Replace the output folder with the
official PSGC list and boundaries (same file names and columns) before using real data.

Run:  python scripts/make_approx_reference.py ../data/reference
"""
import sys
from pathlib import Path

import geopandas as gpd
import pandas as pd
import shapely
from shapely.geometry import MultiPoint, Point

HERE = Path(__file__).parent
PROVINCE_CODES = {"Agusan del Norte": "99001", "Agusan del Sur": "99002", "Surigao del Norte": "99003",
                  "Surigao del Sur": "99004", "Dinagat Islands": "99005"}


def build(out: Path) -> None:
    towns = pd.read_csv(HERE / "caraga_town_centers.csv")
    towns["province_psgc"] = towns["province"].map(PROVINCE_CODES)
    towns = towns.sort_values(["province_psgc", "name"]).reset_index(drop=True)
    towns["psgc"] = [f"{p}{i % 1000:05d}" for p, i in zip(towns.province_psgc, towns.groupby("province_psgc").cumcount() + 1)]

    cells = []
    for province, group in towns.groupby("province"):
        pts = [Point(lon, lat) for lat, lon in zip(group.lat, group.lon)]
        outline = shapely.union_all([p.buffer(0.11) for p in pts]).buffer(0.04).buffer(-0.04)
        vor = shapely.voronoi_polygons(MultiPoint(pts), extend_to=outline.envelope.buffer(0.5))
        for geom in vor.geoms:
            owner = next(i for i, p in zip(group.index, pts) if geom.contains(p))
            cells.append((owner, geom.intersection(outline)))
    gdf = gpd.GeoDataFrame(
        towns.loc[[i for i, _ in cells], ["psgc", "name"]].reset_index(drop=True),
        geometry=[g for _, g in cells], crs="EPSG:4326",
    )
    gdf["geometry"] = gdf.geometry.simplify(0.002)
    out.mkdir(parents=True, exist_ok=True)
    towns[["psgc", "name", "province_psgc", "province", "lat", "lon"]].to_csv(out / "psgc.csv", index=False)
    gdf.to_file(out / "boundaries.geojson", driver="GeoJSON")
    (out / "README.md").write_text(
        "APPROXIMATE reference for development and the synthetic demo.\n\n"
        "- Codes are placeholders (prefix 99), not official PSGC codes.\n"
        "- Shapes are Voronoi cells around approximate town centers, not official boundaries.\n\n"
        "Replace psgc.csv and boundaries.geojson with the official PSGC list and boundaries "
        "(same columns) before using real data.\n"
    )


if __name__ == "__main__":
    build(Path(sys.argv[1]))
