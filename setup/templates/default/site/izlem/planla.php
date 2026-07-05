<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/site/izlem/planla.php
 *
 * Controller : PlannedVisitController
 * Action     : create
 * Canonical  : views/site/izlem/planla.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   $patient, $islemler, $list (islem/personel HTML)
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 */
?>
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

            <div id="plan-doluluk-banner" class="plan-doluluk-banner d-none px-4 py-3 border-bottom" role="region" aria-live="polite" aria-label="Seçilen tarih ve vakit yoğunluk özeti">
                <div id="plan-doluluk-uyari" class="mb-0"></div>
            </div>

            <form class="p-4" id="form-plan-create" action="<?= htmlspecialchars(esh_url('PlannedVisit', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="plantarihi" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-plan-create-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-light-subtle p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-days me-2 text-primary"></i>Plan zamanı
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Planlanan tarih</label>
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-day text-primary"></i></span>
                                        <input type="text" name="planlanantarih_date" class="form-control border-start-0 datepicker" required
                                               placeholder="GG-AA-YYYY" autocomplete="off"
                                               value="<?= htmlspecialchars(\App\Helpers\DateHelper::todayTr(), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-text small">Takvim veya GG-AA-YYYY</div>
                                </div>
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
                                <div>
                                    <label class="form-label x-small text-muted mb-1">Tekrar sayısı</label>
                                    <input type="number" name="tekrar_sayisi" id="plan-tekrar-sayisi" class="form-control shadow-sm"
                                           min="<?= (int) \App\Helpers\IzlemPlanTekrarHelper::SAYI_MIN ?>"
                                           max="<?= (int) \App\Helpers\IzlemPlanTekrarHelper::SAYI_MAX ?>"
                                           value="1" inputmode="numeric">
                                </div>
                                <div>
                                    <label class="form-label x-small text-muted mb-1">Tekrar aralığı</label>
                                    <select name="tekrar_araligi" id="plan-tekrar-araligi" class="form-select shadow-sm">
                                        <?php foreach (\App\Helpers\IzlemPlanTekrarHelper::aralikSecenekleri() as $val => $lab): ?>
                                            <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"<?= ($val === \App\Helpers\IzlemPlanTekrarHelper::ARALIK_YOK) ? ' selected' : '' ?>><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text small mb-0">
                                    <strong>1 hafta her gün:</strong> «Günlük» + tekrar sayısı <strong>7</strong> (planlanan tarihten itibaren 7 ayrı gün). Tek kayıt için «Tek sefer (tekrar yok)» ve sayı 1. Haftada bir: «Haftalık» + istenen tekrar sayısı.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label fw-semibold small mb-2">Ek notlar</label>
                    <textarea name="aciklama" class="form-control shadow-sm" rows="3" placeholder="Adrese özel not, ekip tercihi…"></textarea>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <button type="button" data-esh-action="history-back" class="btn btn-outline-secondary px-4 rounded-pill">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-circle-check me-2"></i>Planı oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
