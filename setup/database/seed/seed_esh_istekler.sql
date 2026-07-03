-- =============================================================================
-- ESH kurulum seed — Konsültasyon istek türleri
-- Tablo: `#__istekler` (kaynak: `esh_istekler`) | Satır: 30
-- Kurulum sırasında Installer otomatik çalıştırır.
-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/seed_esh_istekler.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `#__istekler` (`id`, `kurum_id`, `istek_adi`) VALUES
('1', '0', 'MAMA RAPORU ÇIKARMA'),
('2', '0', 'MAMA RAPORU YENİLEME'),
('3', '0', 'İLAÇ RAPORU YENİLEME'),
('4', '0', 'İLAÇ RAPORU ÇIKARTMA'),
('5', '0', 'TIBBİ MALZEME/CİHAZ RAPORU YENİLEME'),
('6', '0', 'BEZ RAPORU ÇIKARTMA'),
('7', '0', 'BEZ RAPORU YENİLEME'),
('8', '0', 'TAHLİL SONUCU DEĞERLENDİRME'),
('9', '0', 'İLAÇ YAZDIRMA'),
('10', '0', 'SAĞLIK KURULU RAPORU ÇIKARTMA'),
('11', '0', 'SAĞLIK KURULU RAPORU YENİLEME'),
('12', '0', 'TIBBİ MALZEME/CİHAZ RAPORU ÇIKARTMA'),
('13', '0', 'ORTEZ/PROTEZ RAPORU ÇIKARTMA'),
('14', '0', 'ORTEZ/PROTEZ RAPORU YENİLEME'),
('15', '0', 'ENGELLİLİK ORAN RAPORU DEĞERLENDİRMESİ'),
('16', '0', 'TANI KOYMA / TANININ NETLEŞTİRİLMESİ'),
('17', '0', 'TEDAVİ PLANI BELİRLEME'),
('18', '0', 'TEDAVİ PLANI REVİZE / GÜNCELLEME'),
('19', '0', 'YENİDEN TIBBİ DEĞERLENDİRME'),
('20', '0', 'KLİNİK DURUM DEĞİŞİKLİĞİ DEĞERLENDİRMESİ'),
('21', '0', 'PALYATİF BAKIM DEĞERLENDİRMESİ'),
('22', '0', 'GÖRÜNTÜLEME SONUCU DEĞERLENDİRME'),
('23', '0', 'İLERİ TETKİK PLANLAMASI'),
('24', '0', 'BASI YARASI / YARA BAKIMI DEĞERLENDİRMESİ'),
('25', '0', 'PANSUMAN VE YARA BAKIM PLANI'),
('26', '0', 'ENTERAL/PARENTERAL BESLENME DEĞERLENDİRMESİ'),
('27', '0', 'TABURCU SONRASI EVDE BAKIM PLANLAMASI'),
('28', '0', 'EVDE BAKIM PLANI REVİZİONU'),
('29', '0', 'REÇETE DÜZENLEME / İLAÇ DEĞİŞİKLİĞİ DEĞERLENDİRMESİ'),
('30', '0', 'HASTANE SEVKİ ÖNCESİ DEĞERLENDİRME');

SET FOREIGN_KEY_CHECKS = 1;
