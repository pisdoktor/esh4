-- Çok bölgeli SaaS / federasyon — bölge tanımları ve kurum bağlantısı

CREATE TABLE IF NOT EXISTS `esh_federation_regions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kod` VARCHAR(64) NOT NULL,
  `ad` VARCHAR(255) NOT NULL,
  `il_adi` VARCHAR(64) NULL DEFAULT NULL,
  `hub_node_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Merkez hub düğüm referansı',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `aciklama` TEXT NULL DEFAULT NULL,
  `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fed_region_kod` (`kod`),
  KEY `idx_fed_region_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

ALTER TABLE `esh_kurumlar`
  ADD COLUMN `bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Federasyon bölgesi' AFTER `aktif`,
  ADD COLUMN `federation_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Uzak düğüm kurum referansı' AFTER `bolge_id`;

ALTER TABLE `esh_kurumlar` ADD KEY `idx_kurum_bolge` (`bolge_id`);
ALTER TABLE `esh_kurumlar` ADD KEY `idx_kurum_federation_ref` (`federation_ref`);

INSERT INTO `esh_federation_regions` (`kod`, `ad`, `il_adi`, `aktif`, `aciklama`)
SELECT 'varsayilan', 'Varsayılan bölge', NULL, 1, 'Kurulum sonrası otomatik oluşturulur'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_federation_regions` WHERE `kod` = 'varsayilan' LIMIT 1);

UPDATE `esh_kurumlar` SET `bolge_id` = (SELECT `id` FROM `esh_federation_regions` WHERE `kod` = 'varsayilan' LIMIT 1)
WHERE `bolge_id` IS NULL;
