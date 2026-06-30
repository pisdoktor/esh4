-- Aile hekimi telefonu

ALTER TABLE `esh_hastalar`
  ADD COLUMN `ailehekimitel` VARCHAR(32) DEFAULT NULL AFTER `ailehekimi`;
