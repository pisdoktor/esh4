<div class="esh-page esh-page--detail esh-page-hasta container-fluid py-4">
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="<?= htmlspecialchars(esh_url('Patient', 'unified', ['status' => 'active']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm px-3" aria-label="Hasta listesine dön">
                    <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Geri
                </a>
                <button type="button" class="btn btn-danger btn-sm px-3 shadow-sm"<?= !empty($pasifDosyaKapali) ? ' disabled aria-disabled="true" title="Pasif dosyada MERNİS sorgusu yapılamaz"' : ' onclick="tekliMernisSorgula(\'' . htmlspecialchars((string) $hasta->tckimlik, ENT_QUOTES, 'UTF-8') . '\')"' ?> aria-label="MERNİS ile vefat sorgula">
                    <i class="fa-solid fa-sync me-1" aria-hidden="true"></i> MERNİS Sorgula
                </button>

                <?php if (\App\Services\MesajService::canUseMessaging((int) ($_SESSION['user_id'] ?? 0))): ?>
                <a href="<?= htmlspecialchars(esh_url('Mesaj', 'patientThread', ['id' => (int) ($hasta->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-info btn-sm px-3 shadow-sm text-white" title="Hasta hakkında mesajlaş">
                    <i class="fa-solid fa-comments me-1" aria-hidden="true"></i> Mesajlar
                </a>
                <?php endif; ?>

                <?php if (\App\Services\Sms\SmsService::canUseSms((int) ($_SESSION['user_id'] ?? 0))): ?>
                <a href="<?= htmlspecialchars(esh_url('Sms', 'quickFromPatient', ['hasta_id' => (int) ($hasta->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm px-3 shadow-sm" title="Hastaya SMS gönder">
                    <i class="fa-solid fa-comment-sms me-1" aria-hidden="true"></i> SMS
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle px-3 shadow-sm" type="button" data-bs-toggle="dropdown" data-bs-display="static">
                        <i class="fa-solid fa-user-doctor me-1"></i> Tıbbi İşlemler
                    </button>
                    <?php require __DIR__ . '/partials/tibbi_islemler_menu.php'; ?>
                </div>

                <div class="dropdown">
                    <button class="btn btn-warning btn-sm dropdown-toggle px-3 shadow-sm" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Hasta bilgisi parçalı düzenleme menüsü">
                        <i class="fa-solid fa-user-pen me-1"></i> Hasta Bilgisi Düzenle
                    </button>
                    <?php require __DIR__ . '/partials/hasta_bilgisi_duzenle_menu.php'; ?>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <?php
                    $ana_tel = preg_replace('/[^0-9]/', '', (string)($hasta->ceptel1 ?? ''));
                    if ($ana_tel !== '' && substr($ana_tel, 0, 1) === '0') {
                        $ana_tel = '9' . $ana_tel;
                    }
                ?>
                <?php if ($ana_tel !== ''): ?>
                <a href="https://wa.me/<?= htmlspecialchars($ana_tel, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm rounded-circle shadow-sm" title="WhatsApp ile yaz" aria-label="WhatsApp ile yaz">
                    <i class="fa-brands fa-whatsapp fa-lg" aria-hidden="true"></i>
                </a>
                <?php else: ?>
                <span class="btn btn-success btn-sm rounded-circle shadow-sm disabled opacity-50" title="Cep telefonu yok" aria-disabled="true"><i class="fa-brands fa-whatsapp fa-lg" aria-hidden="true"></i></span>
                <?php endif; ?>
                <?php if (!empty($hasta->ceptel1)): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', (string)$hasta->ceptel1), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm rounded-circle shadow-sm" title="Telefonu ara" aria-label="Telefonu ara">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                </a>
                <?php else: ?>
                <span class="btn btn-primary btn-sm rounded-circle shadow-sm disabled opacity-50" title="Telefon yok" aria-disabled="true"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
            $doneCount = (int) ($hasta->izlemsayisi ?? 0);
            $missedCount = (int) ($hasta->yizlemsayisi ?? 0);
            $plannedCount = (int) ($hasta->totalplanli ?? 0);
            $tcQ = urlencode((string) ($hasta->tckimlik ?? ''));
            $lastDoneDate = !empty($hasta->son_yapilan_tarih) ? \App\Helpers\DateHelper::toTr((string) $hasta->son_yapilan_tarih) : '—';
            $lastDoneOps = trim((string) ($hasta->son_yapilan_islemler ?? '')) !== '' ? (string) $hasta->son_yapilan_islemler : 'İşlem bilgisi yok';
            $lastDoneBy = trim((string) ($hasta->son_yapilan_yapanlar ?? '')) !== '' ? (string) $hasta->son_yapilan_yapanlar : 'Kayıtlı değil';
            $lastMissDate = !empty($hasta->son_yapilmayan_tarih) ? \App\Helpers\DateHelper::toTr((string) $hasta->son_yapilmayan_tarih) : '—';
            $lastMissOps = trim((string) ($hasta->son_yapilmayan_islemler ?? '')) !== '' ? (string) $hasta->son_yapilmayan_islemler : 'İşlem bilgisi yok';
            $lastMissBy = trim((string) ($hasta->son_yapilmayan_yapanlar ?? '')) !== '' ? (string) $hasta->son_yapilmayan_yapanlar : 'Kayıtlı değil';
            $lastPlanDate = !empty($hasta->son_planli_tarih) ? \App\Helpers\DateHelper::toTr((string) $hasta->son_planli_tarih) : '—';
            $lastPlanOps = trim((string) ($hasta->son_planli_islemler ?? '')) !== '' ? (string) $hasta->son_planli_islemler : 'İşlem bilgisi yok';
            $lastPlanBy = trim((string) ($hasta->son_planli_planlayanlar ?? '')) !== '' ? (string) $hasta->son_planli_planlayanlar : 'Kayıtlı değil';
        ?>
        <?php include ROOT_PATH . '/views/site/hasta/partials/izlem_ozet_kutulari.php'; ?>
    </div>

    <div class="card-body bg-light">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="bg-white p-4 rounded border shadow-sm h-100">
                    <?php
                        $patientPhotoUrl = !empty($hasta->profil_foto)
                            ? (esh_upload_url('patients', (string) $hasta->profil_foto))
                            : '';
                    ?>
                    <?php include ROOT_PATH . '/views/site/hasta/partials/pasif_dosya_banner.php'; ?>

                    <?php if (\App\Helpers\PatientCompletenessHelper::isVisibleForPatient($hasta)): ?>
                    <?php include ROOT_PATH . '/views/site/hasta/partials/kart_doluluk_compact.php'; ?>
                    <?php endif; ?>

                    <?php include ROOT_PATH . '/views/site/hasta/partials/kimlik_karti.php'; ?>

                </div>
            </div>

            <div class="col-lg-5 d-flex flex-column gap-4">
                <div class="bg-white rounded border shadow-sm overflow-hidden">
                    <?php
                    $canToggleClinicalFlags = \App\Models\Patient::isAktif($hasta->pasif ?? null);
                    include __DIR__ . '/partials/detail_care_tabs.php';
                    ?>
                </div>

                <div class="card shadow-sm border-0 border-top border-warning border-4 rounded">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 fw-bold text-warning small"><i class="fa-solid fa-note-sticky me-2"></i>Hasta Notları</h6>
                        <button class="btn btn-warning btn-sm border-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#changenotes">
                            <i class="fa-solid fa-plus text-dark"></i>
                        </button>
                    </div>
                    <div class="card-body bg-light esh-patient-notes-scroll">
    <?php
