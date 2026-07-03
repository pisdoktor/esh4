-- e-Nabız / USBS uyum hazırlığı — bildirim referansları ve e-Reçete köprüsü

ALTER TABLE `esh_hastalar`
  ADD COLUMN `enabiz_hasta_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'e-Nabız hasta kayıt no' AFTER `esys_basvuru_ref`,
  ADD COLUMN `usbs_hasta_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'USBS hasta / dosya no' AFTER `enabiz_hasta_ref`;

ALTER TABLE `esh_izlemler`
  ADD COLUMN `usbs_bildirim_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'USBS izlem bildirim no' AFTER `esys_konsultasyon_ref`,
  ADD COLUMN `usbs_bildirim_durum` VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'pending,sent,failed,skipped' AFTER `usbs_bildirim_ref`,
  ADD COLUMN `usbs_bildirim_at` DATETIME NULL DEFAULT NULL COMMENT 'Bildirim gönderim / onay zamanı' AFTER `usbs_bildirim_durum`,
  ADD COLUMN `erecete_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'e-Reçete / Medula reçete no' AFTER `usbs_bildirim_at`;

ALTER TABLE `esh_hastalar` ADD KEY `idx_hastalar_enabiz_ref` (`enabiz_hasta_ref`);
ALTER TABLE `esh_hastalar` ADD KEY `idx_hastalar_usbs_hasta_ref` (`usbs_hasta_ref`);
ALTER TABLE `esh_izlemler` ADD KEY `idx_izlemler_usbs_bildirim_ref` (`usbs_bildirim_ref`);
ALTER TABLE `esh_izlemler` ADD KEY `idx_izlemler_usbs_bildirim_durum` (`usbs_bildirim_durum`);
