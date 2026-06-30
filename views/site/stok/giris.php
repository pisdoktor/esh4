<?php
declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\FormHelper;
use App\Helpers\StokHelper;

/** @var list<object> $malzemeler */
$malzemeler = $malzemeler ?? [];
$malzemeOpts = ['' => '— Malzeme seçin —'];
foreach ($malzemeler as $m) {
    $label = (string) ($m->ad ?? '');
    if (!empty($m->kod)) {
        $label = '[' . $m->kod . '] ' . $label;
    }
    $label .= ' (mevcut: ' . StokHelper::formatMiktar($m->mevcut_miktar ?? 0) . ' ' . StokHelper::birimLabel((string) ($m->birim ?? '')) . ')';
    $malzemeOpts[(string) (int) $m->id] = $label;
}
$todayTr = DateHelper::todayTr();
$malzemeSelectOptions = StokHelper::toSelectOptions($malzemeOpts);
$rowTemplate = '<div class="col-12 esh-stok-giris-line border rounded p-3 mb-2 bg-light-subtle">'
    . '<div class="row g-2 align-items-end">'
    . '<div class="col-md-5">'
    . FormHelper::fieldSelect('lines[__IDX__][malzeme_id]', 'Malzeme', $malzemeSelectOptions, '', [
        'labelClass' => 'form-label small text-muted mb-1',
        'required' => true,
        'tomSelect' => false,
    ])
    . '</div><div class="col-md-2">'
    . FormHelper::fieldInput('lines[__IDX__][miktar]', 'Miktar', '', [
        'labelClass' => 'form-label small text-muted mb-1',
        'type' => 'number',
        'required' => true,
        'extraAttrs' => ['min' => '0.001', 'step' => '0.001'],
    ])
    . '</div><div class="col-md-2">'
    . FormHelper::fieldInput('lines[__IDX__][lot_no]', 'Lot no', '', [
        'labelClass' => 'form-label small text-muted mb-1',
        'placeholder' => 'Opsiyonel',
    ])
    . '</div><div class="col-md-2">'
    . FormHelper::fieldDate('lines[__IDX__][skt]', 'SKT', '', [
        'labelClass' => 'form-label small text-muted mb-1',
    ])
    . '</div><div class="col-md-1 text-end">'
    . '<button type="button" class="btn btn-outline-danger btn-sm esh-stok-giris-remove" title="Satırı sil"><i class="fas fa-times"></i></button>'
    . '</div></div></div>';
?>
<div class="esh-page esh-page--form esh-page-stok container-fluid py-4" id="esh-stok-giris-root"
     data-esh-line-template="<?= htmlspecialchars($rowTemplate, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-success"><i class="fas fa-arrow-down me-2"></i>Stok Girişi</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" id="esh-stok-giris-add-line">
                        <i class="fas fa-plus me-1"></i>Satır ekle
                    </button>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Stok', 'girisStore'), ENT_QUOTES, 'UTF-8') ?>" method="POST" id="esh-stok-giris-form">
                        <?= esh_csrf_field() ?>
                        <div id="esh-stok-giris-lines"></div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <?= FormHelper::fieldDate('hareket_tarihi', 'Tarih', $todayTr, [
                                    'labelClass' => 'form-label small text-muted',
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldTextarea('aciklama', 'Açıklama / belge no', '', [
                                    'labelClass' => 'form-label small text-muted',
                                    'rows' => 2,
                                    'placeholder' => 'Fatura no, tedarikçi vb.',
                                ]) ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-2">
                            <a href="<?= htmlspecialchars(esh_url('Stok', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Stok durumu</a>
                            <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Girişleri Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
