-- RBAC: eksik admin/superadmin, mesajlaşma ve nöbet izin kayıtları
-- Uygulama: mysql -u KULLANICI -p VERİTABANI < database/patch_rbac_permissions_extend.sql
-- Not: seed_esh_rbac.sql ve migrate_rbac_crud_map_align.sql ile aynı slug seti

SET NAMES utf8mb4;

INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('patient', 'admin', 'patient.admin', 'Hasta — yönetici işlemleri'),
('user', 'admin', 'user.admin', 'Kullanıcı — yönetim'),
('ilac_rehber', 'admin', 'ilac_rehber.admin', 'İlaç rehberi — yönetim'),
('ilac_rehber', 'superadmin', 'ilac_rehber.superadmin', 'İlaç rehberi — süper yönetici'),
('mesajlasma', 'admin', 'mesajlasma.admin', 'Mesajlaşma — duyuru (broadcast)'),
('mesajlasma', 'update', 'mesajlasma.update', 'Mesajlaşma — güncelleme'),
('mesajlasma', 'delete', 'mesajlasma.delete', 'Mesajlaşma — kalıcı silme'),
('nobet', 'admin', 'nobet.admin', 'Nöbet — plan yeniden oluşturma');
