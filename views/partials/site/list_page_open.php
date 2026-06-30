<?php
declare(strict_types=1);
/**
 * Site liste sayfaları — ortak açılış (ESH sayfa dili).
 * Kapanış: list_page_close.php
 *
 * Zorunlu: $site_list_title
 * İsteğe bağlı: $site_list_module, $site_list_icon, $site_list_subtitle,
 * $site_list_search_form, $site_list_actions, $site_list_header_aside,
 * $site_list_card_extra_classes, $site_list_container_extra_classes,
 * $site_list_container_id, $site_list_show_flash, $site_list_body_class
 */
$title = isset($site_list_title) ? (string) $site_list_title : 'Liste';
$icon = isset($site_list_icon) ? (string) $site_list_icon : 'fa-solid fa-table-list';
$subtitle = isset($site_list_subtitle) ? (string) $site_list_subtitle : '';
$searchForm = isset($site_list_search_form) ? (string) $site_list_search_form : '';
$actions = isset($site_list_actions) ? (string) $site_list_actions : '';
$aside = isset($site_list_header_aside) ? (string) $site_list_header_aside : '';
$cardExtra = isset($site_list_card_extra_classes) ? trim((string) $site_list_card_extra_classes) : '';
$contExtra = isset($site_list_container_extra_classes) ? trim((string) $site_list_container_extra_classes) : 'py-4';
$contId = isset($site_list_container_id) ? trim((string) $site_list_container_id) : '';
$showFlash = !isset($site_list_show_flash) || $site_list_show_flash;
$bodyClass = isset($site_list_body_class) ? trim((string) $site_list_body_class) : '';

$moduleSlug = isset($site_list_module) ? trim((string) $site_list_module) : '';
if ($moduleSlug === '') {
    $c = (string) ($GLOBALS['controllerName'] ?? '');
    $moduleSlug = strtolower(preg_replace('/[^a-z0-9_-]/', '', $c));
}
$moduleClass = $moduleSlug !== '' ? ' esh-page-' . $moduleSlug : '';

$idAttr = $contId !== '' ? ' id="' . htmlspecialchars($contId, ENT_QUOTES, 'UTF-8') . '"' : '';
$containerClass = trim('esh-page esh-page--list' . $moduleClass . ' container-fluid site-list-page ' . $contExtra);
?>
<article<?= $idAttr ?> class="<?= htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') ?>" lang="tr">
    <?php if ($showFlash && isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <?php if ($searchForm !== '' || $actions !== ''): ?>
    <header class="esh-page__header mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-4">
                <h1 class="esh-page__heading h4 mb-0">
                    <?php if ($icon !== ''): ?>
                        <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-2" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="esh-page__lead small mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <?php if ($searchForm !== ''): ?>
            <div class="col-md-5">
                <?= $searchForm ?>
            </div>
            <?php endif; ?>
            <?php if ($actions !== ''): ?>
            <div class="col-md-<?= $searchForm !== '' ? '3' : '8' ?> text-md-end esh-page__toolbar">
                <?= $actions ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($aside !== ''): ?>
        <div class="mt-2 small text-muted"><?= $aside ?></div>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <section class="esh-page__panel esh-page__panel--data esh-list-table-card card border-0<?= $cardExtra !== '' ? ' ' . htmlspecialchars($cardExtra, ENT_QUOTES, 'UTF-8') : '' ?>">
        <?php if ($searchForm === '' && $actions === ''): ?>
        <header class="esh-page__panel-head card-header bg-white py-3 border-bottom">
            <div class="row align-items-start g-2">
                <div class="<?= $aside !== '' ? 'col-12 col-lg-8' : 'col' ?>">
                    <h1 class="esh-page__heading h5 mb-0">
                        <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-2" aria-hidden="true"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <?php if ($subtitle !== ''): ?>
                        <p class="esh-page__lead small mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($aside !== ''): ?>
                    <div class="col-12 col-lg-4 text-lg-end small text-muted"><?= $aside ?></div>
                <?php endif; ?>
                <?php if ($actions !== ''): ?>
                    <div class="col-12 col-md-auto text-md-end ms-md-auto esh-page__toolbar"><?= $actions ?></div>
                <?php endif; ?>
            </div>
        </header>
        <?php endif; ?>
        <div class="esh-page__panel-body card-body<?= $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : '' ?><?= ($searchForm !== '' || $actions !== '') ? ' p-0' : '' ?>">
