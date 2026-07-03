-- İzlem kaydına isteğe bağlı saha GPS check-in alanları

ALTER TABLE `esh_izlemler`
  ADD COLUMN `checkin_lat` DECIMAL(10,7) NULL DEFAULT NULL AFTER `aciklama`,
  ADD COLUMN `checkin_lon` DECIMAL(10,7) NULL DEFAULT NULL AFTER `checkin_lat`,
  ADD COLUMN `checkin_at` DATETIME NULL DEFAULT NULL AFTER `checkin_lon`;
