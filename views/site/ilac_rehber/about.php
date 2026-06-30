<?php

use App\Helpers\DateHelper;
use App\Helpers\PageShellHelper;



$etkenCount = (int) ($etkenCount ?? 0);

$ilacCount = (int) ($ilacCount ?? 0);

$etkenWithoutIlacCount = (int) ($etkenWithoutIlacCount ?? 0);

$scrapedSummary = $scrapedSummary ?? ['last_scraped_at' => null, 'source_sites' => []];

$lastScrapedAt = $scrapedSummary['last_scraped_at'] ?? null;

$sourceSites = $scrapedSummary['source_sites'] ?? [];

$sourceSitesLabel = $sourceSites === [] ? '—' : implode(', ', $sourceSites);



$formatDt = static function (?string $raw): string {
    if ($raw === null || $raw === '') {
        return '—';
    }
    $s = DateHelper::toTrDotDateTimeOrEmpty($raw);

    return $s !== '' ? $s : $raw;
};

?>

<?php

PageShellHelper::pageOpen([

    'kind' => 'detail',

    'module' => 'ilac_rehber',

]);

PageShellHelper::pageHeader(

    'İlaç rehberi — veri kaynağı',

    'Veritabanı özeti ve aktarım durumu.',

    '<a href="' . htmlspecialchars(esh_url('IlacRehber', 'search'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-secondary btn-sm">Aramaya dön</a>',

    ['icon' => 'fa-solid fa-circle-info']

);

?>



<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php
        $irMigrationHref = '';
        if (!empty($_SESSION['user_id'])
            && class_exists(\App\Helpers\AuthHelper::class, false)
            && \App\Helpers\AuthHelper::sessionIsSuperAdmin()) {
            $irMigrationHref = esh_url('IlacRehber', 'migration');
        }
        ?>
        <?php if ($irMigrationHref !== ''): ?>
        <p class="mb-3">
            <a href="<?= htmlspecialchars($irMigrationHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-database me-1"></i>Veri aktarımı yönetimi
            </a>
        </p>
        <?php endif; ?>



        <h2 class="h6 fw-bold">Veritabanı özeti</h2>

        <?php include __DIR__ . '/_live_stats_panel.php'; ?>

        <ul class="list-unstyled small mb-3">

            <li><strong>Kayıtlı kaynak site:</strong> <?= htmlspecialchars($sourceSitesLabel, ENT_QUOTES, 'UTF-8') ?></li>

            <li><strong>Son scrape zamanı:</strong> <?= htmlspecialchars($formatDt($lastScrapedAt), ENT_QUOTES, 'UTF-8') ?></li>

        </ul>

    </div>

</div>



<?php PageShellHelper::pageClose(); ?>


