    <?php require dirname(__DIR__, 4) . '/partials/admin/stats_breadcrumb.php'; ?>
    <div class="row mb-3 align-items-end">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>İstatistik merkezi</h3>
            <?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-gauge-high me-1"></i>Yönetim paneli
            </a>
        </div>
    </div>