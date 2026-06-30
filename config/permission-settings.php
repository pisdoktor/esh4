<?php
declare(strict_types=1);

/**
 * RBAC çalışma zamanı ayarları.
 *
 * enabled: router ve can() kontrolleri aktif mi?
 * tables_required: false ise tablolar yokken sessizce devre dışı kalır.
 */
return [
    'enabled' => true,
    'tables_required' => true,
];
