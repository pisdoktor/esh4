-- Ünvan ↔ rol bağlantısı + doktor rolü
-- Uygulama: mysql -u KULLANICI -p VERİTABANI < database/patch_rbac_unvan_link.sql

SET NAMES utf8mb4;

-- unvan_code: esh_users.unvan ile eşleşen rol (NULL = yalnızca manuel atama)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'esh_roles'
      AND COLUMN_NAME = 'unvan_code'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `esh_roles` ADD COLUMN `unvan_code` VARCHAR(64) NULL DEFAULT NULL COMMENT ''esh_users.unvan ile otomatik eşleme'' AFTER `slug`, ADD UNIQUE KEY `uk_roles_unvan_code` (`unvan_code`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `esh_roles` SET `unvan_code` = NULL WHERE `slug` IN ('personel', 'istatistik_goruntuleyici', 'salt_okuma');
UPDATE `esh_roles` SET `unvan_code` = 'hemsire' WHERE `slug` = 'hemsire';
UPDATE `esh_roles` SET `unvan_code` = 'tekniker' WHERE `slug` = 'tekniker';
UPDATE `esh_roles` SET `unvan_code` = 'eczaci' WHERE `slug` = 'eczaci';

INSERT IGNORE INTO `esh_roles` (`id`, `slug`, `unvan_code`, `name`, `description`, `is_system`, `sort_order`) VALUES
(7, 'doktor', 'doktor', 'Doktor', 'Klinik personel — hasta/izlem/randevu tam erişim (planlama/pansuman/istatistik hariç)', 1, 15);

-- Doktor: personel ile aynı klinik set (planlama, pansuman, stats hariç)
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 7, p.id FROM `esh_permissions` p
WHERE p.slug NOT LIKE 'planning.%'
  AND p.slug NOT LIKE 'pansuman.%'
  AND p.slug NOT LIKE 'stats.%';

-- Mevcut personel kullanıcıları: ünvana göre rol güncelle (özel/manuel roller korunur)
UPDATE `esh_user_roles` ur
INNER JOIN `esh_users` u ON u.id = ur.user_id
INNER JOIN `esh_roles` r ON r.unvan_code = u.unvan AND r.unvan_code IS NOT NULL AND TRIM(r.unvan_code) <> ''
SET ur.role_id = r.id
WHERE u.isadmin = 0
  AND u.unvan IS NOT NULL
  AND TRIM(u.unvan) <> '';
