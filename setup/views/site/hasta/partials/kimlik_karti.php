<?php
/**
 * Hasta kartı — kimlik bölümü (tek akış; ön/arka yüz sekmesi yok).
 *
 * @var object $hasta
 * @var string $patientPhotoUrl
 * @var mixed $guvence
 * @var object|null $anaadres
 * @var object|null $mahallePlanMeta
 * @var array $diger_adres
 * @var array $hastalikCardItems
 * @var array<int, bool> $hastalikRaporVar
 * @var array<int, array{bitis_tr?: string, raporyeri?: int, status?: string}> $hastalikRaporMeta
 */
use App\Helpers\AuthHelper;
use App\Helpers\BarthelScaleHelper;
use App\Helpers\HastaIlacRaporStatusHelper;
use App\Helpers\PatientClinicalFlagsHelper;
$isMale = (($hasta->cinsiyet ?? '') === '1' || ($hasta->cinsiyet ?? '') === 'E');
$canManageProfilePhoto = AuthHelper::sessionIsAdmin();
$kimlikCardBg = $isMale
    ? 'linear-gradient(135deg, #dcefff 0%, #b8dbff 48%, #8cc2ff 100%)'
    : 'linear-gradient(135deg, #ffe2ec 0%, #ffc1d8 48%, #ff99be 100%)';

