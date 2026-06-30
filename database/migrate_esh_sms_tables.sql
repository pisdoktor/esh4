-- SMS bildirim modülü tabloları

CREATE TABLE IF NOT EXISTS `esh_sms_sablonlari` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `kod` VARCHAR(64) NOT NULL DEFAULT '',
  `baslik` VARCHAR(255) NOT NULL DEFAULT '',
  `govde` VARCHAR(1600) NOT NULL DEFAULT '',
  `degiskenler_json` TEXT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_sablon_kurum` (`kurum_id`, `aktif`),
  KEY `idx_sms_sablon_kod` (`kod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_gonderim` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `olusturan_id` INT UNSIGNED NOT NULL,
  `segment_tipi` VARCHAR(32) NOT NULL DEFAULT 'tek_hasta',
  `segment_param_json` TEXT NULL,
  `sablon_id` INT UNSIGNED NULL DEFAULT NULL,
  `govde_ozet` VARCHAR(500) NOT NULL DEFAULT '',
  `mesaj_turu` ENUM('bilgilendirme', 'ticari') NOT NULL DEFAULT 'bilgilendirme',
  `durum` ENUM('taslak', 'beklemede', 'gonderiliyor', 'tamamlandi', 'hata') NOT NULL DEFAULT 'beklemede',
  `toplam` INT UNSIGNED NOT NULL DEFAULT 0,
  `basarili` INT UNSIGNED NOT NULL DEFAULT 0,
  `basarisiz` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_gonderim_kurum` (`kurum_id`, `created_at`),
  KEY `idx_sms_gonderim_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_alici` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gonderim_id` INT UNSIGNED NOT NULL,
  `hasta_id` INT UNSIGNED NULL DEFAULT NULL,
  `rol` ENUM('hasta', 'hasta2', 'bakimveren', 'ailehekimi') NOT NULL DEFAULT 'hasta',
  `telefon_norm` VARCHAR(16) NOT NULL DEFAULT '',
  `govde` VARCHAR(1600) NOT NULL DEFAULT '',
  `provider_msg_id` VARCHAR(64) NULL DEFAULT NULL,
  `durum` ENUM('beklemede', 'gonderildi', 'teslim', 'hata', 'atlandi') NOT NULL DEFAULT 'beklemede',
  `hata_kodu` VARCHAR(32) NULL DEFAULT NULL,
  `hata_mesaj` VARCHAR(255) NULL DEFAULT NULL,
  `gonderim_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sms_alici_gonderim` (`gonderim_id`),
  KEY `idx_sms_alici_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_optout` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telefon_norm` VARCHAR(16) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `kaynak` ENUM('manuel', 'sms_stop') NOT NULL DEFAULT 'manuel',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_optout_tel_kurum` (`telefon_norm`, `kurum_id`),
  KEY `idx_sms_optout_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
