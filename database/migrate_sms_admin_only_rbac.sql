-- SMS modülü yalnızca kurum yöneticisi / süper yönetici (isadmin >= 1) kullanır.
-- Personel rollerinden SMS izinlerini kaldır.

DELETE rp FROM `esh_role_permissions` rp
INNER JOIN `esh_permissions` p ON p.id = rp.permission_id
WHERE p.slug LIKE 'sms_bildirim.%';
