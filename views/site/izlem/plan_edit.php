<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
$pid = (string) ($plan->id ?? '');
$planDateTr = \App\Helpers\DateHelper::toTrOrEmpty((string) ($plan->planlanantarih ?? ''));
if ($planDateTr === '') {
    $planDateTr = \App\Helpers\DateHelper::todayTr();
}
$onc = (int) ($plan->oncelik ?? 1);
if ($onc < 1 || $onc > 3) {
    $onc = 1;
}
$zm = \App\Helpers\ZamanDilimiHelper::normalize($plan->zaman ?? null);
if ($zm < 0 || $zm > 2) {
    $zm = 1;
}
?>
<div class="container-fluid px-3 px-lg-4 py-4 izlem-plan-page izlem-plan-edit-page">
    <div class="card border-0 shadow rounded-3 overflow-hidden izlem-plan-create-card">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-0">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Planlı izlem düzenle</h5>
                <p class="small mb-0 mt-1 opacity-90">Plan tarihi, öncelik ve yapılacak işlemleri güncelleyin.</p>
            </div>
            <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => (string) ($patient->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-light rounded-pill shadow-sm">
                <i class="fa-solid fa-calendar-week me-1"></i>Planlı izlemler
            </a>
        </div>
        <div class="card-body p-0">
            <div class="px-4 pt-4 pb-3 border-bottom bg-body-tertiary">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fa-solid fa-user-large fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars(trim(($patient->isim ?? '') . ' ' . ($patient->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="font-monospace text-secondary small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')) ?></span>
                        <span class="badge bg-secondary ms-2">Plan #<?= $pid ?></span>
                    </div>
                </div>
            </div>

            <div id="plan-warnings-banner" class="d-none px-4 py-3 border-bottom bg-warning-subtle bg-opacity-25" role="region" aria-label="Plan uyarıları">
                <div class="vstack gap-2">
                    <div id="plan-overlap-alert" class="alert alert-danger mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-danger" role="alert" aria-live="polite"></div>
                </div>
            </div>

            <form class="p-4 esh-plan-form" id="form-plan-edit-<?= $pid ?>" action="<?= htmlspecialchars(esh_url('PlannedVisit', 'update'), ENT_QUOTES, 'UTF-8') ?>" method="POST"
                  data-exclude-plan-id="<?= $pid ?>">
                <input type="hidden" name="id" value="<?= $pid ?>">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-plan-create-layout">
                    <div class="col-12 col-lg-6">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-light-subtle p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-days me-2 text-primary"></i>Plan zamanı
                            </h6>
                            <div class="vstack gap-4">
                                <?= \App\Helpers\FormHelper::fieldDateGroup('planlanantarih_date', 'Planlanan tarih', $plan->planlanantarih ?? '', [
                                    'col' => '',
                                    'labelClass' => 'form-label fw-semibold small mb-2',
                                    'icon' => 'fa-solid fa-calendar-day text-primary',
                                    'prefixIconClass' => 'bg-white border-end-0',
                                    'class' => 'border-start-0',
                                    'inputGroupExtraClass' => 'shadow-sm',
                                    'required' => true,
                                    'rawValue' => $planDateTr,
                                    'afterInput' => '<div class="form-text small">Takvim veya GG-AA-YYYY</div>',
                                ]) ?>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Öncelik</label>
                                    <?= \App\Helpers\UIHelper::planOncelikRadios('oncelik', 'plan-edit-onc-' . $pid, $onc) ?>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Zaman dilimi</label>
                                    <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', 'plan-edit-' . $pid, $zm) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>İşlem ve planı yapanlar
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Yapılacak işlem <span class="text-danger">*</span></label>
                                    <div class="izlem-plan-select-wrap esh-tomselect-field">
                                        <?= $list['islem']; ?>
                                    </div>
                                    <div class="form-text small">Çoklu seçim: Ctrl / Cmd + tıklama</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Planı yapan <span class="text-danger">*</span></label>
                                    <div class="izlem-plan-select-wrap esh-tomselect-field">
                                        <?= $list['personel']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <?= \App\Helpers\FormHelper::fieldTextarea('aciklama', 'Ek notlar', (string) ($plan->aciklama ?? $plan->notlar ?? ''), [
                        'col' => '',
                        'labelClass' => 'form-label fw-semibold small mb-2',
                        'class' => 'shadow-sm',
                        'rows' => 3,
                        'placeholder' => 'Adrese özel not, ekip tercihi…',
                    ]) ?>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => (string) ($patient->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4 rounded-pill">Vazgeç</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/plannedvisit-create.js') ?>
