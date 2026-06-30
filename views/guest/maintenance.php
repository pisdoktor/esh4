<?php



declare(strict_types=1);



/**

 * Bakım modu — erişim engeli sayfası (503).

 *

 * @var string $eshMaintenanceMessage

 * @var bool $eshMaintenanceHasSession

 */



$eshMaintenanceMessage = trim((string) ($eshMaintenanceMessage ?? ''));

if ($eshMaintenanceMessage === '') {

    $eshMaintenanceMessage = 'Sistem bakımda. Lütfen kısa süre sonra tekrar deneyin.';

}

$eshMaintenanceHasSession = !empty($eshMaintenanceHasSession);

$__loginUrl = esh_url('Auth', 'login', [], true);

$__logoutUrl = esh_url('Auth', 'logout', [], true);

$__assets = defined('ASSETS_URL') ? rtrim((string) ASSETS_URL, '/') : rtrim((string) SITEURL, '/') . '/public/assets';

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="robots" content="noindex, nofollow">

    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · Bakım</title>

    <?= esh_version_meta_tag(); ?>

    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>

    <link rel="stylesheet" href="<?= htmlspecialchars($__assets . '/pages/css/maintenance.css', ENT_QUOTES, 'UTF-8') ?>">

</head>

<body class="esh-maintenance-page">

    <div class="esh-maint-card" role="alert">

        <div class="esh-maint-icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></div>

        <h1 class="h4 fw-bold mb-3">Site bakımda</h1>

        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($eshMaintenanceMessage, ENT_QUOTES, 'UTF-8')) ?></p>

        <div class="esh-maint-actions">

            <a class="primary" href="<?= htmlspecialchars($__loginUrl, ENT_QUOTES, 'UTF-8') ?>">Giriş sayfası</a>

            <?php if ($eshMaintenanceHasSession): ?>

                <a class="danger" href="<?= htmlspecialchars($__logoutUrl, ENT_QUOTES, 'UTF-8') ?>">Güvenli çıkış</a>

            <?php endif; ?>

        </div>

        <p class="small text-muted mt-4 mb-0"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8') ?> v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8') ?></p>

    </div>

</body>

</html>

