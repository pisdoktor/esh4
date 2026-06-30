-- =============================================================================
-- Kurum ↔ platform tanı ataması (#__kurum_hastalik)
-- Elle: php tools/run_sql_migration.php database/migrate_esh_kurum_hastalik_create.sql
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `esh_kurum_hastalik` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `hastalik_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`kurum_id`, `hastalik_id`),
  KEY `idx_kh_hastalik` (`hastalik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
