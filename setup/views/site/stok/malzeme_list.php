<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;

ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Stok', 'malzemeCreate'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success shadow-sm">
    <i class="fas fa-plus me-1"></i>Yeni Malzeme
</a>
<a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary shadow-sm">
    <i class="fas fa-boxes-stacked me-1"></i>Stok Durumu
</a>
<?php
$toolbarHtml = ob_get_clean();
?>
<?php
PageShellHelper::pageOpen(['kind' => 'list', 'module' => 'stok', 'id' => 'esh-stok-malzeme-list-root']);
PageShellHelper::pageHeader(
    'Malzeme Kartları',
    'Stok malzemelerini tanımlayın ve düzenleyin.',
    $toolbarHtml,
    ['icon' => 'fas fa-boxes-stacked']
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
                    <th>Birim</th>
                    <th class="text-end">Mevcut</th>
                    <th class="text-end">Min.</th>
                    <th>Aktif</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody id="esh-stok-malzeme-list-tbody" data-esh-fetch-url="<?= htmlspecialchars((string) ($indexRowsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <tr><td colspan="8" class="text-center text-muted py-4">Yükleniyor…</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php PageShellHelper::pageClose(); ?>
