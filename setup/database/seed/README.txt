ESH kurulum seed dosyaları

==========================



Installer şema dosyasını çalıştırdıktan sonra `install_seeds.php` listesindeki

dosyaları sırayla içe aktarır (MySQL formatından hedef sürücüye uyarlanır).



Sürücü → şema dosyası

---------------------

  mysql  → database/schemas/schema.sql

  sqlsrv → database/schemas/schema.mssql.sql

  pgsql  → database/schemas/schema.pgsql.sql

  sqlite → database/schemas/schema.sqlite.sql

  oci    → database/schemas/schema.oci.sql



Şema üretimi (geliştirici):

  php tools/build_schema_mssql.php

  php tools/build_schema_dialect.php all



Tablo öneki: kurulumda sihirbazdaki `db_prefix` değerine göre dönüştürülür.



  1. seed_esh_rbac.sql

  2. seed_esh_unvan_roles.sql

  3. seed_esh_guvence.sql

  4. seed_esh_hastalikcat.sql   (21 ICD üst kategori + icd_range)

  5. seed_esh_hastaliklar.sql   (~14.7k ICD-10-TR platform kataloğu)

  6. seed_esh_branslar.sql

  7. seed_esh_islemler.sql

  8. seed_esh_istekler.sql

  9. seed_esh_adrestablosu.sql



Yenileme: php tools/export_install_seed_sql.php



Mevcut veritabanında kategori hizalama (eski kurulumlar):

  php tools/run_sql_migration.php database/migrate_fix_hastalik_cat_ids.sql
  php tools/remap_hastalik_cat.php
  php tools/remap_hastalik_cat.php --verify



ICD-10 katalog güncelleme (kurulum sonrası, isteğe bağlı):

  1. database/import/README_icd10.txt adımlarını izleyin

  2. php tools/build_icd10_hastaliklar_from_skrs.php

  3. php tools/migrate_import_icd10_hastaliklar.php

  4. php tools/export_install_seed_sql.php seed_esh_hastaliklar.sql

  5. Kurum admin: Hastalık yönetimi → shuttle ile tanı seçimi

