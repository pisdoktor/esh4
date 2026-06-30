<?php
$vmLabels = [];
$vmData = [];
foreach ($visitMonths as $vm) {
    $vmLabels[] = $vm->ym ?? '';
    $vmData[] = (int) ($vm->n ?? 0);
}
?>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('pulseVisitMonths');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($vmLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Tamamlanmış izlem',
                data: <?= json_encode($vmData) ?>,
                fill: true,
                tension: 0.25,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>