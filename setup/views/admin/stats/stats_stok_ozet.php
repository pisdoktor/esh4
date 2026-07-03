<?php
declare(strict_types=1);

use App\Helpers\StokHelper;

/** @var array{kritik_sayisi: int, cikis_30_gun: float, kategori: array<string, int>} $ozet */
/** @var list<object> $kritikItems */
$ozet = $ozet ?? ['kritik_sayisi' => 0, 'cikis_30_gun' => 0.0, 'kategori' => []];
$kritikItems = $kritikItems ?? [];
?>
<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<div class="container-fluid mt-3 esh-stats-report pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="fas fa-warehouse me-2 text-secondary"></i>Stok Özeti</h1>
            <p class="text-muted small mb-0">Kritik stok, son 30 gün çıkış hacmi ve kategori dağılımı.</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-boxes-stacked me-1"></i>Stok modülü
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Kritik malzeme</div>
                    <div class="display-6 fw-bold text-danger"><?= (int) ($ozet['kritik_sayisi'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Son 30 gün çıkış (toplam miktar)</div>
                    <div class="display-6 fw-bold text-primary"><?= htmlspecialchars(StokHelper::formatMiktar($ozet['cikis_30_gun'] ?? 0), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Kategori kırılımı (aktif kart)</div>
                    <?php if (empty($ozet['kategori'])): ?>
                        <span class="text-muted small">Veri yok</span>
                    <?php else: ?>
                        <ul class="list-unstyled small mb-0">
                            <?php foreach ($ozet['kategori'] as $kat => $cnt): ?>
                                <li class="d-flex justify-content-between">
                                    <span><?= htmlspecialchars(StokHelper::kategoriLabel((string) $kat), ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= (int) $cnt ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0">Kritik stoktaki malzemeler</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Malzeme</th>
                        <th>Kategori</th>
                        <th class="text-end">Mevcut</th>
                        <th class="text-end">Min.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kritikItems === []): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Kritik malzeme yok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kritikItems as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars(StokHelper::kategoriLabel((string) ($row->kategori ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end text-danger fw-semibold"><?= htmlspecialchars(StokHelper::formatMiktar($row->mevcut_miktar ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($row->min_stok ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
