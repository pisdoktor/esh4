<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CdnAssetHelper;
use App\Helpers\ThemeViewHelper;

/**
 * Sistem sahibi (PS): sabit CDN sürümlerini npm/cdnjs ile karşılaştırma; isteğe bağlı HEAD sondası.
 * Dosyaya sürüm yazma yalnızca CLI: {@see ROOT_PATH}/tools/check_cdn_versions.php --apply
 */
class CdnCheckController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
    }

    public function index(): void
    {
        $timeout = isset($_GET['timeout']) ? max(1, min(60, (int) $_GET['timeout'])) : 12;
        $probe = isset($_GET['probe']) && (string) $_GET['probe'] === '1';

        $compareRows = CdnAssetHelper::comparePinnedToRegistryLatest($timeout);
        $suggested = CdnAssetHelper::suggestPinnedUpdatesFromRegistry($compareRows, true);

        $probeReport = null;
        if ($probe) {
            $probeReport = CdnAssetHelper::formatProbeReport(CdnAssetHelper::probeCdnUrls($timeout));
        }

        $pageTitle = 'CDN sürüm kontrolü';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'cdn_check/index');
        include ThemeViewHelper::resolvePartial('footer');
    }
}
