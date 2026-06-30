<?php
declare(strict_types=1);
/**
 * Admin liste sayfaları — ortak açılış.
 * Kapanış: aynı istek içinde list_page_close.php (varsayılan $admin_list_close_part = full).
 *
 * Zorunlu:
 * - $admin_list_title (string)
 *
 * İsteğe bağlı:
 * - $admin_list_icon (string) örn. fa-solid fa-table-list
 * - $admin_list_subtitle (string) — kısa açıklama (HTML yok, düz metin)
 * - $admin_list_actions (string) — sağ üst araç çubuğu (güvenilir HTML)
 * - $admin_list_header_aside (string) — geniş ekranda başlık yanı / altında yardım metni (HTML)
 * - $admin_list_card_extra_classes (string) — karta ek Bootstrap sınıfları
 * - $admin_list_container_extra_classes (string) — varsayılan: py-4
 * - $admin_list_container_id (string|null) — dış sarmalayıcı id
 * - $admin_list_show_flash (bool) — varsayılan true; card üstünde $_SESSION['success']
 * - $admin_list_skip_body_wrapper (bool) — varsayılan false; true ise card-body yok (tam genişlik tablo)
 * - $admin_list_body_class (string) — card-body ek sınıfı (örn. p-0)
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
?>
<div<?= $idAttr ?> class="<?= htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($showFlash && isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0<?= $cardExtra !== '' ? ' ' . htmlspecialchars($cardExtra, ENT_QUOTES, 'UTF-8') : '' ?>">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-start g-2">
                <div class="<?= $aside !== '' ? 'col-12 col-lg-4' : 'col' ?>">
                    <h5 class="mb-0 text-primary">
                        <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <?php if ($subtitle !== ''): ?>
                        <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($aside !== ''): ?>
                    <div class="col-12 col-lg-8 text-lg-end small text-muted">
                        <?= $aside ?>
                    </div>
                <?php endif; ?>
                <?php if ($actions !== '' && $aside === ''): ?>
                    <div class="col-12 col-md-auto text-md-end ms-md-auto">
                        <?= $actions ?>
                    </div>
                <?php elseif ($actions !== ''): ?>
                    <div class="col-12 text-lg-end">
                        <?= $actions ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$skipBody): ?>
        <div class="card-body<?= $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : '' ?>">
        <?php endif; ?>
