<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Helpers\UsbsComplianceHelper;
use App\Models\UsbsSyncLog;

/**
 * USBS / e-Nabız uyum hazırlığı — alan eşlemesi referansı (sistem sahibi).
 */
class UsbsComplianceController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
    }

    public function index(): void
    {
        $mapping = UsbsComplianceHelper::mapping();
        $sections = UsbsComplianceHelper::entitySections();
        $columnsReady = UsbsComplianceHelper::columnsReady();
        $kurumId = AuthHelper::sessionIsSuperAdmin() ? null : TenantContext::sessionKurumId();
        $kpis = UsbsComplianceHelper::complianceKpis($kurumId);
        $lastSync = (new UsbsSyncLog())->recent(1, $kurumId);
        $lastSyncRow = $lastSync[0] ?? null;
        $pageTitle = 'USBS alan eşlemesi';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'usbs_compliance/index');
        include ThemeViewHelper::resolvePartial('footer');
    }
}
