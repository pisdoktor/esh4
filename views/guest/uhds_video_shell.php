<?php
declare(strict_types=1);
/**
 * Hasta görüntülü görüşme — misafir kabuk.
 * @var string $eshGuestPageTitle
 * @var string $eshGuestInnerFile
 */
$__lt = \App\Helpers\ThemeViewHelper::siteThemeSlug();
$__loginUrl = esh_url('Auth', 'login', [], true);
$__assets = rtrim((string) SITEURL, '/') . '/public/assets';
?>
<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars((string) ($eshGuestPageTitle ?? 'Görüntülü görüşme'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?= esh_version_meta_tag(); ?>
    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
    <?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($__assets . '/pages/css/uhds-video.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="guest-uhds-video-page theme-<?= htmlspecialchars($__lt, ENT_QUOTES, 'UTF-8'); ?> min-vh-100 d-flex flex-column bg-body-tertiary">
    <header class="py-3 px-3 border-bottom bg-white">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold">
                <i class="fa-solid fa-video text-primary me-2"></i>
                <?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · Görüntülü görüşme
            </div>
            <a href="<?= htmlspecialchars($__loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary rounded-pill">Personel girişi</a>
        </div>
    </header>
    <main class="flex-grow-1 container-fluid px-3 py-4">
        <?php include $eshGuestInnerFile; ?>
    </main>
    <footer class="py-3 text-center text-muted small border-top bg-white">
        Kişisel verileriniz KVKK kapsamında işlenmektedir.
    </footer>
    <script>
    window.__eshUhdsVideo = window.__eshUhdsVideo || {
        configUrl: <?= json_encode($uhdsVideoConfigUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
        jitsiScriptUrl: <?= json_encode($uhdsJitsiScriptUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
        appointmentId: <?= (int) ($uhdsAppointmentId ?? 0) ?>,
        isPatientJoin: true
    };
    </script>
    <script src="<?= htmlspecialchars($__assets . '/pages/js/uhds-video.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
