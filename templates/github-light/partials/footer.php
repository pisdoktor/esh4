        </div>
</main>

    <footer class="footer py-4 border-top cpp-site-footer">
        <div class="container-xxl d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="cpp-footer-brand small">
                <span class="cpp-footer-brand__mark" aria-hidden="true"><i class="fa-brands fa-github"></i></span>
            </div>
            <?= esh_footer_right_cluster_html('rounded-pill cpp-footer-pill', 'rounded-pill bg-success text-white border-0'); ?>
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

                    loadTimeElement.className = 'badge rounded-pill ' + badgeClass + ' text-white border-0';
                    loadTimeElement.textContent = timeText;
                    loadTimeElement.classList.remove('d-none');
                    loadTimeElement.title = 'Sayfa yüklenme süresi: ' + loadTime.toFixed(2) + 'ms';
                }
            });

            if (typeof window.eshInitTomSelectOnPage === 'function') {
                window.eshInitTomSelectOnPage();
            }

            toastr.options = { "progressBar": true, "positionClass": "toast-top-right", "timeOut": "4000" };

            <?php if (isset($_SESSION['success']) || isset($_SESSION['error']) || isset($_SESSION['warning'])): ?>
                <?php \App\Helpers\FlashHelper::renderSessionToastrLines(); ?>
            <?php endif; ?>

            const params = new URLSearchParams(window.location.search);
            if (params.get('msg') === 'kayit_basarili') toastr.success('İşlem başarıyla tamamlandı.');
        });
    </script>
</body>
</html>
