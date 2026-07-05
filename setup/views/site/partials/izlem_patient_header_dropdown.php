<?php
/**
 * İzlem geçmişi / planlı izlem (hasta TC) — sağ üst hasta adı menüsü (3.1.2 ile uyumlu).
 *
 * @var string $izlemPatientHeaderMenuActive 'history'|'plans'
 */
$active = (string) ($izlemPatientHeaderMenuActive ?? 'history');
$tc = (string) ($tc ?? '');
$tcQ = isset($tcQ) ? (string) $tcQ : rawurlencode($tc);
$patientIdForHeader = (string) ($patientIdForHeader ?? '');
$histAktif = isset($histAktif) ? (bool) $histAktif : true;
?>
<ul class="dropdown-menu shadow border-0 py-2">
    <li>
        <a class="dropdown-item<?= $patientIdForHeader < 1 ? ' disabled' : '' ?>"
           href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $patientIdForHeader]), ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-id-card text-primary me-2"></i>Hasta kartı
        </a>
    </li>
    <?php if ($tc !== ''): ?>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item<?= $active === 'history' ? ' active' : '' ?>"
               href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $tcQ]), ENT_QUOTES, 'UTF-8') ?>"
               <?= $active === 'history' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-list-check text-info me-2"></i>İzlem geçmişi
            </a>
        </li>
        <li>
            <a class="dropdown-item<?= $active === 'plans' ? ' active' : '' ?>"
               href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => $tcQ]), ENT_QUOTES, 'UTF-8') ?>"
               <?= $active === 'plans' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-calendar-week text-warning me-2"></i>Planlı izlemler
            </a>
        </li>
    <?php endif; ?>
    <?php if ($histAktif && $tc !== ''): ?>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item"
               href="<?= htmlspecialchars(esh_url('Visit', 'create', ['tc' => $tcQ]), ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-plus text-success me-2"></i>İzlem gir
            </a>
        </li>
        <li>
            <a class="dropdown-item"
               href="<?= htmlspecialchars(esh_url('PlannedVisit', 'create', ['tc' => $tcQ]), ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-calendar-plus text-primary me-2"></i>İzlem planla
            </a>
        </li>
    <?php endif; ?>
</ul>
