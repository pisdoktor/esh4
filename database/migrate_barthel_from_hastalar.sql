-- Mevcut esh_hastalar.bar* verilerini esh_hasta_barthel tablosuna taşır.
-- Önce migrate_esh_hasta_barthel.sql çalıştırılmalıdır.

INSERT INTO `esh_hasta_barthel` (
  `kurum_id`,
  `hasta_id`,
  `degerlendirme_tarihi`,
  `barbeslenme`,
  `barbanyo`,
  `barbakim`,
  `bargiyinme`,
  `barbarsak`,
  `barmesane`,
  `bartuvalet`,
  `bartransfer`,
  `barmobilite`,
  `barmerdiven`,
  `toplam_skor`,
  `bagimlilik_duzeyi`,
  `kaydeden_id`,
  `created_at`
)
SELECT
  h.`kurum_id`,
  h.`id`,
  COALESCE(NULLIF(h.`kayittarihi`, '0000-00-00'), CURDATE()),
  COALESCE(h.`barbeslenme`, 0),
  COALESCE(h.`barbanyo`, 0),
  COALESCE(h.`barbakim`, 0),
  COALESCE(h.`bargiyinme`, 0),
  COALESCE(h.`barbarsak`, 0),
  COALESCE(h.`barmesane`, 0),
  COALESCE(h.`bartuvalet`, 0),
  COALESCE(h.`bartransfer`, 0),
  COALESCE(h.`barmobilite`, 0),
  COALESCE(h.`barmerdiven`, 0),
  (
    COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
    + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
    + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
    + COALESCE(h.`barmerdiven`, 0)
  ) AS `toplam_skor`,
  CASE
    WHEN (
      COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
      + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
      + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
      + COALESCE(h.`barmerdiven`, 0)
    ) <= 20 THEN 'Tam Bağımlı'
    WHEN (
      COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
      + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
      + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
      + COALESCE(h.`barmerdiven`, 0)
    ) <= 60 THEN 'Ağır Bağımlı'
    WHEN (
      COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
      + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
      + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
      + COALESCE(h.`barmerdiven`, 0)
    ) <= 90 THEN 'Orta Bağımlı'
    WHEN (
      COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
      + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
      + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
      + COALESCE(h.`barmerdiven`, 0)
    ) <= 99 THEN 'Hafif Derecede Bağımlı'
    ELSE 'Bağımsız'
  END AS `bagimlilik_duzeyi`,
  NULL,
  NOW()
FROM `esh_hastalar` h
WHERE (
  COALESCE(h.`barbeslenme`, 0) + COALESCE(h.`barbanyo`, 0) + COALESCE(h.`barbakim`, 0)
  + COALESCE(h.`bargiyinme`, 0) + COALESCE(h.`barbarsak`, 0) + COALESCE(h.`barmesane`, 0)
  + COALESCE(h.`bartuvalet`, 0) + COALESCE(h.`bartransfer`, 0) + COALESCE(h.`barmobilite`, 0)
  + COALESCE(h.`barmerdiven`, 0)
) > 0
AND NOT EXISTS (
  SELECT 1 FROM `esh_hasta_barthel` b WHERE b.`hasta_id` = h.`id`
);
