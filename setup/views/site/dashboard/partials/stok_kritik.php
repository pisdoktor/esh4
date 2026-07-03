<?php
declare(strict_types=1);

use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Models\StokMalzeme;
use App\Services\Stok\StokService;

$stokKritikCount = (int) ($stokKritikCount ?? 0);
if (
    $stokKritikCount <= 0
    || !AppSettings::isModuleEnabled('stok')
    || !StokService::moduleReady()
    || !(AuthHelper::can('stok.read') || AuthHelper::can('stok.admin'))
) {
    return;
}
?>
<div class="card border-0 shadow-sm mb-3 border-start border-4 border-danger esh-dash-stok-kritik">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="small text-muted text-uppercase fw-semibold">Stok uyarısı</div>
                <div class="fw-bold text-danger"><?= $stokKritikCount ?> malzeme kritik seviyede</div>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Stok', 'index', ['kritik' => '1']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-boxes-stacked me-1"></i>Görüntüle
            </a>
        </div>
    </div>
</div>
