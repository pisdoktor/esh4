-- Koordinat kaynağı artık yalnızca esh_adrestablosu (tip=kapino).
-- Önce: database/migrate_hasta_coords_to_kapino.sql (legacy hasta.coords varsa).
ALTER TABLE `esh_hastalar` DROP COLUMN `coords`;
