<?php

declare(strict_types=1);

/** @var object $hasta */

/** @var bool $canToggleClinicalFlags */

use App\Helpers\BadgeHelper;

use App\Helpers\PatientClinicalFlagsHelper;

$generalGroups = PatientClinicalFlagsHelper::generalTabGroups();

$canToggleClinicalFlags = !empty($canToggleClinicalFlags);

$groupKeys = array_keys($generalGroups);

$groupIcons = [
    'dosya' => 'fa-folder-open',
    'solunum' => 'fa-lungs',
    'beslenme' => 'fa-utensils',
    'stoma' => 'fa-droplet',
    'vaskuler' => 'fa-heart-pulse',
    'diger' => 'fa-layer-group',
];

?>

<div class="accordion accordion-flush esh-patient-genel-accordion" id="esh-patient-genel-accordion">
    <?php foreach ($generalGroups as $groupKey => $group):
        $collapseId = 'esh-patient-genel-' . htmlspecialchars((string) $groupKey, ENT_QUOTES, 'UTF-8');
        $headingId = $collapseId . '-heading';
        $isFirst = ($groupKey === $groupKeys[0]);
        $keys = $group['keys'];
        $keyCount = count($keys);
    ?>
    <div class="accordion-item esh-patient-genel-accordion__item" data-esh-genel-group="<?= htmlspecialchars((string) $groupKey, ENT_QUOTES, 'UTF-8') ?>">
        <h2 class="accordion-header" id="<?= $headingId ?>">
            <button class="accordion-button<?= $isFirst ? '' : ' collapsed' ?> esh-patient-genel-accordion__btn shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $collapseId ?>"
                    aria-expanded="<?= $isFirst ? 'true' : 'false' ?>"
                    aria-controls="<?= $collapseId ?>">
                <span class="esh-patient-genel-accordion__lead">
                    <span class="esh-patient-genel-accordion__icon" aria-hidden="true">
                        <i class="fa-solid <?= htmlspecialchars((string) ($groupIcons[$groupKey] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?>"></i>
                    </span>
                    <span class="esh-patient-genel-accordion__title"><?= htmlspecialchars((string) $group['title'], ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>
        </h2>
        <div id="<?= $collapseId ?>"
             class="accordion-collapse collapse<?= $isFirst ? ' show' : '' ?>"
             aria-labelledby="<?= $headingId ?>">
            <div class="accordion-body esh-patient-genel-accordion__body">
                <div class="list-group list-group-flush small" id="esh-patient-genel-flags-<?= htmlspecialchars((string) $groupKey, ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($keys as $ki => $key):
                        $isLastItem = ($ki === $keyCount - 1);
                        $borderClass = $isLastItem ? ' border-0' : '';
                        $fieldLabel = PatientClinicalFlagsHelper::label($key);
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0<?= $borderClass ?>">
                        <span><?= htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') ?>:</span>
                        <?= BadgeHelper::yesNoEvetHayirToggleable($hasta->$key ?? null, $key, $fieldLabel, $canToggleClinicalFlags) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
