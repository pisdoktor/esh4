<?php
declare(strict_types=1);
/**
 * Hasta / bakım veren portalı — ortak kabuk.
 * @var string $eshPortalPageTitle
 * @var string $eshPortalInnerFile
 * @var string $eshPortalScript login|index
 */
$__lt = \App\Helpers\ThemeViewHelper::siteThemeSlug();
$__homeUrl = esh_url('PatientPortal', 'login', [], true);
$__loginUrl = esh_url('Auth', 'login', [], true);
$__logoutUrl = esh_url('PatientPortal', 'logout', [], true);
$__assets = rtrim((string) SITEURL, '/') . '/public/assets';
$__portalScript = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($eshPortalScript ?? 'login')));
$__isDashboard = ($__portalScript === 'index');
?>
<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars((string) ($eshPortalPageTitle ?? 'Hasta portalı'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>
    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
    <?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($__assets . '/pages/css/public-hastaarama.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($__assets . '/pages/css/patient-portal.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="guest-portal-page pha-page theme-<?= htmlspecialchars($__lt, ENT_QUOTES, 'UTF-8'); ?> min-vh-100 d-flex flex-column">
    <div class="pha-bg" aria-hidden="true">
        <span class="pha-bg__orb pha-bg__orb--1"></span>
        <span class="pha-bg__orb pha-bg__orb--2"></span>
        <span class="pha-bg__orb pha-bg__orb--3"></span>
    </div>

    <header class="pha-header py-3 px-3">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-3">
            <a href="<?= htmlspecialchars($__isDashboard ? esh_url('PatientPortal', 'index', [], true) : $__homeUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pha-header__brand">
                <i class="fa-solid fa-house-chimney-user" aria-hidden="true"></i>
                <span>
                    <?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?>
                    <small>Evde Sağlık · Hasta portalı</small>
                </span>
            </a>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($__isDashboard): ?>
                    <a href="<?= htmlspecialchars($__logoutUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm pha-btn-ghost">
                        <i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i> Çıkış
                    </a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($__loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm pha-btn-ghost">
                    <i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i> Personel girişi
                </a>
            </div>
        </div>
    </header>

    <main class="pha-main flex-grow-1 py-4 px-3">
        <div class="container-fluid" style="max-width: 52rem;">
            <?php include $eshPortalInnerFile; ?>
        </div>
    </main>

    <footer class="pha-footer py-3 text-center text-muted small">
        Kişisel verileriniz KVKK kapsamında işlenmektedir.
    </footer>
<?php
$__portalJs = ROOT_PATH . '/public/assets/pages/js/publicpatientportal-' . $__portalScript . '.js';
if (is_file($__portalJs)) {
    echo esh_csp_script_src_tag($__assets . '/pages/js/publicpatientportal-' . $__portalScript . '.js');
}
?>
</body>
</html>
