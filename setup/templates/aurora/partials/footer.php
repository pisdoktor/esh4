<?php
/*
 * Aurora Light teması — alt kabuk (layout footer)
 * Yol: templates/aurora/partials/footer.php
 *
 * Controller : (footer kullanan tüm aksiyonlar)
 * Action     : —
 * Canonical  : views/partials/footer.php
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 */
?>
</main>

    <footer class="footer aurora-footer py-3 border-top">
        <div class="container d-flex flex-wrap align-items-center gap-2">
            <?= esh_footer_right_cluster_html('aurora-badge-soft', 'aurora-badge-accent'); ?>
        </div>
    </footer>
<?php include ROOT_PATH . '/views/partials/db_debug_console.php'; ?>
<?php require ROOT_PATH . '/views/partials/footer_page_scripts.php'; ?>
    <script<?= esh_csp_nonce_attr() ?>>
        window.pageLoadStart = performance.now();

        $(document).ready(function() {
            window.addEventListener('load', function() {
                const loadTime = performance.now() - window.pageLoadStart;
                const loadTimeElement = document.getElementById('pageLoadTime');

                if (loadTimeElement) {
                    let timeText = '';

                    if (loadTime < 500) {
                        timeText = '⚡ ' + loadTime.toFixed(0) + 'ms';
                    } else if (loadTime < 1000) {
                        timeText = '🚀 ' + loadTime.toFixed(0) + 'ms';
                    } else if (loadTime < 2000) {
                        timeText = '🐌 ' + (loadTime / 1000).toFixed(2) + 's';
                    } else {
                        timeText = '🐢 ' + (loadTime / 1000).toFixed(2) + 's';
                    }

                    loadTimeElement.className = 'badge aurora-badge-accent';
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
