<?php
declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\FormHelper;
use App\Helpers\StokHelper;

/** @var list<object> $malzemeler */
$malzemeler = $malzemeler ?? [];
/** @var list<object> $ekipler */
$ekipler = $ekipler ?? [];
/** @var object|null $preHasta */
$preHasta = $preHasta ?? null;

$malzemeOpts = ['' => '— Malzeme seçin —'];
foreach ($malzemeler as $m) {
    $label = (string) ($m->ad ?? '');
    $malzemeOpts[(string) (int) $m->id] = $label;
}

$ekipOpts = ['' => '— Opsiyonel —'];
foreach ($ekipler as $e) {
    $lbl = (string) ($e->tarih ?? '') . ' — Ekip ' . (int) ($e->ekip_no ?? 0);
    $ekipOpts[(string) (int) $e->id] = $lbl;
}

$todayTr = DateHelper::todayTr();
$hastaId = (int) ($preHasta->id ?? 0);
$hastaLabel = $preHasta
    ? trim((string) ($preHasta->isim ?? '') . ' ' . (string) ($preHasta->soyisim ?? ''))
    : '';
$lookupUrl = esh_url('Stok', 'hastaLookupAjax');
?>
<div class="esh-page esh-page--form esh-page-stok container-fluid py-4" id="esh-stok-iade-root"
     data-esh-hasta-lookup-url="<?= htmlspecialchars($lookupUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-info"><i class="fas fa-rotate-left me-2"></i>Stok İadesi</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Stok', 'iadeStore'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= esh_csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <?= FormHelper::fieldSelect(
                                    'malzeme_id',
                                    'Malzeme',
                                    StokHelper::toSelectOptions($malzemeOpts),
                                    '',
                                    [
                                    'labelClass' => 'form-label small text-muted',
                                    'required' => true,
                                    'tomSelect' => false,
                                ]
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('miktar', 'Miktar', '', [
                                    'type' => 'number',
                                    'required' => true,
                                    'extraAttrs' => ['min' => '0.001', 'step' => '0.001'],
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldDate('hareket_tarihi', 'Tarih', $todayTr, ['required' => true]) ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted" for="esh-stok-iade-hasta-q">Hasta (opsiyonel)</label>
                                <input type="hidden" name="hasta_id" id="esh-stok-hasta-id" value="<?= $hastaId > 0 ? $hastaId : '' ?>">
                                <input type="text" class="form-control form-control-sm" id="esh-stok-hasta-q"
                                       value="<?= htmlspecialchars($hastaLabel, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="TC veya ad soyad" autocomplete="off">
                                <div id="esh-stok-hasta-suggestions" class="list-group position-absolute shadow-sm d-none" style="z-index:1050;"></div>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldSelect(
                                    'ekip_id',
                                    'Ekip',
                                    StokHelper::toSelectOptions($ekipOpts),
                                    '',
                                    ['tomSelect' => false]
                                ) ?>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldTextarea('aciklama', 'Açıklama', '', ['rows' => 2]) ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-2">
                            <a href="<?= htmlspecialchars(esh_url('Stok', 'hareketler'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Hareketler</a>
                            <button type="submit" class="btn btn-info px-4 text-white"><i class="fas fa-save me-1"></i>İade Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
