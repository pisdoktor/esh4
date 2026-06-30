<div class="esh-page esh-page--form esh-page-hasta container-fluid py-4">
<form action="<?= htmlspecialchars(esh_url('Patient', 'fsave'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="needs-validation" novalidate id="patientForm" data-esh-required-legend="off">
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-primary py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa-solid fa-user-plus me-2"></i>Yeni Hasta İlk Kayıt
            </h5>
        </div>

        <div class="card-body bg-light-50 p-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-info-subtle text-info-emphasis fw-bold py-3 px-3 border-0">
                            <i class="fa-solid fa-id-card me-2"></i> Kimlik ve Kişisel Bilgiler
                        </div>
                        <div class="card-body p-4">
                            <?php $eshBekleyenMode = 'ilkkayit'; include ROOT_PATH . '/views/site/hasta/partials/edit_sections/bekleyen_kimlik.php'; ?>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold py-3 px-3 border-0">
                            <i class="fa-solid fa-phone me-2"></i> İletişim Bilgileri
                        </div>
                        <div class="card-body p-4">
                            <?php include ROOT_PATH . '/views/site/hasta/partials/edit_sections/bekleyen_iletisim.php'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 mb-4">
                        <div class="card-header bg-success-subtle text-success-emphasis d-flex justify-content-between align-items-center py-3 px-3 border-0">
                            <span class="fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i> Adres Bilgileri</span>
                            <button type="button" class="btn btn-outline-success btn-sm fw-bold" id="btn-add-address"><i class="fa-solid fa-plus me-1"></i> Yeni Adres</button>
                        </div>
                        <div class="card-body p-4">
                            <?php include ROOT_PATH . '/views/site/hasta/partials/edit_sections/bekleyen_adres.php'; ?>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-secondary-subtle text-secondary-emphasis fw-bold py-3 px-3 border-0">
                            <i class="fa-solid fa-clipboard-list me-2"></i> Kayıt ve Randevu Detayları
                        </div>
                        <div class="card-body p-4">
                            <?php include ROOT_PATH . '/views/site/hasta/partials/edit_sections/kayit_randevu.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white py-3 border-top">
            <div class="d-flex justify-content-end gap-2">
                <a href="javascript:history.go(-1);" class="btn btn-light border px-4 rounded-pill">
                    <i class="fa-solid fa-xmark me-2"></i>İptal
                </a>
                <button type="submit" id="save" class="btn btn-primary px-5 rounded-pill shadow-sm" aria-busy="false">
                    <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Hastayı Kaydet
                </button>
            </div>
        </div>
    </div>
</form>
<div id="patient-ilkkayit-config" hidden></div>
</div>
