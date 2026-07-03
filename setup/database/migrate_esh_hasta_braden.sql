-- Braden ölçeği değerlendirme tarihçesi (basiyarasi=1 hastalar)
CREATE TABLE IF NOT EXISTS `esh_hasta_braden` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` INT UNSIGNED NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `duyusal` TINYINT UNSIGNED NOT NULL,
  `nem` TINYINT UNSIGNED NOT NULL,
  `aktivite` TINYINT UNSIGNED NOT NULL,
  `hareket` TINYINT UNSIGNED NOT NULL,
  `beslenme` TINYINT UNSIGNED NOT NULL,
  `surtunme` TINYINT UNSIGNED NOT NULL,
  `toplam_skor` TINYINT UNSIGNED NOT NULL,
  `risk_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_braden_hasta` (`hasta_id`),
  KEY `idx_braden_tarih` (`degerlendirme_tarihi`),
  KEY `idx_braden_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
