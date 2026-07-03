-- =============================================================================
-- esh_hastaliklar → platform kataloğu (kurum_id=0) + benzersiz ICD indeksi
-- Mevcut satırlar kurum_id=0 yapılır; id ve icd korunur.
-- Elle: php tools/run_sql_migration.php database/migrate_esh_hastaliklar_platform_catalog.sql
-- =============================================================================

SET NAMES utf8mb4;

UPDATE `esh_hastaliklar` SET `kurum_id` = 0 WHERE `kurum_id` IS NULL OR `kurum_id` <> 0;

ALTER TABLE `esh_hastaliklar`
  MODIFY COLUMN `kurum_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=platform kataloğu';

-- Yinelenen (kurum_id, icd) varsa önce manuel temizlik gerekir
ALTER TABLE `esh_hastaliklar`
  ADD UNIQUE KEY `uk_hastaliklar_kurum_icd` (`kurum_id`, `icd`);

ALTER TABLE `esh_hastaliklar`
  ADD KEY `idx_hastaliklar_icd` (`icd`);
