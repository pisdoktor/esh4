<div class="esh-page esh-page--form esh-page-hasta container-fluid py-4">
    <form action="<?= htmlspecialchars(esh_url('Patient', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST" id="patientForm" class="needs-validation" novalidate data-esh-submit-guard="off" data-esh-required-legend="off">
        <input type="hidden" name="id" value="<?= $patient->id ?>">
        <div id="patientFormValidationSummary" class="alert alert-danger border-danger shadow-sm<?= !empty($_SESSION['patient_edit_validation_fields']) ? '' : ' d-none' ?> mb-4" role="alert" aria-live="polite">
            <?php if (!empty($_SESSION['patient_edit_validation_fields']) && is_array($_SESSION['patient_edit_validation_fields'])): ?>
                <strong><i class="fa-solid fa-circle-exclamation me-1"></i>Kayıt tamamlanamadı.</strong>
                Aşağıdaki alanları doldurun veya düzeltin:
                <ul class="mb-0 mt-2 ps-3 small">
                    <?php foreach ($_SESSION['patient_edit_validation_fields'] as $vfLabel): ?>
                        <li><?= htmlspecialchars((string) $vfLabel, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['patient_edit_validation_fields']); ?>
            <?php endif; ?>
        </div>
        <?php if ((int) ($patient->pasif ?? 0) === 1): ?>
            <?php
            $eshPassiveReasonLabel = \App\Helpers\PatientCareHelper::pasifDosyaNedeniLabelByCode($patient->pasifnedeni ?? '');
            $eshPassiveDateTr = !empty($patient->pasiftarihi) ? \App\Helpers\DateHelper::toTr($patient->pasiftarihi) : '';
            ?>
            <div class="alert alert-warning border-warning shadow-sm mb-4 d-flex gap-3 esh-patient-edit-passive-banner" role="status">
                <div class="fs-4 text-warning flex-shrink-0"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold">Bu hasta kaydı <span class="text-dark">pasif</span> (kapalı dosya).</div>
                    <p class="small text-muted mb-0 mt-1">
                        <?php if ($eshPassiveReasonLabel !== '' && $eshPassiveReasonLabel !== 'Tanımsız'): ?>
                            Pasif nedeni: <strong class="text-body"><?= htmlspecialchars($eshPassiveReasonLabel, ENT_QUOTES, 'UTF-8') ?></strong>.
                        <?php endif; ?>
                        <?php if ($eshPassiveDateTr !== ''): ?>
                            Pasif tarihi: <strong class="text-body"><?= htmlspecialchars($eshPassiveDateTr, ENT_QUOTES, 'UTF-8') ?></strong>.
                        <?php endif; ?>
                        Değişiklik yapabilirsiniz; kaydı yeniden <strong class="text-body">aktif</strong> etmek için aşağıdaki «Dosya Durumu» bölümünü kullanın.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-primary  fw-bold py-3"><i class="fa-solid fa-id-card me-2"></i> Kimlik ve İletişim</div>
                    <div class="card-body">
                        <?php
                        $guvenceList = $guvence;
                        $eshFormIdPrefix = '';
                        include ROOT_PATH . '/views/site/hasta/partials/edit_sections/kimlik_iletisim.php';
                        ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white fw-bold py-3"><i class="fa-solid fa-folder-open me-2"></i> Dosya Seçenekleri</div>
                    <div class="card-body">
                        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/dosya_secenekleri.php'; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 border-start border-info border-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-info">
            <i class="fa-solid fa-weight-scale me-2"></i>Fiziksel Ölçümler
        </h6>
    </div>
    <div class="card-body">
        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/fiziksel_olcumler.php'; ?>
    </div>
</div>



                <div class="mb-4">
                    <?php include ROOT_PATH . '/views/site/hasta/partials/barthel_form.php'; ?>
                </div>

                <div class="card shadow-sm border-0 mb-4 border-start border-warning border-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-warning">
            <i class="fa-solid fa-note-sticky me-2"></i>Hasta Notları Yönetimi
        </h6>
    </div>
    <div class="card-body">
        <?php include ROOT_PATH . '/views/site/hasta/partials/edit_sections/notlar.php'; ?>
    </div>
</div>

            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-success fw-bold py-3">
                        <span><i class="fa-solid fa-map-location-dot me-2"></i> Adres Bilgileri</span>
                    </div>
                    <div class="card-body">
                        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/adres.php'; ?>
                    </div>
                </div>

            <div class="card shadow-sm border-0 mb-4 border-start border-primary border-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-primary">
            <i class="fa-solid fa-notes-medical me-2"></i>Klinik Tanılar
        </h6>
        <span class="badge bg-light text-primary border fw-normal">ICD-10 Uyumlu</span>
    </div>
    <div class="card-body">
        <?php include ROOT_PATH . '/views/site/hasta/partials/edit_sections/klinik_tanilar.php'; ?>

        <div class="alert alert-info py-2 px-3 mb-0 border-0 shadow-xs">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-lightbulb me-2"></i>
                <div class="small">Birden fazla tanı seçmek için listeyi kontrol edin. Seçilen tanılar profil sayfasında otomatik listelenir.</div>
            </div>
        </div>
    </div>
</div>

            <div class="card shadow-sm border-0 mb-4 border-start border-danger border-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-danger">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Klinik Uyarılar
        </h6>
    </div>
    <div class="card-body">
        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/klinik_uyarilar.php'; ?>
    </div>
</div>


                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-danger  fw-bold py-3"><i class="fa-solid fa-microchip me-2"></i> Tıbbi Cihaz ve Destek</div>
                    <div class="card-body">
                        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/tibbi_cihaz.php'; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-warning text-dark fw-bold py-3"><i class="fa-solid fa-box-open me-2"></i> Bakım ve Sarf Malzeme</div>
                    <div class="card-body">
                        <?php $eshFormIdPrefix = ''; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/bakim_sarf.php'; ?>
                    </div>
                </div>

                <?php
                $eshEditFullAccess = true;
                include ROOT_PATH . '/views/site/hasta/partials/edit_dosya_durumu.php';
                ?>
            </div>
        </div>

        <div class="esh-patient-edit-sticky-actions position-sticky bottom-0 mt-4 pt-2 bg-body border-top shadow-sm rounded-3">
            <div class="card shadow border-0">
                <div class="card-body text-center p-3 bg-light d-flex flex-wrap justify-content-center align-items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm rounded-pill" id="patientFormSubmit">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i> Kaydı Tamamla
                    </button>
                    <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => (int) $patient->id]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-lg rounded-pill">İptal</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
preg_match('/<select\b[^>]*>(.*)<\/select>/is', $lists['ilce'], $__editMainIlce);
$editMainIlceOptionsInner = $__editMainIlce[1] ?? '';
?>
<div id="patient-edit-config"
     data-main-ilce-options="<?= htmlspecialchars($editMainIlceOptionsInner, ENT_QUOTES, 'UTF-8') ?>"
     data-patient-id="<?= (int) ($patient->id ?? 0) ?>"
     data-yupas-guvence-ids="<?= htmlspecialchars((string) ($yupasGuvenceIdCsv ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     hidden></div>
