<?php
/**
 * @var string $sectionTitle
 * @var string $sectionIcon
 * @var string $calendarUrl
 * @var array $report
 * @var string $chartId
 * @var string $durumPositiveLabel
 * @var string $durumNegativeLabel
 */
/**
 * @var string $sectionTitle
 * @var string $sectionIcon
 * @var string $calendarUrl
 * @var array $report
 * @var string $chartId
 * @var string $durumPositiveLabel
 * @var string $durumNegativeLabel
 */
use App\Models\KonsRandevu;

$turkceAylar = [
    1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz',
    7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
];

$summary = $report['summary'] ?? (object) [];
$byMonth = is_array($report['by_month'] ?? null) ? $report['by_month'] : [];
$byBrans = is_array($report['by_brans'] ?? null) ? $report['by_brans'] : [];
$byZaman = is_array($report['by_zaman'] ?? null) ? $report['by_zaman'] : [];
$ok = !empty($report['ok']);

$chartLabels = [];
$chartData = [];
foreach ($byMonth as $row) {
    $yil = (int) ($row->yil ?? 0);
    $ay = (int) ($row->ay ?? 0);
    $chartLabels[] = ($turkceAylar[$ay] ?? (string) $ay) . ' ' . $yil;
    $chartData[] = (int) ($row->adet ?? 0);
}

$toplam = (int) ($summary->toplam_randevu ?? 0);
$benzersiz = (int) ($summary->benzersiz_hasta ?? 0);
$bransCnt = (int) ($summary->brans_sayisi ?? 0);
$olumlu = (int) ($summary->durum_olumlu ?? 0);
$olumsuz = (int) ($summary->durum_olumsuz ?? 0);
$belirsiz = (int) ($summary->durum_belirtilmedi ?? 0);
?>
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid <?= htmlspecialchars($sectionIcon, ENT_QUOTES, 'UTF-8') ?> text-info me-2"></i>
            <?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?>
        </h5>
        <a href="<?= htmlspecialchars($calendarUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Takvime git
        </a>
    </div>
    <div class="card-body">
        <?php if (!$ok): ?>
            <p class="text-muted small mb-0">Veri okunamadı veya tablo henüz oluşturulmamış.</p>
        <?php elseif ($toplam === 0): ?>
            <p class="text-muted small mb-0">Seçilen dönemde randevu kaydı yok.</p>
        <?php else: ?>
            <div class="row g-2 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle">
                        <div class="small text-muted">Toplam randevu</div>
                        <div class="fs-5 fw-bold text-primary"><?= number_format($toplam, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle">
                        <div class="small text-muted">Benzersiz hasta</div>
                        <div class="fs-5 fw-bold"><?= number_format($benzersiz, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle">
                        <div class="small text-muted">Branş (çeşit)</div>
                        <div class="fs-5 fw-bold"><?= number_format($bransCnt, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="border rounded-3 p-2 text-center bg-light-subtle">
                        <div class="small text-muted">Ort. randevu / hasta</div>
                        <div class="fs-5 fw-bold"><?= $benzersiz > 0 ? number_format($toplam / $benzersiz, 1, ',', '.') : '—' ?></div>
                    </div>
                </div>
            </div>

            <?php if ($chartLabels !== []): ?>
            <div class="mb-3" style="height: 220px;">
                <canvas id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></canvas>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="small fw-semibold text-muted text-uppercase mb-2">Branşa göre (ilk 20)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Branş</th><th class="text-end">Adet</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($byBrans === []): ?>
                                    <tr><td colspan="2" class="text-muted small">—</td></tr>
                                <?php else: ?>
                                    <?php foreach ($byBrans as $b): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($b->brans_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-semibold"><?= (int) ($b->adet ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="small fw-semibold text-muted text-uppercase mb-2">Zaman dilimi</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Dilim</th><th class="text-end">Adet</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($byZaman === []): ?>
                                    <tr><td colspan="2" class="text-muted small">—</td></tr>
                                <?php else: ?>
                                    <?php foreach ($byZaman as $z): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(KonsRandevu::zamanLabel((int) ($z->zaman ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-semibold"><?= (int) ($z->adet ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <h6 class="small fw-semibold text-muted text-uppercase mb-2">Durum</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><span class="text-muted">Belirtilmedi:</span> <strong><?= $belirsiz ?></strong></li>
                        <li class="mb-1"><span class="text-muted"><?= htmlspecialchars($durumPositiveLabel, ENT_QUOTES, 'UTF-8') ?>:</span> <strong class="text-success"><?= $olumlu ?></strong></li>
                        <li><span class="text-muted"><?= htmlspecialchars($durumNegativeLabel, ENT_QUOTES, 'UTF-8') ?>:</span> <strong class="text-danger"><?= $olumsuz ?></strong></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($ok && $toplam > 0 && $chartLabels !== []): ?>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById(<?= json_encode($chartId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Randevu',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
<?php endif; ?>
