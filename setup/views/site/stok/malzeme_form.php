<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\StokHelper;

/** @var \App\Models\StokMalzeme $item */
$item = $item ?? new \App\Models\StokMalzeme();
$isEdit = (int) ($item->id ?? 0) > 0;
?>
<div class="esh-page esh-page--form esh-page-stok container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-<?= $isEdit ? 'pen' : 'plus-circle' ?> me-2"></i>
                        <?= $isEdit ? 'Malzeme Düzenle' : 'Yeni Malzeme Kartı' ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Stok', 'malzemeStore'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= esh_csrf_field() ?>
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?= (int) $item->id ?>">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <?= FormHelper::fieldInput('kod', 'Stok kodu', (string) ($item->kod ?? ''), [
                                    'labelClass' => 'form-label small text-muted',
                                    'class' => 'form-control-sm',
                                    'placeholder' => 'Opsiyonel',
                                ]) ?>
                            </div>
                            <div class="col-md-8">
                                <?= FormHelper::fieldInput('ad', 'Malzeme adı', (string) ($item->ad ?? ''), [
                                    'labelClass' => 'form-label small text-muted',
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect(
                                    'kategori',
                                    'Kategori',
                                    StokHelper::toSelectOptions(StokHelper::kategoriOptions()),
                                    (string) ($item->kategori ?? 'sarf'),
                                    [
                                    'labelClass' => 'form-label small text-muted',
                                    'tomSelect' => false,
                                ]
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect(
                                    'birim',
                                    'Birim',
                                    StokHelper::toSelectOptions(StokHelper::birimOptions()),
                                    (string) ($item->birim ?? 'adet'),
                                    [
                                    'labelClass' => 'form-label small text-muted',
                                    'tomSelect' => false,
                                ]
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('min_stok', 'Kritik stok eşiği', StokHelper::formatMiktar($item->min_stok ?? 0), [
                                    'labelClass' => 'form-label small text-muted',
                                    'class' => 'form-control-sm',
                                    'type' => 'number',
                                    'extraAttrs' => ['min' => '0', 'step' => '0.001'],
                                ]) ?>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="aktif" value="1" id="esh-stok-malzeme-aktif"<?= !isset($item->aktif) || !empty($item->aktif) ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="esh-stok-malzeme-aktif">Aktif</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::fieldTextarea('aciklama', 'Açıklama', (string) ($item->aciklama ?? ''), [
                                    'labelClass' => 'form-label small text-muted',
                                    'rows' => 2,
                                    'class' => 'form-control-sm',
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('tedarikci_adi', 'Tedarikçi adı', (string) ($item->tedarikci_adi ?? ''), [
                                    'labelClass' => 'form-label small text-muted',
                                    'class' => 'form-control-sm',
                                    'placeholder' => 'Opsiyonel',
                                ]) ?>
                            </div>
                            <div class="col-md-3">
                                <?= FormHelper::fieldInput('tedarikci_tel', 'Tedarikçi telefon', (string) ($item->tedarikci_tel ?? ''), [
                                    'labelClass' => 'form-label small text-muted',
                                    'class' => 'form-control-sm',
                                    'placeholder' => 'Opsiyonel',
                                ]) ?>
                            </div>
                            <div class="col-md-3">
                                <?= FormHelper::fieldInput('birim_fiyat', 'Birim fiyat (₺)', $item->birim_fiyat !== null && $item->birim_fiyat !== '' ? (string) $item->birim_fiyat : '', [
                                    'labelClass' => 'form-label small text-muted',
                                    'class' => 'form-control-sm',
                                    'type' => 'number',
                                    'extraAttrs' => ['min' => '0', 'step' => '0.01'],
                                ]) ?>
                            </div>
                        </div>
                        <div class="esh-form-actions d-flex justify-content-between">
                            <a href="<?= htmlspecialchars(esh_url('Stok', 'malzemeList'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Listeye Dön</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
