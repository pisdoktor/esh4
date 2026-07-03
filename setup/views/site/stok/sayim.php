<?php
declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\FormHelper;
use App\Helpers\StokHelper;

/** @var list<object> $malzemeler */
$malzemeler = $malzemeler ?? [];
$malzemeOpts = ['' => '— Malzeme seçin —'];
$stockMap = [];
foreach ($malzemeler as $m) {
    $id = (string) (int) $m->id;
    $label = (string) ($m->ad ?? '');
    if (!empty($m->kod)) {
        $label = '[' . $m->kod . '] ' . $label;
    }
    $mevcut = (float) ($m->mevcut_miktar ?? 0);
    $stockMap[$id] = $mevcut;
    $label .= ' (sistem: ' . StokHelper::formatMiktar($mevcut) . ' ' . StokHelper::birimLabel((string) ($m->birim ?? '')) . ')';
    $malzemeOpts[$id] = $label;
}
$todayTr = DateHelper::todayTr();
$malzemeSelectOptions = StokHelper::toSelectOptions($malzemeOpts);
?>
<div class="esh-page esh-page--form esh-page-stok container-fluid py-4" id="esh-stok-sayim-root"
     data-esh-stock-map="<?= htmlspecialchars(json_encode($stockMap, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-clipboard-check me-2"></i>Stok Sayımı / Düzeltme</h5>
                    <p class="small text-muted mb-0 mt-1">Fark otomatik giriş veya çıkış hareketi olarak «Sayım düzeltmesi» notuyla kaydedilir.</p>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Stok', 'sayimStore'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= esh_csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <?= FormHelper::fieldSelect(
                                    'malzeme_id',
                                    'Malzeme',
                                    $malzemeSelectOptions,
                                    '',
                                    [
                                        'labelClass' => 'form-label small text-muted',
                                        'required' => true,
                                        'tomSelect' => false,
                                        'extraAttrs' => ['id' => 'esh-stok-sayim-malzeme'],
                                    ]
                                ) ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Sistemdeki miktar</label>
                                <input type="text" class="form-control" id="esh-stok-sayim-sistem" readonly value="—">
                            </div>
                            <div class="col-md-4">
                                <?= FormHelper::fieldInput('sayilan_miktar', 'Sayılan miktar', '', [
                                    'labelClass' => 'form-label small text-muted',
                                    'type' => 'number',
                                    'required' => true,
                                    'extraAttrs' => ['min' => '0', 'step' => '0.001', 'id' => 'esh-stok-sayim-sayilan'],
                                ]) ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Fark</label>
                                <input type="text" class="form-control" id="esh-stok-sayim-fark" readonly value="—">
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldDate('hareket_tarihi', 'Sayım tarihi', $todayTr, [
                                    'labelClass' => 'form-label small text-muted',
                                    'required' => true,
                                ]) ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-2">
                            <a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Stok durumu</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Sayımı Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
