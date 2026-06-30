<?php
use App\Models\Erapor;

/** @var Erapor|null $item düzenleme modunda dolu */
$isEdit = isset($item) && $item instanceof Erapor && !empty($item->id);
$basvuruTr = $isEdit
    ? \App\Helpers\DateHelper::toTrOrEmpty((string) ($item->basvurutarihi ?? ''))
    : \App\Helpers\DateHelper::todayTr();
$eshEraporPatientLocked = $isEdit && (int) ($item->kayitlimi ?? 0) === 1;
?>
<div class="container mt-4 au-page-erapor-create">
    <div class="au-panel col-md-8 mx-auto overflow-hidden">
        <div class="au-panel__head">
            <span class="au-icon-chip au-icon-chip--grad"><i class="fas fa-file-medical"></i></span>
            <h2 class="au-panel__title mb-0"><?= $isEdit ? 'Rapor Kaydı Düzenle' : 'Yeni Rapor Kaydı Girişi' ?></h2>
        </div>
        <div class="au-panel__body p-4">
            <form action="<?= htmlspecialchars(esh_url('Erapor', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST" id="erapor-create-form" data-erapor-patient-locked="<?= $eshEraporPatientLocked ? '1' : '0' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $item->id ?>">
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger">Hasta T.C. Kimlik No *</label>
                        <input type="text" name="hastatckimlik" class="form-control" maxlength="11" required placeholder="11 haneli TC No" autocomplete="off"
                               value="<?= $isEdit ? htmlspecialchars((string) $item->hastatckimlik, ENT_QUOTES, 'UTF-8') : '' ?>">
                        <div class="form-text d-flex flex-wrap align-items-center gap-2 mt-1" id="tcLookupMeta">
                            <span id="tcLookupHelp">TC girildiğinde otomatik kontrol yapılır.</span>
                            <span id="tcLookupStatusBadge" class="badge d-none" role="status" aria-live="polite"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cep Telefonu</label>
                        <input type="tel" name="ceptel1" class="form-control" placeholder="05XX XXX XX XX"
                               value="<?= $isEdit ? htmlspecialchars((string) ($item->ceptel1 ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Adı <span class="text-muted small fw-normal">(büyük harf)</span></label>
                        <input type="text" name="isim" id="erapor-field-isim" class="form-control erapor-upper erapor-patient-auto<?= $eshEraporPatientLocked ? ' erapor-patient-locked' : '' ?>" required maxlength="120" autocomplete="given-name" placeholder="ÖRN: AYŞE"
                               value="<?= $isEdit ? htmlspecialchars((string) ($item->isim ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>"<?= $eshEraporPatientLocked ? ' readonly' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Soyadı <span class="text-muted small fw-normal">(büyük harf)</span></label>
                        <input type="text" name="soyisim" id="erapor-field-soyisim" class="form-control erapor-upper erapor-patient-auto<?= $eshEraporPatientLocked ? ' erapor-patient-locked' : '' ?>" required maxlength="120" autocomplete="family-name" placeholder="ÖRN: YILMAZ"
                               value="<?= $isEdit ? htmlspecialchars((string) ($item->soyisim ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>"<?= $eshEraporPatientLocked ? ' readonly' : '' ?>>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Sistemde Kayıtlı mı?</label>
                        <select name="kayitlimi" id="erapor-field-kayitlimi" class="form-select erapor-patient-auto<?= $eshEraporPatientLocked ? ' erapor-patient-locked' : '' ?>"<?= $eshEraporPatientLocked ? ' disabled' : '' ?>>
                            <option value="0" <?= $isEdit && (int) ($item->kayitlimi ?? 0) === 0 ? 'selected' : (!$isEdit ? 'selected' : '') ?>>Hayır (Yeni Kayıt)</option>
                            <option value="1" <?= $isEdit && (int) ($item->kayitlimi ?? 0) === 1 ? 'selected' : '' ?>>Evet (Mevcut Hasta)</option>
                        </select>
                        <?php if ($eshEraporPatientLocked): ?>
                            <input type="hidden" name="kayitlimi" value="1" id="erapor-kayitlimi-hidden">
                        <?php endif; ?>
                        <div class="form-text small text-muted erapor-patient-locked-hint<?= $eshEraporPatientLocked ? '' : ' d-none' ?>">Hasta sistemde kayıtlı; ad, soyad ve kayıt durumu değiştirilemez.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rapor Yenilendi mi?</label>
                        <select name="yenilendimi" class="form-select">
                            <option value="0" <?= $isEdit && (int) ($item->yenilendimi ?? 0) === 0 ? 'selected' : (!$isEdit ? 'selected' : '') ?>>Hayır</option>
                            <option value="1" <?= $isEdit && (int) ($item->yenilendimi ?? 0) === 1 ? 'selected' : '' ?>>Evet</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rapor Branşı / Türü</label>
                        <select name="brans" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($branslar as $b): ?>
                                <?php
                                $brStored = $isEdit ? trim((string) ($item->brans ?? '')) : '';
                                $optBransAdi = (string) ($b->bransadi ?? '');
                                $optId = (int) ($b->id ?? 0);
                                $brSel = $isEdit && $brStored !== '' && (
                                    $brStored === $optBransAdi
                                    || (string) (int) $brStored === (string) $optId
                                    || $brStored === (string) $optId
                                );
                                ?>
                                <option value="<?= $optId ?>" <?= $brSel ? 'selected' : '' ?>><?= htmlspecialchars($optBransAdi, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rapor Tarihi</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light text-primary border-end-0"><i class="fa-solid fa-calendar-days"></i></span>
                            <input type="text" name="basvurutarihi" class="form-control datepicker border-start-0 ps-0"
                                   value="<?= htmlspecialchars($basvuruTr, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="GG-AA-YYYY" autocomplete="off" maxlength="10" required>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Notlar / Neden</label>
                    <textarea name="neden" class="form-control" rows="3" placeholder="Notlarınızı yazın..."><?= $isEdit ? htmlspecialchars((string) ($item->neden ?? ''), ENT_QUOTES, 'UTF-8') : '' ?></textarea>
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
