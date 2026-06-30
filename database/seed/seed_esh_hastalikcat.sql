-- =============================================================================
-- ESH kurulum seed — Hastalık kategorileri (ICD üst katman)
-- Tablo: `#__hastalikcat` (kaynak: `esh_hastalikcat`) | Satır: 21
-- Kurulum sırasında Installer otomatik çalıştırır.
-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/seed_esh_hastalikcat.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `#__hastalikcat` (`id`, `name`, `icd_range`) VALUES
('1', 'A-B Enfeksiyöz ve Parazit Hastalıkları', 'A00–B99'),
('2', 'C-D Kanserler', 'C00–D48'),
('3', 'D Kan ve Kan Yapıcı Organ Hastalıkları', 'D50–D89'),
('4', 'E Endokrin, Beslenme ve Metabolizma Hastalıkları', 'E00–E89'),
('5', 'F Zihinsel ve Davranışsal Bozukluklar', 'F00–F99'),
('6', 'G Sinir Sistemi Hastalıkları', 'G00–G99'),
('7', 'H Göz ve Adneks Hastalıkları', 'H00–H59'),
('8', 'H Kulak ve Mastoid Çıkıntı Hastalıkları', 'H60–H95'),
('9', 'I Dolaşım Sistemi Hastalıkları', 'I00–I99'),
('10', 'J Solunum Sistemi Hastalıkları', 'J00–J99'),
('11', 'K Sindirim Sistemi Hastalıkları', 'K00–K95'),
('12', 'L Deri ve Derialtı Doku Hastalıkları', 'L00–L99'),
('13', 'M Kas iskelet ve bağdoku Hastalıkları', 'M00–M99'),
('14', 'N Genitoüriner Sistem Hastalıkları', 'N00–N99'),
('15', 'O Gebelik, Doğum ve Lohusalık', 'O00–O99'),
('16', 'P Perinatal Dönem Sorunları', 'P00–P96'),
('17', 'Q Konjenital Malformasyonlar, Kromozom Anomalileri', 'Q00–Q99'),
('18', 'R Semptomlar, Anormal Klinik Bulgular', 'R00–R99'),
('19', 'S-T Yaralanmalar, Zehirlenmeler ve Dış Nedenli Sorunlar', 'S00–T98'),
('20', 'V-W-Y Morbidite ve Mortalitenin Dış Nedenleri', 'V01–Y98'),
('21', 'Z Sağlık Durumunu ve Sağlık Hizmetleriyle Teması Etkileyen Faktörler', 'Z00–Z99');

SET FOREIGN_KEY_CHECKS = 1;
