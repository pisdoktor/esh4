<?php
declare(strict_types=1);
?>
<div class="esh-page container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 mb-1 text-primary">
                <i class="fa-solid fa-building-circle-arrow-right me-2"></i>Gelen nakil talepleri
            </h1>
            <p class="text-muted small mb-0">Başka kurumlardan onayınıza sunulan hasta nakil talepleri.</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Patient', 'unified', ['status' => 'waiting']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-user-clock me-1"></i>Bekleyen hastalar
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Hasta</th>
                        <th>TC</th>
                        <th>Tip</th>
                        <th>Kaynak kurum</th>
                        <?php if (\App\Helpers\AuthHelper::sessionIsSuperAdmin()): ?>
                            <th>Hedef kurum</th>
                        <?php endif; ?>
                        <th>Talep tarihi</th>
                        <th class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody id="esh-nakil-incoming-tbody"
                       data-esh-fetch-url="<?= htmlspecialchars($incomingRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <tr class="esh-nakil-incoming-loading-row">
                        <td colspan="<?= \App\Helpers\AuthHelper::sessionIsSuperAdmin() ? 7 : 6 ?>" class="text-center text-muted py-5 border-0">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Liste yükleniyor…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
