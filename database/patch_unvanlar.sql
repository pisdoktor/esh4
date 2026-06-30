-- Personel ünvanları kataloğu + ünvan başına RBAC rolü
-- Uygulama: mysql -u KULLANICI -p VERİTABANI < database/patch_unvanlar.sql
-- Önkoşul: patch_rbac.sql uygulanmış olmalı (esh_roles, esh_permissions)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `esh_unvanlar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kod` VARCHAR(64) NOT NULL COMMENT 'esh_users.unvan ile eşleşir (snake_case)',
  `ad` VARCHAR(128) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL DEFAULT 'diger' COMMENT 'hekim, hemsirelik, teknik, multidisipliner, idari, diger',
  `izin_sablonu` VARCHAR(64) NOT NULL DEFAULT 'personel' COMMENT 'Yeni rol izinleri için şablon rol slug',
  `sort_order` INT NOT NULL DEFAULT 100,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `mevzuat_notu` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unvan_kod` (`kod`),
  KEY `idx_unvan_aktif_sort` (`aktif`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO `esh_unvanlar` (`kod`, `ad`, `kategori`, `izin_sablonu`, `sort_order`, `is_system`, `mevzuat_notu`) VALUES
('uzman_doktor', 'Uzman Doktor', 'hekim', 'doktor', 10, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği — uzman hekim'),
('doktor', 'Doktor', 'hekim', 'doktor', 15, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği — hekim'),
('dis_hekimi', 'Diş Hekimi', 'hekim', 'doktor', 18, 1, 'D tipi evde sağlık birimi'),
('hemsire', 'Hemşire', 'hemsirelik', 'hemsire', 20, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('ebe', 'Ebe', 'hemsirelik', 'hemsire', 25, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('saglik_memuru', 'Sağlık Memuru', 'hemsirelik', 'hemsire', 30, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('toplum_sagligi_teknisyeni', 'Toplum Sağlığı Teknisyeni', 'hemsirelik', 'hemsire', 35, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('tekniker', 'Tekniker', 'teknik', 'tekniker', 40, 1, 'Evde bakım / yaşlı bakım teknikeri (genel)'),
('evde_hasta_bakim_teknikeri', 'Evde Hasta Bakım Teknikeri', 'teknik', 'tekniker', 45, 1, 'T/H tip birim çekirdek kadro'),
('yasli_bakim_teknikeri', 'Yaşlı Bakım Teknikeri', 'teknik', 'tekniker', 50, 1, 'T/H tip birim çekirdek kadro'),
('agiz_dis_sagligi_teknikeri', 'Ağız ve Diş Sağlığı Teknikeri', 'teknik', 'tekniker', 55, 1, 'D tip birim'),
('dis_protez_teknikeri', 'Diş Protez Teknikeri', 'teknik', 'tekniker', 58, 1, 'D tip birim'),
('yardimci_saglik_personeli', 'Yardımcı Sağlık Personeli', 'teknik', 'tekniker', 60, 1, 'T/H tip birim'),
('eczaci', 'Eczacı', 'multidisipliner', 'eczaci', 70, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('gerontolog', 'Gerontolog', 'multidisipliner', 'personel', 80, 1, 'Multidisipliner ekip (kurumsal)'),
('psikolog', 'Psikolog', 'multidisipliner', 'personel', 85, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('sosyal_calismaci', 'Sosyal Çalışmacı', 'multidisipliner', 'personel', 90, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('fizyoterapist', 'Fizyoterapist', 'multidisipliner', 'personel', 95, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('diyetisyen', 'Diyetisyen', 'multidisipliner', 'personel', 100, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('tibbi_sekreter', 'Tıbbi Sekreter', 'idari', 'personel', 110, 1, 'Birim kayıt ve iletişim'),
('saglik_yoneticisi', 'Sağlık Yöneticisi', 'idari', 'personel', 115, 1, 'İdari koordinasyon'),
('sofor', 'Şoför', 'idari', 'personel', 120, 1, 'Evde sağlık aracı (sürücü yetkisi)'),
('diger', 'Diğer', 'diger', 'personel', 200, 1, 'Tanımsız / diğer personel');

-- Eski sekreter kodu → tıbbi sekreter
UPDATE `esh_users` SET `unvan` = 'tibbi_sekreter' WHERE `unvan` = 'sekreter';

-- Mevcut ünvan rollerinin adlarını güncelle
UPDATE `esh_roles` r
INNER JOIN `esh_unvanlar` u ON u.kod = r.unvan_code
SET r.name = u.ad,
    r.description = CONCAT('Ünvan rolü: ', u.ad),
    r.sort_order = u.sort_order
WHERE r.unvan_code IS NOT NULL AND TRIM(r.unvan_code) <> '';

-- Eksik ünvan rollerini oluştur (slug = kod, unvan_code = kod)
INSERT IGNORE INTO `esh_roles` (`slug`, `unvan_code`, `name`, `description`, `is_system`, `sort_order`)
SELECT u.kod, u.kod, u.ad, CONCAT('Ünvan rolü: ', u.ad), 1, u.sort_order
FROM `esh_unvanlar` u
LEFT JOIN `esh_roles` r ON r.unvan_code = u.kod
WHERE r.id IS NULL;

-- Yeni rollere izin şablonunu klonla (henüz izni olmayan roller)
INSERT IGNORE INTO `esh_role_permissions` (`role_id`, `permission_id`)
SELECT r_dest.id, rp.permission_id
FROM `esh_unvanlar` u
INNER JOIN `esh_roles` r_dest ON r_dest.unvan_code = u.kod
INNER JOIN `esh_roles` r_tpl ON r_tpl.slug = u.izin_sablonu
INNER JOIN `esh_role_permissions` rp ON rp.role_id = r_tpl.id
WHERE NOT EXISTS (
    SELECT 1 FROM `esh_role_permissions` x WHERE x.role_id = r_dest.id LIMIT 1
);

-- Personel kullanıcı rollerini ünvana göre güncelle
UPDATE `esh_user_roles` ur
INNER JOIN `esh_users` u ON u.id = ur.user_id
INNER JOIN `esh_roles` r ON r.unvan_code = u.unvan AND r.unvan_code IS NOT NULL AND TRIM(r.unvan_code) <> ''
SET ur.role_id = r.id
WHERE u.isadmin = 0
  AND u.unvan IS NOT NULL
  AND TRIM(u.unvan) <> '';

INSERT IGNORE INTO `esh_user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `esh_users` u
INNER JOIN `esh_roles` r ON r.unvan_code = u.unvan
WHERE u.isadmin = 0
  AND u.unvan IS NOT NULL
  AND TRIM(u.unvan) <> '';
