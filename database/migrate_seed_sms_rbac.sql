-- SMS bildirim modülü RBAC izinleri (yalnızca admin/superadmin oturumu kullanır; personel rollerine atanmaz)

INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('sms_bildirim', 'read', 'sms_bildirim.read', 'SMS bildirimleri — okuma'),
('sms_bildirim', 'create', 'sms_bildirim.create', 'SMS bildirimleri — gönderme'),
('sms_bildirim', 'admin', 'sms_bildirim.admin', 'SMS bildirimleri — şablon yönetimi');

-- Eski kurulumlarda personel rollerine verilmiş SMS izinlerini temizle
DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug LIKE 'sms_bildirim.%';
