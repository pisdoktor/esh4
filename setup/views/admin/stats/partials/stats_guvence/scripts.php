<script<?= esh_csp_nonce_attr() ?>>
(function () {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById('guvenceDistChart');
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Hasta',
                data: <?= json_encode($chartValues) ?>,
                backgroundColor: '#0d6efd',
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