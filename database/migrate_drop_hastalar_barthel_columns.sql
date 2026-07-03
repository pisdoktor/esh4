-- migrate_barthel_from_hastalar.sql çalıştırıldıktan sonra uygulanır.
ALTER TABLE `esh_hastalar`
  DROP COLUMN `barbeslenme`,
  DROP COLUMN `barbanyo`,
  DROP COLUMN `barbakim`,
  DROP COLUMN `bargiyinme`,
  DROP COLUMN `barbarsak`,
  DROP COLUMN `barmesane`,
  DROP COLUMN `bartuvalet`,
  DROP COLUMN `bartransfer`,
  DROP COLUMN `barmobilite`,
  DROP COLUMN `barmerdiven`;
