<?php
use App\Helpers\StatsNavHelper;

$quickStatGroups = StatsNavHelper::dashboardQuickGroups();
require __DIR__ . '/partials/quick_cards_vars.php';
?>
<div class="esh-page esh-page--list esh-page-dashboard container-fluid py-4">
    <div class="row mb-4 align-items-end">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-shield text-warning me-2"></i>Yönetim Ana Sayfası</h3>
            <p class="text-muted mb-0">Operasyon özeti, hızlı yönetim kısayolları ve öne çıkan rapor bağlantıları.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= htmlspecialchars(esh_url('Stats', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-table-cells-large me-1"></i>Tüm raporlar (katalog)
            </a>
        </div>
    </div>

    <?php require __DIR__ . '/partials/dashboard_two_column.php'; ?>
</div>
