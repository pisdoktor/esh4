<h4 class="fw-bold mb-3">
    <i class="fa-solid <?= htmlspecialchars((string) ($meta['icon'] ?? 'fa-chart-pie'), ENT_QUOTES, 'UTF-8') ?> text-<?= htmlspecialchars((string) ($meta['color'] ?? 'primary'), ENT_QUOTES, 'UTF-8') ?> me-2"></i>
    <?= htmlspecialchars((string) ($meta['pageTitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</h4>

<?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>
