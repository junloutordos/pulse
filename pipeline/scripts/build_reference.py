"""Builds data/reference from the open PSGC municipality shapefile (altcoder/philippines-psgc-shapefiles, MIT).

Download PH_Adm3_MuniCities.shp.zip from that repository (git-lfs), unzip it, then run:

    python scripts/build_reference.py /work/data/raw/altcoder/extracted/Municities.shp.shp /work/data/reference

Output: psgc.csv (official 10-digit PSGC codes, names, province, a representative point) and
boundaries.geojson holding shape=boundary (simplified official polygon) and shape=tile (equal-area hexagon for
the tile map) per municipality, plus shape=province outlines.
"""
import sys
from pathlib import Path

import geopandas as gpd
import pandas as pd
import shapely

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "src"))
from pulse_pipeline.tiles import hex_tiles  # noqa: E402

REGION = "PH16"  # Caraga
PROVINCES = {  # first five digits of the 10-digit PSGC code
    "16002": "Agusan del Norte", "16003": "Agusan del Sur", "16067": "Surigao del Norte",
    "16068": "Surigao del Sur", "16085": "Dinagat Islands",
    "16304": "Agusan del Norte",  # Butuan City: independent city inside Agusan del Norte
}
PROVINCE_CODE = {"Agusan del Norte": "1600200000", "Agusan del Sur": "1600300000", "Surigao del Norte": "1606700000",
                 "Surigao del Sur": "1606800000", "Dinagat Islands": "1608500000"}
SIMPLIFY_DEGREES = 0.0015


def build(shapefile: Path, out: Path) -> None:
    g = gpd.read_file(shapefile)
    g = g[g["adm1_pcode"] == REGION].copy()
    g["psgc"] = g["psgc_code"].astype("int64").astype(str).str.zfill(10)
    g["province"] = g["psgc"].str[:5].map(PROVINCES)
    if g["province"].isna().any():
        raise ValueError(f"Unmapped province prefixes: {sorted(g.loc[g.province.isna(), 'psgc'].str[:5].unique())}")
    g["province_psgc"] = g["province"].map(PROVINCE_CODE)
    g = g.sort_values("psgc").reset_index(drop=True)

    point = g.geometry.representative_point()
    munis = g[["psgc", "name", "province_psgc", "province"]].assign(lat=point.y.round(5), lon=point.x.round(5))

    simplified = shapely.coverage_simplify(g.geometry.to_numpy(), SIMPLIFY_DEGREES)
    boundary = gpd.GeoDataFrame({"psgc": g["psgc"], "name": g["name"], "province": g["province"], "shape": "boundary"}, geometry=simplified, crs="EPSG:4326")
    boundary["geometry"] = shapely.set_precision(boundary.geometry.to_numpy(), 0.0001)
    tiles = hex_tiles(munis).merge(g[["psgc", "name", "province"]], on="psgc").assign(shape="tile")
    tiles["geometry"] = shapely.set_precision(tiles.geometry.to_numpy(), 0.0001)
    provinces = boundary.dissolve(by="province", as_index=False)[["province", "geometry"]].assign(psgc="", name=lambda d: d["province"], shape="province")

    out.mkdir(parents=True, exist_ok=True)
    munis.to_csv(out / "psgc.csv", index=False)
    gpd.GeoDataFrame(pd.concat([boundary, tiles, provinces], ignore_index=True), geometry="geometry", crs="EPSG:4326").to_file(
        out / "boundaries.geojson", driver="GeoJSON"
    )
    (out / "README.md").write_text(
        "Reference data for Caraga (73 cities and municipalities).\n\n"
        "- Codes and names: Philippine Standard Geographic Code (PSGC), 10-digit.\n"
        "- Polygons: altcoder/philippines-psgc-shapefiles (MIT licence), built from public data and updated with PSA PSGC changes\n"
        "  (the repository states it reflects the PSGC as of 31 December 2023). Simplified for the web.\n"
        "- `boundaries.geojson` has two features per municipality: `shape=boundary` (official polygon) and `shape=tile`\n"
        "  (an equal-area hexagon placed near the real location, for the tile map).\n"
        "- Check the quarter against the current PSA PSGC publication before relying on it for real data.\n\n"
        "Rebuild with `pipeline/scripts/build_reference.py` (see its docstring).\n"
    )


if __name__ == "__main__":
    build(Path(sys.argv[1]), Path(sys.argv[2]))
