-- KVKK / iç denetim — işlem günlüğü
CREATE TABLE IF NOT EXISTS `esh_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL COMMENT 'patient.view, visit.create, stats.export, ...',
  `entity_type` VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'patient, visit, stats, auth, ...',
  `entity_id` INT UNSIGNED NULL DEFAULT NULL,
  `entity_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'TC, rapor anahtarı vb.',
  `ip_address` VARCHAR(64) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `request_uri` VARCHAR(512) NULL DEFAULT NULL,
  `context_json` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user_created` (`user_id`, `created_at`),
  KEY `idx_audit_action_created` (`action`, `created_at`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_kurum_created` (`kurum_id`, `created_at`),
  KEY `idx_audit_entity_ref` (`entity_ref`, `created_at`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
