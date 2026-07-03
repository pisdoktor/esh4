<?php
declare(strict_types=1);

use App\Helpers\PageShellHelper;
use App\Helpers\StokHelper;

/** @var list<object> $items */
$items = $items ?? [];
ob_start();
$eshListPdfBtnId = 'esh-stok-siparis-pdf-btn';
$eshListPdfBtnDisabled = empty($items);
include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
?>
<a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary shadow-sm">
    <i class="fas fa-boxes-stacked me-1"></i>Stok durumu
</a>
<?php
$toolbarHtml = ob_get_clean();
?>
<div class="esh-page esh-page--list esh-page-stok container-fluid py-4">
<?php
PageShellHelper::pageOpen([
    'kind' => 'list',
    'module' => 'stok',
    'id' => 'esh-stok-siparis-root',
    'attrs' => [
        'data-esh-pdf-url' => (string) ($siparisExportDataUrl ?? ''),
    ],
]);
PageShellHelper::pageHeader(
    'Sipariş Önerisi',
    'Kritik eşiğin altındaki malzemeler için önerilen sipariş miktarları (bilgi amaçlı).',
    $toolbarHtml,
    ['icon' => 'fas fa-cart-shopping']
);
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Malzeme</th>
                    <th>Kategori</th>
                    <th class="text-end">Mevcut</th>
                    <th class="text-end">Min.</th>
                    <th class="text-end text-primary">Öneri</th>
                    <th>Tedarikçi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sipariş önerisi gerektiren malzeme yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td class="text-muted small"><?= htmlspecialchars((string) ($row->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small"><?= htmlspecialchars(StokHelper::kategoriLabel((string) ($row->kategori ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($row->mevcut_miktar ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($row->min_stok ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end fw-semibold text-primary"><?= htmlspecialchars(StokHelper::formatMiktar($row->oneri_miktar ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small">
                                <?= htmlspecialchars((string) ($row->tedarikci_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($row->tedarikci_tel)): ?>
                                    <span class="text-muted d-block"><?= htmlspecialchars((string) $row->tedarikci_tel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require ROOT_PATH . '/views/site/partials/list_pdf_assets.php';
PageShellHelper::pageClose();
?>
</div>
