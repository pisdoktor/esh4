-- RBAC: permission-crud-map.php ile hizalama (mevcut kurulumlar)
-- Uygulama: mysql --protocol=TCP -h 127.0.0.1 -u root esh4 < database/migrate_rbac_crud_map_align.sql
--
-- - Arşiv: restore/purge kaldırıldı → yalnızca archive.read
-- - Nöbet: nobet.admin (rebuild) eklendi
-- - SMS: yalnızca sms_bildirim.admin (read/create kaldırılır)
-- - Mesajlaşma: update/delete/admin (patch_rbac_permissions_extend ile uyumlu)

SET NAMES utf8mb4;

-- Arşiv: artık kullanılmayan izinler
DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug IN ('archive.update', 'archive.delete');

DELETE FROM `esh_permissions`
WHERE slug IN ('archive.update', 'archive.delete');

UPDATE `esh_permissions`
SET label = 'Arşiv — listeleme'
WHERE slug = 'archive.read';

-- Nöbet rebuild
INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('nobet', 'admin', 'nobet.admin', 'Nöbet — plan yeniden oluşturma');

-- Mesajlaşma (thread/poll/startDm hizası — extend patch ile çakışmaz)
INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('patient', 'admin', 'patient.admin', 'Hasta — yönetici işlemleri'),
('user', 'admin', 'user.admin', 'Kullanıcı — yönetim'),
('ilac_rehber', 'admin', 'ilac_rehber.admin', 'İlaç rehberi — yönetim'),
('ilac_rehber', 'superadmin', 'ilac_rehber.superadmin', 'İlaç rehberi — süper yönetici'),
('mesajlasma', 'admin', 'mesajlasma.admin', 'Mesajlaşma — duyuru (broadcast)'),
('mesajlasma', 'update', 'mesajlasma.update', 'Mesajlaşma — güncelleme'),
('mesajlasma', 'delete', 'mesajlasma.delete', 'Mesajlaşma — kalıcı silme');

-- SMS: crud map yalnızca admin bucket
DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug IN ('sms_bildirim.read', 'sms_bildirim.create');

DELETE FROM `esh_permissions`
WHERE slug IN ('sms_bildirim.read', 'sms_bildirim.create');

INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('sms_bildirim', 'admin', 'sms_bildirim.admin', 'SMS bildirimleri — yönetim');

-- Personel/doktor rollerinden nöbet rebuild ve SMS kalıntılarını temizle
DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug IN ('nobet.admin', 'sms_bildirim.read', 'sms_bildirim.create', 'sms_bildirim.admin')
  AND rp.role_id IN (1, 2, 3, 4, 5, 6, 7);

-- Hemşire/tekniker/eczacı: mesaj okundu/çöp kutusu için update
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `esh_roles` r
INNER JOIN `esh_permissions` p ON p.slug = 'mesajlasma.update'
WHERE r.slug IN ('hemsire', 'tekniker', 'eczaci');
