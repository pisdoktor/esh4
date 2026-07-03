-- REST API v1 — bearer token kimlik doğrulama

CREATE TABLE IF NOT EXISTS `esh_api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `label` VARCHAR(128) NOT NULL DEFAULT '',
  `token_prefix` VARCHAR(16) NOT NULL COMMENT 'esh_live_ + ilk 8 hex — arama',
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256(token + pepper)',
  `scopes` VARCHAR(255) NOT NULL DEFAULT 'read' COMMENT 'read veya patients,visits,plans',
  `expires_at` DATETIME NULL DEFAULT NULL,
  `last_used_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_token_prefix` (`token_prefix`),
  KEY `idx_api_token_user` (`user_id`),
  KEY `idx_api_token_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
