-- Federasyon bölgesi ↔ adres ağacı (tip=bolge) eşlemesi

ALTER TABLE `#__adrestablosu`
  ADD COLUMN `federation_bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'esh_federation_regions.id' AFTER `tip`;

ALTER TABLE `#__adrestablosu`
  ADD UNIQUE KEY `uk_adres_fed_bolge` (`federation_bolge_id`);
