-- RBAC: roller, izinler, rol-izin ve kullanıcı-rol tabloları + seed
-- Uygulama: mysql -u KULLANICI -p VERİTABANI < database/patch_rbac.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `esh_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `unvan_code` VARCHAR(64) NULL DEFAULT NULL COMMENT 'esh_users.unvan ile otomatik eşleme',
  `name` VARCHAR(128) NOT NULL,
  `description` VARCHAR(512) NULL DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`),
  UNIQUE KEY `uk_roles_unvan_code` (`unvan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_key` VARCHAR(64) NOT NULL,
  `crud` VARCHAR(32) NOT NULL,
  `slug` VARCHAR(128) NOT NULL,
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(512) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_slug` (`slug`),
  KEY `idx_permissions_module` (`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `esh_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `esh_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `esh_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `esh_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Seed roller (INSERT IGNORE — tekrar çalıştırılabilir)
INSERT IGNORE INTO `esh_roles` (`id`, `slug`, `unvan_code`, `name`, `description`, `is_system`, `sort_order`) VALUES
(1, 'personel', NULL, 'Personel', 'Varsayılan personel — ünvan eşleşmesi yoksa', 1, 10),
(7, 'doktor', 'doktor', 'Doktor', 'Klinik personel — hasta/izlem/randevu tam erişim', 1, 15),
(2, 'hemsire', 'hemsire', 'Hemşire', 'Hasta/ziyaret okuma ve güncelleme', 1, 20),
(3, 'tekniker', 'tekniker', 'Tekniker', 'Pansuman ve ziyaret odaklı kısıtlı erişim', 1, 30),
(4, 'eczaci', 'eczaci', 'Eczacı', 'İlaç modülleri tam; hasta salt okuma', 1, 40),
(5, 'istatistik_goruntuleyici', NULL, 'İstatistik görüntüleyici', 'İstatistik okuma ve dışa aktarma (manuel atama)', 1, 50),
(6, 'salt_okuma', NULL, 'Salt okuma', 'Core modüllerde yalnızca okuma (manuel atama)', 1, 60);

-- Seed izinler (temel CRUD seti — PermissionService sync ile genişletilebilir)
INSERT IGNORE INTO `esh_permissions` (`id`, `module_key`, `crud`, `slug`, `label`) VALUES
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
(52, 'patient', 'admin', 'patient.admin', 'Hasta — yönetici işlemleri'),
(53, 'user', 'admin', 'user.admin', 'Kullanıcı — yönetim'),
(54, 'ilac_rehber', 'admin', 'ilac_rehber.admin', 'İlaç rehberi — yönetim'),
(55, 'ilac_rehber', 'superadmin', 'ilac_rehber.superadmin', 'İlaç rehberi — süper yönetici'),
(56, 'mesajlasma', 'admin', 'mesajlasma.admin', 'Mesajlaşma — duyuru (broadcast)'),
(57, 'mesajlasma', 'update', 'mesajlasma.update', 'Mesajlaşma — güncelleme'),
(58, 'mesajlasma', 'delete', 'mesajlasma.delete', 'Mesajlaşma — kalıcı silme');

-- Personel rolü: mevcut personel davranışı (planlama/pansuman/istatistik admin modülleri hariç)
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `esh_permissions` p
WHERE p.slug NOT LIKE 'planning.%'
  AND p.slug NOT LIKE 'pansuman.%'
  AND p.slug NOT LIKE 'stats.%';

-- Doktor (personel ile aynı klinik set)
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 7, p.id FROM `esh_permissions` p
WHERE p.slug NOT LIKE 'planning.%'
  AND p.slug NOT LIKE 'pansuman.%'
  AND p.slug NOT LIKE 'stats.%';

-- Hemşire
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 2, p.id FROM `esh_permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'patient.update', 'visit.read', 'visit.create', 'visit.update',
  'planned_visit.read', 'planned_visit.create', 'planned_visit.update',
  'pansuman.read', 'pansuman.update', 'stats.read', 'user.read', 'user.update',
  'erapor.read', 'erapor.create', 'erapor.update', 'randevu.read', 'randevu.update',
  'uhds.read', 'uhds.update', 'hasta_ilac_rapor.read', 'ilac_rehber.read',
  'mesajlasma.read', 'mesajlasma.create', 'nobet.read'
);

-- Tekniker
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 3, p.id FROM `esh_permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'visit.read', 'visit.create', 'visit.update',
  'planned_visit.read', 'pansuman.read', 'pansuman.update', 'user.read', 'user.update',
  'ilac_rehber.read', 'mesajlasma.read', 'mesajlasma.create'
);

-- Eczacı
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 4, p.id FROM `esh_permissions` p
WHERE p.slug IN (
  'dashboard.read', 'patient.read', 'user.read', 'user.update',
  'hasta_ilac_rapor.read', 'hasta_ilac_rapor.create', 'hasta_ilac_rapor.update', 'hasta_ilac_rapor.delete',
  'ilac_rehber.read', 'mesajlasma.read', 'mesajlasma.create'
);

-- İstatistik görüntüleyici
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 5, p.id FROM `esh_permissions` p
WHERE p.slug IN (
  'dashboard.read', 'stats.read', 'stats.export', 'user.read', 'user.update', 'mesajlasma.read'
);

-- Salt okuma
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT 6, p.id FROM `esh_permissions` p
WHERE p.crud = 'read';

-- Mevcut personel kullanıcılarına varsayılan rol (isadmin=0)
INSERT IGNORE INTO `esh_user_roles` (`user_id`, `role_id`)
SELECT u.id, 1 FROM `esh_users` u
WHERE u.isadmin = 0;
