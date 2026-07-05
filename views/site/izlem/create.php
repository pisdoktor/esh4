<div class="esh-page esh-page--form esh-page-izlem container-fluid py-4">
<div class="container-fluid px-3 px-lg-4 py-4 izlem-form-page izlem-create-page">
    <div class="card border-0 shadow rounded-3 overflow-hidden izlem-create-card">
        <div class="card-header bg-success text-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-0">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-notes-medical me-2"></i>Yeni izlem kaydı</h5>
                <p class="small mb-0 mt-1 opacity-90">Hasta için yapılan veya planlanan izlem bilgisini girin.</p>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-light rounded-pill shadow-sm">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Geçmiş
            </a>
        </div>
        <div class="card-body p-0">
            <div class="px-4 pt-4 pb-3 border-bottom bg-body-tertiary">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fa-solid fa-user-large fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars(trim(($patient->isim ?? '') . ' ' . ($patient->soyisim ?? ''))) ?></div>
                        <span class="font-monospace text-secondary small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')) ?></span>
                    </div>
                </div>
                <?php if (!empty($plan)): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-0 mt-3 py-2 small">
                        <i class="fa-solid fa-link me-1"></i>Planlı izlemden geldiniz; kayıt sonrası ilgili plan yapıldı olarak işaretlenir.
                    </div>
                <?php endif; ?>
                <?php if (!empty($uhdsId) && trim((string) $uhdsId) !== ''): ?>
                    <div class="alert alert-info border-0 shadow-sm mb-0 mt-3 py-2 small">
                        <i class="fa-solid fa-video me-1"></i>UHDS görüntülü görüşmeden geldiniz; kayıt sonrası randevuya bağlanır.
                    </div>
                <?php endif; ?>
                <?php if (!empty($clinicalDecisionAlerts)): ?>
                    <div class="px-4 pb-3 border-bottom bg-body-tertiary">
                        <?php include ROOT_PATH . '/views/site/hasta/partials/clinical_decision_alerts.php'; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="izlem-create-warnings-banner" class="d-none px-4 py-3 border-bottom bg-warning-subtle bg-opacity-25" role="region" aria-label="İzlem tarihi uyarıları">
                <div class="vstack gap-2">
                    <div id="izlem-create-same-day-alert" class="alert alert-warning mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-warning" role="alert" aria-live="polite"></div>
                    <div id="izlem-create-planned-alert" class="alert alert-info mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-info" role="status" aria-live="polite"></div>
                </div>
            </div>

            <form id="form-izlem-create" class="p-4" action="<?= htmlspecialchars(esh_url('Visit', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="checkin_lat" value="">
                <input type="hidden" name="checkin_lon" value="">
                <input type="hidden" name="checkin_at" value="">
                <input type="hidden" name="checkin_accuracy" value="">
                <?php if (!empty($plan) && !empty($plan->id)): ?>
                    <input type="hidden" name="plan_id" value="<?= (string) $plan->id ?>">
                <?php endif; ?>
                <?php if (!empty($uhdsId) && trim((string) $uhdsId) !== ''): ?>
                    <input type="hidden" name="uhds_id" value="<?= htmlspecialchars((string) $uhdsId, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <?php include __DIR__ . '/partials/checkin_panel.php'; ?>

                <div class="row g-4 izlem-create-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-light-subtle p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-check me-2 text-success"></i>Tarih ve durum
                            </h6>
                            <div class="vstack gap-4">
                                <?= \App\Helpers\FormHelper::fieldDateGroup('izlemtarihi', 'İzlem tarihi', $defaultIzlemDate ?? date('Y-m-d'), [
                                    'col' => '',
                                    'labelClass' => 'form-label fw-semibold small mb-2',
                                    'icon' => 'fa-solid fa-calendar-day text-success',
                                    'prefixIconClass' => 'bg-white border-end-0',
                                    'class' => 'border-start-0',
                                    'inputGroupExtraClass' => 'shadow-sm',
                                    'required' => true,
                                    'afterInput' => '<div class="form-text small">Bugün veya geçmiş bir tarih (GG-AA-YYYY)</div>',
                                ]) ?>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Zaman</label>
                                    <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', 'izlem-create', $defaultZaman ?? null) ?>
                                    <div class="form-text small">08:00–12:00 sabah, 12:00–16:00 öğle, 16:00–21:00 akşam otomatik seçilir.</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Yapıldı / yapılmadı</label>
                                    <?= \App\Helpers\UIHelper::izlemYapildimiRadios('yapildimi', 'izlem-create', 1) ?>
                                </div>
                                <div>
                                    <div class="collapse" id="izlem-create-neden-collapse">
                                        <div class="rounded-3 border border-warning bg-warning-subtle p-3 shadow-sm">
                                            <label class="form-label fw-semibold small text-dark mb-2">Yapılmama nedeni</label>
                                            <?= \App\Helpers\IzlemYapilmamaNedenHelper::renderRadios('izlem-create', \App\Helpers\IzlemYapilmamaNedenHelper::defaultKey()) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-list-check me-2 text-success"></i>İşlem ve ekip
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Yapılan / yapılacak işlemler <span class="text-danger">*</span></label>
                                    <div class="izlem-form-select-wrap esh-tomselect-field">
                                        <?= $list['islem'] ?? '' ?>
                                    </div>
                                    <div class="form-text small">Çoklu seçim: Ctrl / Cmd + tıklama</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">İzlemi yapan personel</label>
                                    <div class="izlem-form-select-wrap esh-tomselect-field">
                                        <?= $list['personel'] ?? '' ?>
                                    </div>
                                    <div class="form-text small">Boş bırakılırsa oturumdaki kullanıcı yazılır.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm izlem-create-arac-panel">
                            <?php
                            $radiosSuffix = 'izlem-create-arac';
                            $aracPickerAccent = 'success';
                            include __DIR__ . '/partials/arac_secimi.php';
                            ?>
                        </div>
                    </div>
                </div>

                <?php $visit = null; include __DIR__ . '/partials/esys_ref_panel.php'; ?>

                <?php $visit = null; include __DIR__ . '/partials/usbs_ref_panel.php'; ?>

                <div class="mt-4">
                    <?= \App\Helpers\FormHelper::fieldTextarea('aciklama', 'Açıklama', $defaultAciklama ?? '', [
                        'col' => '',
                        'labelClass' => 'form-label fw-semibold small mb-2',
                        'class' => 'shadow-sm',
                        'rows' => 3,
                        'placeholder' => 'Ek notlar, ölçüm veya özel durum…',
                    ]) ?>
                </div>

                <div class="mt-4 pt-3 border-top d-none d-lg-flex flex-wrap justify-content-between gap-2 align-items-center izlem-desktop-actions">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-esh-action="history-back">Vazgeç</button>
                    <button type="submit" class="btn btn-success px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Kaydet
                    </button>
                </div>
                <div class="izlem-mobile-actions d-lg-none d-flex flex-wrap gap-2 justify-content-between align-items-stretch">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 rounded-pill" data-esh-action="history-back">Vazgeç</button>
                    <button type="submit" class="btn btn-success flex-grow-1 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/partials/field_visit_page_config.php'; ?>