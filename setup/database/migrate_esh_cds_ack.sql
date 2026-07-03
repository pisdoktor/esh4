CREATE TABLE IF NOT EXISTS `esh_cds_ack` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hasta_id` INT UNSIGNED NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `alert_code` VARCHAR(80) NOT NULL,
  `ack_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `ack_note` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cds_ack_hasta` (`hasta_id`),
  KEY `idx_cds_ack_kurum` (`kurum_id`),
  KEY `idx_cds_ack_code` (`alert_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
