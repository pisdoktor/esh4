<?php
declare(strict_types=1);
/**
 * Girişsiz hasta TC sorgu sayfaları — ortak kabuk.
 * @var string $eshGuestPageTitle
 * @var string $eshGuestInnerFile
 * @var string $eshGuestScript index|sonuc
 */
$__lt = \App\Helpers\ThemeViewHelper::siteThemeSlug();
$__homeUrl = esh_url('PublicHastaarama', 'index', [], true);
$__loginUrl = esh_url('Auth', 'login', [], true);
$__assets = rtrim((string) SITEURL, '/') . '/public/assets';
$__phaScript = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($eshGuestScript ?? 'index')));
$__phaWide = ($__phaScript === 'sonuc');
?>
<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars((string) ($eshGuestPageTitle ?? 'Hasta sorgulama'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>
    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
    <?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($__assets . '/pages/css/public-hastaarama.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="guest-hastaarama-page pha-page theme-<?= htmlspecialchars($__lt, ENT_QUOTES, 'UTF-8'); ?> min-vh-100 d-flex flex-column">
    <div class="pha-bg" aria-hidden="true">
        <span class="pha-bg__orb pha-bg__orb--1"></span>
        <span class="pha-bg__orb pha-bg__orb--2"></span>
        <span class="pha-bg__orb pha-bg__orb--3"></span>
    </div>

    <header class="pha-header py-3 px-3">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-3">
            <a href="<?= htmlspecialchars($__homeUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pha-header__brand">
                <i class="fa-solid fa-house-medical-flag" aria-hidden="true"></i>
                <span>
                    <?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?>
                    <small>Evde Sağlık · Hasta doğrulama</small>
                </span>
            </a>
            <a href="<?= htmlspecialchars($__loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm pha-btn-ghost">
                <i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i> Personel girişi
            </a>
        </div>
    </header>

    <main class="pha-main flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="pha-stage<?= $__phaWide ? ' pha-stage--wide' : ''; ?>">
            <?php
            $inner = (string) ($eshGuestInnerFile ?? '');
            if ($inner !== '' && is_file($inner)) {
                include $inner;
            }
            ?>
        </div>
    </main>

    <footer class="pha-footer">
        <p class="mb-1">Bu sorgu <strong>kayıtlı hasta dosyası</strong> ve <strong>dosya durumunu</strong> gösterir; tedavi veya izlem gibi kişisel sağlık verisi paylaşılmaz.</p>
        <p class="mb-0">© <?= date('Y'); ?> <?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · <a href="<?= htmlspecialchars($__loginUrl, ENT_QUOTES, 'UTF-8') ?>">Yönetim paneli</a></p>
    </footer>

    <?= esh_csp_script_src_tag(\App\Helpers\CdnAssetHelper::jqueryJsHref()) ?>
    <?= esh_csp_script_src_tag(\App\Helpers\CdnAssetHelper::jquery4LegacyShimJsHref()) ?>
    <?= esh_csp_script_src_tag(\App\Helpers\CdnAssetHelper::bootstrapBundleJsHref()) ?>
<?php
$__phaJs = ROOT_PATH . '/public/assets/pages/js/publichastaarama-' . $__phaScript . '.js';
if (is_file($__phaJs)) {
    echo esh_csp_script_src_tag($__assets . '/pages/js/publichastaarama-' . $__phaScript . '.js');
}
unset($__phaJs);
?>
</body>
</html>
