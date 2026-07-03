-- Platform sahibi (isadmin=3) katmanı — mevcut kurulumlarda en eski süper yönetici hesabını yükseltir.
-- Yeni kurulumlar Installer ile doğrudan isadmin=3 ile başlar.

UPDATE `#__users`
SET `isadmin` = 3
WHERE `id` = (
    SELECT `min_id` FROM (
        SELECT MIN(`id`) AS `min_id` FROM `#__users` WHERE `isadmin` = 2
    ) AS `_esh_po_promote`
)
AND EXISTS (SELECT 1 FROM `#__users` WHERE `isadmin` = 2 LIMIT 1);
