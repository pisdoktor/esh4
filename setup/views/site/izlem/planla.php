<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<div class="container-fluid px-3 px-lg-4 py-4 izlem-plan-page izlem-plan-create-page">
    <div class="card border-0 shadow rounded-3 overflow-hidden izlem-plan-create-card">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-0">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i>Yeni izlem planla</h5>
                <p class="small mb-0 mt-1 opacity-90">Tarih, öncelik, tekrar ve yapılacak işlemleri belirleyin.</p>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode((string) ($patient->tckimlik ?? ''))]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-light rounded-pill shadow-sm">
                <i class="fa-solid fa-notes-medical me-1"></i>İzlem geçmişi
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
                    </div>
                </div>
            </div>

            <div id="plan-warnings-banner" class="d-none px-4 py-3 border-bottom bg-warning-subtle bg-opacity-25" role="region" aria-label="Plan uyarıları">
                <div class="vstack gap-2">
                    <div id="plan-overlap-alert" class="alert alert-danger mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-danger" role="alert" aria-live="polite"></div>
                </div>
            </div>

            <div id="plan-doluluk-banner" class="plan-doluluk-banner d-none px-4 py-3 border-bottom" role="region" aria-live="polite" aria-label="Seçilen tarih ve vakit yoğunluk özeti">
                <div id="plan-doluluk-uyari" class="mb-0"></div>
            </div>

            <form class="p-4 esh-plan-form" id="form-plan-create" action="<?= htmlspecialchars(esh_url('PlannedVisit', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="plantarihi" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-plan-create-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-light-subtle p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-days me-2 text-primary"></i>Plan zamanı
                            </h6>
                            <div class="vstack gap-4">
                                <?= \App\Helpers\FormHelper::fieldDateGroup('planlanantarih_date', 'Planlanan tarih', \App\Helpers\DateHelper::todayTr(), [
                                    'col' => '',
                                    'labelClass' => 'form-label fw-semibold small mb-2',
                                    'icon' => 'fa-solid fa-calendar-day text-primary',
                                    'prefixIconClass' => 'bg-white border-end-0',
                                    'class' => 'border-start-0',
                                    'inputGroupExtraClass' => 'shadow-sm',
                                    'required' => true,
                                    'rawValue' => \App\Helpers\DateHelper::todayTr(),
                                    'afterInput' => '<div class="form-text small">Takvim veya GG-AA-YYYY</div>',
                                ]) ?>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Öncelik</label>
                                    <?= \App\Helpers\UIHelper::planOncelikRadios('oncelik', 'plan-yeni-onc', 1) ?>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Zaman dilimi</label>
                                    <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', 'plan-yeni', 1) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
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
                    <div class="col-12 col-lg-4">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-repeat me-2 text-primary"></i>Tekrar
                            </h6>
                            <div class="vstack gap-3">
                                <?= \App\Helpers\FormHelper::fieldInput('tekrar_sayisi', 'Tekrar sayısı', '1', [
                                    'col' => '',
                                    'id' => 'plan-tekrar-sayisi',
                                    'type' => 'number',
                                    'labelClass' => 'form-label x-small text-muted mb-1',
                                    'class' => 'shadow-sm',
                                    'inputmode' => 'numeric',
                                    'extraAttrs' => [
                                        'min' => (string) \App\Helpers\IzlemPlanTekrarHelper::SAYI_MIN,
                                        'max' => (string) \App\Helpers\IzlemPlanTekrarHelper::SAYI_MAX,
                                    ],
                                ]) ?>
                                <?php
                                $eshPlanTekrarOptions = [];
                                foreach (\App\Helpers\IzlemPlanTekrarHelper::aralikSecenekleri() as $val => $lab) {
                                    $eshPlanTekrarOptions[] = \App\Helpers\FormHelper::makeOption((string) $val, $lab);
                                }
                                echo \App\Helpers\FormHelper::fieldSelect('tekrar_araligi', 'Tekrar aralığı', $eshPlanTekrarOptions, \App\Helpers\IzlemPlanTekrarHelper::ARALIK_YOK, [
                                    'col' => '',
                                    'id' => 'plan-tekrar-araligi',
                                    'labelClass' => 'form-label x-small text-muted mb-1',
                                    'class' => 'shadow-sm',
                                    'tomSelect' => false,
                                ]);
                                ?>
                                <div class="form-text small mb-0">
                                    <strong>1 hafta her gün:</strong> «Günlük» + tekrar sayısı <strong>7</strong> (planlanan tarihten itibaren 7 ayrı gün). Tek kayıt için «Tek sefer (tekrar yok)» ve sayı 1. Haftada bir: «Haftalık» + istenen tekrar sayısı.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <?= \App\Helpers\FormHelper::fieldTextarea('aciklama', 'Ek notlar', '', [
                        'col' => '',
                        'labelClass' => 'form-label fw-semibold small mb-2',
                        'class' => 'shadow-sm',
                        'rows' => 3,
                        'placeholder' => 'Adrese özel not, ekip tercihi…',
                    ]) ?>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4 rounded-pill">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-circle-check me-2"></i>Planı oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>