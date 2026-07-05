<?php
/** @var string $chartId */
/** @var array<int, string> $chartLabels */
/** @var array<int, int|float> $chartValues */
/** @var string $barColor */
$chartId = (string) ($chartId ?? 'eshChart');
$chartLabels = $chartLabels ?? [];
$chartValues = array_map('intval', $chartValues ?? []);
$barColor = (string) ($barColor ?? '#0d6efd');
if ($chartValues === [] || array_sum($chartValues) === 0) {
    echo '<p class="text-muted small mb-0">Grafik için yeterli veri yok.</p>';
    return;
}
?>
<canvas id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></canvas>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById(<?= json_encode($chartId, JSON_UNESCAPED_UNICODE) ?>);
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($chartLabels), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Hasta',
                data: <?= json_encode(array_values($chartValues)) ?>,
                backgroundColor: <?= json_encode($barColor, JSON_UNESCAPED_UNICODE) ?>,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
