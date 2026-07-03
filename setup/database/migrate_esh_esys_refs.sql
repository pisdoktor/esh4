-- ESYS uyum hazırlığı — manuel köprü referans numaraları (entegrasyon öncesi)

ALTER TABLE `esh_hastalar`
  ADD COLUMN `esys_hasta_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS hasta kayıt no' AFTER `erapor`,
  ADD COLUMN `esys_basvuru_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS başvuru / dosya no' AFTER `esys_hasta_ref`;

ALTER TABLE `esh_izlemler`
  ADD COLUMN `esys_izlem_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS izlem kayıt no' AFTER `kons_brans_istek`,
  ADD COLUMN `esys_konsultasyon_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS konsültasyon kayıt no' AFTER `esys_izlem_ref`;

ALTER TABLE `esh_pizlemler`
  ADD COLUMN `esys_plan_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS planlı ziyaret no' AFTER `durum`;

ALTER TABLE `esh_erapor`
  ADD COLUMN `esys_erapor_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ESYS e-rapor / başvuru eşleşme no' AFTER `neden`;

ALTER TABLE `esh_hastalar` ADD KEY `idx_hastalar_esys_hasta_ref` (`esys_hasta_ref`);
ALTER TABLE `esh_hastalar` ADD KEY `idx_hastalar_esys_basvuru_ref` (`esys_basvuru_ref`);
ALTER TABLE `esh_izlemler` ADD KEY `idx_izlemler_esys_izlem_ref` (`esys_izlem_ref`);
