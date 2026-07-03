<?php
/** @var int $ek3OpenVisitId */
$ek3OpenVisitId = (int) ($ek3OpenVisitId ?? 0);
?>
<div class="modal fade" id="eshEk3DocumentModal" tabindex="-1" aria-labelledby="eshEk3DocumentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fw-bold" id="eshEk3DocumentModalLabel">
                    <i class="fa-solid fa-file-lines me-2 text-primary"></i>EK-3 formu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div id="eshEk3DocumentTabsWrap" class="d-none border-bottom bg-light px-2 pt-2">
                <ul class="nav nav-tabs nav-tabs-sm flex-nowrap overflow-auto mb-0" id="eshEk3DocumentTabList" role="tablist"></ul>
            </div>
            <div class="modal-body p-0">
                <iframe id="eshEk3DocumentFrame" class="esh-ek3-document-frame d-block w-100 border-0 bg-white" title="EK-3 formu önizleme" src="about:blank"></iframe>
            </div>
            <div class="modal-footer flex-wrap gap-2 py-2">
                <a href="#" id="eshEk3DocumentNewTab" class="btn btn-outline-secondary btn-sm rounded-pill" target="_blank" rel="noopener">
                    <i class="fa-solid fa-up-right-from-square me-1"></i>Yeni pencerede aç
                </a>
                <button type="button" class="btn btn-primary btn-sm rounded-pill" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<?php if ($ek3OpenVisitId > 0): ?>
<div id="eshEk3OpenTrigger" class="d-none" data-visit-id="<?= $ek3OpenVisitId ?>"></div>
<?php endif; ?>
