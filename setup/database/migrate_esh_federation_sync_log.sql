-- Federasyon dosya köprüsü — senkron günlüğü

CREATE TABLE IF NOT EXISTS `esh_federation_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `direction` VARCHAR(32) NOT NULL COMMENT 'node_snapshot, hub_to_node, node_to_hub',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `stats_json` TEXT NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fed_sync_created` (`created_at`),
  KEY `idx_fed_sync_direction` (`direction`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
