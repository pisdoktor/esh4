<div class="container-fluid px-3 px-lg-4 py-4 izlem-plan-page izlem-plan-create-page au-izlem-form">
    <section class="au-panel izlem-plan-create-card">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-calendar-plus"></i></span>
                <div class="min-w-0">
                    <h2 class="au-panel__title mb-0">Yeni izlem planla</h2>
                    <p class="au-panel__sub">Tarih, öncelik, tekrar ve yapılacak işlemleri belirleyin.</p>
                </div>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode((string) ($patient->tckimlik ?? ''))]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fa-solid fa-notes-medical me-1"></i>İzlem geçmişi
            </a>
        </div>
        <div class="au-panel__body">
            <div class="d-flex flex-wrap align-items-center gap-3 pb-3 mb-3 border-bottom">
                <span class="au-icon-chip au-icon-chip--soft"><i class="fa-solid fa-user-large"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars(trim(($patient->isim ?? '') . ' ' . ($patient->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                    <span class="font-monospace text-secondary small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')) ?></span>
                </div>
            </div>

            <div id="plan-doluluk-banner" class="plan-doluluk-banner d-none mb-3" role="region" aria-live="polite" aria-label="Seçilen tarih ve vakit yoğunluk özeti">
                <div id="plan-doluluk-uyari" class="mb-0"></div>
            </div>

            <form id="form-plan-create" action="<?= htmlspecialchars(esh_url('PlannedVisit', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="plantarihi" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-plan-create-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-plan-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-days me-2 text-primary"></i>Plan zamanı
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Planlanan tarih</label>
                                    <div class="input-group au-search-group">
                                        <span class="input-group-text"><i class="fa-solid fa-calendar-day text-primary"></i></span>
                                        <input type="text" name="planlanantarih_date" class="form-control datepicker" required
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
                    <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4 rounded-pill">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-circle-check me-2"></i>Planı oluştur
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
