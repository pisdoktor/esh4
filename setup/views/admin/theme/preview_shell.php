<?php

/** @var string $previewThemeSlug */

/** @var string $previewBodyClasses */

/** @var list<string> $previewStylesheetUrls */

$eshPreviewAssets = rtrim((string) (defined('ASSETS_URL') ? ASSETS_URL : ''), '/');

$previewBodyClasses = trim($previewBodyClasses . ' esh-theme-preview-frame');

$previewMainClass = \App\Helpers\ThemeViewHelper::themeMainClassAttribute($previewThemeSlug);

$previewLoginThemeClass = 'page-login-theme-' . $previewThemeSlug;
if (\App\Helpers\ThemeViewHelper::themeUsesDefaultChrome($previewThemeSlug) || $previewThemeSlug === 'theme-default') {
    $previewLoginThemeClass .= ' page-login-theme-default';
}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tema önizleme — sayfa standardı</title>

    <?= \App\Helpers\CdnAssetHelper::vendorCdnStylesHtml() ?>

    <?php \App\Helpers\ThemeViewHelper::renderHeadStylesheetsHtml($previewThemeSlug); ?>
    <?= \App\Helpers\CdnAssetHelper::previewShellExtraStylesHtml() ?>

    <?php if ($eshPreviewAssets !== ''): ?>

    <link rel="stylesheet" href="<?= htmlspecialchars($eshPreviewAssets . '/pages/css/theme-preview-shell.css', ENT_QUOTES, 'UTF-8') ?>">

    <?php endif; ?>

    <style>

        html, body { margin: 0; min-height: 100%; }

    </style>

</head>

<body class="<?= htmlspecialchars($previewBodyClasses, ENT_QUOTES, 'UTF-8') ?>">

<?php if ($previewThemeSlug === 'aurora'): ?>

<div class="aurora-backdrop" aria-hidden="true"></div>

<?php endif; ?>


<?php include __DIR__ . '/partials/mod_kabuk.php'; ?>

<main class="<?= htmlspecialchars($previewMainClass, ENT_QUOTES, 'UTF-8') ?>">

<?php include __DIR__ . '/partials/mod_top.php'; ?>
<?php include __DIR__ . '/partials/mod_ortak.php'; ?>
<?php include __DIR__ . '/partials/mod_liste.php'; ?>
<?php include __DIR__ . '/partials/mod_dashboard.php'; ?>
<?php include __DIR__ . '/partials/mod_rota.php'; ?>
<?php include __DIR__ . '/partials/mod_hasta.php'; ?>
<?php include __DIR__ . '/partials/mod_uyari.php'; ?>
<?php include __DIR__ . '/partials/mod_giris.php'; ?>
<?php include __DIR__ . '/partials/mod_form.php'; ?>
</article>

</main>



<?= \App\Helpers\CdnAssetHelper::previewShellScriptsHtml() ?>

</body>

</html>

