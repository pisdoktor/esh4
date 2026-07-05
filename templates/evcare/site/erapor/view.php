<?php
/**
 * e-Rapor kaydı salt okunur görünümü (`EraporController::view`).
 *
 * @var \App\Models\Erapor $item
 */
use App\Models\Erapor;

if (!isset($item) || !$item instanceof Erapor) {
    return;
}
$basvuruTr = \App\Helpers\DateHelper::toTrOrEmpty((string) ($item->basvurutarihi ?? ''));
$tcFmt = \App\Helpers\ValidationHelper::formatTc((string) ($item->hastatckimlik ?? ''));
$isAdmin = !empty($_SESSION['isadmin']);
?>
<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL . '/pages/css/erapor-index.css', ENT_QUOTES, 'UTF-8') ?>">
<article class="ev-sheet mr-page mr-page--erapor-view container mt-4 mb-5" lang="tr">
    <header class="ev-erapor-view-hero mb-4">
        <div class="ev-erapor-view-hero__inner">
            <span class="ev-erapor-view-hero__badge" aria-hidden="true"><i class="fas fa-file-medical"></i></span>
            <div class="flex-grow-1 min-w-0">
                <p class="ev-erapor-view-hero__eyebrow mb-1">e-Rapor havuzu</p>
                <h1 class="ev-erapor-view-hero__title h4 mb-1">Rapor kaydı #<?= (int) ($item->id ?? 0); ?></h1>
                <p class="ev-erapor-view-hero__meta mb-0 small font-monospace"><?= htmlspecialchars($tcFmt, ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars(trim(($item->isim ?? '') . ' ' . ($item->soyisim ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start justify-content-lg-end">
                <a href="<?= htmlspecialchars(esh_url('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="fa-solid fa-arrow-left-long me-1"></i>Havuza dön
                </a>
                <a href="<?= htmlspecialchars(esh_url('Erapor', 'edit', ['id' => (int) ($item->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm rounded-pill">
                    <i class="fa-solid fa-pen-to-square me-1"></i>Düzenle
                </a>
            </div>
        </div>
    </header>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <dl class="row mb-0 ev-erapor-dl">
                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">TC Kimlik</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($tcFmt, ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Ad Soyad</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars(trim(($item->isim ?? '') . ' ' . ($item->soyisim ?? '')), ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Cep telefonu</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars((string) ($item->ceptel1 ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Başvuru tarihi</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars($basvuruTr !== '' ? $basvuruTr : '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Branş</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= htmlspecialchars(trim((string) ($item->bransadi ?? $item->brans ?? '')), ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Sistemde kayıtlı</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= (int) ($item->kayitlimi ?? 0) === 1 ? 'Evet' : 'Hayır'; ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold">Yenilendi</dt>
                <dd class="col-sm-8 col-lg-9 mb-3"><?= (int) ($item->yenilendimi ?? 0) === 1 ? 'Evet' : 'Hayır'; ?></dd>

                <dt class="col-sm-4 col-lg-3 text-muted small fw-semibold align-self-start pt-1">Not / neden</dt>
                <dd class="col-sm-8 col-lg-9 mb-0">
                    <?php $neden = trim((string) ($item->neden ?? '')); ?>
                    <?php if ($neden !== ''): ?>
                        <span class="d-block p-3 rounded-3 bg-body-secondary small"><?= nl2br(htmlspecialchars($neden, ENT_QUOTES, 'UTF-8')); ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>

        <?php if ($isAdmin): ?>
            <div class="card-footer bg-body-tertiary border-0 py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <form action="<?= htmlspecialchars(esh_form_action('Erapor', 'markAsProcessed'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="m-0">
                <?= esh_form_route_hiddens('Erapor', 'markAsProcessed') ?>
                    <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">
                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill">
                        <i class="fa-solid fa-check-double me-1"></i>İşlendi olarak işaretle
                    </button>
                </form>
                <form action="<?= htmlspecialchars(esh_form_action('Erapor', 'delete'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="m-0" data-esh-confirm="Bu kaydı havuzdan silmek istediğinize emin misiniz?">
                <?= esh_form_route_hiddens('Erapor', 'delete') ?>
                    <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                        <i class="fa-solid fa-trash me-1"></i>Havuzdan sil
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</article>
