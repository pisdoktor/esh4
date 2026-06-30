<?php
/** @var object $patient */
?>
<div class="btn-group-vertical btn-group-sm w-100 shadow-sm border rounded">
    <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $patient->tckimlik]), ENT_QUOTES, 'UTF-8') ?>"
       class="btn btn-info py-0 px-1 esh-patient-izlem-btn" data-bs-toggle="tooltip" title="Yapılan: <?= $patient->izlemsayisi ?? 0 ?>">
        <i class="fa-solid fa-check"></i> <?= $patient->izlemsayisi ?? 0 ?>
    </a>
    <a href="<?= htmlspecialchars(esh_url('Visit', 'missed', ['tc' => $patient->tckimlik]), ENT_QUOTES, 'UTF-8') ?>"
       class="btn <?= ($patient->yizlemsayisi ?? 0) > 0 ? 'btn-danger' : 'btn-light' ?> py-0 px-1 esh-patient-izlem-btn"
       data-bs-toggle="tooltip" title="Yapılmayan: <?= $patient->yizlemsayisi ?? 0 ?>">
        <i class="fa-solid fa-xmark"></i> <?= $patient->yizlemsayisi ?? 0 ?>
    </a>
    <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'list', ['tc' => $patient->tckimlik]), ENT_QUOTES, 'UTF-8') ?>"
       class="btn <?= ($patient->totalplanli ?? 0) > 0 ? 'btn-warning' : 'btn-light' ?> py-0 px-1 esh-patient-izlem-btn"
       data-bs-toggle="tooltip" title="Planlı: <?= $patient->totalplanli ?? 0 ?>">
        <i class="fa-solid fa-clock"></i> <?= $patient->totalplanli ?? 0 ?>
    </a>
</div>
