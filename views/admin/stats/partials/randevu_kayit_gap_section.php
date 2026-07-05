<?php
/**
 * @var string $sectionTitle
 * @var string $sectionIcon
 * @var array $report
 * @var string $chartHistId
 * @var string $chartMonthId
 */
use App\Helpers\DateHelper;
use App\Helpers\UIHelper;
$turkceAylar = [
    1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz',
    7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
];

$ok = !empty($report['ok']);
$summary = $report['summary'] ?? (object) [];
$histogram = is_array($report['histogram'] ?? null) ? $report['histogram'] : [];
$byMonth = is_array($report['by_month'] ?? null) ? $report['by_month'] : [];
$byIlce = is_array($report['by_ilce'] ?? null) ? $report['by_ilce'] : [];
$longest = is_array($report['longest'] ?? null) ? $report['longest'] : [];

$toplam = (int) ($summary->toplam_hasta ?? 0);
$ortalama = $summary->ortalama_gun ?? null;
$medyan = $summary->medyan_gun ?? null;
$minGun = $summary->min_gun ?? null;
$maxGun = $summary->max_gun ?? null;
$kayitOncesi = (int) ($summary->kayit_oncesi ?? 0);
$ayniGun = (int) ($summary->ayni_gun ?? 0);
$randevuBos = (int) ($summary->randevu_tarihi_bos ?? 0);

$histLabels = [];
$histData = [];
$histColors = [
    'neg' => 'rgba(220, 53, 69, 0.75)',
    '0' => 'rgba(25, 135, 84, 0.75)',
    '1_7' => 'rgba(13, 110, 253, 0.75)',
    '8_14' => 'rgba(13, 110, 253, 0.68)',
    '15_30' => 'rgba(13, 110, 253, 0.58)',
    '31_60' => 'rgba(108, 117, 125, 0.68)',
    '61_90' => 'rgba(108, 117, 125, 0.58)',
    '91_180' => 'rgba(108, 117, 125, 0.48)',
    '181_365' => 'rgba(108, 117, 125, 0.38)',
    '366' => 'rgba(255, 193, 7, 0.75)',
];
$histBg = [];
foreach ($histogram as $h) {
    $histLabels[] = (string) ($h['label'] ?? '');
    $histData[] = (int) ($h['adet'] ?? 0);
    $histBg[] = $histColors[$h['key'] ?? ''] ?? 'rgba(13, 110, 253, 0.6)';
}

$monthLabels = [];
$monthAvg = [];
$monthCnt = [];
foreach ($byMonth as $row) {
    $yil = (int) ($row->yil ?? 0);
    $ay = (int) ($row->ay ?? 0);
    $monthLabels[] = ($turkceAylar[$ay] ?? (string) $ay) . ' ' . $yil;
    $monthAvg[] = (float) ($row->ortalama_gun ?? 0);
    $monthCnt[] = (int) ($row->adet ?? 0);
}

$chartHistId = $chartHistId ?? 'hastaKayitRandevuHist';
$chartMonthId = $chartMonthId ?? 'hastaKayitRandevuMonth';
$hasChartData = $ok && $toplam > 0 && ($histLabels !== [] || $monthLabels !== []);

