<script>
(function () {
    if (typeof Chart === 'undefined') return;
    new Chart(document.getElementById('statsAgeGenderChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                { label: 'Erkek', data: <?= json_encode(array_values($stats['E'])) ?>, backgroundColor: '#0d6efd', borderRadius: 6 },
                { label: 'Kadın', data: <?= json_encode(array_values($stats['K'])) ?>, backgroundColor: '#dc3545', borderRadius: 6 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { x: { stacked: false }, y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>