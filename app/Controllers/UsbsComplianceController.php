<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Helpers\UsbsComplianceHelper;

/**
 * USBS / e-Nabız uyum hazırlığı — alan eşlemesi referansı (yönetici).
 */
class UsbsComplianceController
{
    public function __construct()
    {
        AuthHelper::requireAdmin();
    }

    public function index(): void
    {
        $mapping = UsbsComplianceHelper::mapping();
        $sections = UsbsComplianceHelper::entitySections();
        $columnsReady = UsbsComplianceHelper::columnsReady();
        $pageTitle = 'USBS alan eşlemesi';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'usbs_compliance/index');
        include ThemeViewHelper::resolvePartial('footer');
    }
}
