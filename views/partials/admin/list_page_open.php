<?php
declare(strict_types=1);
/**
 * Admin liste sayfaları — ortak açılış (ESH sayfa dili).
 * Kapanış: list_page_close.php
 *
 * Zorunlu: $admin_list_title
 * İsteğe bağlı: $admin_list_module (yoksa controller adından), $admin_list_icon, $admin_list_subtitle,
 * $admin_list_actions, $admin_list_header_aside, $admin_list_card_extra_classes,
 * $admin_list_container_extra_classes, $admin_list_container_id, $admin_list_show_flash,
 * $admin_list_skip_body_wrapper, $admin_list_body_class
 */
$title = isset($admin_list_title) ? (string) $admin_list_title : 'Liste';
$icon = isset($admin_list_icon) ? (string) $admin_list_icon : 'fa-solid fa-table-list';
$subtitle = isset($admin_list_subtitle) ? (string) $admin_list_subtitle : '';
$actions = isset($admin_list_actions) ? (string) $admin_list_actions : '';
$aside = isset($admin_list_header_aside) ? (string) $admin_list_header_aside : '';
$cardExtra = isset($admin_list_card_extra_classes) ? trim((string) $admin_list_card_extra_classes) : '';
$contExtra = isset($admin_list_container_extra_classes) ? trim((string) $admin_list_container_extra_classes) : 'py-4';
$contId = isset($admin_list_container_id) ? trim((string) $admin_list_container_id) : '';
$showFlash = !isset($admin_list_show_flash) || $admin_list_show_flash;
$skipBody = !empty($admin_list_skip_body_wrapper);
$bodyClass = isset($admin_list_body_class) ? trim((string) $admin_list_body_class) : '';

$moduleSlug = isset($admin_list_module) ? trim((string) $admin_list_module) : '';
if ($moduleSlug === '') {
    $c = (string) ($GLOBALS['controllerName'] ?? '');
    $moduleSlug = strtolower(preg_replace('/[^a-z0-9_-]/', '', $c));
}
$moduleClass = $moduleSlug !== '' ? ' esh-page-' . $moduleSlug : '';

$GLOBALS['_admin_list_body_opened'] = !$skipBody;

$idAttr = $contId !== '' ? ' id="' . htmlspecialchars($contId, ENT_QUOTES, 'UTF-8') . '"' : '';
$containerClass = trim('esh-page esh-page--list' . $moduleClass . ' container-fluid admin-list-page ' . $contExtra);
?>
<div<?= $idAttr ?> class="<?= htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($showFlash && isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <section class="esh-page__panel esh-page__panel--data esh-list-table-card card border-0<?= $cardExtra !== '' ? ' ' . htmlspecialchars($cardExtra, ENT_QUOTES, 'UTF-8') : '' ?>">
        <header class="esh-page__panel-head card-header bg-white py-3 border-bottom">
            <div class="row align-items-start g-2">
                <div class="<?= $aside !== '' ? 'col-12 col-lg-4' : 'col' ?>">
                    <h1 class="esh-page__heading h5 mb-0">
                        <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-2" aria-hidden="true"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <?php if ($subtitle !== ''): ?>
                        <p class="esh-page__lead small mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($aside !== ''): ?>
                    <div class="col-12 col-lg-8 text-lg-end small text-muted">
                        <?= $aside ?>
                    </div>
                <?php endif; ?>
                <?php if ($actions !== '' && $aside === ''): ?>
                    <div class="col-12 col-md-auto text-md-end ms-md-auto esh-page__toolbar">
                        <?= $actions ?>
                    </div>
                <?php elseif ($actions !== ''): ?>
                    <div class="col-12 text-lg-end esh-page__toolbar">
                        <?= $actions ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        <?php if (!$skipBody): ?>
        <div class="esh-page__panel-body card-body<?= $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : '' ?>">
        <?php endif; ?>
