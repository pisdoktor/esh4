-- Bölge düzeyi operasyonel ayarlar (kurum ayarlar_json ile aynı yapı)

ALTER TABLE `esh_federation_regions`
  ADD COLUMN `ayarlar_json` LONGTEXT NULL DEFAULT NULL COMMENT 'Bölge varsayılan ayarları (modules, islem_ids, operational)' AFTER `aciklama`;
