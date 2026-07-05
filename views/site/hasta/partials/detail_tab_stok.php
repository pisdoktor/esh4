<?php
declare(strict_types=1);

use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\DateHelper;
use App\Helpers\StokHelper;
use App\Models\StokHareket;
use App\Services\Stok\StokService;

/** @var object $hasta */
$hastaId = (string) ($hasta->id ?? '');
$stokRows = [];
$stokModuleOk = $hastaId > 0
    && AppSettings::isModuleEnabled('stok')
    && StokService::moduleReady()
    && AuthHelper::can('stok.read');

if ($stokModuleOk) {
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    $stokRows = (new StokHareket())->listFiltered([
        'hasta_id' => $hastaId,
        'hareket_tipi_in' => ['cikis', 'iade'],
        'date_from' => $sixMonthsAgo,
    ], 20, 0);
}
?>
<?php if (!$stokModuleOk): ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-boxes-stacked fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Stok modülü bu kullanıcı için kullanılamıyor.</p>
    </div>
<?php elseif ($stokRows === []): ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-box-open fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Son 6 ayda sarf çıkışı / iadesi kaydı yok.</p>
    </div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small text-muted">Son 6 ay — çıkış ve iade</span>
        <a href="<?= htmlspecialchars(esh_url('Stok', 'hastaOzet', ['hasta_id' => $hastaId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning">
            Tüm geçmiş
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <th>Tip</th>
                    <th>Malzeme</th>
                    <th class="text-end">Miktar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stokRows as $row): ?>
                    <?php
                    $tip = (string) ($row->hareket_tipi ?? '');
                    $tipClass = $tip === 'cikis' ? 'text-danger' : 'text-info';
                    ?>
                    <tr>
                        <td class="text-nowrap small"><?= htmlspecialchars(DateHelper::toTrOrEmpty($row->hareket_tarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small <?= $tipClass ?>"><?= htmlspecialchars(StokHelper::hareketTipiLabel($tip), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars((string) ($row->malzeme_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end small">
                            <?= htmlspecialchars(StokHelper::formatMiktar($row->miktar ?? 0), ENT_QUOTES, 'UTF-8') ?>
                            <span class="text-muted"><?= htmlspecialchars(StokHelper::birimLabel((string) ($row->malzeme_birim ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
