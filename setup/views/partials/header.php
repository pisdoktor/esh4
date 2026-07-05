<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?> | Evde Sağlık Hizmetleri</title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>
    <?= \App\Helpers\PwaHelper::renderHeadHtml(); ?>

<?= \App\Helpers\CdnAssetHelper::vendorCdnStylesHtml() ?>
<?php \App\Helpers\ThemeViewHelper::renderHeadStylesheetsHtml(); ?>
<?php
\App\Helpers\PageAssetHelper::registerFromRequest(
    (string) ($GLOBALS['controllerName'] ?? ''),
    (string) ($GLOBALS['actionName'] ?? '')
);
echo \App\Helpers\PageAssetHelper::renderRegisteredStylesheetsHtml();
$__eshMapCtrl = strtolower((string) ($GLOBALS['controllerName'] ?? ''));
$__eshMapAct = strtolower((string) ($GLOBALS['actionName'] ?? ''));
if (($__eshMapCtrl === 'harita' && $__eshMapAct === 'index')
    || ($__eshMapCtrl === 'manuelkoordinat' && $__eshMapAct === 'index')
    || ($__eshMapCtrl === 'adrestanim' && $__eshMapAct === 'index')
    || ($__eshMapCtrl === 'dashboard' && $__eshMapAct === 'showroute')) {
    echo \App\Helpers\CdnAssetHelper::mapRoutingPageStylesHtml();
}
?>
<?= \App\Helpers\CdnAssetHelper::vendorCdnScriptsHtml() ?>
<?php \App\Helpers\ThemeViewHelper::renderHeadScriptsHtml(); ?>
<?= esh_csp_script_src_tag(ASSETS_URL . '/csrf-guard.js', 'defer') ?>
</head>
<?php
$__eshMainWrapper = \App\Helpers\ThemeViewHelper::themeMainWrapperKind();
?>
<body class="<?= \App\Helpers\ThemeViewHelper::themeBodyClassAttribute() ?>">
<?php if (\App\Helpers\ThemeViewHelper::activeTheme() === 'aurora'): ?>
<div class="aurora-backdrop" aria-hidden="true"></div>
<?php endif; ?>
<?php \App\Helpers\AlertHelper::display(); ?>
<?php
$cName = $GLOBALS['controllerName'] ?? 'Dashboard';
$aName = $GLOBALS['actionName'] ?? 'index';
\App\Helpers\UIHelper::renderTopMenu($cName, $aName);
?>

<?php if ($__eshMainWrapper === 'cpp'): ?>
    <main class="<?= \App\Helpers\ThemeViewHelper::themeMainClassAttribute() ?>">
        <div class="cpp-main-inner container-xxl py-4 pb-5 px-3 px-sm-4 d-flex flex-column flex-grow-1">
<?php elseif ($__eshMainWrapper === 'ev'): ?>
    <main class="<?= \App\Helpers\ThemeViewHelper::themeMainClassAttribute() ?>">
        <div class="ev-main-sheet container-xxl py-4 pb-5 px-3 px-sm-4 d-flex flex-column flex-grow-1">
<?php else: ?>
    <main class="<?= \App\Helpers\ThemeViewHelper::themeMainClassAttribute() ?>">
<?php endif; ?>
