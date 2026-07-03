<?php
$vid = (int) ($visit->id ?? 0);
$izt = !empty($visit->izlemtarihi) ? date('Y-m-d', strtotime((string) $visit->izlemtarihi)) : date('Y-m-d');
$zm = isset($visit->zaman) ? (int) $visit->zaman : 0;
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
<article class="mr-page mr-page--izlem-edit container-fluid px-3 px-lg-4 py-4 izlem-form-page izlem-edit-page" lang="tr">
<header class="visually-hidden"><h1>İzlem kaydını düzenle</h1></header>
    <div class="card border-0 shadow rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-0">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>İzlem kaydını düzenle</h5>
                <p class="small mb-0 mt-1 opacity-90">Bilgileri güncelleyip kaydedin.</p>
            </div>
            <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-light rounded-pill shadow-sm">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Geçmiş
            </a>
        </div>
        <div class="card-body p-0">
            <div class="px-4 pt-4 pb-3 border-bottom bg-body-tertiary">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fa-solid fa-user-large fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars(trim(($patient->isim ?? '') . ' ' . ($patient->soyisim ?? ''))) ?></div>
                        <span class="font-monospace text-secondary small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')) ?></span>
                        <span class="badge bg-secondary ms-2">Kayıt #<?= $vid ?></span>
                    </div>
                </div>
            </div>

            <form id="form-izlem-edit-<?= $vid ?>" class="p-4" action="<?= htmlspecialchars(esh_url('Visit', 'update'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="id" value="<?= $vid ?>">
                <input type="hidden" name="hastatckimlik" value="<?= htmlspecialchars((string) ($patient->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-4 izlem-create-layout izlem-edit-layout">
                    <div class="col-12 col-lg-4">
                        <div class="izlem-form-panel h-100 rounded-3 border bg-light-subtle p-3 p-md-4 shadow-sm">
                            <h6 class="text-uppercase text-secondary small fw-bold letter-spacing mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="fa-solid fa-calendar-check me-2 text-primary"></i>Tarih ve durum
                            </h6>
                            <div class="vstack gap-4">
                                <div>
                                    <label class="form-label fw-semibold small mb-2">İzlem tarihi</label>
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-day text-primary"></i></span>
                                        <input type="text" name="izlemtarihi" class="form-control border-start-0 datepicker" required
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
                            include __DIR__ . '/partials/arac_secimi.php';
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
    </div>
</article>
