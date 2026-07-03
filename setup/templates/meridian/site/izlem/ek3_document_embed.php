<?php
/** @var string $ek3PdfJson pdfMake docDefinition JSON */
/** @var string $ek3PdfJson pdfMake docDefinition JSON */
/** @var bool $pasifUyari */
/** @var object $visit */
/** @var object $hasta */
$hastaAd = '';
if (!empty($hasta)) {
    $hastaAd = trim((string) (($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')));
}
$ek3DlName = htmlspecialchars((string) (preg_replace('/\D/', '', (string) ($hasta->tckimlik ?? '')) ?: 'hasta'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EK-3<?= $hastaAd !== '' ? ' — ' . htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') : '' ?></title>
    <?= \App\Helpers\CdnAssetHelper::vendorCdnStylesHtml() ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim((string) SITEURL, '/') . '/public/assets/esh-ek3-embed.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="esh-ek3-embed-body">
<script>
var dd = <?= $ek3PdfJson ?>;
</script>
<div class="esh-ek3-embed-toolbar d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-light small">
    <?php if ($hastaAd !== ''): ?>
        <span class="text-muted"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
    <?php if (!empty($pasifUyari)): ?>
        <span class="badge text-bg-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>PASİF</span>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-2 ms-auto">
        <button type="button" class="btn btn-warning btn-sm" onclick="pdfMake.createPdf(dd).print();">
            <i class="fa-solid fa-print me-1"></i>Yazdır
        </button>
        <button type="button" class="btn btn-info btn-sm" onclick="pdfMake.createPdf(dd).download('<?= $ek3DlName ?>-EK3.pdf');">
            <i class="fa-solid fa-download me-1"></i>İndir
        </button>
    </div>
</div>
<iframe id="iframeEk3" class="esh-ek3-embed-frame" title="EK-3 Önizleme"></iframe>
<?= \App\Helpers\CdnAssetHelper::pdfMakeScriptsHtml() ?>
<script>
(function () {
    if (typeof pdfMake === 'undefined' || !document.getElementById('iframeEk3')) return;
    pdfMake.createPdf(dd).getDataUrl().then(function (url) {
        document.getElementById('iframeEk3').src = url;
    });
})();
</script>
</body>
</html>
