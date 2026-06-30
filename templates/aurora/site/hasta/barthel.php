<?php
/**
 * Aurora Light teması — site/hasta/barthel
 * Yol: templates/aurora/site/hasta/barthel.php
 * Canonical: views/site/hasta/barthel.php
 *
 * @var object $hasta
 * @var object $patient
 * @var array $barthelFields
 * @var array $barthelscore
 * @var string $bopt
 */
$hastaId = (int) ($hasta->id ?? 0);
$viewUrl = esh_url('Patient', 'view', ['id' => $hastaId]);
$barthelScore = (int) ($barthelscore['score'] ?? 0);
$barthelStatus = (string) ($barthelscore['status'] ?? '—');
?>
<div class="container-lg py-4 esh-page-patient-barthel">
    <section class="au-panel mb-4">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-chart-line"></i></span>
                <div class="min-w-0">
                    <h1 class="au-panel__title mb-0">Barthel İndeksi</h1>
                    <p class="au-panel__sub mb-0">
                        <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <span class="font-monospace"><?= \App\Helpers\ValidationHelper::formatTc((string) ($hasta->tckimlik ?? '')) ?></span>
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                <span class="badge bg-primary-subtle text-primary border fs-6 px-3 py-2">
                    <?= $barthelScore ?> puan — <?= htmlspecialchars($barthelStatus, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="fa-solid fa-arrow-left me-1"></i>Hasta kartı
                </a>
            </div>
        </div>
    </section>

    <form action="<?= htmlspecialchars(esh_url('Patient', 'saveBarthel'), ENT_QUOTES, 'UTF-8') ?>" method="post" id="patientBarthelForm" data-esh-required-legend="off" data-esh-required-markers="off">
        <input type="hidden" name="id" value="<?= $hastaId ?>">
        <div class="au-panel">
            <div class="au-panel__body">
                <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'hasta/partials/barthel_form'); ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3 border-top">
            <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="fa-solid fa-xmark me-1"></i>İptal
            </a>
            <button type="submit" class="btn btn-primary fw-semibold rounded-pill">
                <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
            </button>
        </div>
    </form>
</div>
