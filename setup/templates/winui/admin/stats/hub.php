<div class="fluent-page fluent-admin-shell container-fluid mt-0 py-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
    <div class="row mb-3 align-items-end">
        <div class="col-md-8">
            <h3 class="fluent-page-title fw-bold mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>İstatistik merkezi</h3>
            <?php require ROOT_PATH . '/views/partials/admin/stats_page_intro.php'; ?>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-gauge-high me-1"></i>Yönetim paneli
            </a>
        </div>
    </div>

    <?php require ROOT_PATH . '/views/admin/stats/partials/hub_groups_grid.php'; ?>
</div>
