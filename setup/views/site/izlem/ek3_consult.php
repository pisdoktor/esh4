<?php
/** @var object $visit */
/** @var object $patient */
/** @var array $branslar */
/** @var array $isteklerList */
/** @var int[] $selBrans */
/** @var array<int, int[]> $selBransIstek */
use App\Helpers\AuthHelper;
$pid = (int) ($patient->id ?? 0);
$vid = (int) ($visit->id ?? 0);
$tcRaw = (string) ($patient->tckimlik ?? '');
$tc = $tcRaw;
$tcQ = isset($tcQ) ? (string) $tcQ : rawurlencode($tcRaw);
$isAdmin = AuthHelper::sessionIsAdmin();
$isSuperAdmin = \App\Helpers\AuthHelper::sessionIsSuperAdmin();
$selBrans = $selBrans ?? [];
$selBransIstek = $selBransIstek ?? [];
$patientIdForHeader = (int) ($patientIdForHeader ?? $pid);
$patientLabel = trim((string) ($patientLabel ?? ''));
if ($patientLabel === '') {
    $patientLabel = trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''));
}
$histErkek = isset($histErkek) ? (bool) $histErkek : (($patient->cinsiyet ?? '') === 'E' || ($patient->cinsiyet ?? '') === '1');
$histAktif = isset($histAktif) ? (bool) $histAktif : \App\Models\Patient::isAktif($patient->pasif ?? null);
$histNameColor = $histErkek ? '#0d6efd' : '#dc3545';
$izlemTarihiTr = \App\Helpers\DateHelper::toTrOrEmpty((string) ($visit->izlemtarihi ?? ''));
$ek3ConsultWrapper = $ek3ConsultWrapper ?? 'div';
$ek3ConsultWrapperClass = $ek3ConsultWrapperClass ?? 'container-fluid py-4 px-3 px-lg-4 izlem-form-page izlem-ek3-consult-page';
$ek3ConsultCardClass = $ek3ConsultCardClass ?? 'card border-0 shadow rounded-3 overflow-hidden';
$ek3ConsultShowA11yHeader = !empty($ek3ConsultShowA11yHeader);
$ek3ConsultWrapperTag = preg_match('/^[a-z][a-z0-9]*$/i', (string) $ek3ConsultWrapper) ? (string) $ek3ConsultWrapper : 'div';
?>
<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<<?= $ek3ConsultWrapperTag ?> class="<?= htmlspecialchars((string) $ek3ConsultWrapperClass, ENT_QUOTES, 'UTF-8') ?>" lang="tr" id="konsForm">
<?php if ($ek3ConsultShowA11yHeader): ?>
<header class="visually-hidden"><h1>Konsültasyon — EK-3 öncesi bilgiler</h1></header>
<?php endif; ?>

    <div class="mb-3 esh-patient-page-header">
        <h4 class="fw-bold text-dark mb-2">
            <i class="fa-solid fa-stethoscope text-primary me-2"></i>Konsültasyon — EK-3
        </h4>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 gap-md-3 esh-patient-page-header__meta">
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <?php if ($patientLabel !== ''): ?>
                    <div class="dropdown esh-history-patient-dropdown">
                        <a href="#"
                           class="patient-link esh-patient-page-header__name dropdown-toggle d-inline-block text-decoration-none fw-bold"
                           role="button"
                           data-bs-toggle="dropdown"
                           data-bs-display="static"
                           aria-expanded="false"
                           style="color:<?= htmlspecialchars($histNameColor, ENT_QUOTES, 'UTF-8') ?>;">
                            <?= htmlspecialchars($patientLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php
                        $izlemPatientHeaderMenuActive = 'history';
                        include ROOT_PATH . '/views/site/partials/izlem_patient_header_dropdown.php';
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ($tcRaw !== ''): ?>
                    <code class="text-dark small"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tcRaw), ENT_QUOTES, 'UTF-8') ?></code>
                <?php endif; ?>
            </div>
            <div class="text-muted small">
                <?php if ($izlemTarihiTr !== ''): ?>
                    <span class="me-2"><i class="fa-solid fa-calendar-day me-1"></i><?= htmlspecialchars($izlemTarihiTr, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span><i class="fa-solid fa-hashtag me-1"></i>İzlem #<?= $vid ?></span>
            </div>
        </div>
    </div>

    <div class="<?= htmlspecialchars((string) $ek3ConsultCardClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-header bg-primary text-white py-3 px-4">
            <h5 class="mb-0 fw-bold">EK-3 öncesi bilgiler</h5>
            <p class="small mb-0 mt-2 opacity-90">1) Soldan branşları seçin &nbsp; 2) Her branş için sağda başvuru amacını işaretleyin. Kayıttan sonra her branş EK-3 formunda ayrı sekme olur.</p>
        </div>
        <div class="card-body p-4">
            <?php if (empty($isteklerList)): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <strong>Başvuru amacı</strong> listesi boş (ESH başvuru seçenekleri tanımlı değil).
                    <?php if ($isAdmin): ?>
                        <a href="<?= htmlspecialchars(esh_url('Istek', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="alert-link fw-semibold">Yönetim — EK-3 başvuru amaçları</a> sayfasından kayıt ekleyin veya
                        <code>database/archive/migrate_add_konsultasyon_ek3.sql</code> veya güncel <code>database/schemas/schema.sql</code> ile varsayılanları yükleyin.
                    <?php else: ?>
                        Lütfen sistem yöneticinize başvurun.
                    <?php endif ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars(esh_url('Visit', 'ek3SavePrint'), ENT_QUOTES, 'UTF-8') ?>" class="vstack gap-4" id="eshEk3ConsultForm" novalidate>
                <input type="hidden" name="izlem_id" value="<?= $vid ?>">
                <input type="hidden" name="hasta_id" value="<?= $pid ?>">
                <input type="hidden" name="tc" value="<?= htmlspecialchars($tcRaw, ENT_QUOTES, 'UTF-8') ?>">

                <div id="eshEk3FormAlert" class="alert alert-danger border-0 shadow-sm d-none mb-0" role="alert"></div>

                <div class="izlem-ek3-summary izlem-ek3-summary--empty border rounded-3 px-3 py-3" id="eshEk3Summary" aria-live="polite">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 gap-md-3">
                        <div class="izlem-ek3-summary__status d-flex align-items-center gap-2 min-w-0">
                            <span class="izlem-ek3-summary__icon rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </span>
                            <div class="js-ek3-summary-text mb-0">Henüz branş seçilmedi.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 js-ek3-summary-chips"></div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <div class="fw-semibold text-dark mb-0">Branş ve başvuru amacı</div>
                        <div class="text-muted small">Soldan branş seçin; sağda o branşa özel başvuru amacını işaretleyin.</div>
                    </div>
                    <?php if ($isSuperAdmin && !empty($branslar)): ?>
                        <a href="<?= htmlspecialchars(esh_url('Brans', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success rounded-pill flex-shrink-0">
                            <i class="fa-solid fa-gear me-1"></i>Branşları Yönet
                        </a>
                    <?php elseif ($isAdmin && !empty($isteklerList)): ?>
                        <a href="<?= htmlspecialchars(esh_url('Istek', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0">
                            <i class="fa-solid fa-gear me-1"></i>Başvuru amaçlarını yönet
                        </a>
                    <?php endif ?>
                </div>

                <?php if (empty($branslar)): ?>
                    <div class="alert alert-light border small mb-0 py-2">Tanımlı branş bulunamadı.</div>
                <?php else: ?>
                    <div class="row g-0 izlem-ek3-layout border rounded-3 shadow-sm overflow-hidden">
                        <div class="col-12 col-lg-4 izlem-ek3-layout__master">
                            <div class="px-3 py-2 border-bottom bg-body-tertiary bg-opacity-50">
                                <div class="fw-semibold small text-dark mb-0">Branşlar</div>
                                <div class="text-muted izlem-arac-picker__hint small">Seçmek için işaretleyin; düzenlemek için satıra tıklayın.</div>
                            </div>
                            <ul class="list-unstyled mb-0 izlem-ek3-brans-master" role="listbox" aria-label="Konsültasyon branşları">
                                <?php foreach ($branslar as $b): ?>
                                    <?php
                                    $bid = (int) ($b->id ?? 0);
                                    if ($bid < 1) {
                                        continue;
                                    }
                                    $brAd = htmlspecialchars((string) ($b->bransadi ?? ''), ENT_QUOTES, 'UTF-8');
                                    $brid = 'br' . $vid . '_' . $bid;
                                    $isBransChecked = in_array($bid, $selBrans, true);
                                    $branchIstekIds = $selBransIstek[$bid] ?? [];
                                    $hasIstek = $branchIstekIds !== [];
                                    $statusClass = !$isBransChecked ? '' : ($hasIstek ? ' izlem-ek3-brans-master__item--complete' : ' izlem-ek3-brans-master__item--incomplete');
                                    $detailId = 'ek3-detail-' . $vid . '-' . $bid;
                                    ?>
                                    <li class="izlem-ek3-brans-master__item<?= $statusClass ?>"
                                        role="option"
                                        data-brans-id="<?= $bid ?>"
                                        data-brans-name="<?= $brAd ?>"
                                        aria-selected="false"
                                        aria-controls="<?= htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8') ?>">
                                        <input class="form-check-input flex-shrink-0 mt-0 js-ek3-brans-toggle" type="checkbox" name="brans[]" id="<?= htmlspecialchars($brid, ENT_QUOTES, 'UTF-8') ?>"
                                               value="<?= $bid ?>" autocomplete="off"<?= $isBransChecked ? ' checked' : '' ?>>
                                        <button type="button" class="izlem-ek3-brans-master__label btn btn-link text-start text-decoration-none p-0 flex-grow-1 js-ek3-brans-activate">
                                            <i class="fa-solid fa-user-doctor text-success me-1"></i><?= $brAd ?>
                                        </button>
                                        <span class="badge rounded-pill js-ek3-brans-badge<?= !$isBransChecked ? ' bg-secondary-subtle text-secondary' : ($hasIstek ? ' bg-success-subtle text-success' : ' bg-warning-subtle text-warning-emphasis') ?>">
                                            <?= !$isBransChecked ? 'Seçilmedi' : ($hasIstek ? 'Tamam' : 'İstek eksik') ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="col-12 col-lg-8 izlem-ek3-layout__detail">
                            <div class="izlem-ek3-detail-empty px-3 py-4 text-center text-muted js-ek3-detail-empty">
                                <i class="fa-solid fa-hand-pointer fa-2x mb-2 opacity-50"></i>
                                <div class="small">Soldan bir branş seçin veya işaretleyin; başvuru amaçları burada görünür.</div>
                            </div>

                            <?php foreach ($branslar as $b): ?>
                                <?php
                                $bid = (int) ($b->id ?? 0);
                                if ($bid < 1) {
                                    continue;
                                }
                                $brAd = htmlspecialchars((string) ($b->bransadi ?? ''), ENT_QUOTES, 'UTF-8');
                                $detailId = 'ek3-detail-' . $vid . '-' . $bid;
                                $branchIstekIds = $selBransIstek[$bid] ?? [];
                                ?>
                                <div class="izlem-ek3-brans-detail d-none" id="<?= htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8') ?>"
                                     data-brans-id="<?= $bid ?>" data-brans-name="<?= $brAd ?>" hidden aria-live="polite">
                                    <div class="px-3 py-2 border-bottom bg-primary bg-opacity-10">
                                        <div class="fw-semibold small text-dark mb-0">
                                            <i class="fa-solid fa-clipboard-list text-primary me-1"></i>
                                            <span class="js-ek3-detail-brans-name"><?= $brAd ?></span> — başvuru amacı
                                        </div>
                                        <div class="text-muted izlem-arac-picker__hint small">Bu branş için EK-3 başvuru amacını seçin (birden fazla işaretlenebilir).</div>
                                    </div>
                                    <div class="px-3 py-3">
                                        <?php if (empty($isteklerList)): ?>
                                            <div class="alert alert-light border small mb-0 py-2">Başvuru amacı listesi boş.</div>
                                        <?php else: ?>
                                            <?php
                                            $ek3IstekVid = $vid;
                                            $ek3IstekBid = $bid;
                                            $ek3IstekSelectedIds = $branchIstekIds;
                                            include ROOT_PATH . '/views/site/izlem/partials/ek3_istek_choices.php';
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="kons-aciklama-<?= $vid ?>" class="form-label fw-semibold">Açıklama</label>
                    <textarea name="aciklama" id="kons-aciklama-<?= $vid ?>" class="form-control shadow-sm" rows="4"
                              placeholder="Konsültasyon / izlem notu"><?= htmlspecialchars((string) ($visit->aciklama ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="form-text small">Bu metin izlem kaydındaki açıklama alanına yazılır.</div>
                </div>

                <div class="izlem-ek3-form-actions d-flex flex-wrap gap-2 justify-content-between pt-2 border-top">
                    <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($tcRaw)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary rounded-pill px-4">İzlem geçmişine dön</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Kaydet ve EK-3 formunu göster
                    </button>
                </div>
            </form>
        </div>
    </div>
</<?= $ek3ConsultWrapperTag ?>>
</div>
