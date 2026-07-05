</main>

    <footer class="footer py-3 bg-white border-top">
        <div class="container d-flex flex-wrap align-items-center gap-2">
            <?= esh_footer_right_cluster_html(); ?>
        </div>
    </footer>
<?php include ROOT_PATH . '/views/partials/db_debug_console.php'; ?>
<?php require ROOT_PATH . '/views/partials/footer_page_scripts.php'; ?>
    <script<?= esh_csp_nonce_attr() ?>>
        // Sayfa yüklenme zamanını hesapla
        window.pageLoadStart = performance.now();
        
        $(document).ready(function() {
            // Sayfa yüklenme süresini hesapla ve göster
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
                    
                    loadTimeElement.className = 'badge ' + badgeClass + ' text-white border';
                    loadTimeElement.textContent = timeText;
                    loadTimeElement.classList.remove('d-none');
                    loadTimeElement.title = 'Sayfa yüklenme süresi: ' + loadTime.toFixed(2) + 'ms';
                }
            });

            // Global Tom Select başlatıcı
            if (typeof window.eshInitTomSelectOnPage === 'function') {
                window.eshInitTomSelectOnPage();
            }

            // Toastr Ayarları
            toastr.options = { "progressBar": true, "positionClass": "toast-top-right", "timeOut": "4000" };

            // PHP'den gelen Session Mesajlarını Yakala
            <?php \App\Helpers\FlashHelper::renderSessionToastrLines(); ?>

            // URL'den gelen status mesajlarını yakala
            const params = new URLSearchParams(window.location.search);
            if(params.get('msg') === 'kayit_basarili') toastr.success('İşlem başarıyla tamamlandı.');
        });
    </script>
</body>
</html>
