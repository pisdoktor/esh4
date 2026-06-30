<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/partials/admin/stats_page_intro.php
 *
 * Controller : (StatsController render — include)
 * Action     : —
 * Canonical  : views/partials/admin/stats_page_intro.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   $statsIntro (string) — StatsIntroHelper::forAction; boşsa partial çıkmaz
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 * Not: Stats rapor view’larında stats_page_intro.php ile include edilir.
 */
declare(strict_types=1);
/** @var string $statsIntro */
$statsIntro = trim((string) ($statsIntro ?? ''));
if ($statsIntro === '') {
    return;
}
?>
<p class="text-muted small mb-3"><?= htmlspecialchars($statsIntro, ENT_QUOTES, 'UTF-8') ?></p>
