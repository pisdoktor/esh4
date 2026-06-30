<?php if ($ok && $toplam > 0 && $chartLabels !== []): ?>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    var monthEl = document.getElementById('plannedVisitMonthChart');
    if (monthEl) {
        new Chart(monthEl, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label: 'Tamamlanan',
                        data: <?= json_encode($chartDone) ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.75)',
                        borderRadius: 4
                    },
                    {
                        label: 'Bekleyen',
                        data: <?= json_encode($chartPending) ?>,
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    var durumEl = document.getElementById('plannedVisitDurumChart');
    if (durumEl) {
        new Chart(durumEl, {
            type: 'doughnut',
            data: {
                labels: ['Tamamlanan', 'Bekleyen'],
                datasets: [{
                    data: [<?= (int) $tamamlanan ?>, <?= (int) $bekleyen ?>],
                    backgroundColor: ['rgba(25, 135, 84, 0.85)', 'rgba(255, 193, 7, 0.85)'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
</script>
<?php endif; ?>