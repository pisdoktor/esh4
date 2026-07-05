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
    if (!empty($m->kod)) {
        $label = '[' . $m->kod . '] ' . $label;
    }
    $label .= ' (mevcut: ' . StokHelper::formatMiktar($m->mevcut_miktar ?? 0) . ')';
    $malzemeOpts[(string) (int) $m->id] = $label;
}

$ekipOpts = ['' => '— Opsiyonel —'];
foreach ($ekipler as $e) {
    $lbl = (string) ($e->tarih ?? '');
    if (!empty($e->ekip_no)) {
        $lbl .= ' — Ekip ' . (int) $e->ekip_no;
    }
    if (!empty($e->vardiya)) {
        $lbl .= ' (' . $e->vardiya . ')';
    }
    $ekipOpts[(string) (int) $e->id] = $lbl;
}

$todayTr = DateHelper::todayTr();
$hastaId = $preHasta ? (string) ($preHasta->id ?? '') : '';
$hastaLabel = '';
if ($preHasta) {
    $hastaLabel = trim((string) ($preHasta->isim ?? '') . ' ' . (string) ($preHasta->soyisim ?? ''))
        . ' — ' . (string) ($preHasta->tckimlik ?? '');
}
$careSummary = StokHelper::patientCareFlagsSummary($preHasta);
$lookupUrl = esh_url('Stok', 'hastaLookupAjax', ['scope' => 'aktif']);
?>
<div class="esh-page esh-page--form esh-page-stok container-fluid py-4" id="esh-stok-cikis-root"
     data-esh-hasta-lookup-url="<?= htmlspecialchars($lookupUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-warning"><i class="fas fa-truck-ramp-box me-2"></i>Stok Çıkışı / Dağıtım</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Stok', 'cikisStore'), ENT_QUOTES, 'UTF-8') ?>" method="POST" id="esh-stok-cikis-form">
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
                                    'labelClass' => 'form-label small text-muted',
                                    'type' => 'number',
                                    'required' => true,
                                    'extraAttrs' => ['min' => '0.001', 'step' => '0.001'],
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldDate('hareket_tarihi', 'Tarih', $todayTr, [
                                    'labelClass' => 'form-label small text-muted',
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted" for="esh-stok-hasta-q">Hasta (aktif, opsiyonel)</label>
                                <input type="hidden" name="hasta_id" id="esh-stok-hasta-id" value="<?= htmlspecialchars($hastaId, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="text" class="form-control form-control-sm" id="esh-stok-hasta-q"
                                       value="<?= htmlspecialchars($hastaLabel, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="TC veya ad soyad ile ara" autocomplete="off">
                                <div id="esh-stok-hasta-suggestions" class="list-group position-absolute shadow-sm d-none" style="z-index:1050;max-width:100%;"></div>
                                <div id="esh-stok-hasta-care" class="form-text small text-muted mt-1"><?= htmlspecialchars($careSummary, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldSelect(
                                    'ekip_id',
                                    'Ekip (son 30 gün)',
                                    StokHelper::toSelectOptions($ekipOpts),
                                    '',
                                    [
                                    'labelClass' => 'form-label small text-muted',
                                    'tomSelect' => false,
                                ]
                                ) ?>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldTextarea('aciklama', 'Açıklama', '', [
                                    'labelClass' => 'form-label small text-muted',
                                    'rows' => 2,
                                ]) ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-2">
                            <a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Stok durumu</a>
                            <button type="submit" class="btn btn-warning px-4"><i class="fas fa-save me-1"></i>Çıkış Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
