-- =============================================================================
-- ESH kurulum seed — Evde sağlık işlem referans listesi
-- Tablo: `#__islemler` (kaynak: `esh_islemler`) | Satır: 42
-- Kurulum sırasında Installer otomatik çalıştırır.
-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/seed_esh_islemler.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `#__islemler` (`id`, `kurum_id`, `islemadi`) VALUES
('1', '0', 'HASTA MUAYENESİ (İLK ZİYARET)'),
('2', '0', 'KONSÜLTASYON'),
('3', '0', 'SAĞLIK KURULU RAPORU (TIBBİ CİHAZ)'),
('4', '0', 'SAĞLIK KURULU RAPORU (TEDAVİ)'),
('5', '0', 'MESANE SONDA ÇIKARMA'),
('6', '0', 'INTRAMÜSKÜLER İLAÇ ENJEKSİYONU'),
('7', '0', 'INTRAVENÖZ İLAÇ UYGULAMASI'),
('8', '0', 'MESANE SONDA DEĞİŞİMİ'),
('9', '0', 'NAZOGASTRİK SONDA UYGULAMASI'),
('10', '0', 'SUBKUTAN ENJEKSİYON'),
('11', '0', 'SÜTUR ALINMASI'),
('12', '0', 'TOTAL PARANTERAL NUTRİSYON TAKİBİ'),
('13', '0', 'YANIK PANSUMANI'),
('14', '0', 'YARA PANSUMANI'),
('15', '0', 'PSİKİYATRİK UYGULAMALAR'),
('16', '0', 'EĞİTİM UYGULAMALARI'),
('17', '0', 'TETKİK İÇİN KAN ALMA'),
('18', '0', 'HASTA NAKİL İŞLEMİ'),
('19', '0', 'HASTA MUAYENESİ (KONTROL)'),
('20', '0', 'PORT ÇIKARMA İŞLEMİ'),
('21', '0', 'REÇETE YAZILMASI'),
('22', '0', 'MESANE SONDA TAKILMASI'),
('23', '0', 'PORT TAKMA İŞLEMİ'),
('24', '0', 'E-RAPOR TALEBİ'),
('25', '0', 'E-RAPOR GÖRÜNTÜLÜ MUAYENE'),
('26', '0', 'İDRAR VE TİT NUMUNESİ'),
('27', '0', 'İLAÇ DANIŞMANLIĞI EĞİTİMİ'),
('28', '0', 'İLK ZİYARET'),
('29', '0', 'PEG BAKIMI'),
('30', '0', 'PEG SONDA DEĞİŞİMİ'),
('31', '0', 'TRAKEOSTOMİ BAKIMI'),
('32', '0', 'TRAKEOSTOMİ KANÜL DEĞİŞİMİ'),
('33', '0', 'SERUM UYGULAMASI'),
('34', '0', 'KOLOSTOMİ BAKIMI'),
('35', '0', 'STOMA BAKIMI'),
('36', '0', 'BASI YARASI PANSUMANI'),
('37', '0', 'OKSİJEN TEDAVİSİ TAKİBİ'),
('38', '0', 'MEKANİK VENTİLATÖR TAKİBİ'),
('39', '0', 'KAN ŞEKERİ ÖLÇÜMÜ'),
('40', '0', 'TANSİYON ÖLÇÜMÜ'),
('41', '0', 'LAVMAN UYGULAMASI'),
('42', '0', 'AŞI UYGULAMASI');

SET FOREIGN_KEY_CHECKS = 1;
