SKRS ICD-10-TR tanı listesi — içe aktarma ve güncelleme

=======================================================



Yeni kurulum: `database/seed/seed_esh_hastaliklar.sql` tam platform kataloğunu içerir.

Bu akış yalnızca SKRS’ten katalog **güncellemek** veya seed dosyasını yenilemek içindir.



1. SKRS ICD-10 listesini indirin VEYA `storage/ICD10Listesi.xlsx` dosyasını kullanın.

2. Dosyayı şu konumlardan birine koyun (öncelik sırası):

   - storage/ICD10Listesi.xlsx  (SKRS export — Adı/Kodu sütunları)

   - database/import/icd10-tr.xlsx

   - database/import/icd10-tr.csv



Beklenen sütun başlıkları (büyük/küçük harf duyarsız):

  - ICD kodu: KOD, ICD, ICD-10, ICD10 ...

  - Tanı adı: ADI, Adı, TANI ADI, Açıklama ...



3. JSON üret:

   php tools/build_icd10_hastaliklar_from_skrs.php



Alternatif (SKRS dosyası yoksa — İngilizce adlar, ~71k alt kod):

   İndir: https://raw.githubusercontent.com/k4m1113/ICD-10-CSV/master/codes.csv

   → database/import/icd10-github-raw.csv

   php tools/convert_icd10_github_csv_to_skrs.php

   php tools/build_icd10_hastaliklar_from_skrs.php



4. Veritabanına aktar (önce şema migrasyonları):

   php tools/run_sql_migration.php database/migrate_esh_kurum_hastalik_create.sql

   php tools/run_sql_migration.php database/migrate_esh_hastaliklar_platform_catalog.sql

   php tools/migrate_import_icd10_hastaliklar.php



5. Kurulum seed’ini güncelle (isteğe bağlı):

   php tools/export_install_seed_sql.php seed_esh_hastaliklar.sql



Kategori eşlemesi: `App\Helpers\Icd10CatMapper` → `esh_hastalikcat.id` (1–21).



Kurum tanı seçimi: Yönetim → Hastalık yönetimi (kurum admin shuttle).

