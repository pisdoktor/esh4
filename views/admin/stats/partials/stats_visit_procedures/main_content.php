<div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                </div>
            </div>
            <button
                id="stats-visit-procedures-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#stats-visit-procedures-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="stats-visit-procedures-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="stats-visit-procedures-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'visitProcedures'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'visitProcedures') ?>
                <?= \App\Helpers\FormHelper::fieldDateRangeFilter('date_from', 'date_to', $date_from, $date_to, [
                    'col' => 'col-12 col-md-6',
                    'prefixIconClass' => 'bg-white text-primary border-end-0',
                ]) ?>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="fa-solid fa-filter me-1"></i>Uygula</button>
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'visitProcedures'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('İşlem dağılımı grafiği', 'grafik'); ?>
                <div class="card-body"><div style="height:280px;"><canvas id="chartProc"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('İşlem tablosu', 'tablo'); ?>
            <div class="card-body p-0">
            <div class="table-responsive" style="max-height:360px;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top"><tr><th>İşlem</th><th class="text-end">Adet</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php if (($r->adet ?? 0) <= 0) continue; ?>
                            <tr><td><?= htmlspecialchars((string) ($r->islemadi ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td class="text-end fw-bold"><?= (int) $r->adet ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        if (typeof Chart === 'undefined') return;
        var ctx = document.getElementById('chartProc');
        if (!ctx) return;
        var labels = <?= json_encode(array_map(fn ($x) => $x->islemadi, $top), JSON_UNESCAPED_UNICODE) ?>;
        var data = <?= json_encode(array_map(fn ($x) => (int)$x->adet, $top)) ?>;
        if (!labels.length) { ctx.parentElement.innerHTML = '<p class="text-muted small p-3 mb-0">Grafik için yeterli veri yok.</p>'; return; }
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: 'Adet', data: data, backgroundColor: '#0d6efd', borderRadius: 4 }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    })();
    </script>