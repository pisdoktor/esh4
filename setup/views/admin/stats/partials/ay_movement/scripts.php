<script>
(function () {
    if (typeof Chart === 'undefined') return;
    new Chart(document.getElementById('ayCinsChart'), {
        type: 'doughnut',
        data: {
            labels: ['Erkek', 'Kadın'],
            datasets: [{ data: [<?= (int) ($gen->active_male ?? 0) ?>, <?= (int) ($gen->active_female ?? 0) ?>], backgroundColor: ['#0d6efd', '#dc3545'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('ayHareketChart'), {
        type: 'bar',
        data: {
            labels: ['Yeni kayıt', 'Takipten çıkan'],
            datasets: [{ label: 'Sayı', data: [<?= $newT ?>, <?= $exT ?>], backgroundColor: ['#198754', '#6c757d'], borderRadius: 8 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $collapse = $('#stats-ay-movement-filter-collapse');
        var $toggleText = $('#stats-ay-movement-filter-toggle .js-filter-toggle-text');
        if ($collapse.length && $toggleText.length) {
            $collapse.on('shown.bs.collapse', function () {
                $toggleText.text('Filtreleri Gizle');
            });
            $collapse.on('hidden.bs.collapse', function () {
                $toggleText.text('Filtreleri Göster');
            });
        }
    });