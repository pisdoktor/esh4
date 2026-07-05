<div class="container-fluid px-3 px-lg-4 py-4 izlem-form-page izlem-create-page au-izlem-form">
    <section class="au-panel izlem-create-card">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-notes-medical"></i></span>
                <div class="min-w-0">
                    <h2 class="au-panel__title mb-0">Yeni izlem kaydı</h2>
                    <p class="au-panel__sub">Hasta için yapılan veya planlanan izlem bilgisini girin.</p>
                </div>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Geçmiş
            </a>
        </div>
        <div class="au-panel__body">
            <div class="d-flex flex-wrap align-items-center gap-3 pb-3 mb-3 border-bottom">
                <span class="au-icon-chip au-icon-chip--soft"><i class="fa-solid fa-user-large"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars(trim(($patient->isim ?? '') . ' ' . ($patient->soyisim ?? ''))) ?></div>
                    <span class="font-monospace text-secondary small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')) ?></span>
                </div>
            </div>
            <?php if (!empty($plan)): ?>
                <div class="alert alert-success border-0 shadow-sm mb-3 py-2 small">
                    <i class="fa-solid fa-link me-1"></i>Planlı izlemden geldiniz; kayıt sonrası ilgili plan yapıldı olarak işaretlenir.
                </div>
            <?php endif; ?>

            <div id="izlem-create-warnings-banner" class="d-none mb-3" role="region" aria-label="İzlem tarihi uyarıları">
                <div class="vstack gap-2">
                    <div id="izlem-create-same-day-alert" class="alert alert-warning mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-warning" role="alert" aria-live="polite"></div>
                    <div id="izlem-create-planned-alert" class="alert alert-info mb-0 py-3 px-3 fw-semibold shadow-sm d-none border-start border-4 border-info" role="status" aria-live="polite"></div>
                </div>
            </div>

            <form id="form-izlem-create" action="<?= htmlspecialchars(esh_url('Visit', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($plan) && !empty($plan->id)): ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $plan->id ?>">
                <?php endif; ?>

                <div class="row g-4 izlem-create-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-check me-2 text-success"></i>Tarih ve durum
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">İzlem tarihi</label>
                                    <div class="input-group au-search-group">
                                        <span class="input-group-text"><i class="fa-solid fa-calendar-day text-success"></i></span>
                                        <input type="text" name="izlemtarihi" class="form-control datepicker" required
                                               placeholder="GG-AA-YYYY" autocomplete="off"
                                               value="<?= htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty($defaultIzlemDate ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-text small">Bugün veya geçmiş bir tarih (GG-AA-YYYY)</div>
                                </div>
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
                            include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'izlem/partials/arac_secimi');
                            ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label fw-semibold small mb-2">Açıklama</label>
                    <textarea name="aciklama" class="form-control shadow-sm" rows="3" placeholder="Ek notlar, ölçüm veya özel durum…"></textarea>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-esh-action="history-back">Vazgeç</button>
                    <button type="submit" class="btn btn-success px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
