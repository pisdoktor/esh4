    </main>

    <footer class="footer fluent-winui-footer py-3 border-top fluent-footer-shell">
        <div class="container d-flex flex-wrap align-items-center gap-2">
            <?= esh_footer_right_cluster_html('fluent-badge-soft text-muted', 'bg-success text-white border-0 fluent-badge-accent'); ?>
        </div>
    </footer>
<?php include ROOT_PATH . '/views/partials/db_debug_console.php'; ?>
<?php require ROOT_PATH . '/views/partials/footer_page_scripts.php'; ?>
    <script>
        window.pageLoadStart = performance.now();

        $(document).ready(function() {
            window.addEventListener('load', function() {
                const loadTime = performance.now() - window.pageLoadStart;
                const loadTimeElement = document.getElementById('pageLoadTime');

                if (loadTimeElement) {
                    let badgeClass = 'bg-success';
                    let timeText = '';

                    if (loadTime < 500) {
                        badgeClass = 'bg-success';
                        timeText = '⚡ ' + loadTime.toFixed(0) + 'ms';
                    } else if (loadTime < 1000) {
                        badgeClass = 'bg-info';
                        timeText = '🚀 ' + loadTime.toFixed(0) + 'ms';
                    } else if (loadTime < 2000) {
                        badgeClass = 'bg-warning';
                        timeText = '🐌 ' + (loadTime / 1000).toFixed(2) + 's';
                    } else {
                        badgeClass = 'bg-danger';
                        timeText = '🐢 ' + (loadTime / 1000).toFixed(2) + 's';
                    }

                    loadTimeElement.className = 'badge ' + badgeClass + ' text-white border-0 fluent-badge-accent';
                    loadTimeElement.textContent = timeText;
                    loadTimeElement.classList.remove('d-none');
                    loadTimeElement.title = 'Sayfa yüklenme süresi: ' + loadTime.toFixed(2) + 'ms';
                }
            });

            if (typeof window.eshInitTomSelectOnPage === 'function') {
                window.eshInitTomSelectOnPage();
            }

            toastr.options = { "progressBar": true, "positionClass": "toast-top-right", "timeOut": "4000" };

            <?php \App\Helpers\FlashHelper::renderSessionToastrLines(); ?>

            const params = new URLSearchParams(window.location.search);
            if(params.get('msg') === 'kayit_basarili') toastr.success('İşlem başarıyla tamamlandı.');
        });
    </script>
</body>
</html>
