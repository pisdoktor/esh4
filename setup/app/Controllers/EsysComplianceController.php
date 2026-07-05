<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\EsysBridgeHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\EsysSyncLog;

/**
 * ESYS uyum hazırlığı — alan eşlemesi referansı (sistem sahibi).
 */
class EsysComplianceController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
    }

    public function index(): void
    {
        $mapping = EsysComplianceHelper::mapping();
        $sections = EsysComplianceHelper::entitySections();
        $columnsReady = EsysComplianceHelper::columnsReady();
        $kurumId = AuthHelper::sessionIsSuperAdmin() ? null : TenantContext::sessionKurumId();
        $kpis = EsysComplianceHelper::complianceKpis($kurumId);
        $lastSync = (new EsysSyncLog())->recent(1, $kurumId);
        $lastSyncRow = $lastSync[0] ?? null;
        $pageTitle = 'ESYS alan eşlemesi';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'esys_compliance/index');
        include ThemeViewHelper::resolvePartial('footer');
    }
}
