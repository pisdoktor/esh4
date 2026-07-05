<?php
declare(strict_types=1);

/**
 * Yerel / sunucu yapılandırması şablonu.
 * Kurulum: bu dosyayı config/config.local.php olarak kopyalayın (git'e eklemeyin).
 *
 * Güvenlik:
 * - config/config.local.php .gitignore içindedir; repoya commit etmeyin.
 * - Harita API anahtarları daha önce repoda ifşa olduysa sağlayıcı panelinden rotate edin
 *   (TomTom, Mapbox, OpenRouteService, Google Maps).
 * - Üretimde ortam değişkeni tercih edilebilir: TOMTOM_KEY vb. (config.php önceliğine bakın).
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
    'tomtom_key' => '',
    'openrouteservice_key' => '',
    'mapbox_token' => '',
    // 'google_maps_key' => '',
    // Sıkı script CSP (tüm inline script nonce + onclick kaldırıldıktan sonra): true
    // 'csp_script_nonce_strict' => false,
];
