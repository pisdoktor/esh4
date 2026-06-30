-- =============================================================================
-- İsteğe bağlı migrasyon — eksik güvence tanımlarını ekler; GSS tekrarlarını temizler.
-- id 13, 22, 24 → id 8 (Genel Sağlık Sigortası) ile aynı kapsam; kaldırılır.
-- Elle: mysql -u KULLANICI -p VERITABANI < database/migrate_seed_esh_guvence.sql
-- =============================================================================

SET NAMES utf8mb4;

UPDATE `esh_hastalar` SET `guvence` = 8 WHERE `guvence` IN (13, 22, 24);

DELETE FROM `esh_guvence` WHERE `id` IN (13, 22, 24);

INSERT IGNORE INTO `esh_guvence` (`id`, `guvenceadi`) VALUES
(11, 'SGK Emekli'),
(12, 'Bakmakla Yükümlü Kişi'),
(14, 'Vatansız'),
(15, 'Uluslararası Koruma (Sığınmacı)'),
(16, 'Geçici Koruma'),
(17, 'Mavi Kart'),
(18, 'Tamamlayıcı Sağlık Sigortası (TSS)'),
(19, 'Yabancı Uyruklu (GSS)'),
(20, 'Harp Malulü / Gazi / Şehit Yakını'),
(21, '2022 Aylığı (Muhtaç / ŞÖYEM)'),
(23, 'Hükümlü / Tutuklu');
