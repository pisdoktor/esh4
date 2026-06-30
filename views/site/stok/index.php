<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;
use App\Helpers\StokHelper;

$canAdmin = AuthHelper::can('stok.admin');
$canCreate = AuthHelper::can('stok.create') || $canAdmin;
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'kategori' => trim((string) ($_GET['kategori'] ?? '')),
    'kritik_only' => isset($_GET['kritik']) && (string) $_GET['kritik'] === '1',
];

ob_start();
if ($canCreate) {
    ?>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'cikis'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning shadow-sm">
        <i class="fas fa-truck-ramp-box me-1"></i>Çıkış / Dağıtım
    </a>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'iade'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info shadow-sm">
        <i class="fas fa-rotate-left me-1"></i>İade
    </a>
    <?php
}
if ($canAdmin) {
    ?>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'giris'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success shadow-sm">
        <i class="fas fa-arrow-down me-1"></i>Stok Girişi
    </a>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'sayim'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary shadow-sm">
        <i class="fas fa-clipboard-check me-1"></i>Sayım
    </a>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'siparisOneri'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning shadow-sm">
        <i class="fas fa-cart-shopping me-1"></i>Sipariş önerisi
    </a>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'malzemeList'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary shadow-sm">
        <i class="fas fa-boxes-stacked me-1"></i>Malzeme Kartları
    </a>
    <?php
}
?>
<a href="<?= htmlspecialchars(esh_url('Stok', 'hareketler'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary shadow-sm">
    <i class="fas fa-clock-rotate-left me-1"></i>Hareketler
</a>
<?php
$toolbarHtml = ob_get_clean();
$criticalCount = (int) ($criticalCount ?? 0);
?>
<?php if ($criticalCount > 0): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-3">
    <span><i class="fas fa-triangle-exclamation me-2"></i><strong><?= (int) $criticalCount ?></strong> malzeme kritik stok seviyesinde.</span>
    <a href="<?= htmlspecialchars(esh_url('Stok', 'index', ['kritik' => '1']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-danger">Kritik listesi</a>
</div>
<?php endif; ?>
<?php
ob_start();
if ($canAdmin) {
    $eshListPdfBtnId = 'esh-stok-index-pdf-btn';
    $eshListPdfBtnDisabled = true;
    include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
}
$exportToolbar = ob_get_clean();
$toolbarHtml = $exportToolbar . $toolbarHtml;
?>
<?php
PageShellHelper::pageOpen([
    'kind' => 'list',
    'module' => 'stok',
    'id' => 'esh-stok-index-root',
    'attrs' => [
        'data-esh-pdf-url' => (string) ($indexExportDataUrl ?? ''),
    ],
]);
PageShellHelper::pageHeader(
    'Stok Durumu',
    'Kurum malzeme bakiyeleri ve kritik stok uyarıları.',
    $toolbarHtml,
    ['icon' => 'fas fa-boxes-stacked']
);
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
            <?= esh_form_route_hiddens('Stok', 'index') ?>
            <div class="col-md-4">
                <?= FormHelper::fieldInput('search', 'Arama', (string) ($filters['search'] ?? ''), [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm',
                    'placeholder' => 'Malzeme adı veya kod',
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= FormHelper::fieldSelect(
                    'kategori',
                    'Kategori',
                    StokHelper::toSelectOptions(array_merge(['' => 'Tümü'], StokHelper::kategoriOptions())),
                    (string) ($filters['kategori'] ?? ''),
                    [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-select-sm',
                    'tomSelect' => false,
                ]
                ) ?>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="kritik" value="1" id="esh-stok-kritik-filter"<?= !empty($filters['kritik_only']) ? ' checked' : '' ?>>
                    <label class="form-check-label small" for="esh-stok-kritik-filter">Yalnız kritik stok</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrele</button>
            </div>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Malzeme</th>
                    <th>Kategori</th>
                    <th>Birim</th>
                    <th class="text-end">Mevcut</th>
                    <th class="text-end">Min.</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody id="esh-stok-list-tbody" data-esh-fetch-url="<?= htmlspecialchars((string) ($indexRowsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <tr><td colspan="7" class="text-center text-muted py-4">Yükleniyor…</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php
if ($canAdmin) {
    require ROOT_PATH . '/views/site/partials/list_pdf_assets.php';
}
PageShellHelper::pageClose(); ?>
