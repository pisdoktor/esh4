-- =============================================================================
-- ESH kurulum seed — Güvence / ödeme türleri
-- Tablo: `#__guvence` (kaynak: `esh_guvence`) | Satır: 21
-- Kaynak: 5510 sayılı Kanun GSS kişi kapsamı + evde sağlık hasta kayıt pratiği
-- Kurulum sırasında Installer otomatik çalıştırır.
-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/seed_esh_guvence.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `#__guvence` (`id`, `guvenceadi`) VALUES
('1', 'Emekli Sandığı'),
('2', 'Bağkur'),
('3', 'SSK'),
('4', 'Güvencesiz'),
('5', 'YUPAS'),
('6', 'Özel Sigorta'),
('7', 'Ücretli Hasta'),
('8', 'Genel Sağlık Sigortası'),
('9', '65 Yaş Üstü'),
('10', 'Yeşilkart'),
('11', 'SGK Emekli'),
('12', 'Bakmakla Yükümlü Kişi'),
('14', 'Vatansız'),
('15', 'Uluslararası Koruma (Sığınmacı)'),
('16', 'Geçici Koruma'),
('17', 'Mavi Kart'),
('18', 'Tamamlayıcı Sağlık Sigortası (TSS)'),
('19', 'Yabancı Uyruklu (GSS)'),
('20', 'Harp Malulü / Gazi / Şehit Yakını'),
('21', '2022 Aylığı (Muhtaç / ŞÖYEM)'),
('23', 'Hükümlü / Tutuklu');

SET FOREIGN_KEY_CHECKS = 1;
