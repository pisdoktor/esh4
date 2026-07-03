<?php
/**
 * Hızlı istatistikler — yalnızca grup düğmeleri (kart gövdesi).
 * @var array<int, array<string, mixed>> $quickStatGroups
 * @var string $innerWrapClass
 * @var string $statsGroupColClass
 */
use App\Helpers\StatsNavHelper;

$innerWrapClass = $innerWrapClass ?? 'border rounded-3 p-3 h-100';
$statsGroupColClass = $statsGroupColClass ?? 'col-12 col-xl-6';
$eshDashQuickStatBtnClass = StatsNavHelper::DASHBOARD_QUICK_BTN_CLASS;
?>
<div class="row g-3">
    <?php foreach ($quickStatGroups as $group): ?>
        <div class="<?= htmlspecialchars($statsGroupColClass, ENT_QUOTES, 'UTF-8') ?>">
            <div class="<?= htmlspecialchars($innerWrapClass, ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-<?= htmlspecialchars($group['accent'], ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($group['accent'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid <?= htmlspecialchars($group['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($group['items'] as $s): ?>
                        <a class="btn btn-sm <?= htmlspecialchars($eshDashQuickStatBtnClass, ENT_QUOTES, 'UTF-8') ?> esh-dash-quick-stat-btn" href="<?= htmlspecialchars(esh_url('Stats', $s['action']), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid <?= htmlspecialchars($s['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
