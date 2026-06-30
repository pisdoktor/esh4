<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;
use App\Helpers\StokHelper;

/** @var list<object> $malzemeler */
$malzemeler = $malzemeler ?? [];
/** @var list<object> $items */
$items = $items ?? [];
$filters = $filters ?? [];

$malzemeOpts = ['' => 'Tüm malzemeler'];
foreach ($malzemeler as $m) {
    $malzemeOpts[(string) (int) $m->id] = (string) ($m->ad ?? '');
}
$tipOpts = array_merge(['' => 'Tüm tipler'], StokHelper::hareketTipiOptions());

ob_start();
$eshListPdfBtnId = 'esh-stok-hareketler-pdf-btn';
$eshListPdfBtnDisabled = false;
include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
?>
<a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary shadow-sm">
    <i class="fas fa-boxes-stacked me-1"></i>Stok Durumu
</a>
<?php
$toolbarHtml = ob_get_clean();
?>
<?php
PageShellHelper::pageOpen(['kind' => 'list', 'module' => 'stok', 'id' => 'esh-stok-hareketler-root', 'attrs' => [
    'data-esh-pdf-url' => (string) ($hareketlerExportDataUrl ?? ''),
]]);
PageShellHelper::pageHeader(
    'Stok Hareketleri',
    'Giriş, çıkış ve iade kayıtlarının geçmişi.',
    $toolbarHtml,
    ['icon' => 'fas fa-clock-rotate-left']
);
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= htmlspecialchars(esh_url('Stok', 'hareketler'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
            <?= esh_form_route_hiddens('Stok', 'hareketler') ?>
            <div class="col-md-3">
                <?= FormHelper::fieldSelect(
                    'malzeme_id',
                    'Malzeme',
                    StokHelper::toSelectOptions($malzemeOpts),
                    (string) ($filters['malzeme_id'] ?? ''),
                    [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-select-sm',
                    'tomSelect' => false,
                ]
                ) ?>
            </div>
            <div class="col-md-2">
                <?= FormHelper::fieldSelect(
                    'hareket_tipi',
                    'Tip',
                    StokHelper::toSelectOptions($tipOpts),
                    (string) ($filters['hareket_tipi'] ?? ''),
                    [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-select-sm',
                    'tomSelect' => false,
                ]
                ) ?>
            </div>
            <div class="col-md-2">
                <?= FormHelper::fieldDate('date_from', 'Başlangıç', (string) ($filters['date_from_tr'] ?? ''), [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm',
                ]) ?>
            </div>
            <div class="col-md-2">
                <?= FormHelper::fieldDate('date_to', 'Bitiş', (string) ($filters['date_to_tr'] ?? ''), [
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm',
                ]) ?>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
            </div>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <th>Tip</th>
                    <th>Malzeme</th>
                    <th class="text-end">Miktar</th>
                    <th>Hasta</th>
                    <th>Ekip</th>
                    <th>Kullanıcı</th>
                    <th>Not</th>
                </tr>
            </thead>
            <tbody id="esh-stok-hareketler-tbody">
                <?php include __DIR__ . '/partials/hareketler_rows.php'; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require ROOT_PATH . '/views/site/partials/list_pdf_assets.php';
PageShellHelper::pageClose(); ?>
