<script<?= esh_csp_nonce_attr() ?>>

(function () {

    if (typeof Chart === 'undefined') return;

    var ctx = document.getElementById(<?= json_encode((string) ($meta['chartId'] ?? 'chartClinicalScale'), JSON_UNESCAPED_UNICODE) ?>);

    if (!ctx) return;

    var colors = <?= json_encode(array_values($meta['chartColors'] ?? ['#0d6efd']), JSON_UNESCAPED_UNICODE) ?>;

    var labels = <?= json_encode(array_keys($grup), JSON_UNESCAPED_UNICODE) ?>;

    var data = <?= json_encode(array_values($grup)) ?>;

    while (colors.length < data.length) {

        colors.push('#6c757d');

    }

    new Chart(ctx, {

        type: 'doughnut',

        data: {

            labels: labels,

            datasets: [{ data: data, backgroundColor: colors.slice(0, data.length) }]

        },

        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }

    });

})();

</script>
