<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
/** @var string $ek3PdfJson pdfMake docDefinition JSON */
/** @var bool $pasifUyari */
/** @var list<array{bransId:int,bransName:string,docUrl:string}> $ek3Tabs */
$ek3Tabs = is_array($ek3Tabs ?? null) ? $ek3Tabs : [];
$ek3MultiTabs = count($ek3Tabs) > 1;
$hastaAd = '';
if (!empty($hasta)) {
    $hastaAd = trim((string) (($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')));
}
$historyUrl = htmlspecialchars(
    esh_url('Visit', 'history', ['tc' => urlencode((string) ($visit->hastatckimlik ?? ''))]),
    ENT_QUOTES,
    'UTF-8'
);
?>
<?php if (!$ek3MultiTabs): ?>
<script<?= esh_csp_nonce_attr() ?>>
var dd = <?= $ek3PdfJson ?>;
</script>
<?php endif; ?>
<div class="container-lg py-4">
    <div class="card border-<?= !empty($pasifUyari) ? 'warning' : 'info' ?> shadow rounded-3">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="fa-solid fa-file-lines me-2"></i>Hasta EK-3 formu<?= $hastaAd !== '' ? ': ' . htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') : '' ?>
                <?php if (!empty($pasifUyari)): ?>
                    <span class="float-end small"><i class="fa-solid fa-triangle-exclamation"></i> DOSYA KAPALI / PASİF</span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="card-body">
            <?php if ($ek3MultiTabs): ?>
                <ul class="nav nav-tabs flex-nowrap overflow-auto" id="ek3FullPageTabList" role="tablist">
                    <?php foreach ($ek3Tabs as $index => $tab): ?>
                        <?php
                        $bid = (int) ($tab['bransId'] ?? 0);
                        $bname = (string) ($tab['bransName'] ?? ('Branş #' . $bid));
                        $docUrl = (string) ($tab['docUrl'] ?? '');
                        ?>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link<?= $index === 0 ? ' active' : '' ?>"
                                id="ek3-tab-<?= $bid ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#ek3-pane-<?= $bid ?>"
                                type="button"
                                role="tab"
                                aria-controls="ek3-pane-<?= $bid ?>"
                                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                            ><?= htmlspecialchars($bname, ENT_QUOTES, 'UTF-8') ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content mt-2" id="ek3FullPageTabContent">
                    <?php foreach ($ek3Tabs as $index => $tab): ?>
                        <?php
                        $bid = (int) ($tab['bransId'] ?? 0);
                        $bname = (string) ($tab['bransName'] ?? ('Branş #' . $bid));
                        $docUrl = (string) ($tab['docUrl'] ?? '');
                        ?>
                        <div
                            class="tab-pane fade<?= $index === 0 ? ' show active' : '' ?>"
                            id="ek3-pane-<?= $bid ?>"
                            role="tabpanel"
                            aria-labelledby="ek3-tab-<?= $bid ?>"
                        >
                            <iframe
                                class="esh-ek3-fullpage-frame rounded border bg-white w-100 d-block"
                                style="height: 75vh; min-height: 600px;"
                                title="<?= htmlspecialchars($bname, ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-url="<?= htmlspecialchars($docUrl, ENT_QUOTES, 'UTF-8') ?>"
                                <?php if ($index === 0 && $docUrl !== ''): ?>
                                    src="<?= htmlspecialchars($docUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    data-loaded="1"
                                <?php endif; ?>
                            ></iframe>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <a href="<?= $historyUrl ?>" class="btn btn-outline-secondary">
                    İzlem geçmişi
                </a>
            <?php else: ?>
                <iframe id="iframeEk3" title="EK-3 Önizleme" width="100%" height="600" class="rounded border bg-white"></iframe>
                <hr>
                <div class="btn-group flex-wrap gap-2">
                    <button type="button" class="btn btn-warning" data-esh-pdfmake="print">
                        <i class="fa-solid fa-print me-1"></i>Yazdır
                    </button>
                    <button type="button" class="btn btn-info" data-esh-pdfmake="download" data-esh-pdfmake-filename="<?= htmlspecialchars((string) (preg_replace('/\D/', '', (string) ($hasta->tckimlik ?? '')) ?: 'hasta'), ENT_QUOTES, 'UTF-8') ?>-EK3.pdf">
                        <i class="fa-solid fa-download me-1"></i>İndir
                    </button>
                    <a href="<?= $historyUrl ?>" class="btn btn-outline-secondary">
                        İzlem geçmişi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if ($ek3MultiTabs): ?>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    var tabList = document.getElementById('ek3FullPageTabList');
    if (!tabList) {
        return;
    }
    tabList.addEventListener('shown.bs.tab', function (event) {
        var btn = event.target;
        if (!btn || !btn.getAttribute) {
            return;
        }
        var targetSelector = btn.getAttribute('data-bs-target');
        if (!targetSelector) {
            return;
        }
        var pane = document.querySelector(targetSelector);
        if (!pane) {
            return;
        }
        var frame = pane.querySelector('iframe[data-doc-url]');
        if (!frame || frame.getAttribute('data-loaded') === '1') {
            return;
        }
        var docUrl = frame.getAttribute('data-doc-url') || '';
        if (!docUrl) {
            return;
        }
        frame.src = docUrl;
        frame.setAttribute('data-loaded', '1');
    });
})();
</script>
<?php else: ?>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    if (typeof pdfMake === 'undefined' || !document.getElementById('iframeEk3')) return;
    pdfMake.createPdf(dd).getDataUrl().then(function (url) {
        document.getElementById('iframeEk3').src = url;
    });
})();
</script>
<?php endif; ?>
</div>
