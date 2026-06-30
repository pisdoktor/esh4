<?php
$vid = (int) ($visit->id ?? 0);
$izt = !empty($visit->izlemtarihi) ? date('Y-m-d', strtotime((string) $visit->izlemtarihi)) : date('Y-m-d');
$zm = \App\Helpers\ZamanDilimiHelper::normalize($visit->zaman ?? null);
if ($zm < 0 || $zm > 2) {
    $zm = 0;
}
$yd = (int) ($visit->yapildimi ?? 0) ? 1 : 0;
if ($yd === 0) {
    $nedenKey = \App\Helpers\IzlemYapilmamaNedenHelper::parseKey((string) ($visit->neden ?? ''));
} else {
    $nedenKey = \App\Helpers\IzlemYapilmamaNedenHelper::defaultKey();
}
$izlemTarihiTr = \App\Helpers\DateHelper::toTrOrEmpty($izt);
if ($izlemTarihiTr === '') {
    $izlemTarihiTr = \App\Helpers\DateHelper::todayTr();
}
?>
<div class="container-fluid px-3 px-lg-4 py-4 izlem-form-page izlem-edit-page au-izlem-form">
    <section class="au-panel">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-pen-to-square"></i></span>
                <div class="min-w-0">
                    <h2 class="au-panel__title mb-0">İzlem kaydını düzenle</h2>
                    <p class="au-panel__sub">Bilgileri güncelleyip kaydedin.</p>
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
                    <span class="badge bg-secondary ms-2">Kayıt #<?= $vid ?></span>
                </div>
            </div>

            <form id="form-izlem-edit-<?= $vid ?>" action="<?= htmlspecialchars(esh_url('Visit', 'update'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="id" value="<?= $vid ?>">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-create-layout izlem-edit-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-check me-2 text-primary"></i>Tarih ve durum
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">İzlem tarihi</label>
                                    <div class="input-group au-search-group">
                                        <span class="input-group-text"><i class="fa-solid fa-calendar-day text-primary"></i></span>
                                        <input type="text" name="izlemtarihi" class="form-control datepicker" required
                                               placeholder="GG-AA-YYYY" autocomplete="off"
                                               value="<?= htmlspecialchars($izlemTarihiTr, ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-text small">Takvim veya GG-AA-YYYY</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Zaman</label>
                                    <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', 'izlem-edit-' . $vid, $zm) ?>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold small mb-2">Yapıldı / yapılmadı</label>
                                    <?= \App\Helpers\UIHelper::izlemYapildimiRadios('yapildimi', 'izlem-edit-' . $vid, $yd) ?>
                                </div>
                                <div>
                                    <div class="collapse<?= $yd === 0 ? ' show' : '' ?>" id="izlem-edit-neden-collapse-<?= $vid ?>">
                                        <div class="rounded-3 border border-warning bg-warning-subtle p-3 shadow-sm">
                                            <label class="form-label fw-semibold small text-dark mb-2">Yapılmama nedeni</label>
                                            <?= \App\Helpers\IzlemYapilmamaNedenHelper::renderRadios('izlem-edit-' . $vid, $nedenKey) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i>İşlem ve ekip
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
                        <div class="izlem-form-panel h-100 rounded-3 border bg-white p-3 p-md-4 shadow-sm izlem-edit-arac-panel">
                            <?php
                            $radiosSuffix = 'izlem-edit-arac-' . $vid;
                            $aracPickerAccent = 'primary';
                            include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'izlem/partials/arac_secimi');
                            ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label fw-semibold small mb-2">Açıklama</label>
                    <textarea name="aciklama" class="form-control shadow-sm" rows="3" placeholder="Ek notlar, ölçüm veya özel durum…"><?= htmlspecialchars((string) ($visit->aciklama ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary px-4 rounded-pill">Vazgeç</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
