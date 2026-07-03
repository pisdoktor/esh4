-- İzlem GPS doğruluk alanı (saha mobil Faz 2)

ALTER TABLE `esh_izlemler`
  ADD COLUMN `checkin_accuracy` DECIMAL(8,1) NULL DEFAULT NULL AFTER `checkin_at`;
