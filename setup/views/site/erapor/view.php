<?php
/**
 * e-Rapor havuz kaydı — salt okunur görünüm (`EraporController::view`).
 *
 * @var \App\Models\Erapor $item
 */
use App\Models\Erapor;
use App\Helpers\AuthHelper;

if (!isset($item) || !$item instanceof Erapor) {
    return;
}

$basvuruTr = \App\Helpers\DateHelper::toTrOrEmpty((string) ($item->basvurutarihi ?? ''));
$tcFmt = \App\Helpers\ValidationHelper::formatTc((string) ($item->hastatckimlik ?? ''));
$adSoyad = trim((string) ($item->isim ?? '') . ' ' . (string) ($item->soyisim ?? ''));
$bransLabel = trim((string) ($item->bransadi ?? $item->brans ?? ''));
$isAdmin = AuthHelper::sessionIsAdmin();
$eraporId = \App\Helpers\IdHelper::normalizeRequestId($item->id ?? null) ?? '';
$eraporIdLabel = $eraporId !== '' ? strtoupper(substr($eraporId, 0, 8)) : '—';
?>
<div class="esh-page esh-page--detail esh-page-erapor container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div class="min-w-0">
            <p class="text-muted small text-uppercase mb-1">e-Rapor havuzu</p>
            <h1 class="h4 fw-bold mb-1">
                <i class="fas fa-file-medical text-primary me-2"></i>Rapor kaydı #<?= htmlspecialchars($eraporIdLabel, ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="text-muted small mb-0 font-monospace">
                <?= htmlspecialchars($tcFmt, ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars($adSoyad, ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
            <a href="<?= htmlspecialchars(esh_url('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left-long me-1"></i>Havuza dön
            </a>
            <a href="<?= htmlspecialchars(esh_url('Erapor', 'edit', ['id' => $eraporId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pen-to-square me-1"></i>Düzenle
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 col-lg-10 mx-auto">
        <div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">TC Kimlik</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($tcFmt, ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Ad Soyad</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($adSoyad, ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Cep telefonu</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars((string) ($item->ceptel1 ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Başvuru tarihi</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($basvuruTr !== '' ? $basvuruTr : '—', ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Branş</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($bransLabel !== '' ? $bransLabel : '—', ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Sistemde kayıtlı</dt>
                <dd class="col-sm-8 col-lg-9 mb-3">
                    <?= (int) ($item->kayitlimi ?? 0) === 1
                        ? '<span class="badge bg-success">Evet</span>'
                        : '<span class="badge bg-secondary">Hayır</span>' ?>
                </dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Yenilendi</dt>
                <dd class="col-sm-8 col-lg-9 mb-3">
                    <?= (int) ($item->yenilendimi ?? 0) === 1
                        ? '<span class="badge bg-info">Evet</span>'
                        : '<span class="text-muted">—</span>' ?>
                </dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold align-self-start pt-1">Not / neden</dt>
                <dd class="col-sm-8 col-lg-9 mb-0">
                    <?php $neden = trim((string) ($item->neden ?? '')); ?>
                    <?php if ($neden !== ''): ?>
                        <span class="d-block p-3 rounded bg-body-secondary small"><?= nl2br(htmlspecialchars($neden, ENT_QUOTES, 'UTF-8')) ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>

        <?php if ($isAdmin): ?>
            <div class="card-footer bg-body-tertiary border-top d-flex flex-wrap gap-2 justify-content-between align-items-center py-3">
                <form action="<?= htmlspecialchars(esh_form_action('Erapor', 'markAsProcessed'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="m-0">
                    <?= esh_form_route_hiddens('Erapor', 'markAsProcessed') ?>
                    <input type="hidden" name="id" value="<?= $eraporId ?>">
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-check-double me-1"></i>İşlendi olarak işaretle
                    </button>
                </form>
                <form action="<?= htmlspecialchars(esh_form_action('Erapor', 'delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="m-0"
                      data-esh-confirm="Bu kaydı havuzdan silmek istediğinize emin misiniz?">
                    <?= esh_form_route_hiddens('Erapor', 'delete') ?>
                    <input type="hidden" name="id" value="<?= $eraporId ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-trash me-1"></i>Havuzdan sil
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
