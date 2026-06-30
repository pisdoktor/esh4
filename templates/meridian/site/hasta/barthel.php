<article class="mr-page mr-page--hasta-barthel" lang="tr">
<header class="visually-hidden"><h1>Hasta Barthel indeksi</h1></header>
<?php
/**
 * Hasta Barthel indeksi — ayrı düzenleme sayfası.
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
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <h1 class="h5 mb-0 fw-bold text-primary">
                        <i class="fa-solid fa-chart-line me-2"></i>Barthel İndeksi
                    </h1>
                    <p class="small text-muted mb-0 mt-1">
                        <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <span class="font-monospace"><?= \App\Helpers\ValidationHelper::formatTc((string) ($hasta->tckimlik ?? '')) ?></span>
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto flex-shrink-0">
                    <span class="badge bg-primary-subtle text-primary border fs-6 px-3 py-2">
                        <?= $barthelScore ?> puan — <?= htmlspecialchars($barthelStatus, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i>Hasta kartı
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="<?= htmlspecialchars(esh_url('Patient', 'saveBarthel'), ENT_QUOTES, 'UTF-8') ?>" method="post" id="patientBarthelForm" data-esh-required-legend="off" data-esh-required-markers="off">
        <input type="hidden" name="id" value="<?= $hastaId ?>">
        <div class="card shadow-sm border-0 border-top border-primary border-4">
            <div class="card-body p-3 p-md-4">
                <?php include __DIR__ . '/partials/barthel_form.php'; ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3 border-top">
            <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i>İptal
            </a>
            <button type="submit" class="btn btn-primary fw-semibold shadow-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
            </button>
        </div>
    </form>
</div>
</article>
