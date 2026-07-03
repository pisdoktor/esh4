-- UHDS görüntülü görüşme (Jitsi / telehealth) alanları
ALTER TABLE `esh_goruntulu_randevu`
  ADD COLUMN `video_room_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Jitsi oda adı' AFTER `hasta_geldi`,
  ADD COLUMN `video_started_at` DATETIME NULL DEFAULT NULL AFTER `video_room_id`,
  ADD COLUMN `video_ended_at` DATETIME NULL DEFAULT NULL AFTER `video_started_at`,
  ADD COLUMN `visit_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Görüşme sonrası bağlı izlem' AFTER `video_ended_at`,
  ADD COLUMN `telehealth_summary` TEXT NULL DEFAULT NULL COMMENT 'Görüşme notu / özet' AFTER `visit_id`,
  ADD KEY `idx_gor_video_room` (`video_room_id`),
  ADD KEY `idx_gor_visit` (`visit_id`);
