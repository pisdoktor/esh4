-- =============================================================================
-- Migrasyon — esh_hastalikcat icd_range doldurma
-- Eski kurulumlarda esh_hastaliklar.cat hizalaması için:
--   php tools/remap_hastalik_cat.php --all
-- (cat-11 SQL kullanılmaz; 12–21 aralığındaki doğru değerleri bozar.)
-- =============================================================================

SET NAMES utf8mb4;

UPDATE `esh_hastalikcat` SET `icd_range` = 'A00–B99' WHERE `id` = 1;
UPDATE `esh_hastalikcat` SET `icd_range` = 'C00–D48' WHERE `id` = 2;
UPDATE `esh_hastalikcat` SET `icd_range` = 'D50–D89' WHERE `id` = 3;
UPDATE `esh_hastalikcat` SET `icd_range` = 'E00–E89' WHERE `id` = 4;
UPDATE `esh_hastalikcat` SET `icd_range` = 'F00–F99' WHERE `id` = 5;
UPDATE `esh_hastalikcat` SET `icd_range` = 'G00–G99' WHERE `id` = 6;
UPDATE `esh_hastalikcat` SET `icd_range` = 'H00–H59' WHERE `id` = 7;
UPDATE `esh_hastalikcat` SET `icd_range` = 'H60–H95' WHERE `id` = 8;
UPDATE `esh_hastalikcat` SET `icd_range` = 'I00–I99' WHERE `id` = 9;
UPDATE `esh_hastalikcat` SET `icd_range` = 'J00–J99' WHERE `id` = 10;
UPDATE `esh_hastalikcat` SET `icd_range` = 'K00–K95' WHERE `id` = 11;
UPDATE `esh_hastalikcat` SET `icd_range` = 'L00–L99' WHERE `id` = 12;
UPDATE `esh_hastalikcat` SET `icd_range` = 'M00–M99' WHERE `id` = 13;
UPDATE `esh_hastalikcat` SET `icd_range` = 'N00–N99' WHERE `id` = 14;
UPDATE `esh_hastalikcat` SET `icd_range` = 'O00–O99' WHERE `id` = 15;
UPDATE `esh_hastalikcat` SET `icd_range` = 'P00–P96' WHERE `id` = 16;
UPDATE `esh_hastalikcat` SET `icd_range` = 'Q00–Q99' WHERE `id` = 17;
UPDATE `esh_hastalikcat` SET `icd_range` = 'R00–R99' WHERE `id` = 18;
UPDATE `esh_hastalikcat` SET `icd_range` = 'S00–T98' WHERE `id` = 19;
UPDATE `esh_hastalikcat` SET `icd_range` = 'V01–Y98' WHERE `id` = 20;
UPDATE `esh_hastalikcat` SET `icd_range` = 'Z00–Z99' WHERE `id` = 21;
