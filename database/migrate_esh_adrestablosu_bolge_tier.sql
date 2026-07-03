-- Adres ağacı: bolge → ilce → mahalle → sokak → kapino (ilce artık kök değil)

INSERT INTO `#__adrestablosu` (`id`, `adi`, `ust_id`, `tip`, `has_coords`)
SELECT '00000000-0000-4000-a000-adrestanim001', 'Varsayılan Bölge', '0', 'bolge', 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `#__adrestablosu` WHERE `tip` = 'bolge' LIMIT 1
);

UPDATE `#__adrestablosu` SET `ust_id` = (
    SELECT `id` FROM (
        SELECT `id` FROM `#__adrestablosu` WHERE `tip` = 'bolge' ORDER BY `adi` ASC, `id` ASC LIMIT 1
    ) AS `_bolge_pick`
)
WHERE `tip` = 'ilce'
  AND (`ust_id` IS NULL OR `ust_id` = '0' OR TRIM(`ust_id`) = '');
