    <h4 class="fw-bold mb-3">
        <i class="fa-solid fa-table text-success me-2"></i>
        <?= htmlspecialchars((string) ($report['title'] ?? 'Çapraz tablo'), ENT_QUOTES, 'UTF-8') ?>
    </h4>
    <?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>