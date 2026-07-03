-- MNA-SF beslenme değerlendirme tarihçesi
CREATE TABLE IF NOT EXISTS `esh_hasta_mna` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` INT UNSIGNED NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `besin_alimi` TINYINT UNSIGNED NOT NULL,
  `kilo_kaybi` TINYINT UNSIGNED NOT NULL,
  `mobilite` TINYINT UNSIGNED NOT NULL,
  `stres_hastalik` TINYINT UNSIGNED NOT NULL,
  `noropsikolojik` TINYINT UNSIGNED NOT NULL,
  `bmi_olcum_tipi` VARCHAR(20) NOT NULL DEFAULT 'bmi',
  `bmi_skor` TINYINT UNSIGNED NOT NULL,
  `toplam_skor` TINYINT UNSIGNED NOT NULL,
  `durum_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mna_hasta` (`hasta_id`),
  KEY `idx_mna_tarih` (`degerlendirme_tarihi`),
  KEY `idx_mna_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
