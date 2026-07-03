<div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Ortalama yaş</div><div class="fs-4 fw-bold"><?= (float) ($report['ortalama'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Medyan yaş</div><div class="fs-4 fw-bold"><?= (float) ($report['medyan'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Geçerli doğum tarihi</div><div class="fs-4 fw-bold"><?= (int) ($report['gecerli'] ?? 0) ?></div><div class="small text-muted">/ <?= (int) ($report['aktif'] ?? 0) ?> aktif</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Detay bant</div><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(esh_url('Stats', 'ageGenderBands'), ENT_QUOTES, 'UTF-8') ?>">Yaş × cinsiyet →</a></div></div></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="alert alert-light border mb-0 py-2">Çocuk (&lt;18): <strong><?= (int) ($report['cocuk'] ?? 0) ?></strong></div></div>
        <div class="col-md-4"><div class="alert alert-light border mb-0 py-2">Yetişkin (18–65): <strong><?= (int) ($report['yetiskin'] ?? 0) ?></strong></div></div>
        <div class="col-md-4"><div class="alert alert-light border mb-0 py-2">Yaşlı (65+): <strong><?= (int) ($report['yasli'] ?? 0) ?></strong></div></div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Yaş bantları (ageGenderBands ile aynı)', 'grafik'); ?>
        <div class="card-body esh-chart-wrap--300">
            <?php
            $chartId = 'ageBandChart';
            $chartLabels = $bandLabels;
            $chartValues = $bandValues;
            $barColor = '#0d6efd';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>