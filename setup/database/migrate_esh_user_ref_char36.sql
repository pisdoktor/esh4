-- Kullanıcı referans sütunları: INT → CHAR(36) (UUID / esh_users.id)
-- Mevcut kurulum: mysql --protocol=TCP -h 127.0.0.1 -u root esh4 < database/migrate_esh_user_ref_char36.sql

SET NAMES utf8mb4;

ALTER TABLE `esh_hasta_braden`
  MODIFY COLUMN `kaydeden_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `esh_hasta_barthel`
  MODIFY COLUMN `kaydeden_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `esh_hasta_harizmi`
  MODIFY COLUMN `kaydeden_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `esh_hasta_itaki`
  MODIFY COLUMN `kaydeden_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `esh_hasta_mna`
  MODIFY COLUMN `kaydeden_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `esh_hasta_yara_fotolar`
  MODIFY COLUMN `yukleyen_id` CHAR(36) NULL DEFAULT NULL;
