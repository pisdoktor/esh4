<?php
declare(strict_types=1);
/**
 * İstatistik merkezi breadcrumb (hub gruplarıyla uyumlu).
 *
 * @var list<array{label: string, url?: ?string, muted?: bool}>|null $statsBreadcrumb
 * @var string|null $pageTitle
 */
use App\Helpers\StatsNavHelper;

$eshStatsPageAction = trim((string) ($GLOBALS['actionName'] ?? ''));
if ($eshStatsPageAction === '') {
    $eshStatsPageAction = isset($_GET['action']) ? trim((string) $_GET['action']) : 'index';
}
if (
    $eshStatsPageAction !== ''
    && $eshStatsPageAction !== 'index'
    && $eshStatsPageAction !== 'reportPdfData'
    && empty($GLOBALS['eshStatsReportPdfAssets'])
) {
    $GLOBALS['eshStatsReportPdfAssets'] = true;
    require dirname(__DIR__, 2) . '/site/partials/list_pdf_assets.php';
    $GLOBALS['eshStatsReportPdfScript'] = true;
    echo '<script>window.ESH_PAGE=window.ESH_PAGE||{};window.ESH_PAGE.statsReportAction='
        . json_encode($eshStatsPageAction, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
        . ';</script>' . "\n";
}

$trail = $statsBreadcrumb ?? [];
if ($trail === []) {
    $action = trim((string) ($GLOBALS['actionName'] ?? ''));
    if ($action === '') {
        $action = isset($_GET['action']) ? trim((string) $_GET['action']) : 'index';
    }
    $trail = StatsNavHelper::breadcrumbTrail($action, isset($pageTitle) ? (string) $pageTitle : null);
}
if (count($trail) < 1) {
    return;
}
$lastIndex = count($trail) - 1;
?>
<nav aria-label="breadcrumb" class="esh-stats-breadcrumb mb-2">
    <ol class="breadcrumb esh-stats-breadcrumb__list mb-0 flex-wrap align-items-center">
        <?php foreach ($trail as $i => $crumb):
            $label = (string) ($crumb['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $url = $crumb['url'] ?? null;
            $isActive = $i === $lastIndex;
            $muted = !empty($crumb['muted']);
        ?>
            <li class="breadcrumb-item<?= $isActive ? ' active' : '' ?><?= $muted ? ' esh-stats-breadcrumb__item--muted' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <?php if (!$isActive && $url !== null && $url !== ''): ?>
                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
