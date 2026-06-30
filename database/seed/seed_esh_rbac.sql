-- =============================================================================
-- ESH kurulum seed — RBAC (roller, izinler, şablon rol izinleri)
-- Tablolar: #__roles, #__permissions, #__role_permissions
-- Kurulum: schema.sql sonrası Installer otomatik çalıştırır.
-- Mevcut kurulum: database/patch_rbac.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT IGNORE INTO `#__roles` (`id`, `slug`, `unvan_code`, `name`, `description`, `is_system`, `sort_order`) VALUES
(1, 'personel', NULL, 'Personel', 'Varsayılan personel — ünvan eşleşmesi yoksa', 1, 10),
(7, 'doktor', 'doktor', 'Doktor', 'Klinik personel — hasta/izlem/randevu tam erişim', 1, 15),
(2, 'hemsire', 'hemsire', 'Hemşire', 'Hasta/ziyaret okuma ve güncelleme', 1, 20),
(3, 'tekniker', 'tekniker', 'Tekniker', 'Pansuman ve ziyaret odaklı kısıtlı erişim', 1, 30),
(4, 'eczaci', 'eczaci', 'Eczacı', 'İlaç modülleri tam; hasta salt okuma', 1, 40),
(5, 'istatistik_goruntuleyici', NULL, 'İstatistik görüntüleyici', 'İstatistik okuma ve dışa aktarma (manuel atama)', 1, 50),
(6, 'salt_okuma', NULL, 'Salt okuma', 'Core modüllerde yalnızca okuma (manuel atama)', 1, 60);

