<?php
declare(strict_types=1);

use App\Helpers\AppSettings;

/** Takvim görev türleri — badge içinde ikon + metin; Yapılan yeşil. */
$eshCalLegendItems = [
    ['icon' => 'fa-calendar-check', 'label' => 'İzlem', 'done' => false],
    ['icon' => 'fa-plus-square', 'label' => 'Pansuman', 'done' => false],
    ['icon' => 'fa-user-plus', 'label' => 'İlk ziyaret', 'done' => false],
    ['icon' => 'fa-ambulance', 'label' => 'Nakil', 'done' => false],
];
if (AppSettings::isModuleEnabled('randevu')) {
    $eshCalLegendItems[] = ['icon' => 'fa-calendar-days', 'label' => 'Randevu', 'done' => false];
}
$eshCalLegendItems[] = ['icon' => 'fa-check-circle', 'label' => 'Yapılan', 'done' => true];
?>
<div class="d-flex flex-wrap justify-content-center gap-2 esh-cal-legend" role="list">
    <?php foreach ($eshCalLegendItems as $item):
        $isDone = !empty($item['done']);
        $badgeClass = $isDone ? 'bg-success text-white border-success' : 'bg-white border text-muted';
        $iconClass = $isDone ? 'text-white' : 'text-secondary';
        ?>
        <span class="badge esh-cal-legend__badge <?= $badgeClass ?> fw-normal shadow-sm" role="listitem">
            <i class="fa <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-1 <?= $iconClass ?>" aria-hidden="true"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    <?php endforeach; ?>
</div>
