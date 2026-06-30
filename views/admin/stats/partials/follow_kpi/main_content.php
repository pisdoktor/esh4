<div class="card shadow-sm border-0 <?= htmlspecialchars($kpi->panel_sinifi ?? 'border-secondary', ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-gauge-high me-2"></i>İzlem verimlilik skoru (son 3 ay)</h5>
        </div>
        <div class="card-body text-center py-5">
            <div class="display-3 fw-bold mb-2">%<?= htmlspecialchars((string) ($kpi->skor ?? '0'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="progress mx-auto" style="max-width: 480px; height: 32px;">
                <div class="progress-bar <?= htmlspecialchars($kpi->renk_sinifi ?? 'bg-secondary', ENT_QUOTES, 'UTF-8') ?> progress-bar-striped"
                     role="progressbar" style="width: <?= min(100, (float) ($kpi->skor ?? 0)) ?>%;">
                    %<?= htmlspecialchars((string) ($kpi->skor ?? '0'), ENT_QUOTES, 'UTF-8') ?>