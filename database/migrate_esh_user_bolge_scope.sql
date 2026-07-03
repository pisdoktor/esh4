-- Süper yönetici bölge kapsamı (sistem sahibi atar; NULL = tüm bölgeler)
ALTER TABLE `#__users`
  ADD COLUMN `bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Süper yönetici federasyon bölge kapsamı' AFTER `kurum_id`;

ALTER TABLE `#__users` ADD KEY `idx_users_bolge` (`bolge_id`);
