<?php
declare(strict_types=1);
/**
 * Hasta kartı — Hasta Bilgisi Düzenle dropdown (parçalı modal tetikleyiciler).
 */
$eshEditMenuItems = \App\Helpers\BadgeHelper::patientDetailEditSectionMenuItems();
?>
<ul class="dropdown-menu shadow-lg border-0 py-2 esh-patient-edit-section-menu">
    <?php foreach ($eshEditMenuItems as $eshEditItem): ?>
        <li>
            <button type="button"
                    class="dropdown-item d-flex align-items-center py-2"
                    data-bs-toggle="modal"
                    data-bs-target="#patientEditModal-<?= htmlspecialchars((string) ($eshEditItem['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <i class="<?= htmlspecialchars((string) ($eshEditItem['icon'] ?? 'fa-solid fa-pen'), ENT_QUOTES, 'UTF-8') ?> me-2"></i>
                <?= htmlspecialchars((string) ($eshEditItem['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>