$bolgeGoster = '—';
$gunGoster = '—';
if (!empty($mahallePlanMeta)) {
    $bn = (int) ($mahallePlanMeta->bolge ?? 0);
    $bolgeGoster = $bn > 0 ? (string) $bn : '—';
    $gunEtiket = \App\Models\Address::formatMahalleGunCsvTr((string) ($mahallePlanMeta->gun ?? ''), true);
    $gunGoster = $gunEtiket !== '' ? $gunEtiket : '—';
}
?>
<h5 class="text-primary small fw-bold text-uppercase mb-3"><i class="fa-solid fa-id-card me-2"></i>Kimlik Kartı</h5>
<div class="card esh-hasta-kimlik-kart border-0 shadow-sm mb-3" style="background: <?= htmlspecialchars($kimlikCardBg, ENT_QUOTES, 'UTF-8') ?>;">
    <div class="card-body pt-3 pb-4">
        <div class="row g-3 align-items-start">
            <div class="col-md-3 col-4 text-center">
                <?php if ($patientPhotoUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($patientPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Hasta profil fotoğrafı" class="rounded border shadow-sm" style="width:100%;max-width:120px;aspect-ratio:3/4;object-fit:cover;" loading="lazy" decoding="async">
                <?php else: ?>
                    <div class="rounded border bg-light d-inline-flex align-items-center justify-content-center text-secondary" style="width:100%;max-width:120px;aspect-ratio:3/4;">
                        <i class="fa-solid fa-user fa-2x"></i>
                    </div>
                <?php endif; ?>
                <?php if ($canManageProfilePhoto): ?>
                    <form action="<?= htmlspecialchars(esh_url('Patient', 'uploadPatientPhoto'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" class="mt-2" data-esh-required-legend="off" data-esh-required-markers="off">
                        <input type="hidden" name="id" value="<?= (int) $hasta->id ?>">
                        <input type="file" name="profil_foto" class="form-control form-control-sm mb-1" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-outline-primary btn-sm flex-fill"><i class="fa-solid fa-camera me-1"></i> Güncelle</button>
                        </div>
                    </form>
                    <?php if ($patientPhotoUrl !== ''): ?>
                        <form action="<?= htmlspecialchars(esh_url('Patient', 'deletePatientPhoto'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="mt-1" onsubmit="return confirm('Profil fotoğrafını silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="id" value="<?= (int) $hasta->id ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="fa-solid fa-trash me-1"></i> Sil</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="col-md-9 col-8">
                <div class="small text-muted mb-1">Ad Soyad</div>
                <div class="fw-bold text-dark mb-2" style="font-size:1.05rem;">
                    <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="row g-2 small">
                    <div class="col-sm-6">
                        <div class="text-muted">TC Kimlik No</div>
                        <div class="fw-bold font-monospace"><?= \App\Helpers\ValidationHelper::formatTc($hasta->tckimlik); ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Doğum Tarihi / Yaş</div>
                        <div class="fw-semibold">
                            <?= \App\Helpers\DateHelper::toTr($hasta->dogumtarihi); ?>
                            <span class="badge bg-secondary ms-1"><?= \App\Helpers\DateHelper::calculateAge($hasta->dogumtarihi); ?> Yaş</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Anne Adı</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($hasta->anneAdi ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Baba Adı</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($hasta->babaAdi ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Aile Hekimi</div>
                        <?php
                        $aileHekimiAd = trim((string) ($hasta->ailehekimi ?? ''));
                        $aileHekimiTelRaw = trim((string) ($hasta->ailehekimitel ?? ''));
                        $aileHekimiTelGoster = $aileHekimiTelRaw !== ''
                            ? \App\Helpers\ValidationHelper::formatPhoneDisplay($aileHekimiTelRaw)
                            : '';
                        ?>
                        <div class="fw-semibold"><?= $aileHekimiAd !== '' ? htmlspecialchars($aileHekimiAd, ENT_QUOTES, 'UTF-8') : '—' ?></div>
                        <?php if ($aileHekimiTelRaw !== ''): ?>
                        <div class="text-muted mt-1">Telefon</div>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $aileHekimiTelRaw), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-dark text-decoration-none">
                            <i class="fa-solid fa-phone text-secondary me-1" aria-hidden="true"></i><?= htmlspecialchars($aileHekimiTelGoster, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Kan Grubu</div>
                        <div class="fw-semibold">
                            <?php
                            $kgLabel = \App\Helpers\PatientCareHelper::kanGrubuLabel($hasta->kangrubu ?? '');
                            echo $kgLabel !== '' ? htmlspecialchars($kgLabel, ENT_QUOTES, 'UTF-8') : '—';
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted">Sağlık Güvencesi</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($guvenceAdi ?? $guvence ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (strcasecmp(trim((string) ($guvenceAdi ?? $guvence ?? '')), 'YUPAS') === 0): ?>
                        <?php $yupasNoGoster = trim((string) ($hasta->yupasno ?? '')); ?>
                        <div class="text-muted mt-2">YUPAS No</div>
                        <div class="fw-semibold"><?= $yupasNoGoster !== '' ? htmlspecialchars($yupasNoGoster, ENT_QUOTES, 'UTF-8') : '—' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php $ilkZiyaretRandevuTr = \App\Helpers\DateHelper::toTrOrEmpty($hasta->randevutarihi ?? ''); ?>
                    <div class="col-sm-6">
                        <div class="text-muted">Kayıt Tarihi</div>
                        <div class="fw-semibold"><?= \App\Helpers\DateHelper::toTr($hasta->kayittarihi); ?></div>
                        <?php if ($ilkZiyaretRandevuTr !== ''): ?>
                        <div class="text-muted mt-2">İlk Ziyaret (Randevu) Tarihi</div>
                        <div class="fw-semibold"><?= htmlspecialchars($ilkZiyaretRandevuTr, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (\App\Helpers\EsysComplianceHelper::patientHasRefs($hasta)): ?>
                    <div class="col-sm-6">
                        <div class="text-muted">ESYS hasta no</div>
                        <div class="fw-semibold font-monospace small"><?= htmlspecialchars(trim((string) ($hasta->esys_hasta_ref ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php $esysBasvuru = trim((string) ($hasta->esys_basvuru_ref ?? '')); ?>
                        <?php if ($esysBasvuru !== ''): ?>
                        <div class="text-muted mt-2">ESYS başvuru no</div>
                        <div class="fw-semibold font-monospace small"><?= htmlspecialchars($esysBasvuru, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="esh-hasta-kimlik-kart__continuation vstack gap-3 border-top border-dark border-opacity-10 pt-4 mt-4">
            <?php include ROOT_PATH . '/views/partials/admin/patient_kurum_view_line.php'; ?>
            <?php
            $telefon1Raw = trim((string) ($hasta->ceptel1 ?? ''));
            $telefon2Raw = trim((string) ($hasta->ceptel2 ?? ''));
            $telefon1Goster = $telefon1Raw !== ''
                ? \App\Helpers\ValidationHelper::formatPhoneDisplay($telefon1Raw)
                : '—';
            $telefon2Goster = $telefon2Raw !== ''
                ? \App\Helpers\ValidationHelper::formatPhoneDisplay($telefon2Raw)
                : '—';
            $bakimverenAd = trim((string) ($hasta->bakimveren_ad ?? ''));
            $bakimverenTelRaw = trim((string) ($hasta->bakimveren_tel ?? ''));
            $bakimverenYakinlik = trim((string) ($hasta->bakimveren_yakinlik ?? ''));
            $bakimverenAdGoster = $bakimverenAd !== '' ? $bakimverenAd : '—';
            $bakimverenYakinlikGoster = $bakimverenYakinlik !== '' ? $bakimverenYakinlik : '—';
            $bakimverenTelGoster = $bakimverenTelRaw !== ''
                ? \App\Helpers\ValidationHelper::formatPhoneDisplay($bakimverenTelRaw)
                : '—';
            ?>
            <div class="row g-3 esh-kimlik-iletisim-bakim-row align-items-stretch">
                <div class="col-md-6 d-flex flex-column">
                    <h6 class="text-primary small fw-bold text-uppercase mb-2"><i class="fa-solid fa-phone me-2"></i>İletişim Bilgileri</h6>
                    <div class="esh-kimlik-adres-box esh-kimlik-kurum-box rounded border border-secondary border-opacity-50 bg-white shadow-sm p-3 flex-grow-1">
                        <div class="vstack gap-2 small text-center justify-content-center h-100">
                            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                <span class="text-muted">Telefon 1:</span>
                                <?php if ($telefon1Raw !== ''): ?>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $telefon1Raw), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-dark text-decoration-none">
                                        <i class="fa-solid fa-mobile-screen text-secondary me-1" aria-hidden="true"></i><?= htmlspecialchars($telefon1Goster, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($telefon1Goster, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                <span class="text-muted">Telefon 2:</span>
                                <?php if ($telefon2Raw !== ''): ?>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $telefon2Raw), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-dark text-decoration-none">
                                        <i class="fa-solid fa-phone text-secondary me-1" aria-hidden="true"></i><?= htmlspecialchars($telefon2Goster, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($telefon2Goster, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex flex-column">
                    <h6 class="text-primary small fw-bold text-uppercase mb-2"><i class="fa-solid fa-hand-holding-heart me-2"></i>Bakım Veren / Yakını</h6>
                    <div class="esh-kimlik-adres-box esh-kimlik-kurum-box rounded border border-secondary border-opacity-50 bg-white shadow-sm p-3 flex-grow-1">
                        <div class="vstack gap-2 small text-center justify-content-center h-100">
                            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                <span class="text-muted">Ad:</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($bakimverenAdGoster, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                <span class="text-muted">Yakınlık:</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($bakimverenYakinlikGoster, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                <span class="text-muted">Telefon:</span>
                                <?php if ($bakimverenTelRaw !== ''): ?>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $bakimverenTelRaw), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-dark text-decoration-none">
                                        <i class="fa-solid fa-phone text-secondary me-1" aria-hidden="true"></i><?= htmlspecialchars($bakimverenTelGoster, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($bakimverenTelGoster, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <h6 class="text-primary small fw-bold text-uppercase mb-0"><i class="fa-solid fa-location-dot me-2"></i>Adres Bilgileri</h6>

            <div class="row g-3 align-items-start esh-kimlik-adres-row">
                <div class="col-md-7 col-lg-8">
                    <div class="vstack gap-3 esh-kimlik-adres-stack">
                        <div class="esh-kimlik-adres-box esh-kimlik-adres-box--ana rounded border border-primary border-opacity-50 bg-white shadow-sm p-3">
                            <div class="fw-bold text-primary small mb-2"><i class="fa-solid fa-house me-1"></i>Ana Adres</div>
                            <div class="text-uppercase small text-dark mb-2">
                                <?php if (!empty($anaadres)): ?>
                                    <?= htmlspecialchars((string)($anaadres->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?> MAH. <?= htmlspecialchars((string)($anaadres->sokak ?? ''), ENT_QUOTES, 'UTF-8') ?> SK-CD NO: <?= htmlspecialchars((string)($anaadres->kapino ?? ''), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string)($anaadres->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <span class="fst-italic text-secondary">Adres bileşenleri eksik veya tanımsız.</span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold text-muted small mb-1">Açıklama</div>
                            <div class="fst-italic text-secondary small mb-0"><?= htmlspecialchars((string) ($hasta->adres_aciklama ?: 'Açıklama girilmemiş.'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <?php if (!empty($diger_adres)): ?>
                            <?php foreach ($diger_adres as $digerIdx => $v): ?>
                                <div class="esh-kimlik-adres-box esh-kimlik-adres-box--diger rounded border border-secondary border-opacity-50 bg-white shadow-sm p-3">
                                    <div class="fw-bold text-secondary small mb-2">
                                        <i class="fa-solid fa-location-dot me-1"></i>Diğer Adres<?= count($diger_adres) > 1 ? ' ' . ((int) $digerIdx + 1) : '' ?>
                                    </div>
                                    <div class="text-uppercase small text-dark mb-2">
                                        <?= htmlspecialchars((string) $v['adres']->mahalle, ENT_QUOTES, 'UTF-8') ?> MAH.
                                        <?= htmlspecialchars((string) $v['adres']->sokak, ENT_QUOTES, 'UTF-8') ?> SK-CD NO:
                                        <?= htmlspecialchars((string) $v['adres']->kapino, ENT_QUOTES, 'UTF-8') ?> —
                                        <?= htmlspecialchars((string) $v['adres']->ilce, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="fw-bold text-muted small mb-1">Açıklama</div>
                                    <div class="fst-italic text-secondary small mb-0"><?= htmlspecialchars((string) ($v['adres_aciklama'] ?: 'Açıklama girilmemiş.'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="vstack gap-3 h-100">
                        <div class="esh-kimlik-field esh-kimlik-coords">
                            <div class="fw-bold text-muted small mb-1">Koordinatlar</div>
                            <div class="text-primary font-monospace small mb-2"><?= htmlspecialchars((string) ($hasta->coords ?: 'Girilmemiş'), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (AuthHelper::sessionIsAdmin()): ?>
                                <div class="d-flex flex-wrap gap-2 esh-kimlik-coords-actions">
                                    <form action="<?= htmlspecialchars(esh_url('Patient', 'resolvePatientCoords'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline-block mb-0">
                                        <input type="hidden" name="id" value="<?= (int) $hasta->id ?>">
                                        <button type="submit" class="btn btn-sm fw-semibold shadow-sm esh-coords-btn esh-coords-btn--bul" title="Adresten koordinat bul ve kaydet">
                                            <i class="fa-solid fa-map-pin me-1" aria-hidden="true"></i>Bul
                                        </button>
                                    </form>
                                    <?php if ($hasta->coords): ?>
                                        <a href="https://www.google.com/maps?q=<?= htmlspecialchars((string) $hasta->coords, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm fw-semibold shadow-sm esh-coords-btn esh-coords-btn--git" title="Haritada aç">
                                            <i class="fa-solid fa-diamond-turn-right me-1" aria-hidden="true"></i>Git
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="esh-kimlik-field">
                            <div class="fw-bold text-muted small mb-1">Mahalle planı</div>
                            <div class="small text-dark lh-sm vstack gap-1">
                                <div>
                                    <span class="text-muted">Bölge</span>
                                    <strong class="ms-1"><?= htmlspecialchars($bolgeGoster, ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div>
                                    <span class="text-muted">Günler</span>
                                    <strong class="ms-1"><?= htmlspecialchars($gunGoster, ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="text-muted opacity-25 my-1">

            <h6 class="text-primary small fw-bold text-uppercase mb-0"><i class="fa-solid fa-stethoscope me-2"></i>Klinik Durum</h6>

            <?php
            $alerjiMetin = trim((string) ($hasta->alerji ?? ''));
            $acilNotMetin = trim((string) ($hasta->acil_not ?? ''));
            $izolasyonAktif = !empty($hasta->izolasyon);
            if ($alerjiMetin !== '' || $acilNotMetin !== '' || $izolasyonAktif):
            ?>
            <div class="vstack gap-2 mb-2">
                <?php if ($alerjiMetin !== ''): ?>
                    <div class="alert alert-danger py-2 px-3 mb-0 small border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-allergies me-1"></i><strong>Alerji:</strong> <?= htmlspecialchars($alerjiMetin, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if ($izolasyonAktif): ?>
                    <div class="alert alert-warning py-2 px-3 mb-0 small border-0 shadow-sm text-dark" role="alert">
                        <i class="fa-solid fa-shield-virus me-1"></i><strong>İzolasyon / enfeksiyon önlemi</strong> gereklidir.
                    </div>
                <?php endif; ?>
                <?php if ($acilNotMetin !== ''): ?>
                    <div class="alert alert-warning border-danger py-2 px-3 mb-0 small text-dark" role="alert">
                        <i class="fa-solid fa-truck-medical me-1"></i><strong>Acil not:</strong> <?= htmlspecialchars($acilNotMetin, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="esh-kimlik-field">
                <div class="fw-bold text-muted small mb-1">Tanılar / Hastalıklar</div>
                <div class="small lh-sm text-dark">
                    <?php if (!empty($hastalikCardItems)): ?>
                        <?php
                        $hTanParts = [];
                        foreach ($hastalikCardItems as $hi) {
                            $hid = (int) ($hi->id ?? 0);
                            $lab = htmlspecialchars((string) ($hi->ad ?? ''), ENT_QUOTES, 'UTF-8');
                            if (!empty($hastalikRaporVar[$hid])) {
                                $raporTitle = 'Bu tanı için rapor bilgisi kayıtlı';
                                $meta = $hastalikRaporMeta[$hid] ?? null;
                                $raporStatus = is_array($meta) ? (string) ($meta['status'] ?? HastaIlacRaporStatusHelper::STATUS_RAPORLU) : HastaIlacRaporStatusHelper::STATUS_RAPORLU;
                                if (is_array($meta) && !empty($meta['bitis_tr'])) {
                                    $raporTitle .= ' — Bitiş: ' . $meta['bitis_tr'];
                                }
                                $wrapClass = 'esh-hasta-kart-diagnosis';
                                $raporClass = 'esh-hasta-kart-diagnosis__rapor';
                                $labelClass = 'esh-hasta-kart-diagnosis__label';
                                $statusBadge = '';
                                if ($raporStatus === HastaIlacRaporStatusHelper::STATUS_EXPIRED) {
                                    $wrapClass .= ' esh-hasta-kart-diagnosis--expired';
                                    $raporClass .= ' esh-hasta-kart-diagnosis__rapor--expired';
                                    $labelClass .= ' esh-hasta-kart-diagnosis__label--expired';
                                    $raporTitle .= ' — Süresi doldu';
                                    $statusBadge = ' <span class="badge bg-danger-subtle text-danger border border-danger-subtle esh-hasta-kart-diagnosis__badge">Süresi doldu</span>';
                                } elseif ($raporStatus === HastaIlacRaporStatusHelper::STATUS_EXPIRING) {
                                    $wrapClass .= ' esh-hasta-kart-diagnosis--expiring';
                                    $raporClass .= ' esh-hasta-kart-diagnosis__rapor--expiring';
                                    $raporTitle .= ' — 30 gün içinde bitiyor';
                                } elseif ($raporStatus === HastaIlacRaporStatusHelper::STATUS_RAPORLU) {
                                    $raporClass .= ' esh-hasta-kart-diagnosis__rapor--active';
                                }
                                $hTanParts[] = '<span class="' . $wrapClass . '">'
                                    . '<sup class="' . $raporClass . '" title="' . htmlspecialchars($raporTitle, ENT_QUOTES, 'UTF-8') . '">R</sup>'
                                    . '<span class="' . $labelClass . '">' . $lab . '</span>'
                                    . $statusBadge
                                    . '</span>';
                            } else {
                                $hTanParts[] = '<span>' . $lab . '</span>';
                            }
                        }
                        echo implode(', ', $hTanParts);
                        ?>
                    <?php else: ?>
                        <em class="text-muted">Tanı girilmemiş</em>
                    <?php endif; ?>
                </div>
            </div>

            <?php include ROOT_PATH . '/views/site/hasta/partials/klinik_ilac_rapor_ozet.php'; ?>

            <?php $aktifCihazlar = PatientClinicalFlagsHelper::activeFlags($hasta); ?>
            <?php if ($aktifCihazlar !== []): ?>
            <div class="esh-kimlik-field mt-2">
                <div class="fw-bold text-muted small mb-1">Aktif cihaz / destek özeti</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($aktifCihazlar as $chip): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?= htmlspecialchars(PatientClinicalFlagsHelper::label($chip['key']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) $chip['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $barthelLatestRow = $barthelLatest ?? null;
            $barthelTotal = (int) ($barthelLatestRow->toplam_skor ?? 0);
            $barthelStatusMeta = BarthelScaleHelper::resolveDependencyLevel($barthelTotal);
            $barthelStatus = $barthelLatestRow
                ? (string) ($barthelLatestRow->bagimlilik_duzeyi ?? $barthelStatusMeta['label'])
                : '—';

            $bkiBoyCm = \App\Helpers\BmiHelper::normalizeBoyCm($hasta->boy ?? null);
            $bkiKiloKg = \App\Helpers\BmiHelper::normalizeKiloKg($hasta->kilo ?? null);
            $bkiGoster = '—';
            if ($bkiBoyCm !== null && $bkiKiloKg !== null) {
                $bkiDeger = \App\Helpers\BmiHelper::calculateBmi($bkiKiloKg, $bkiBoyCm);
                $bkiKategori = \App\Helpers\BmiHelper::categories()[\App\Helpers\BmiHelper::classifyBmi($bkiDeger)]['label'] ?? '';
                $bkiGoster = $bkiDeger . ($bkiKategori !== '' ? ' (' . $bkiKategori . ')' : '');
            }
            $boyGoster = '—';
            if ($bkiBoyCm !== null) {
                $boyRounded = round($bkiBoyCm, 1);
                $boyGoster = (abs($boyRounded - round($boyRounded)) < 0.05)
                    ? (string) (int) round($boyRounded)
                    : rtrim(rtrim(number_format($boyRounded, 1, '.', ''), '0'), '.');
            }
            $kiloGoster = '—';
            if ($bkiKiloKg !== null) {
                $kiloRounded = round($bkiKiloKg, 1);
                $kiloGoster = (abs($kiloRounded - round($kiloRounded)) < 0.05)
                    ? (string) (int) round($kiloRounded)
                    : rtrim(rtrim(number_format($kiloRounded, 1, '.', ''), '0'), '.');
            }
            ?>
            <div class="row g-2 mt-2 small">
                <div class="col-sm-6">
                    <span class="text-muted">Barthel İndeksi:</span>
                    <span class="fw-semibold text-dark"><?= (int) $barthelTotal ?> puan — <?= htmlspecialchars($barthelStatus, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">BKİ:</span>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars((string) $bkiGoster, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-muted ms-2">Boy:</span>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($boyGoster, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-muted ms-2">Kilo:</span>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($kiloGoster, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
