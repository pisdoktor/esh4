E-imza trust store kullanimi
==========================

1) Bu klasore guvenilen kok/ara sertifika zinciri dosyasini koyun:
   - Dosya: eimza_trust_store.pem
   - Icerik: PEM formatinda bir veya birden cok CA sertifikasi

2) config.local.php icinde e-imza girisini acin:
   'eimza_login_enabled' => true,
   'eimza_trust_store_path' => __DIR__ . '/../storage/certs/eimza_trust_store.pem',

3) Uyarilar:
   - Bu altyapi yalnizca teknik sertifika/imza dogrulamasini yapar.
   - Kurumsal mevzuat icin OCSP/CRL ve zaman damgasi kontrollerini de ekleyin.
