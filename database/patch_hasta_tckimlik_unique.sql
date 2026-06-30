-- Tek hasta satırı: global TC unique (kurum_id + tckimlik kaldırılır)
-- Önce: php tools/migrate_hasta_single_tc.php --apply

ALTER TABLE `esh_hastalar` DROP INDEX `uk_kurum_tckimlik`;
ALTER TABLE `esh_hastalar` ADD UNIQUE KEY `uk_tckimlik` (`tckimlik`);
