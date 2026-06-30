<?php
declare(strict_types=1);

use App\Helpers\StokHelper;

/** @var string $date_from */
/** @var string $date_to */
/** @var int $mamaTotal */
/** @var int $bezTotal */
/** @var array{kritik_sayisi: int, cikis_30_gun: float, kategori: array<string, int>} $stokOzet */
/** @var list<object> $kritikItems */
$stokOzet = $stokOzet ?? ['kritik_sayisi' => 0, 'cikis_30_gun' => 0.0, 'kategori' => []];
$kritikItems = $kritikItems ?? [];
?>
<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<div class="container-fluid mt-3 esh-stats-report pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="fas fa-boxes-stacked me-2 text-warning"></i>Sarf + Stok Paneli</h1>
            <p class="text-muted small mb-0">
                Rapor bitiş aralığı: <strong><?= htmlspecialchars((string) ($date_from ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                — <strong><?= htmlspecialchars((string) ($date_to ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars((string) ($supplyReportsUrl ?? esh_url('Stats', 'supplyReports')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-baby me-1"></i>Mama / bez listesi
            </a>
            <a href="<?= htmlspecialchars((string) ($stokIndexUrl ?? esh_url('Stok', 'index')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-warehouse me-1"></i>Stok durumu
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small">Mama raporu (filtre aralığı)</div>
                    <div class="h3 fw-bold mb-0"><?= (int) ($mamaTotal ?? 0) ?></div>
                    <div class="small text-muted">hasta</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small">Bez raporu (filtre aralığı)</div>
                    <div class="h3 fw-bold mb-0"><?= (int) ($bezTotal ?? 0) ?></div>
                    <div class="small text-muted">hasta</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small">Kritik stok kalemi</div>
                    <div class="h3 fw-bold text-danger mb-0"><?= (int) ($stokOzet['kritik_sayisi'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small">30 gün çıkış (miktar)</div>
                    <div class="h3 fw-bold mb-0"><?= htmlspecialchars(StokHelper::formatMiktar($stokOzet['cikis_30_gun'] ?? 0), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0">Kritik stok — depo ihtiyacı</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Malzeme</th>
                        <th>Kategori</th>
                        <th class="text-end">Mevcut</th>
                        <th class="text-end">Min.</th>
                        <th class="text-end">Öneri</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kritikItems === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Kritik stok kaydı yok veya stok modülü kapalı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kritikItems as $row): ?>
                            <?php
                            $mevcut = (float) ($row->mevcut_miktar ?? 0);
                            $min = (float) ($row->min_stok ?? 0);
                            $oneri = max($min - $mevcut, 0);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars(StokHelper::kategoriLabel((string) ($row->kategori ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end text-danger"><?= htmlspecialchars(StokHelper::formatMiktar($mevcut), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($min), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold"><?= htmlspecialchars(StokHelper::formatMiktar($oneri), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
