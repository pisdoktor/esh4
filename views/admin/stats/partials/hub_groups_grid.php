<?php
/**
 * İstatistik hub — ana başlıklar 3 sütun (xl), alt bölümler ana başlıkla hizalı.
 *
 * @var array<int, array<string, mixed>>|null $groups
 */
use App\Helpers\StatsNavHelper;

if (!isset($groups)) {
    $groups = StatsNavHelper::hubGroups();
}
?>
<div class="row g-3 esh-stats-hub">
    <?php foreach ($groups as $group):
        $accent = (string) ($group['accent'] ?? 'primary');
        $sections = $group['sections'] ?? [['label' => null, 'cards' => $group['cards'] ?? []]];
    ?>
        <div class="col-sm-6 col-xl-4 d-flex">
            <div class="card esh-stats-hub__group border-0 shadow-sm h-100 w-100">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <span class="esh-stats-hub__group-icon rounded-2 bg-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> bg-opacity-10 text-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid <?= htmlspecialchars((string) ($group['icon'] ?? 'fa-chart-pie'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-grow-1">
                            <h2 class="esh-stats-hub__group-title h6 mb-0 fw-bold lh-sm"><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php if (!empty($group['desc'])): ?>
                                <p class="esh-stats-hub__group-desc mb-0 mt-1"><?= htmlspecialchars((string) $group['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body py-2 px-3">
                    <?php foreach ($sections as $section):
                        $cards = $section['cards'] ?? [];
                        if ($cards === []) {
                            continue;
                        }
                        $sectionLabel = trim((string) ($section['label'] ?? ''));
                    ?>
                        <?php if ($sectionLabel !== ''): ?>
                            <div class="esh-stats-hub__section-label border-start border-2 border-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> ps-2 text-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <div class="esh-stats-hub__items<?= $sectionLabel !== '' ? '' : ' esh-stats-hub__items--flush' ?>">
                            <?php foreach ($cards as $c):
                                $color = (string) ($c['color'] ?? 'primary');
                                $reportAction = (string) ($c['action'] ?? '');
                            ?>
                                <a href="<?= htmlspecialchars(esh_url('Stats', $reportAction), ENT_QUOTES, 'UTF-8') ?>"
                                   class="esh-stats-hub__item border-start border-3 border-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>"
                                   title="<?= htmlspecialchars((string) ($c['desc'] ?? $c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="esh-stats-hub__item-icon bg-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?> bg-opacity-10 text-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid <?= htmlspecialchars((string) ($c['icon'] ?? 'fa-chart-simple'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                    </span>
                                    <span class="esh-stats-hub__item-title"><?= htmlspecialchars((string) ($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
