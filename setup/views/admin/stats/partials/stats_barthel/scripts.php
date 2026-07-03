<script>

(function () {

    if (typeof Chart === 'undefined') return;

    var ctx = document.getElementById('chartBarthel');

    if (!ctx) return;

    new Chart(ctx, {

        type: 'doughnut',

        data: {

            labels: <?= json_encode(array_keys($grup), JSON_UNESCAPED_UNICODE) ?>,

            datasets: [{ data: <?= json_encode(array_values($grup)) ?>, backgroundColor: ['#dc3545','#fd7e14','#0dcaf0','#0d6efd','#198754'] }]

        },

        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }

    });

})();

</script>