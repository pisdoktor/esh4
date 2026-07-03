<?php
declare(strict_types=1);
/**
 * Aurora Light — admin liste sayfası açılış (au-panel).
 * Sözleşme: views/partials/admin/list_page_open.php ile aynı değişkenler.
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

$GLOBALS['_admin_list_body_opened'] = !$skipBody;

$idAttr = $contId !== '' ? ' id="' . htmlspecialchars($contId, ENT_QUOTES, 'UTF-8') . '"' : '';
$containerClass = trim('container-fluid admin-list-page ' . $contExtra);
$panelExtra = $cardExtra !== '' ? ' ' . htmlspecialchars($cardExtra, ENT_QUOTES, 'UTF-8') : '';
?>
<div<?= $idAttr ?> class="<?= htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($showFlash && isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <div class="au-panel<?= $panelExtra ?>">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                <span class="au-icon-chip au-icon-chip--soft flex-shrink-0"><i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i></span>
                <div class="min-w-0">
                    <h2 class="au-panel__title mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if ($subtitle !== ''): ?>
                        <p class="au-panel__sub mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($actions !== '' && $aside === ''): ?>
                <div class="d-flex flex-wrap gap-2 align-items-center ms-md-auto">
                    <?= $actions ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($aside !== ''): ?>
            <div class="px-3 pb-2 small text-muted border-bottom"><?= $aside ?></div>
        <?php endif; ?>
        <?php if ($actions !== '' && $aside !== ''): ?>
            <div class="px-3 py-2 border-bottom text-end"><?= $actions ?></div>
        <?php endif; ?>
        <?php if (!$skipBody): ?>
        <div class="au-panel__body<?= $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : '' ?>">
        <?php endif; ?>
