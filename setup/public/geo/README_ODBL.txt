Denizli Pamukkale + Merkezefendi mahalle (admin_level=8) GeoJSON dosyaları
=======================================================================

Kaynak: OpenStreetMap (ODbL 1.0). Haritada kullanımda © OpenStreetMap katkıcıları atfı gerekir.

Üretim (geliştirici makinesinde):
1. `tools/overpass_bbox_admin8.txt` ile bbox içi admin_level=8 ilişkileri çekilir (`php tools/fetch_overpass.php ...`).
2. `tools/overpass_mahalle_78_geom.txt` ile tam geometri indirilir.
3. `python tools/osm_relations_to_geojson.py` — ilçe sınırları OSM relation 1606201 / 1606211 (polygons.openstreetmap.fr) ile mahalleler ayrıştırılır; Shapely `linemerge` + `polygonize` kullanılır.

Çıktılar:
- `denizli_pamukkale_mahalleler.geojson`
- `denizli_merkezefendi_mahalleler.geojson`
- `denizli_pamukkale_merkezefendi_mahalleler.geojson` (tümü; `properties.ilce_atama`)

Yönetim haritasında `mahalle-fill` + `mahalle-outline` + `mahalle-label` (`properties.name`, poligon merkezi) katmanları birleşik dosyayı `fetch` ile yükler; yalnızca `esh_adrestablosu` içinde Pamukkale/Merkezefendi mahalle `adi` ile isim eşleşen poligonlar çizilir (checkbox).
