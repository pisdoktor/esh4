    <?php if ($toplam > 0): ?>
    <script<?= esh_csp_nonce_attr() ?>>
    (function () {
        if (typeof Chart === 'undefined') return;
        var el = document.getElementById('statsExitReasonChart');
        if (!el) return;
        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($data) ?>,
                    backgroundColor: <?= json_encode($colors) ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    })();
    </script>
    <?php endif; ?>