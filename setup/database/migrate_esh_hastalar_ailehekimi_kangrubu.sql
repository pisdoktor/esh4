-- Hasta kimlik: aile hekimi ve kan grubu

ALTER TABLE `esh_hastalar`
  ADD COLUMN `ailehekimi` VARCHAR(128) DEFAULT NULL AFTER `yupasno`,
  ADD COLUMN `kangrubu` VARCHAR(8) DEFAULT NULL AFTER `ailehekimi`;
