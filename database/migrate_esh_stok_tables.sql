-- Stok takip modülü tabloları

CREATE TABLE IF NOT EXISTS `esh_stok_malzeme` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `kod` VARCHAR(64) NULL DEFAULT NULL,
  `ad` VARCHAR(255) NOT NULL DEFAULT '',
  `kategori` ENUM('mama', 'bez', 'pansuman', 'yatak', 'sarf', 'cihaz', 'diger') NOT NULL DEFAULT 'sarf',
  `birim` ENUM('adet', 'kutu', 'paket', 'litre', 'kg') NOT NULL DEFAULT 'adet',
  `min_stok` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `aciklama` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_malzeme_kurum` (`kurum_id`, `aktif`),
  KEY `idx_stok_malzeme_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_mevcut` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `miktar` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kurum_id`, `malzeme_id`),
  KEY `idx_stok_mevcut_malzeme` (`malzeme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_hareket` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `hareket_tipi` ENUM('giris', 'cikis', 'iade') NOT NULL,
  `miktar` DECIMAL(12,3) NOT NULL,
  `hareket_tarihi` DATE NOT NULL,
  `hasta_id` INT UNSIGNED NULL DEFAULT NULL,
  `ekip_id` INT UNSIGNED NULL DEFAULT NULL,
  `kullanici_id` INT UNSIGNED NOT NULL,
  `aciklama` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_hareket_kurum_tarih` (`kurum_id`, `hareket_tarihi`),
  KEY `idx_stok_hareket_malzeme` (`malzeme_id`),
  KEY `idx_stok_hareket_hasta` (`hasta_id`),
  KEY `idx_stok_hareket_ekip` (`ekip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
