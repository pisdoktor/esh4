-- Bozuk kodlamalı SMS şablon metinlerini düzelt (UTF-8)
SET NAMES utf8mb4;

UPDATE `esh_sms_sablonlari` SET
  `baslik` = 'Ziyaret hatırlatması',
  `govde` = 'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için {{tarih}} {{zaman_dilimi}} evde sağlık ziyareti planlanmıştır. {{kurum_adi}}'
WHERE `kurum_id` IS NULL AND `kod` = 'ziyaret_hatirlatma';

UPDATE `esh_sms_sablonlari` SET
  `baslik` = 'Pansuman günü',
  `govde` = 'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için bugün pansuman günüdür. {{kurum_adi}} Evde Sağlık'
WHERE `kurum_id` IS NULL AND `kod` = 'pansuman_gunu';

UPDATE `esh_sms_sablonlari` SET
  `baslik` = 'Sonda değişimi',
  `govde` = 'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için sonda değişimi {{sonda_tarih}} tarihinde planlanmıştır. {{kurum_adi}}'
WHERE `kurum_id` IS NULL AND `kod` = 'sonda_degisim';

UPDATE `esh_sms_sablonlari` SET
  `baslik` = 'İlk ziyaret randevusu',
  `govde` = 'Sayın {{hasta_ad_soyad}}, {{tarih}} {{zaman_dilimi}} tarihinde evde sağlık ilk ziyaret randevunuz bulunmaktadır. {{kurum_adi}}'
WHERE `kurum_id` IS NULL AND `kod` = 'ilk_ziyaret';

UPDATE `esh_sms_sablonlari` SET
  `baslik` = 'Günün planı bilgilendirme',
  `govde` = 'Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için {{tarih}} {{zaman_dilimi}} saatinde {{islem}} planlanmıştır. {{kurum_adi}}'
WHERE `kurum_id` IS NULL AND `kod` = 'gunun_plani';
