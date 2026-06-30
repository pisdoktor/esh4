-- Stok modülü genişletme: tedarikçi, uyarı logu, parti (SKT/lot)

ALTER TABLE `esh_stok_malzeme`
  ADD COLUMN `tedarikci_adi` VARCHAR(255) NULL DEFAULT NULL AFTER `aciklama`,
  ADD COLUMN `tedarikci_tel` VARCHAR(32) NULL DEFAULT NULL AFTER `tedarikci_adi`,
  ADD COLUMN `birim_fiyat` DECIMAL(12,2) NULL DEFAULT NULL AFTER `tedarikci_tel`;

CREATE TABLE IF NOT EXISTS `esh_stok_uyari_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `uyari_tarihi` DATE NOT NULL,
  `sms_gonderildi` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stok_uyari_gun` (`kurum_id`, `malzeme_id`, `uyari_tarihi`),
  KEY `idx_stok_uyari_kurum` (`kurum_id`, `uyari_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_parti` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `lot_no` VARCHAR(64) NULL DEFAULT NULL,
  `skt` DATE NULL DEFAULT NULL,
  `miktar` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_parti_malzeme` (`kurum_id`, `malzeme_id`, `skt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
