-- Varsayılan SMS şablonları (platform — kurum_id NULL)
SET NAMES utf8mb4;

INSERT INTO `esh_sms_sablonlari` (`kurum_id`, `kod`, `baslik`, `govde`, `degiskenler_json`, `aktif`)
SELECT NULL, 'ziyaret_hatirlatma', 'Ziyaret hatırlatması',
  'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için {{tarih}} {{zaman_dilimi}} evde sağlık ziyareti planlanmıştır. {{kurum_adi}}',
  '["hasta_ad_soyad","bakimveren_ad","tarih","zaman_dilimi","kurum_adi"]', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_sms_sablonlari` WHERE `kurum_id` IS NULL AND `kod` = 'ziyaret_hatirlatma' LIMIT 1);

INSERT INTO `esh_sms_sablonlari` (`kurum_id`, `kod`, `baslik`, `govde`, `degiskenler_json`, `aktif`)
SELECT NULL, 'pansuman_gunu', 'Pansuman günü',
  'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için bugün pansuman günüdür. {{kurum_adi}} Evde Sağlık',
  '["hasta_ad_soyad","bakimveren_ad","kurum_adi"]', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_sms_sablonlari` WHERE `kurum_id` IS NULL AND `kod` = 'pansuman_gunu' LIMIT 1);

INSERT INTO `esh_sms_sablonlari` (`kurum_id`, `kod`, `baslik`, `govde`, `degiskenler_json`, `aktif`)
SELECT NULL, 'sonda_degisim', 'Sonda değişimi',
  'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için sonda değişimi {{sonda_tarih}} tarihinde planlanmıştır. {{kurum_adi}}',
  '["hasta_ad_soyad","bakimveren_ad","sonda_tarih","kurum_adi"]', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_sms_sablonlari` WHERE `kurum_id` IS NULL AND `kod` = 'sonda_degisim' LIMIT 1);

INSERT INTO `esh_sms_sablonlari` (`kurum_id`, `kod`, `baslik`, `govde`, `degiskenler_json`, `aktif`)
SELECT NULL, 'ilk_ziyaret', 'İlk ziyaret randevusu',
  'Sayın {{hasta_ad_soyad}}, {{tarih}} {{zaman_dilimi}} tarihinde evde sağlık ilk ziyaret randevunuz bulunmaktadır. {{kurum_adi}}',
  '["hasta_ad_soyad","tarih","zaman_dilimi","kurum_adi"]', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_sms_sablonlari` WHERE `kurum_id` IS NULL AND `kod` = 'ilk_ziyaret' LIMIT 1);

INSERT INTO `esh_sms_sablonlari` (`kurum_id`, `kod`, `baslik`, `govde`, `degiskenler_json`, `aktif`)
SELECT NULL, 'gunun_plani', 'Günün planı bilgilendirme',
  'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için {{tarih}} {{zaman_dilimi}} saatinde {{islem}} planlanmıştır. {{kurum_adi}}',
  '["hasta_ad_soyad","bakimveren_ad","tarih","zaman_dilimi","islem","kurum_adi"]', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `esh_sms_sablonlari` WHERE `kurum_id` IS NULL AND `kod` = 'gunun_plani' LIMIT 1);
