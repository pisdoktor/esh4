#!/usr/bin/env python3
"""
Overpass OSM JSON (relation + members geometry) -> GeoJSON FeatureCollection.
Pamukkale / Merkezefendi ilçe sınırları (OSM relation) ile mahalle merkezini içeren ilçeye atar.
Girdi: storage/geo_raw_mahalle_78_osm.json
Çıktı: public/geo/denizli_pamukkale_mahalleler.geojson, public/geo/denizli_merkezefendi_mahalleler.geojson
"""
from __future__ import annotations

import json
import sys
import urllib.request
from pathlib import Path

from shapely.geometry import LineString, MultiPolygon, Polygon, mapping, shape
from shapely.ops import linemerge, polygonize

ROOT = Path(__file__).resolve().parents[1]
OSM_IN = ROOT / "storage" / "geo_raw_mahalle_78_osm.json"
OUT_PAM = ROOT / "public" / "geo" / "denizli_pamukkale_mahalleler.geojson"
OUT_MEF = ROOT / "public" / "geo" / "denizli_merkezefendi_mahalleler.geojson"
OUT_ALL = ROOT / "public" / "geo" / "denizli_pamukkale_merkezefendi_mahalleler.geojson"

POLY_BASE = "https://polygons.openstreetmap.fr/get_geojson.py"


def fetch_json(url: str) -> dict:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "ESH-geo-build/1.0 (local; contact: admin)"},
    )
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode("utf-8"))


def multipolygon_from_osm_fr(relation_id: int) -> MultiPolygon | Polygon:
    raw = fetch_json(f"{POLY_BASE}?id={relation_id}&params=0")
    geom = shape(raw)
    if not geom.is_valid:
        geom = geom.buffer(0)
    return geom


def relation_to_polygon(rel: dict) -> Polygon | MultiPolygon | None:
    outers = []
    for m in rel.get("members") or []:
        if m.get("type") != "way" or m.get("role") not in ("outer", ""):
            continue
        g = m.get("geometry")
        if not g or len(g) < 2:
            continue
        coords = [(float(p["lon"]), float(p["lat"])) for p in g]
        outers.append(LineString(coords))
    if not outers:
        return None
    try:
        merged = linemerge(outers)
    except Exception:
        return None
    if merged.is_empty:
        return None
    polys = list(polygonize(merged))
    if not polys:
        return None
    if len(polys) == 1:
        p = polys[0]
        return p if p.is_valid else p.buffer(0)
    u = MultiPolygon(polys)
    return u if u.is_valid else u.buffer(0)


def main() -> int:
    if not OSM_IN.is_file():
        print("Eksik:", OSM_IN, file=sys.stderr)
        return 1

    data = json.loads(OSM_IN.read_text(encoding="utf-8"))
    pam_ilce = multipolygon_from_osm_fr(1606201)
    mef_ilce = multipolygon_from_osm_fr(1606211)

    feats_pam: list[dict] = []
    feats_mef: list[dict] = []
    feats_all: list[dict] = []

    for el in data.get("elements") or []:
        if el.get("type") != "relation":
            continue
        tags = el.get("tags") or {}
        name = tags.get("name") or f"relation-{el.get('id')}"
        geom = relation_to_polygon(el)
        if geom is None or geom.is_empty:
            print("Atlandı (geometri yok):", el.get("id"), name, file=sys.stderr)
            continue
        c = geom.representative_point()
        in_pam = pam_ilce.contains(c) or pam_ilce.touches(c)
        in_mef = mef_ilce.contains(c) or mef_ilce.touches(c)
        props = {
            "name": name,
            "osm_relation_id": el.get("id"),
            "admin_level": tags.get("admin_level"),
            "source": "OpenStreetMap (ODbL)",
        }
        if in_pam and in_mef:
            ilce = "Her ikisi"
        elif in_pam:
            ilce = "Pamukkale"
        elif in_mef:
            ilce = "Merkezefendi"
        else:
            ilce = "Belirsiz"
        feat = {
            "type": "Feature",
            "properties": props,
            "geometry": mapping(geom),
        }
        feats_all.append(
            {
                **feat,
                "properties": {**props, "ilce_atama": ilce},
            }
        )

        if in_pam and not in_mef:
            feats_pam.append(feat)
        elif in_mef and not in_pam:
            feats_mef.append(feat)
        elif in_pam and in_mef:
            print("Çift kesişim (atlandı):", el.get("id"), name, file=sys.stderr)
        else:
            print("İlçe dışı (atlandı):", el.get("id"), name, file=sys.stderr)

    OUT_PAM.parent.mkdir(parents=True, exist_ok=True)

    def dump(path: Path, feats: list[dict]) -> None:
        fc = {"type": "FeatureCollection", "features": feats}
        path.write_text(json.dumps(fc, ensure_ascii=False, indent=2), encoding="utf-8")

    dump(OUT_PAM, feats_pam)
    dump(OUT_MEF, feats_mef)
    dump(OUT_ALL, feats_all)

    print("Pamukkale mahalle:", len(feats_pam), "->", OUT_PAM)
    print("Merkezefendi mahalle:", len(feats_mef), "->", OUT_MEF)
    print("Hepsi (etiketli):", len(feats_all), "->", OUT_ALL)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
