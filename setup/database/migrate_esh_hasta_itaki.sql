-- İTAKİ II düşme riski değerlendirme tarihçesi (18+ yaş hastalar)
CREATE TABLE IF NOT EXISTS `esh_hasta_itaki` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` INT UNSIGNED NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `degerlendirme_gerekcesi` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `secimler_json` TEXT NOT NULL,
  `toplam_skor` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `risk_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_itaki_hasta` (`hasta_id`),
  KEY `idx_itaki_tarih` (`degerlendirme_tarihi`),
  KEY `idx_itaki_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
