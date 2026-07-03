-- e-Nabız / USBS entegrasyon köprüsü — senkron günlüğü
CREATE TABLE IF NOT EXISTS `esh_usbs_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `direction` VARCHAR(24) NOT NULL COMMENT 'esh_to_usbs, usbs_to_esh, api_push',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `stats_json` TEXT NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usbs_sync_created` (`created_at`),
  KEY `idx_usbs_sync_kurum_created` (`kurum_id`, `created_at`),
  KEY `idx_usbs_sync_direction` (`direction`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
