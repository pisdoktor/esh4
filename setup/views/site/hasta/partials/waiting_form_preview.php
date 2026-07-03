<div class="card border-<?= !empty($hasta->pasif) ? 'warning' : 'info'; ?>">

    <div class="card-header">
    <div class="row">
    <div class="col-9"><h4><i class="fa-solid fa-file-lines"></i> Hasta Değerlendirme Formu:  <?= htmlspecialchars((string) (($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?></h4></div>
    <div class="col-3 text-end"><h4></h4></div>
    </div>
    </div>

    <div class="card-body">
    <iframe id="iframeContainer" width="100%" height="600px"></iframe>
</div>

</div>
<script>
pdfMake.createPdf(dd).getDataUrl().then(function (url) {
    document.getElementById('iframeContainer').setAttribute('src', url);
});
</script>
