<?php if (!empty($chartLabels) && $sumToplam > 0): ?>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById('kayitMonthsChart');
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Erkek',
                    data: <?= json_encode($chartErkek) ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.75)',
                    borderRadius: 4
                },
                {
                    label: 'Kadın',
                    data: <?= json_encode($chartKadin) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.75)',
                    borderRadius: 4
                },
                {
                    label: 'Toplam',
                    data: <?= json_encode($chartToplam) ?>,
                    type: 'line',
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.08)',
                    borderWidth: 2,
                    tension: 0.25,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
})();
</script>
<?php endif; ?>