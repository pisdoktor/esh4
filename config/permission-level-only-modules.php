<?php
declare(strict_types=1);

/**
 * Registry modülleri — personel RBAC (permission-crud-map) dışı.
 * Erişim controller içi seviye kontrolü ile: requireAdmin / requireSuperAdmin / platform owner.
 *
 * İlişkili: config/permission-crud-map.php (personel RBAC), config/app-modules.registry.php
 *
 * @return list<string> Modül anahtarları (registry key)
 */
return [
    'role',
    'kurum',
    'unvan',
    'adres_fetch',
    'brans',
    'guvence',
    'hastalik',
    'islem',
    'istek',
    'arac',
    'ilac_listesi',
    'db_maintenance',
    'audit_log',
    'esys_compliance',
    'usbs_compliance',
    'federation',
    'rest_api',
    'cdn_check',
    'theme',
    'settings',
    'kps_tc_sorgu',
    'adrestanim',
    'harita',
    'adres_koordinat',
    'manuel_koordinat',
];
