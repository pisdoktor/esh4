<?php
/**
 * Hasta Braden ölçeği — değerlendirme formu ve geçmiş.
 *
 * @var object $hasta
 * @var array $bradenAssessments
 * @var array $bradenFields
 * @var object|null $bradenLatest
 * @var bool $pasifDosyaKapali
 */
use App\Helpers\BradenScaleHelper;

$hastaId = (int) ($hasta->id ?? 0);
$viewUrl = esh_url('Patient', 'view', ['id' => $hastaId]);
$latestTotal = (int) ($bradenLatest->toplam_skor ?? 0);
$latestRisk = (string) ($bradenLatest->risk_duzeyi ?? '—');
$latestRiskMeta = $latestTotal > 0 ? BradenScaleHelper::resolveRisk($latestTotal) : ['label' => '—', 'badgeClass' => 'bg-secondary-subtle text-secondary border'];
$assessmentCount = is_array($bradenAssessments ?? null) ? count($bradenAssessments) : 0;
?>
<div class="container-lg py-4 esh-page-patient-braden">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <h1 class="h5 mb-0 fw-bold text-danger">
                        <i class="fa-solid fa-bed-pulse me-2"></i>Braden Ölçeği
                    </h1>
                    <p class="small text-muted mb-0 mt-1">
                        <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <span class="font-monospace"><?= \App\Helpers\ValidationHelper::formatTc((string) ($hasta->tckimlik ?? '')) ?></span>
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto flex-shrink-0">
                    <?php if ($latestTotal > 0): ?>
                        <span class="badge <?= htmlspecialchars((string) $latestRiskMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?> border fs-6 px-3 py-2" id="braden-header-badge">
                            <?= $latestTotal ?> puan — <?= htmlspecialchars($latestRisk, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border fs-6 px-3 py-2">
                            Henüz değerlendirme yok
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-light text-dark border"><?= (int) $assessmentCount ?> kayıt</span>
                    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i>Hasta kartı
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($pasifDosyaKapali)): ?>
        <form action="<?= htmlspecialchars(esh_url('Patient', 'saveBraden'), ENT_QUOTES, 'UTF-8') ?>" method="post" id="patientBradenForm" class="mb-4" data-esh-required-legend="off" data-esh-required-markers="off">
            <input type="hidden" name="id" value="<?= $hastaId ?>">
            <div class="card shadow-sm border-0 border-top border-danger border-4">
                <div class="card-body p-3 p-md-4">
                    <?php include ROOT_PATH . '/views/site/hasta/partials/braden_form.php'; ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                <button type="submit" class="btn btn-danger fw-semibold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Değerlendirmeyi kaydet
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-secondary border mb-4">
            <i class="fa-solid fa-lock me-1"></i>Pasif dosyada yeni Braden değerlendirmesi eklenemez; geçmiş kayıtlar görüntülenebilir.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-clock-rotate-left me-2 text-danger"></i>Değerlendirme geçmişi
        </div>
        <div class="card-body p-0">
            <?php include ROOT_PATH . '/views/site/hasta/partials/braden_history.php'; ?>
        </div>
    </div>
</div>
