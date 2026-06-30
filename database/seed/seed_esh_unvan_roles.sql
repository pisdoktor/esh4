-- =============================================================================
-- ESH kurulum seed — ünvan başına RBAC rolleri
-- Önkoşul: schema.sql (#__unvanlar) + seed_esh_rbac.sql
-- Tablolar: #__roles, #__role_permissions
-- Mevcut kurulum: database/patch_unvanlar.sql (veri kısmı)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

UPDATE `#__roles` r
INNER JOIN `#__unvanlar` u ON u.kod = r.unvan_code
SET r.name = u.ad,
    r.description = CONCAT('Ünvan rolü: ', u.ad),
    r.sort_order = u.sort_order
WHERE r.unvan_code IS NOT NULL AND TRIM(r.unvan_code) <> '';

INSERT IGNORE INTO `#__roles` (`slug`, `unvan_code`, `name`, `description`, `is_system`, `sort_order`)
SELECT u.kod, u.kod, u.ad, CONCAT('Ünvan rolü: ', u.ad), 1, u.sort_order
FROM `#__unvanlar` u
LEFT JOIN `#__roles` r ON r.unvan_code = u.kod
WHERE r.id IS NULL;

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT r_dest.id, rp.permission_id
FROM `#__unvanlar` u
INNER JOIN `#__roles` r_dest ON r_dest.unvan_code = u.kod
INNER JOIN `#__roles` r_tpl ON r_tpl.slug = u.izin_sablonu
INNER JOIN `#__role_permissions` rp ON rp.role_id = r_tpl.id
WHERE NOT EXISTS (
    SELECT 1 FROM `#__role_permissions` x WHERE x.role_id = r_dest.id LIMIT 1
);

SET FOREIGN_KEY_CHECKS = 1;