INSERT IGNORE INTO `#__permissions` (`id`, `module_key`, `crud`, `slug`, `label`) VALUES
(1, 'dashboard', 'read', 'dashboard.read', 'Ana panel — okuma'),
(2, 'patient', 'read', 'patient.read', 'Hasta — okuma'),
(3, 'patient', 'create', 'patient.create', 'Hasta — oluşturma'),
(4, 'patient', 'update', 'patient.update', 'Hasta — güncelleme'),
(5, 'patient', 'delete', 'patient.delete', 'Hasta — silme/pasifleştirme'),
(6, 'visit', 'read', 'visit.read', 'İzlem — okuma'),
(7, 'visit', 'create', 'visit.create', 'İzlem — oluşturma'),
(8, 'visit', 'update', 'visit.update', 'İzlem — güncelleme'),
(9, 'visit', 'delete', 'visit.delete', 'İzlem — silme'),
(10, 'planned_visit', 'read', 'planned_visit.read', 'Planlı ziyaret — okuma'),
(11, 'planned_visit', 'create', 'planned_visit.create', 'Planlı ziyaret — oluşturma'),
(12, 'planned_visit', 'update', 'planned_visit.update', 'Planlı ziyaret — güncelleme'),
(13, 'planned_visit', 'delete', 'planned_visit.delete', 'Planlı ziyaret — silme'),
(14, 'pansuman', 'read', 'pansuman.read', 'Pansuman — okuma'),
(15, 'pansuman', 'update', 'pansuman.update', 'Pansuman — güncelleme'),
(16, 'planning', 'read', 'planning.read', 'Günlük planlama — okuma'),
(17, 'planning', 'update', 'planning.update', 'Günlük planlama — güncelleme'),
(18, 'stats', 'read', 'stats.read', 'İstatistik — okuma'),
(19, 'stats', 'export', 'stats.export', 'İstatistik — dışa aktarma'),
(20, 'user', 'read', 'user.read', 'Profil — okuma'),
(21, 'user', 'update', 'user.update', 'Profil — güncelleme'),
(22, 'erapor', 'read', 'erapor.read', 'e-Rapor — okuma'),
(23, 'erapor', 'create', 'erapor.create', 'e-Rapor — oluşturma'),
(24, 'erapor', 'update', 'erapor.update', 'e-Rapor — güncelleme'),
(25, 'erapor', 'delete', 'erapor.delete', 'e-Rapor — silme'),
(26, 'randevu', 'read', 'randevu.read', 'Randevu — okuma'),
(27, 'randevu', 'create', 'randevu.create', 'Randevu — oluşturma'),
(28, 'randevu', 'update', 'randevu.update', 'Randevu — güncelleme'),
(29, 'randevu', 'delete', 'randevu.delete', 'Randevu — silme'),
(30, 'uhds', 'read', 'uhds.read', 'Uhds — okuma'),
(31, 'uhds', 'create', 'uhds.create', 'Uhds — oluşturma'),
(32, 'uhds', 'update', 'uhds.update', 'Uhds — güncelleme'),
(33, 'uhds', 'delete', 'uhds.delete', 'Uhds — silme'),
(34, 'hasta_ilac_rapor', 'read', 'hasta_ilac_rapor.read', 'İlaç/tanı raporu — okuma'),
(35, 'hasta_ilac_rapor', 'create', 'hasta_ilac_rapor.create', 'İlaç/tanı raporu — oluşturma'),
(36, 'hasta_ilac_rapor', 'update', 'hasta_ilac_rapor.update', 'İlaç/tanı raporu — güncelleme'),
(37, 'hasta_ilac_rapor', 'delete', 'hasta_ilac_rapor.delete', 'İlaç/tanı raporu — silme'),
(38, 'ilac_rehber', 'read', 'ilac_rehber.read', 'İlaç rehberi — okuma'),
(39, 'mesajlasma', 'read', 'mesajlasma.read', 'Mesajlaşma — okuma'),
(40, 'mesajlasma', 'create', 'mesajlasma.create', 'Mesajlaşma — gönderme'),
(41, 'archive', 'read', 'archive.read', 'Arşiv — okuma'),
(42, 'archive', 'update', 'archive.update', 'Arşiv — geri yükleme'),
(43, 'archive', 'delete', 'archive.delete', 'Arşiv — kalıcı silme'),
(44, 'ekip', 'read', 'ekip.read', 'Ekip — okuma'),
(45, 'ekip', 'create', 'ekip.create', 'Ekip — oluşturma'),
(46, 'ekip', 'update', 'ekip.update', 'Ekip — güncelleme'),
(47, 'ekip', 'delete', 'ekip.delete', 'Ekip — silme'),
(48, 'nobet', 'read', 'nobet.read', 'Nöbet — okuma'),
(49, 'nobet', 'create', 'nobet.create', 'Nöbet — oluşturma'),
(50, 'nobet', 'update', 'nobet.update', 'Nöbet — güncelleme'),
(51, 'nobet', 'delete', 'nobet.delete', 'Nöbet — silme'),
(52, 'sms_bildirim', 'read', 'sms_bildirim.read', 'SMS bildirimleri — okuma'),
(53, 'sms_bildirim', 'create', 'sms_bildirim.create', 'SMS bildirimleri — gönderme'),
(54, 'sms_bildirim', 'admin', 'sms_bildirim.admin', 'SMS bildirimleri — şablon yönetimi'),
(55, 'stok', 'read', 'stok.read', 'Stok takibi — okuma'),
(56, 'stok', 'create', 'stok.create', 'Stok takibi — çıkış / iade'),
(57, 'stok', 'admin', 'stok.admin', 'Stok takibi — malzeme kartı ve giriş');

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `#__permissions` p
WHERE p.slug NOT LIKE 'planning.%'
  AND p.slug NOT LIKE 'pansuman.%'
  AND p.slug NOT LIKE 'stats.%'
  AND p.slug NOT LIKE 'sms_bildirim.%';

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 7, p.id FROM `#__permissions` p
WHERE p.slug NOT LIKE 'planning.%'
  AND p.slug NOT LIKE 'pansuman.%'
  AND p.slug NOT LIKE 'stats.%'
  AND p.slug NOT LIKE 'sms_bildirim.%';

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 2, p.id FROM `#__permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'patient.update', 'visit.read', 'visit.create', 'visit.update',
  'planned_visit.read', 'planned_visit.create', 'planned_visit.update',
  'pansuman.read', 'pansuman.update', 'stats.read', 'user.read', 'user.update',
  'erapor.read', 'erapor.create', 'erapor.update', 'randevu.read', 'randevu.update',
  'uhds.read', 'uhds.update', 'hasta_ilac_rapor.read', 'ilac_rehber.read',
  'mesajlasma.read', 'mesajlasma.create', 'nobet.read'
);

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 3, p.id FROM `#__permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'visit.read', 'visit.create', 'visit.update',
  'planned_visit.read', 'pansuman.read', 'pansuman.update', 'user.read', 'user.update',
  'ilac_rehber.read', 'mesajlasma.read', 'mesajlasma.create'
);

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 4, p.id FROM `#__permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'user.read', 'user.update',
  'hasta_ilac_rapor.read', 'hasta_ilac_rapor.create', 'hasta_ilac_rapor.update', 'hasta_ilac_rapor.delete',
  'ilac_rehber.read', 'mesajlasma.read', 'mesajlasma.create'
);

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 5, p.id FROM `#__permissions` p
WHERE p.slug IN (
  'dashboard.read', 'stats.read', 'stats.export', 'user.read', 'user.update', 'mesajlasma.read'
);

INSERT IGNORE INTO `#__role_permissions` (`role_id`, `permission_id`)
SELECT 6, p.id FROM `#__permissions` p
WHERE p.crud = 'read';

SET FOREIGN_KEY_CHECKS = 1;
