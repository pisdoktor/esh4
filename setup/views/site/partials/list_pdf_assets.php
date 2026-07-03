<?php

/** pdfMake + SheetJS + ortak liste PDF/Excel oluşturucu */

if (!\App\Helpers\AuthHelper::sessionIsAdmin()) {
    return;
}

?>

<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/esh-list-pdf.js', ENT_QUOTES, 'UTF-8') ?>"></script>

<?= \App\Helpers\CdnAssetHelper::pdfMakeScriptsHtml() ?>

<?= \App\Helpers\CdnAssetHelper::sheetJsScriptsHtml() ?>

<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/esh-list-excel.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/esh-fetch-list-export.js', ENT_QUOTES, 'UTF-8') ?>"></script>

