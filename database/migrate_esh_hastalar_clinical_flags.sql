-- Hasta kartı klinik alanlar genişletmesi (bayraklar + iletişim/uyarı metinleri)

ALTER TABLE `esh_hastalar`
  ADD COLUMN `trakeostomi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `kolostomi`,
  ADD COLUMN `cpap` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trakeostomi`,
  ADD COLUMN `aspirasyon` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cpap`,
  ADD COLUMN `ileostomi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `aspirasyon`,
  ADD COLUMN `urostomi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ileostomi`,
  ADD COLUMN `picc` TINYINT(1) NOT NULL DEFAULT 0 AFTER `urostomi`,
  ADD COLUMN `dren` TINYINT(1) NOT NULL DEFAULT 0 AFTER `picc`,
  ADD COLUMN `diyaliz` TINYINT(1) NOT NULL DEFAULT 0 AFTER `dren`,
  ADD COLUMN `basiyarasi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `diyaliz`,
  ADD COLUMN `ivtedavi` TINYINT(1) NOT NULL DEFAULT 0 AFTER `basiyarasi`,
  ADD COLUMN `izolasyon` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ivtedavi`,
  ADD COLUMN `bakimveren_ad` VARCHAR(128) DEFAULT NULL AFTER `ceptel2`,
  ADD COLUMN `bakimveren_tel` VARCHAR(32) DEFAULT NULL AFTER `bakimveren_ad`,
  ADD COLUMN `bakimveren_yakinlik` VARCHAR(64) DEFAULT NULL AFTER `bakimveren_tel`,
  ADD COLUMN `alerji` TEXT NULL AFTER `bakimveren_yakinlik`,
  ADD COLUMN `acil_not` TEXT NULL AFTER `alerji`;
