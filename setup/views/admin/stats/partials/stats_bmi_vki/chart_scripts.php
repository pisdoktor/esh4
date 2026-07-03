<script>
(function () {
    var labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    var values = <?= json_encode($chartValues) ?>;
    var colors = <?= json_encode($chartColors) ?>;
    var ageBandKeys = <?= json_encode($ageBandKeys, JSON_UNESCAPED_UNICODE) ?>;
    var ageLabels = <?= json_encode($ageBandLabels, JSON_UNESCAPED_UNICODE) ?>;
    var catKeys = <?= json_encode($catKeys, JSON_UNESCAPED_UNICODE) ?>;
    var catLegend = <?= json_encode($ageChartLabels, JSON_UNESCAPED_UNICODE) ?>;
    var byAge = <?= json_encode($byAge, JSON_UNESCAPED_UNICODE) ?>;
    var catColors = <?= json_encode($catChartColors, JSON_UNESCAPED_UNICODE) ?>;

    function initBmiVkiCharts() {
        if (typeof Chart === 'undefined') {
            return;
        }
        var el = document.getElementById('chartBmiVki');
        if (el && values.some(function (v) { return v > 0; })) {
            try {
                new Chart(el, {
                    type: 'doughnut',
                    data: { labels: labels, datasets: [{ data: values, backgroundColor: colors }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            } catch (err) {
                console.error('chartBmiVki', err);
            }
        }
        var ageEl = document.getElementById('chartBmiVkiAge');
        if (!ageEl || !ageBandKeys.length) {
            return;
        }
        try {
            var datasets = catKeys.map(function (key, idx) {
                return {
                    label: catLegend[idx] || key,
                    data: ageBandKeys.map(function (band) {
                        return (byAge[band] && byAge[band][key]) ? byAge[band][key] : 0;
                    }),
                    backgroundColor: catColors[idx] || '#6c757d',
                    borderRadius: 4
                };
            });
            new Chart(ageEl, {
                type: 'bar',
                data: { labels: ageLabels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        } catch (err) {
            console.error('chartBmiVkiAge', err);
        }
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(initBmiVkiCharts);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBmiVkiCharts);
    } else {
        initBmiVkiCharts();
    }
})();
</script>