-- ICD-10 ağaç hiyerarşisi: parent_icd + seviye (SKRS ICD10Listesi)
ALTER TABLE `esh_hastaliklar`
  ADD COLUMN `parent_icd` VARCHAR(32) DEFAULT NULL COMMENT 'SKRS üst kod' AFTER `icd`,
  ADD COLUMN `seviye` TINYINT UNSIGNED DEFAULT NULL COMMENT 'SKRS seviye' AFTER `parent_icd`;

ALTER TABLE `esh_hastaliklar`
  ADD KEY `idx_hastaliklar_parent` (`parent_icd`);
