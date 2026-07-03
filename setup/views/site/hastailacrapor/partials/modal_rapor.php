    <div class="modal fade hastailacrapor-edit-modal" id="hastailacRaporModal" tabindex="-1" aria-labelledby="hastailacRaporModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content hastailacrapor-modal-content border-0 shadow">
                <form action="<?= htmlspecialchars(esh_url('HastaIlacRapor', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="hastailacrapor-modal-form d-flex flex-column min-h-0">
                    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                    <input type="hidden" name="hastalik_id" id="hastailacRaporHastalikId" value="">
                    <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars(preg_replace('/\D+/', '', $tcRaw), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="rapor_id" id="hastailacRaporRowId" value="">
                    <div class="modal-header hastailacrapor-modal-header border-bottom-0 pb-0">
                        <div class="d-flex align-items-start gap-3 w-100 pe-4">
                            <span class="hastailacrapor-modal-header__icon rounded-3 bg-primary-subtle text-primary flex-shrink-0" aria-hidden="true">
                                <i class="fa-solid fa-file-medical"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="modal-title fw-bold mb-0 text-truncate" id="hastailacRaporModalTitle">Tanı raporu</h5>
                                    <span class="badge hastailacrapor-modal-status-badge flex-shrink-0" id="hastailacRaporModalStatusBadge" hidden></span>
                                </div>
                                <p class="small text-muted mb-0 hastailacrapor-modal-subtitle" id="hastailacRaporModalSubtitle">
                                    <i class="fa-solid fa-user me-1 opacity-75" aria-hidden="true"></i><?= htmlspecialchars($patientDisplayName, ENT_QUOTES, 'UTF-8') ?>
                                    <span class="mx-1 opacity-50" aria-hidden="true">·</span>
                                    <span class="font-monospace"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tcRaw), ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($pasifEtiket, ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body hastailacrapor-modal-body pt-2">
                        <section class="hastailacrapor-modal-section" aria-labelledby="hastailacraporSectionDurumLabel">
                            <header class="hastailacrapor-modal-section__head">
                                <span class="hastailacrapor-modal-section__step" aria-hidden="true">1</span>
                                <div>
                                    <h6 class="hastailacrapor-modal-section__title mb-0" id="hastailacraporSectionDurumLabel">Rapor durumu</h6>
                                    <p class="hastailacrapor-modal-section__desc mb-0">Bu tanı için geçerli rapor var mı?</p>
                                </div>
                            </header>
                            <div class="hastailacrapor-segment-group row g-2" role="radiogroup" aria-labelledby="hastailacraporSectionDurumLabel">
                                <div class="col-6">
                                    <input class="btn-check" type="radio" name="rapor" id="hastailacRaporHayir" value="0" checked autocomplete="off">
                                    <label class="btn btn-outline-secondary w-100 hastailacrapor-segment-btn rounded-3 py-3" for="hastailacRaporHayir">
                                        <span class="hastailacrapor-segment-btn__icon text-secondary"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
                                        <span class="hastailacrapor-segment-btn__label">Hayır</span>
                                        <span class="hastailacrapor-segment-btn__hint">Rapor yok</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input class="btn-check" type="radio" name="rapor" id="hastailacRaporEvet" value="1" autocomplete="off"
                                        aria-controls="hastailacRaporDetay" aria-expanded="false">
                                    <label class="btn btn-outline-primary w-100 hastailacrapor-segment-btn rounded-3 py-3" for="hastailacRaporEvet">
                                        <span class="hastailacrapor-segment-btn__icon text-primary"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                                        <span class="hastailacrapor-segment-btn__label">Evet</span>
                                        <span class="hastailacrapor-segment-btn__hint">Raporlu tanı</span>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <div class="collapse hastailacrapor-rapor-detay" id="hastailacRaporDetay">
                            <section class="hastailacrapor-modal-section" aria-labelledby="hastailacraporSectionBitisLabel">
                                <header class="hastailacrapor-modal-section__head">
                                    <span class="hastailacrapor-modal-section__step" aria-hidden="true">2</span>
                                    <div>
                                        <h6 class="hastailacrapor-modal-section__title mb-0" id="hastailacraporSectionBitisLabel">Bitiş tarihi</h6>
                                        <p class="hastailacrapor-modal-section__desc mb-0">Rapor geçerlilik süresinin son günü.</p>
                                    </div>
                                </header>
                                <label class="visually-hidden" for="hastailacRaporBitis">Bitiş tarihi</label>
                                <div class="input-group hastailacrapor-date-input">
                                    <span class="input-group-text bg-body-secondary border-end-0 text-muted"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                                    <input type="text" name="bitistarihi" id="hastailacRaporBitis" class="form-control border-start-0 datepicker" maxlength="10" placeholder="GG-AA-YYYY" autocomplete="off" value="" aria-describedby="hastailacRaporBitisHelp">
                                </div>
                                <div id="hastailacRaporBitisHelp" class="form-text">Raporlu seçildiğinde zorunludur (GG-AA-YYYY).</div>
                            </section>

                            <section class="hastailacrapor-modal-section hastailacrapor-field-brans" aria-labelledby="hastailacraporSectionBransLabel">
                                <header class="hastailacrapor-modal-section__head">
                                    <span class="hastailacrapor-modal-section__step" aria-hidden="true">3</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <h6 class="hastailacrapor-modal-section__title mb-0" id="hastailacraporSectionBransLabel">Raporu yazan branşlar</h6>
                                        <p class="hastailacrapor-modal-section__desc mb-0">Birden fazla branş seçebilirsiniz.</p>
                                    </div>
                                </header>
                                <div class="hastailacrapor-brans-picker border rounded-3 overflow-hidden bg-body">
                                    <?php if (!empty($branslar)): ?>
                                        <div class="hastailacrapor-brans-picker__toolbar px-3 py-2 border-bottom bg-body-tertiary bg-opacity-50">
                                            <label class="visually-hidden" for="hastailacBransFilter">Branş ara</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted" aria-hidden="true"></i></span>
                                                <input type="search" class="form-control border-start-0" id="hastailacBransFilter" placeholder="Branş ara…" autocomplete="off" inputmode="search">
                                            </div>
                                            <p class="form-text small mb-0 mt-1 hastailacrapor-brans-filter-empty text-warning" id="hastailacBransFilterEmpty" hidden>Eşleşen branş bulunamadı.</p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="hastailacrapor-brans-scroll">
                                        <div class="px-3 py-2">
                                            <?php if (empty($branslar)): ?>
                                                <div class="alert alert-warning border-0 small mb-0 py-3 rounded-3">
                                                    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                                                    Tanımlı branş yok. Yönetim › <strong>Branşlar</strong> üzerinden ekleyebilirsiniz.
                                                </div>
                                            <?php else: ?>
                                                <div class="row g-2 align-items-stretch hastailacrapor-brans-grid" id="hastailacBransGrid">
                                                    <?php foreach ($branslar as $b): ?>
                                                        <?php
                                                        $bid = (int) ($b->id ?? 0);
                                                        if ($bid < 1) {
                                                            continue;
                                                        }
                                                        $brAd = (string) ($b->bransadi ?? '');
                                                        $brAdEsc = htmlspecialchars($brAd, ENT_QUOTES, 'UTF-8');
                                                        $brTitle = htmlspecialchars($brAd, ENT_QUOTES, 'UTF-8');
                                                        $rid = 'hastailacBr' . $bid;
                                                        ?>
                                                        <div class="col-12 col-sm-6 col-lg-4 hastailacrapor-brans-picker__item" data-brans-label="<?= $brTitle ?>">
                                                            <input class="btn-check hastailacrapor-brans-cb" type="checkbox" name="brans[]" id="<?= htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') ?>"
                                                                   value="<?= $bid ?>" autocomplete="off">
                                                            <label class="btn btn-outline-primary w-100 h-100 text-start rounded-3 py-2 px-2 hastailacrapor-brans-picker__choice d-flex align-items-center"
                                                                   for="<?= htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') ?>"
                                                                   title="<?= $brTitle ?>">
                                                                <span class="hastailacrapor-brans-picker__name"><?= $brAdEsc ?></span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="hastailacrapor-modal-section mb-0" aria-labelledby="hastailacraporSectionYerLabel">
                                <header class="hastailacrapor-modal-section__head">
                                    <span class="hastailacrapor-modal-section__step" aria-hidden="true">4</span>
                                    <div>
                                        <h6 class="hastailacrapor-modal-section__title mb-0" id="hastailacraporSectionYerLabel">Rapor yeri</h6>
                                        <p class="hastailacrapor-modal-section__desc mb-0">Raporun düzenlendiği kurum.</p>
                                    </div>
                                </header>
                                <div class="hastailacrapor-pill-group btn-group w-100" role="radiogroup" aria-labelledby="hastailacraporSectionYerLabel">
                                    <input class="btn-check" type="radio" name="raporyeri" id="hastailacRyKurum" value="1" autocomplete="off">
                                    <label class="btn btn-outline-primary hastailacrapor-pill-btn" for="hastailacRyKurum">
                                        <i class="fa-solid fa-hospital me-1" aria-hidden="true"></i>Bu kurum
                                    </label>
                                    <input class="btn-check" type="radio" name="raporyeri" id="hastailacRyDis" value="0" checked autocomplete="off">
                                    <label class="btn btn-outline-primary hastailacrapor-pill-btn" for="hastailacRyDis">
                                        <i class="fa-solid fa-building-circle-arrow-right me-1" aria-hidden="true"></i>Dış merkez
                                    </label>
                                </div>
                            </section>
                        </div>
                    </div>
                    <div class="modal-footer hastailacrapor-modal-footer border-top bg-body sticky-bottom">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