$allNotes = json_decode((string)($hasta->notes ?? ''), true);
if (!empty($allNotes) && is_array($allNotes)):
    // Notları orijinal sırasıyla (index bozulmadan) alalım ama görsel olarak tersten dönelim
    $reversedNotes = array_reverse($allNotes, true);
    foreach ($reversedNotes as $index => $note):
?>
    <div class="p-3 bg-white rounded border border-warning shadow-sm mb-3 position-relative note-item">
    <button type="button" 
            class="btn btn-link text-danger position-absolute top-0 end-0 m-1 p-1" 
            onclick="deleteNote(this, <?php echo $hasta->id; ?>, <?php echo $index; ?>)"
            title="Notu Sil">
        <i class="fa-solid fa-trash-can shadow-sm"></i>
    </button>

        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2 pe-4">
            <span class="badge bg-warning text-dark small">
                <i class="fa-solid fa-calendar-day me-1"></i> <?php echo htmlspecialchars((string) ($note['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <p class="mb-0 text-dark small esh-patient-note-pre"><?php echo htmlspecialchars($note['message'] ?? ''); ?></p>
    </div>
<?php 
    endforeach; 
else:
echo "Henüz not girilmemiş";
endif; 
?>
</div>
                </div>

                <?php if (\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($hasta)): ?>
                <?php include ROOT_PATH . '/views/site/hasta/partials/wound_photos_summary.php'; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changenotes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= htmlspecialchars(esh_url('Patient', 'updateNotes'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="id" value="<?php echo $hasta->id; ?>">
            
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-edit me-2"></i>Hasta Notu Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            
            <div class="modal-body p-4">
    <label class="form-label fw-bold small text-muted">Yeni Not Ekle:</label>
    <textarea class="form-control border-warning shadow-none esh-patient-note-textarea"
              name="new_note"
              rows="4"
              placeholder="Mesajınızı yazın (Tarih otomatik eklenecektir)..."></textarea>
    
    <div class="mt-3 p-2 bg-light rounded border">
        <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Önceki notlar korunacak ve bu not listenin başına eklenecektir.</small>
    </div>
</div>
            
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="clearNotesArea()">
                    <i class="fa-solid fa-trash-can me-1"></i> Tümünü Sil
                </button>
                <div class="ms-auto">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-save me-1"></i> Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="shownotes" tabindex="-1" aria-labelledby="shownotesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="shownotesLabel">
                    <i class="fa-solid fa-sticky-note me-2"></i> Önemli Hasta Notları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-3 bg-light esh-patient-notes-modal">
                <?php
$allNotes = json_decode((string)($hasta->notes ?? ''), true);
if (!empty($allNotes) && is_array($allNotes)):
    // Notları orijinal sırasıyla (index bozulmadan) alalım ama görsel olarak tersten dönelim
    $reversedNotes = array_reverse($allNotes, true);
    foreach ($reversedNotes as $index => $note):
?>
    <div class="p-3 bg-white rounded border border-warning shadow-sm mb-3 position-relative note-item">
    <button type="button" 
            class="btn btn-link text-danger position-absolute top-0 end-0 m-1 p-1" 
            onclick="deleteNote(this, <?php echo $hasta->id; ?>, <?php echo $index; ?>)"
            title="Notu Sil">
        <i class="fa-solid fa-trash-can shadow-sm"></i>
    </button>

        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2 pe-4">
            <span class="badge bg-warning text-dark small">
                <i class="fa-solid fa-calendar-day me-1"></i> <?php echo htmlspecialchars((string) ($note['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <p class="mb-0 text-dark small esh-patient-note-pre"><?php echo htmlspecialchars($note['message'] ?? ''); ?></p>
    </div>
<?php 
    endforeach; 
endif; 
?>
            </div>
            <div class="modal-footer bg-white border-top-0">
                <small class="text-muted me-auto small">
                    <i class="fa-solid fa-info-circle me-1"></i> Toplam <?php echo !empty($allNotes) ? count($allNotes) : 0; ?> not bulundu.
                </small>
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-warning btn-sm px-4 fw-bold shadow-sm"<?= !empty($pasifDosyaKapali) ? ' disabled aria-disabled="true" title="Pasif dosyada not eklenemez"' : ' data-bs-toggle="modal" data-bs-target="#changenotes"' ?>>
                    <i class="fa-solid fa-plus me-1"></i> Yeni Not Ekle
                </button>
            </div>
        </div>
    </div>
</div>
<div id="patient-detail-config"
     data-has-notes="<?= (strlen((string) ($hasta->notes ?? '')) > 3) ? '1' : '0' ?>"
     data-open-modal="<?= htmlspecialchars((string) ($_GET['open_modal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-patient-id="<?= (int) ($hasta->id ?? 0) ?>"
     data-clinical-toggle="<?= \App\Models\Patient::isAktif($hasta->pasif ?? null) ? '1' : '0' ?>"
     hidden></div>
<?php if (!empty($eshPatientEditModalsReady)): ?>
    <?php require __DIR__ . '/partials/hasta_edit_modals.php'; ?>
<?php endif; ?>
<?php if (\App\Helpers\PatientCompletenessHelper::isVisibleForPatient($hasta)): ?>
<?php include __DIR__ . '/partials/kart_doluluk_modal.php'; ?>
<?php endif; ?>
</div>