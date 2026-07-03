<?php
declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\PageShellHelper;
use App\Helpers\StokHelper;

/** @var object $hasta */
/** @var list<object> $items */
$hastaId = (int) ($hasta->id ?? 0);
$hastaAd = trim((string) ($hasta->isim ?? '') . ' ' . (string) ($hasta->soyisim ?? ''));
$items = $items ?? [];
?>
<div class="esh-page esh-page--list esh-page-stok container-fluid py-4">
<?php
PageShellHelper::pageOpen([
    'kind' => 'list',
    'module' => 'stok',
    'id' => 'esh-stok-hasta-ozet-root',
]);
ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $hastaId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary shadow-sm">
    <i class="fas fa-user me-1"></i>Hasta kartı
</a>
<?php if (
    (\App\Models\Patient::isAktif($hasta->pasif ?? null))
    && (\App\Helpers\AuthHelper::can('stok.create') || \App\Helpers\AuthHelper::can('stok.admin'))
): ?>
<a href="<?= htmlspecialchars(esh_url('Stok', 'cikis', ['hasta_id' => $hastaId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning shadow-sm">
    <i class="fas fa-truck-ramp-box me-1"></i>Yeni çıkış
</a>
<?php endif; ?>
<?php
$toolbarHtml = ob_get_clean();
PageShellHelper::pageHeader(
    'Stok / Sarf Tüketimi',
    htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') . ' — son 6 ay çıkış ve iade kayıtları.',
    $toolbarHtml,
    ['icon' => 'fas fa-boxes-stacked']
);
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <th>Tip</th>
                    <th>Malzeme</th>
                    <th class="text-end">Miktar</th>
                    <th>Kullanıcı</th>
                    <th>Not</th>
                </tr>
            </thead>
            <tbody id="esh-stok-hasta-ozet-tbody" data-esh-fetch-url="<?= htmlspecialchars((string) ($hastaOzetRowsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?php include __DIR__ . '/partials/hasta_stok_rows.php'; ?>
            </tbody>
        </table>
    </div>
</div>
<?php PageShellHelper::pageClose(); ?>
</div>