?>
<div class="card shadow-sm border-0">
    <?php
    $eshStatsPdfBlock = trim((string) ($eshStatsPdfBlock ?? 'main'));
    \App\Helpers\StatsViewPdfHelper::renderCardHeader(
        '<i class="fa-solid ' . htmlspecialchars($sectionIcon, ENT_QUOTES, 'UTF-8') . ' text-secondary me-2"></i>' . htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8'),
        $eshStatsPdfBlock,
        'h5',
        'card-header bg-white py-3 border-bottom'
    );
    ?>
    <div class="card-body">
        <?php if (!$ok): ?>
            <p class="text-muted small mb-0">Veri okunamadı.</p>
        <?php elseif ($toplam === 0): ?>
            <p class="text-muted small mb-0">Seçilen dönemde uygun kayıt yok.</p>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-xl-5 g-2 mb-3">
                <div class="col">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle h-100">
                        <div class="small text-muted">Hasta</div>
                        <div class="fs-6 fw-bold"><?= number_format($toplam, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle h-100">
                        <div class="small text-muted">Ortalama gün</div>
                        <div class="fs-6 fw-bold text-primary"><?= $ortalama !== null ? number_format((float) $ortalama, 1, ',', '.') : '—' ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle h-100">
                        <div class="small text-muted">Medyan (yaklaşık)</div>
                        <div class="fs-6 fw-bold"><?= $medyan !== null ? (int) $medyan : '—' ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle h-100">
                        <div class="small text-muted">Min – max</div>
                        <div class="fs-6 fw-bold small"><?= $minGun !== null ? (int) $minGun : '—' ?> – <?= $maxGun !== null ? (int) $maxGun : '—' ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle h-100">
                        <div class="small text-muted">Kayıt öncesi / aynı gün</div>
                        <div class="fs-6 fw-bold">
                            <span class="text-danger"><?= $kayitOncesi ?></span>
                            <span class="text-muted">/</span>
                            <span class="text-success"><?= $ayniGun ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($histLabels !== []): ?>
            <div class="mb-3">
                <h6 class="small fw-semibold text-muted text-uppercase mb-2">Gün farkı dağılımı</h6>
                <div class="position-relative w-100 esh-chart-wrap--md">
                    <canvas id="<?= htmlspecialchars($chartHistId, ENT_QUOTES, 'UTF-8') ?>" role="img" aria-label="Gün farkı dağılım grafiği"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($monthLabels !== []): ?>
            <div class="mb-3">
                <h6 class="small fw-semibold text-muted text-uppercase mb-2">Randevu ayına göre ortalama gün farkı</h6>
                <div class="position-relative w-100 esh-chart-wrap--200">
                    <canvas id="<?= htmlspecialchars($chartMonthId, ENT_QUOTES, 'UTF-8') ?>" role="img" aria-label="Aylık ortalama gün farkı grafiği"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($byIlce !== []): ?>
            <div class="mb-3">
                <h6 class="small fw-semibold text-muted text-uppercase mb-2">İlçeye göre (en az 3 hasta, ilk 15)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>İlçe</th><th class="text-end">Hasta</th><th class="text-end">Ort. gün</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byIlce as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($b->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end"><?= (int) ($b->adet ?? 0) ?></td>
                                    <td class="text-end fw-semibold"><?= number_format((float) ($b->ortalama_gun ?? 0), 1, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div>
                <h6 class="small fw-semibold text-muted text-uppercase mb-2">En uzun süre (ilk 25)</h6>
                <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width:76px;"></th>
                                    <th>Hasta</th>
                                    <th>İlçe / mahalle</th>
                                    <th>Doğum (yaş)</th>
                                    <th>Telefon</th>
                                    <th>Kayıt</th>
                                    <th>Randevu</th>
                                    <th class="text-end">Gün</th>
                                    <th>Son izlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($longest as $r): ?>
                                    <tr>
                                        <td class="text-center p-1">
                                            <?= UIHelper::patientSummaryButtons(
                                                (string) ($r->tckimlik ?? ''),
                                                (int) ($r->izlemsayisi ?? 0),
                                                (int) ($r->yizlemsayisi ?? 0),
                                                (int) ($r->totalplanli ?? 0)
                                            ) ?>
                                        </td>
                                        <td class="small"><?= UIHelper::patientStatsCardLink($r) ?></td>
                                        <td class="small">
                                            <span class="d-block fw-semibold"><?= htmlspecialchars((string) ($r->mahalle_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="text-muted"><?= htmlspecialchars((string) ($r->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="small">
                                            <?php $dogumTr = DateHelper::toTrOrEmpty($r->dogumtarihi ?? ''); ?>
                                            <?php if ($dogumTr !== ''): ?>
                                                <span class="d-block"><?= htmlspecialchars($dogumTr, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars((string) DateHelper::calculateAge($r->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?> yaş</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($r->ceptel1)): ?>
                                                <a href="tel:<?= htmlspecialchars((string) $r->ceptel1, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars((string) $r->ceptel1, ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars((string) ($r->kayit_tr ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small"><?= htmlspecialchars((string) ($r->randevu_tr ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end"><span class="badge bg-warning text-dark"><?= (int) ($r->gun_fark ?? 0) ?></span></td>
                                        <td class="small">
                                            <?php if (!empty($r->sonizlemtarihi)): ?>
                                                <span class="fw-semibold text-success d-block"><?= htmlspecialchars(DateHelper::toTrOrEmpty((string) $r->sonizlemtarihi), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if (!empty($r->son_izlem_yapilanlar)): ?>
                                                    <span class="text-muted"><?= htmlspecialchars((string) $r->son_izlem_yapilanlar, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">İzlem yok</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasChartData): ?>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    var histId = <?= json_encode($chartHistId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var monthId = <?= json_encode($chartMonthId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var histLabels = <?= json_encode($histLabels, JSON_UNESCAPED_UNICODE) ?>;
    var histData = <?= json_encode($histData) ?>;
    var histBg = <?= json_encode($histBg) ?>;
    var monthLabels = <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE) ?>;
    var monthAvg = <?= json_encode($monthAvg) ?>;
    var monthCnt = <?= json_encode($monthCnt) ?>;

    function showChartMissing(el) {
        if (!el || el.dataset.eshChartMissingShown) return;
        el.dataset.eshChartMissingShown = '1';
        var note = document.createElement('p');
        note.className = 'small text-muted mb-0 py-2';
        note.textContent = 'Grafik yüklenemedi (Chart.js). İnternet bağlantısı veya CDN engelini kontrol edin.';
        el.parentNode && el.parentNode.appendChild(note);
    }

    function initCharts() {
        if (typeof Chart === 'undefined') {
            var h0 = document.getElementById(histId);
            var m0 = document.getElementById(monthId);
            showChartMissing(h0);
            showChartMissing(m0);
            return;
        }

        var histEl = document.getElementById(histId);
        if (histEl && histLabels.length) {
            try {
                new Chart(histEl, {
                    type: 'bar',
                    data: {
                        labels: histLabels,
                        datasets: [{
                            label: 'Hasta',
                            data: histData,
                            backgroundColor: histBg,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            } catch (err) {
                console.error('randevuKayitGap histogram chart', err);
                showChartMissing(histEl);
            }
        }

        var monthEl = document.getElementById(monthId);
        if (monthEl && monthLabels.length) {
            try {
                new Chart(monthEl, {
                    type: 'bar',
                    data: {
                        labels: monthLabels,
                        datasets: [{
                            label: 'Ortalama gün',
                            data: monthAvg,
                            type: 'line',
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.15)',
                            fill: true,
                            tension: 0.25,
                            yAxisID: 'y',
                            order: 1
                        }, {
                            label: 'Hasta adedi',
                            data: monthCnt,
                            type: 'bar',
                            backgroundColor: 'rgba(108, 117, 125, 0.35)',
                            yAxisID: 'y1',
                            order: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            y: {
                                type: 'linear',
                                beginAtZero: true,
                                position: 'left',
                                title: { display: true, text: 'Gün' }
                            },
                            y1: {
                                type: 'linear',
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            } catch (err) {
                console.error('randevuKayitGap month chart', err);
                showChartMissing(monthEl);
            }
        }
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(initCharts);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
})();
</script>
<?php endif; ?>
