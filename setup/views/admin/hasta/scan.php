<div class="esh-page esh-page--list esh-page-hasta container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark  d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-microchip me-2"></i><?= htmlspecialchars($scanTitle ?? 'MERNIS Toplu Vefat Taraması') ?></h5>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="startBtn">
                    <i class="fa fa-play me-1"></i> Taramayı Başlat
                </button>
                <button type="button" class="btn btn-danger btn-sm d-none" id="stopBtn" disabled>
                    <i class="fa fa-stop me-1"></i> Taramayı Durdur
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small">
                <?= htmlspecialchars($scanInfo ?? "Bilgi: Hastalar TC Kimlik sırasına göre 20'şerli paketler halinde taranır.") ?>
            </div>

            <?php include __DIR__ . '/partials/scan_scope_filter.php'; ?>
            
            <div id="progressArea" class="mb-3 d-none">
                <div class="d-flex justify-content-between mb-1 small fw-bold">
                    <span>İşlem Durumu: <span id="statusText">Hazır</span></span>
                    <span id="percentText">%0</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 450px;">
                <table class="table table-sm table-hover border">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th>TC kimlik</th>
                            <th>Ad soyad</th>
                            <th>Anne / baba</th>
                            <th>İlçe / mahalle</th>
                            <th>Kayıt tarihi</th>
                            <th>Son izlem</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody id="logBody">
                        <tr><td colspan="7" class="text-center text-muted italic">Henüz tarama başlatılmadı...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.totalRecords = <?= json_encode((int) ($totalCount ?? 0), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.scanScope = <?= json_encode((string) ($scanScope ?? 'active'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.scanAction = <?= json_encode((string) ($scanAction ?? 'bulkDiedScan'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.scanConfirm = <?= json_encode((string) ($scanConfirm ?? 'Tarama baslatılsın mı?'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.bulkDiedScanUrl = <?= json_encode(esh_url('Patient', 'bulkDiedScan'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<?php
$__eshPatientScanJs = (($scanScope ?? 'active') === 'waiting') ? 'patient-scanwaiting' : 'patient-scan';
$GLOBALS['eshPatientScanScript'] = $__eshPatientScanJs;
?>
<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/' . $__eshPatientScanJs . '.js') ?>
