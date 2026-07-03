-- SMS bildirim modülü RBAC izinleri (yalnızca admin bucket — permission-crud-map.php)

INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('sms_bildirim', 'admin', 'sms_bildirim.admin', 'SMS bildirimleri — yönetim');

-- Eski kurulumlarda personel rollerine verilmiş SMS izinlerini temizle
DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug LIKE 'sms_bildirim.%';

-- Artık kullanılmayan read/create slug'ları
DELETE FROM `esh_permissions`
WHERE slug IN ('sms_bildirim.read', 'sms_bildirim.create');
