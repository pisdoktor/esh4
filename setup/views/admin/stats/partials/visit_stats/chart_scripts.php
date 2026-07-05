<?php if ($ok && $toplam > 0): ?>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    if (typeof Chart === 'undefined') return;

    var monthEl = document.getElementById('visitMonthChart');
    if (monthEl && <?= $chartLabels !== [] ? 'true' : 'false' ?>) {
        new Chart(monthEl, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label: 'Yapıldı',
                        data: <?= json_encode($chartDone) ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.75)',
                        borderRadius: 4
                    },
                    {
                        label: 'Yapılmadı',
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

    var durumEl = document.getElementById('visitDurumChart');
    if (durumEl) {
        new Chart(durumEl, {
            type: 'doughnut',
            data: {
                labels: ['Yapıldı', 'Yapılmadı'],
                datasets: [{
                    data: [<?= (int) $yapilan ?>, <?= (int) $yapilmayan ?>],
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