<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
$miss_outer = $missedOuterClass ?? 'container-fluid py-4';
$miss_card = $missedCardClass ?? 'card shadow-sm border-0 rounded-3';
?>
<div class="<?= htmlspecialchars((string) $miss_outer, ENT_QUOTES, 'UTF-8') ?>">
    <div class="<?= htmlspecialchars((string) $miss_card, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="m-0 fw-bold text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Yapılmayan izlemler
                </h5>
                <small class="text-muted font-monospace">TC: <?= htmlspecialchars($tc ?? '') ?></small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php $hid = isset($patientIdForHeader) ? trim((string) $patientIdForHeader) : ''; ?>
                <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $hid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary rounded-pill <?= $hid === '' ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-id-card me-1"></i>Hasta kartı
                </a>
                <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($tc ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Tüm izlem geçmişi
                </a>
                <a href="<?= htmlspecialchars(esh_url('Visit', 'create', ['tc' => urlencode($tc ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-success rounded-pill">
                    <i class="fa-solid fa-plus me-1"></i>Yeni izlem kaydı
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Bu listede yalnızca <strong>yapılmadı</strong> (<code>yapildimi = 0</code>) olarak işaretli izlem satırları gösterilir; en eski kayıt üstte.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>İzlem tarihi</th>
                            <th>Hasta</th>
                            <th>TC</th>
                            <th>Yapılması gereken işlem(ler)</th>
                            <th>Zaman</th>
                            <th>İzlemi yapan(lar)</th>
                            <th>Araç</th>
                            <th>Not / neden</th>
                            <th class="text-end" style="width:1%">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="esh-visit-missed-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($missedRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-visit-missed-loading-row">
                            <td colspan="9" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Yapılmayan izlemler yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php $missedPagelink = esh_url('Visit', 'missed', ['tc' => (string) ($tc ?? '')]); ?>
            <div class="card-footer bg-white border-top-0 py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="small text-muted">
                            <?= \App\Helpers\PaginationHelper::infoText((int) ($total ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50)) ?>
                        </div>
                        <div>
                            <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 50), $missedPagelink) ?>
                        </div>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::render((int) ($total ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50), $missedPagelink) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>