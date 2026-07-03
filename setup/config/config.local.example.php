<?php
declare(strict_types=1);

/**
 * Yerel / sunucu yapılandırması şablonu.
 * Kurulum sihirbazı bunu config/config.local.php olarak oluşturur.
 *
 * Desteklenen db_driver değerleri:
 *   mysql  — MySQL / MariaDB (pdo_mysql, database/schemas/schema.sql)
 *   sqlsrv — Microsoft SQL Server (pdo_sqlsrv, database/schemas/schema.mssql.sql)
 *   pgsql  — PostgreSQL (pdo_pgsql, database/schemas/schema.pgsql.sql)
 *   sqlite — SQLite dosyası (pdo_sqlite, database/schemas/schema.sqlite.sql)
 *   oci    — Oracle (pdo_oci, database/schemas/schema.oci.sql)
 *
 * SQLite: db_host = dizin veya boş (storage/data), db_name = dosya adı/yolu.
 * Oracle: db_name = servis adı (ORCL, XEPDB1); CREATE DATABASE yok.
 */
return [
    'db_driver' => 'mysql',
    'db_host' => '127.0.0.1',
    'db_port' => '',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'esh4',
    'db_prefix' => 'esh_',
    'siteurl' => 'http://localhost',
    // 'active_theme' => 'default',
    // Harita/rota sağlayıcı anahtarları (JSON ayar dosyasına yazılmaz):
    // 'tomtom_key' => '',
    // 'openrouteservice_key' => '',
    // 'mapbox_token' => '',
    // 'google_maps_key' => '',
];
