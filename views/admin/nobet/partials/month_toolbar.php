    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('Nobet', 'index', ['ay' => max(1, $ay - 1), 'yil' => ($ay === 1 ? $yil - 1 : $yil)]), ENT_QUOTES, 'UTF-8') ?>">Önceki Ay</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('Nobet', 'index'), ENT_QUOTES, 'UTF-8') ?>">Bu Ay</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('Nobet', 'index', ['ay' => min(12, $ay + 1), 'yil' => ($ay === 12 ? $yil + 1 : $yil)]), ENT_QUOTES, 'UTF-8') ?>">Sonraki Ay</a>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <button type="button"
                    class="btn btn-sm btn-outline-primary js-nobet-modal-link"
                    data-modal-title="Aylık Mesai Dengesi Özeti"
                    data-modal-url="<?= htmlspecialchars(esh_url('Nobet', 'monthlySummary', ['ay' => (int) $ay, 'yil' => (int) $yil]), ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-chart-column me-1"></i>Aylık Mesai Özeti
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-info js-nobet-modal-link"
                    data-modal-title="Yıllık Nöbet İstatistiği"
                    data-modal-url="<?= htmlspecialchars(esh_url('Nobet', 'yearlyStats', ['istatistik_yil' => (int) $yil]), ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-calendar-days me-1"></i>Yıllık Nöbet İstatistiği
            </button>
            <form action="<?= htmlspecialchars(esh_url('Nobet', 'rebuild'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-flex gap-2">
            <input type="hidden" name="ay" value="<?= (int) $ay ?>">
            <input type="hidden" name="yil" value="<?= (int) $yil ?>">
            <button class="btn btn-sm btn-warning" onclick="return confirm('Bu ayın nöbetleri yeniden oluşturulsun mu?')"><i class="fa-solid fa-rotate me-1"></i>Otomatik Dağıtım</button>
            </form>
        </div>
    </div>
