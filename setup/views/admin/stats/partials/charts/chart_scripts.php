<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var hctx = document.getElementById('statsHastalikChart');
    if (hctx) {
        new Chart(hctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($hastalikLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ data: <?= json_encode($hastalikValues) ?>, borderWidth: 1 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } }, cutout: '55%' }
        });
    }
})();
</script>