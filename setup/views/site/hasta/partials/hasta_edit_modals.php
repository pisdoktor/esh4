<?php
declare(strict_types=1);
/**
 * Hasta kartı — parçalı düzenleme modalları (Patient/store + partial_section).
 *
 * @var object $patient
 * @var list<object> $guvenceList
 * @var array<string, string> $lists
 * @var string $hast
 * @var string $yupasGuvenceIdCsv
 */
$eshEditSections = [
    'kimlik_iletisim' => ['title' => 'Kimlik ve İletişim', 'icon' => 'fa-id-card', 'dialog' => 'modal-lg'],
    'dosya_secenekleri' => ['title' => 'Dosya Seçenekleri', 'icon' => 'fa-folder-open', 'dialog' => 'modal-lg'],
    'fiziksel_olcumler' => ['title' => 'Fiziksel Ölçümler', 'icon' => 'fa-weight-scale', 'dialog' => 'modal-lg'],
    'adres' => ['title' => 'Adres Bilgileri', 'icon' => 'fa-map-location-dot', 'dialog' => 'modal-xl'],
    'klinik_tanilar' => ['title' => 'Klinik Tanılar', 'icon' => 'fa-notes-medical', 'dialog' => 'modal-lg'],
    'klinik_uyarilar' => ['title' => 'Klinik Uyarılar', 'icon' => 'fa-triangle-exclamation', 'dialog' => 'modal-lg'],
    'tibbi_cihaz' => ['title' => 'Tıbbi Cihaz ve Destek', 'icon' => 'fa-microchip', 'dialog' => 'modal-lg'],
    'bakim_sarf' => ['title' => 'Bakım ve Sarf Malzeme', 'icon' => 'fa-box-open', 'dialog' => 'modal-xl'],
    'dosya_durumu' => ['title' => 'Dosya Durumu', 'icon' => 'fa-file-circle-exclamation', 'dialog' => 'modal-lg'],
];
$eshPartialDir = __DIR__ . '/edit_sections';
$eshStoreUrl = esh_url('Patient', 'store');
$eshPatientId = (int) ($patient->id ?? 0);
preg_match('/<select\b[^>]*>(.*)<\/select>/is', (string) ($lists['ilce'] ?? ''), $__modalMainIlce);
$eshMainIlceOptionsInner = $__modalMainIlce[1] ?? '';
?>
<?php foreach ($eshEditSections as $eshSectionKey => $eshSectionMeta): ?>
<div class="modal fade patient-partial-edit-modal" id="patientEditModal-<?= htmlspecialchars($eshSectionKey, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-labelledby="patientEditModalLabel-<?= htmlspecialchars($eshSectionKey, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true" data-esh-section="<?= htmlspecialchars($eshSectionKey, ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered <?= htmlspecialchars((string) ($eshSectionMeta['dialog'] ?? 'modal-lg'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= htmlspecialchars($eshStoreUrl, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="patient-partial-edit-form needs-validation d-flex flex-column min-h-0 flex-grow-1" novalidate autocomplete="off" data-esh-submit-guard="off" data-esh-required-legend="off">
                <input type="hidden" name="id" value="<?= $eshPatientId ?>">
                <input type="hidden" name="partial_section" value="<?= htmlspecialchars($eshSectionKey, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header bg-warning text-dark flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="patientEditModalLabel-<?= htmlspecialchars($eshSectionKey, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid <?= htmlspecialchars((string) ($eshSectionMeta['icon'] ?? 'fa-pen'), ENT_QUOTES, 'UTF-8') ?> me-2"></i>
                        <?= htmlspecialchars((string) ($eshSectionMeta['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?> Güncelle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body flex-grow-1 overflow-auto">
                    <?php
                    $eshFormIdPrefix = 'modal-' . $eshSectionKey . '-';
                    $eshInPartialModal = true;
                    if ($eshSectionKey === 'dosya_durumu') {
                        $eshEditFullAccess = true;
                        include __DIR__ . '/edit_dosya_durumu.php';
                    } elseif (is_file($eshPartialDir . '/' . $eshSectionKey . '.php')) {
                        include $eshPartialDir . '/' . $eshSectionKey . '.php';
                    }
                    ?>
                </div>
                <div class="modal-footer bg-light border-top flex-shrink-0 patient-partial-edit-modal__footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<div id="patient-edit-config"
     data-main-ilce-options="<?= htmlspecialchars($eshMainIlceOptionsInner, ENT_QUOTES, 'UTF-8') ?>"
     data-patient-id="<?= $eshPatientId ?>"
     data-yupas-guvence-ids="<?= htmlspecialchars((string) ($yupasGuvenceIdCsv ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-partial-edit="1"
     hidden></div>
