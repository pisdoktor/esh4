<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FederationHelper;
use App\Helpers\ThemeViewHelper;

/**
 * Federasyon özeti — sistem sahibi.
 */
class FederationController
{
    public function index(): void
    {
        AuthHelper::requirePlatformOwner();
        if (!FederationHelper::columnsReady()) {
            $_SESSION['error'] = 'Federasyon tabloları kurulu değil. migrate_esh_federation_regions.sql çalıştırın.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }

        $overviewRows = FederationHelper::overviewRows();
        $columnsReady = FederationHelper::columnsReady();
        $federationReady = FederationHelper::isReady();
        $pageTitle = 'Federasyon özeti';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'federation/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function setBolgeFilter(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        AuthHelper::requireSuperAdmin();
        if (!FederationHelper::enabled()) {
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        $bid = isset($_POST['bolge_id']) ? (int) $_POST['bolge_id'] : 0;
        \App\Helpers\FederationContext::setSessionBolgeFilter($bid > 0 ? $bid : null);
        $redirect = trim((string) ($_POST['redirect'] ?? ''));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            header('Location: ' . $redirect);
        } else {
            header('Location: ' . esh_url('Dashboard', 'index'));
        }
        exit;
    }
}
