-- Akıllı planlama 2.0 — araç kapasitesi ve ekip–araç bağlantısı
ALTER TABLE `esh_araclar`
  ADD COLUMN `kapasite` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Eşzamanlı hasta/ziyaret kapasitesi' AFTER `arac_bilgisi`;

ALTER TABLE `esh_ekipler`
  ADD COLUMN `arac_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'esh_araclar.id' AFTER `user_ids`,
  ADD KEY `idx_ekipler_arac` (`arac_id`);
