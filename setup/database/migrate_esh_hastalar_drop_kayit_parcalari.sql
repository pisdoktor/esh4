-- esh_hastalar.kayityili / kayitay / kayitgun — legacy parçalı kayıt tarihi (kayittarihi kullanılıyor)

UPDATE `esh_hastalar`
SET `kayittarihi` = STR_TO_DATE(
    CONCAT(
        IFNULL(`kayityili`, 0), '-',
        LPAD(IFNULL(`kayitay`, 1), 2, '0'), '-',
        LPAD(IFNULL(NULLIF(`kayitgun`, 0), 1), 2, '0')
    ),
    '%Y-%m-%d'
)
WHERE (`kayittarihi` IS NULL OR `kayittarihi` = '' OR `kayittarihi` = '0000-00-00')
  AND `kayityili` IS NOT NULL AND `kayityili` > 0;

ALTER TABLE `esh_hastalar`
  DROP COLUMN `kayitgun`,
  DROP COLUMN `kayitay`,
  DROP COLUMN `kayityili`;
