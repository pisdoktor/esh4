<?php
use App\Models\Erapor;
use App\Helpers\FormHelper;

/** @var Erapor|null $item düzenleme modunda dolu */
$isEdit = isset($item) && $item instanceof Erapor && !empty($item->id);
$basvuruTr = $isEdit
    ? \App\Helpers\DateHelper::toTrOrEmpty((string) ($item->basvurutarihi ?? ''))
    : \App\Helpers\DateHelper::todayTr();
$eshEraporPatientLocked = $isEdit && (int) ($item->kayitlimi ?? 0) === 1;
?>
<div class="esh-page esh-page--form esh-page-erapor container py-4">
    <div id="esh-erapor-havuz-tc-alert" class="alert alert-warning border-warning shadow-sm col-md-8 mx-auto mb-3 d-none" role="alert" aria-live="polite">
        <div class="d-flex align-items-start gap-2">
            <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
            <div id="esh-erapor-havuz-tc-alert-text" class="small mb-0"></div>
        </div>
    </div>
    <div class="card shadow-sm border-0 col-md-8 mx-auto">
        <div class="card-header bg-primary  py-3">
            <h5 class="mb-0"><i class="fas fa-file-medical me-2"></i><?= $isEdit ? 'Rapor Kaydı Düzenle' : 'Yeni Rapor Kaydı Girişi' ?></h5>
        </div>
        <div class="card-body p-4">
            <form action="<?= htmlspecialchars(esh_url('Erapor', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST" id="erapor-create-form"
                  data-erapor-patient-locked="<?= $eshEraporPatientLocked ? '1' : '0' ?>"
                  data-erapor-edit-id="<?= $isEdit ? htmlspecialchars((string) $item->id, ENT_QUOTES, 'UTF-8') : '' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $item->id, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <?= FormHelper::fieldInput('hastatckimlik', 'Hasta T.C. Kimlik No *', $isEdit ? (string) $item->hastatckimlik : '', [
                            'labelClass' => 'form-label fw-bold text-danger',
                            'maxlength' => '11',
                            'required' => true,
                            'placeholder' => '11 haneli TC No',
                            'autocomplete' => 'off',
                            'afterInput' => '<div class="form-text d-flex flex-wrap align-items-center gap-2 mt-1" id="tcLookupMeta"><span id="tcLookupHelp">TC girildiğinde otomatik kontrol yapılır.</span><span id="tcLookupStatusBadge" class="badge d-none" role="status" aria-live="polite"></span></div>',
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= FormHelper::fieldInput('ceptel1', 'Cep Telefonu', $isEdit ? (string) ($item->ceptel1 ?? '') : '', [
                            'labelClass' => 'form-label fw-bold',
                            'type' => 'tel',
                            'placeholder' => '05XX XXX XX XX',
                        ]) ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <?= FormHelper::fieldInput('isim', 'Adı', $isEdit ? (string) ($item->isim ?? '') : '', [
                            'labelClass' => 'form-label fw-bold',
                            'labelHtml' => 'Adı <span class="text-muted small fw-normal">(büyük harf)</span>',
                            'id' => 'erapor-field-isim',
                            'class' => 'erapor-upper erapor-patient-auto' . ($eshEraporPatientLocked ? ' erapor-patient-locked' : ''),
                            'required' => true,
                            'maxlength' => '120',
                            'autocomplete' => 'given-name',
                            'placeholder' => 'ÖRN: AYŞE',
                            'extraAttrs' => array_filter([
                                'readonly' => $eshEraporPatientLocked ? 'readonly' : null,
                            ]),
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= FormHelper::fieldInput('soyisim', 'Soyadı', $isEdit ? (string) ($item->soyisim ?? '') : '', [
                            'labelClass' => 'form-label fw-bold',
                            'labelHtml' => 'Soyadı <span class="text-muted small fw-normal">(büyük harf)</span>',
                            'id' => 'erapor-field-soyisim',
                            'class' => 'erapor-upper erapor-patient-auto' . ($eshEraporPatientLocked ? ' erapor-patient-locked' : ''),
                            'required' => true,
                            'maxlength' => '120',
                            'autocomplete' => 'family-name',
                            'placeholder' => 'ÖRN: YILMAZ',
                            'extraAttrs' => array_filter([
                                'readonly' => $eshEraporPatientLocked ? 'readonly' : null,
                            ]),
                        ]) ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <?php
                    $eshKayitlimiSelected = $isEdit ? (string) (int) ($item->kayitlimi ?? 0) : '0';
                    $eshKayitlimiOptions = [
                        FormHelper::makeOption('0', 'Hayır (Yeni Kayıt)'),
                        FormHelper::makeOption('1', 'Evet (Mevcut Hasta)'),
                    ];
                    $eshKayitlimiAfter = '';
                    if ($eshEraporPatientLocked) {
                        $eshKayitlimiAfter .= '<input type="hidden" name="kayitlimi" value="1" id="erapor-kayitlimi-hidden">';
                    }
                    $eshKayitlimiAfter .= '<div class="form-text small text-muted erapor-patient-locked-hint' . ($eshEraporPatientLocked ? '' : ' d-none') . '">Hasta sistemde kayıtlı; ad, soyad ve kayıt durumu değiştirilemez.</div>';
                    ?>
                    <div class="col-md-6">
                        <?= FormHelper::fieldSelect('kayitlimi', 'Sistemde Kayıtlı mı?', $eshKayitlimiOptions, $eshKayitlimiSelected, [
                            'col' => '',
                            'id' => 'erapor-field-kayitlimi',
                            'labelClass' => 'form-label fw-bold',
                            'class' => 'erapor-patient-auto' . ($eshEraporPatientLocked ? ' erapor-patient-locked' : ''),
                            'tomSelect' => false,
                            'extraAttrs' => $eshEraporPatientLocked ? ['disabled' => 'disabled'] : [],
                            'afterInput' => $eshKayitlimiAfter,
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $eshYenilendimiSelected = $isEdit ? (string) (int) ($item->yenilendimi ?? 0) : '0';
                        echo FormHelper::fieldSelect('yenilendimi', 'Rapor Yenilendi mi?', [
                            FormHelper::makeOption('0', 'Hayır'),
                            FormHelper::makeOption('1', 'Evet'),
                        ], $eshYenilendimiSelected, [
                            'col' => '',
                            'labelClass' => 'form-label fw-bold',
                            'tomSelect' => false,
                        ]);
                        ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <?php
                        $eshBransOptions = [FormHelper::makeOption('', 'Seçiniz...')];
                        $brStored = $isEdit ? trim((string) ($item->brans ?? '')) : '';
                        foreach ($branslar as $b) {
                            $optBransAdi = (string) ($b->bransadi ?? '');
                            $optId = (int) ($b->id ?? 0);
                            $eshBransOptions[] = FormHelper::makeOption((string) $optId, $optBransAdi);
                        }
                        $eshBransSelected = '';
                        if ($isEdit && $brStored !== '') {
                            foreach ($branslar as $b) {
                                $optBransAdi = (string) ($b->bransadi ?? '');
                                $optId = (int) ($b->id ?? 0);
                                if ($brStored === $optBransAdi || (string) (int) $brStored === (string) $optId || $brStored === (string) $optId) {
                                    $eshBransSelected = (string) $optId;
                                    break;
                                }
                            }
                        }
                        echo FormHelper::fieldSelect('brans', 'Rapor Branşı / Türü', $eshBransOptions, $eshBransSelected, [
                            'col' => '',
                            'labelClass' => 'form-label fw-bold',
                            'tomSelect' => false,
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?= FormHelper::fieldDate('basvurutarihi', 'Rapor Tarihi', $basvuruTr, [
                            'labelClass' => 'form-label fw-bold',
                            'rawValue' => $basvuruTr,
                            'required' => true,
                            'extraAttrs' => ['maxlength' => '10'],
                        ]) ?>
                    </div>
                </div>

                <div class="mb-4">
                    <?= FormHelper::fieldTextarea('neden', 'Notlar / Neden', $isEdit ? (string) ($item->neden ?? '') : '', [
                        'labelClass' => 'form-label fw-bold',
                        'rows' => 3,
                        'placeholder' => 'Notlarınızı yazın...',
                    ]) ?>
                </div>

                <div class="d-flex justify-content-between border-top pt-3">
                    <a href="<?= htmlspecialchars(esh_url('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Vazgeç</a>
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i><?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
