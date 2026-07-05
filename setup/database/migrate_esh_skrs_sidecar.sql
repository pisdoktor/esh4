-- ESH SKRS sidecar — operasyonel veriye dokunmadan resmi kod eşlemesi
-- Uygulama: php tools/run_sql_migration.php database/migrate_esh_skrs_sidecar.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `esh_skrs_systems` (
  `slug` VARCHAR(32) NOT NULL COMMENT 'guvence, il, ilce, mahalle, csbm, kurum, unvan, meslek, cinsiyet, icd10',
  `system_uuid` CHAR(36) NOT NULL COMMENT 'SKRS Sistem Kodu (UUID)',
  `ad` VARCHAR(128) NOT NULL DEFAULT '',
  `last_sync_at` DATETIME NULL DEFAULT NULL COMMENT 'SKRS Excel/servis son çekim',
  `last_sync_source` VARCHAR(32) NULL DEFAULT NULL COMMENT 'excel, web_service, manual',
  `row_count` INT UNSIGNED NULL DEFAULT NULL,
  `notlar` VARCHAR(512) NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO `esh_skrs_systems` (`slug`, `system_uuid`, `ad`) VALUES
  ('cinsiyet', '784d0f4f-0603-4425-937f-1a3941fc3a1f', 'CİNSİYET'),
  ('csbm_tip', 'cf8e0be2-44fd-3db4-e040-7c0a021664e9', 'CSBM TİPİ'),
  ('adres_seviye', 'aa0e83ba-e9db-4817-80da-577fd6a17373', 'ADRES KODU SEVİYESİ'),
  ('icd10', 'c3eaabad-8c4c-56ee-e043-14031b0a5530', 'ICD10');

CREATE TABLE IF NOT EXISTS `esh_skrs_guvence_map` (
  `guvence_id` INT UNSIGNED NOT NULL COMMENT 'esh_guvence.id',
  `skrs_kod` VARCHAR(32) NOT NULL COMMENT 'SKRS SistemKodlari.Kod',
  `skrs_ad` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Denetim / rapor için SKRS adı',
  `system_slug` VARCHAR(32) NOT NULL DEFAULT 'guvence',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME NULL DEFAULT NULL COMMENT 'Manuel veya import doğrulama',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`guvence_id`),
  UNIQUE KEY `uk_skrs_guvence_kod` (`skrs_kod`),
  KEY `idx_skrs_guvence_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_skrs_adres_map` (
  `adres_id` VARCHAR(64) NOT NULL COMMENT 'esh_adrestablosu.id',
  `tip` VARCHAR(20) NOT NULL COMMENT 'il, ilce, mahalle, sokak, kapino',
  `skrs_kod` VARCHAR(64) NOT NULL COMMENT 'UAVT/SKRS adres kodu',
  `skrs_ad` VARCHAR(255) NULL DEFAULT NULL,
  `skrs_ust_kod` VARCHAR(64) NULL DEFAULT NULL COMMENT 'SKRS üst kod (hiyerarşi kontrolü)',
  `csbm_tip_kod` VARCHAR(16) NULL DEFAULT NULL COMMENT 'sokak satırı için CSBM TİPİ kodu',
  `system_slug` VARCHAR(32) NOT NULL DEFAULT 'mahalle',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`adres_id`),
  UNIQUE KEY `uk_skrs_adres_kod_tip` (`skrs_kod`, `tip`),
  KEY `idx_skrs_adres_tip` (`tip`, `aktif`),
  KEY `idx_skrs_adres_skrs_kod` (`skrs_kod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_skrs_kurum_map` (
  `kurum_id` INT UNSIGNED NOT NULL COMMENT 'esh_kurumlar.id',
  `ckys_kodu` VARCHAR(32) NOT NULL COMMENT 'Sağlık kurumu ÇKYS kodu (birincil)',
  `skrs_kurum_kod` VARCHAR(64) NULL DEFAULT NULL COMMENT 'SKRS kurum kodu (varsa)',
  `kurum_adi_resmi` VARCHAR(255) NULL DEFAULT NULL,
  `il_kodu` VARCHAR(8) NULL DEFAULT NULL,
  `ilce_kodu` VARCHAR(16) NULL DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kurum_id`),
  UNIQUE KEY `uk_skrs_ckys_kodu` (`ckys_kodu`),
  KEY `idx_skrs_kurum_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_skrs_birim_map` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL COMMENT 'esh_kurumlar.id',
  `esh_birim_kod` VARCHAR(64) NOT NULL DEFAULT 'evde_saglik' COMMENT 'ESH iç kod',
  `esh_birim_adi` VARCHAR(255) NOT NULL DEFAULT 'Evde Sağlık Hizmetleri',
  `ckys_birim_kodu` VARCHAR(32) NOT NULL COMMENT 'ÇKYS birim kodu',
  `skrs_birim_kod` VARCHAR(64) NULL DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_skrs_birim_kurum_esh` (`kurum_id`, `esh_birim_kod`),
  UNIQUE KEY `uk_skrs_ckys_birim` (`ckys_birim_kodu`),
  KEY `idx_skrs_birim_kurum` (`kurum_id`, `aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_skrs_unvan_map` (
  `unvan_kod` VARCHAR(64) NOT NULL COMMENT 'esh_unvanlar.kod',
  `skrs_unvan_kod` VARCHAR(32) NOT NULL,
  `skrs_unvan_ad` VARCHAR(128) NULL DEFAULT NULL,
  `skrs_meslek_kod` VARCHAR(32) NULL DEFAULT NULL,
  `skrs_meslek_ad` VARCHAR(128) NULL DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`unvan_kod`),
  KEY `idx_skrs_unvan_kod` (`skrs_unvan_kod`),
  KEY `idx_skrs_unvan_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_skrs_map_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `system_slug` VARCHAR(32) NOT NULL,
  `direction` VARCHAR(24) NOT NULL DEFAULT 'skrs_to_esh' COMMENT 'skrs_to_esh, manual',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `stats_json` TEXT NULL DEFAULT NULL,
  `source_file` VARCHAR(255) NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_skrs_map_sync_slug` (`system_slug`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

SET FOREIGN_KEY_CHECKS = 1;
