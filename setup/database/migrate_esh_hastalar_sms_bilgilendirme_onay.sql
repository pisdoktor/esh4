-- Hasta SMS bilgilendirme onayı (KVKK)

ALTER TABLE `esh_hastalar`
  ADD COLUMN `sms_bilgilendirme_onay` TINYINT(1) NOT NULL DEFAULT 1 AFTER `ailehekimitel`;
