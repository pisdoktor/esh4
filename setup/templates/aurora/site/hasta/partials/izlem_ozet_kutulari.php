<?php
/**
 * Hasta kartı üst şerit — yapılan / yapılmayan / planlı izlem özet kutuları.
 *
 * @var string $tcQ
 * @var int $doneCount
 * @var int $missedCount
 * @var int $plannedCount
 * @var string $lastDoneDate
 * @var string $lastDoneOps
 * @var string $lastDoneBy
 * @var string $lastMissDate
 * @var string $lastMissOps
 * @var string $lastMissBy
 * @var string $lastPlanDate
 * @var string $lastPlanOps
 * @var string $lastPlanBy
 */
?>
<div class="row g-2 mt-1 esh-hasta-izlem-ozet">
    <div class="col-md-4">
        <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $tcQ, 'status' => 1]), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
            <div class="border rounded bg-white h-100 shadow-sm esh-hasta-izlem-ozet__tile">
                <div class="esh-hasta-izlem-ozet__body">
                    <div class="esh-hasta-izlem-ozet__count-col">
                        <div class="fw-bold text-success fs-5 esh-hasta-izlem-ozet__count"><i class="fa-solid fa-check me-1"></i><?= (int) $doneCount ?></div>
                    </div>
                    <div class="esh-hasta-izlem-ozet__details">
                        <div class="small text-muted">Yapılan Son İzlem</div>
                        <div class="small text-dark"><strong>Son:</strong> <?= htmlspecialchars($lastDoneDate, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastDoneOps, ENT_QUOTES, 'UTF-8') ?>"><strong>İşlem:</strong> <?= htmlspecialchars($lastDoneOps, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastDoneBy, ENT_QUOTES, 'UTF-8') ?>"><strong>Yapan:</strong> <?= htmlspecialchars($lastDoneBy, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $tcQ, 'status' => 0]), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
            <div class="border rounded bg-white h-100 shadow-sm esh-hasta-izlem-ozet__tile">
                <div class="esh-hasta-izlem-ozet__body">
                    <div class="esh-hasta-izlem-ozet__count-col">
                        <div class="fw-bold text-danger fs-5 esh-hasta-izlem-ozet__count"><i class="fa-solid fa-xmark me-1"></i><?= (int) $missedCount ?></div>
                    </div>
                    <div class="esh-hasta-izlem-ozet__details">
                        <div class="small text-muted">Yapılmayan Son İzlem</div>
                        <div class="small text-dark"><strong>Son:</strong> <?= htmlspecialchars($lastMissDate, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastMissOps, ENT_QUOTES, 'UTF-8') ?>"><strong>İşlem:</strong> <?= htmlspecialchars($lastMissOps, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastMissBy, ENT_QUOTES, 'UTF-8') ?>"><strong>Yapan:</strong> <?= htmlspecialchars($lastMissBy, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => $tcQ]), ENT_QUOTES, "UTF-8") ?>" class="text-decoration-none">
            <div class="border rounded bg-white h-100 shadow-sm esh-hasta-izlem-ozet__tile">
                <div class="esh-hasta-izlem-ozet__body">
                    <div class="esh-hasta-izlem-ozet__count-col">
                        <div class="fw-bold text-warning fs-5 esh-hasta-izlem-ozet__count"><i class="fa-solid fa-clock me-1"></i><?= (int) $plannedCount ?></div>
                    </div>
                    <div class="esh-hasta-izlem-ozet__details">
                        <div class="small text-muted">Planlı Son İzlem</div>
                        <div class="small text-dark"><strong>Tarih:</strong> <?= htmlspecialchars($lastPlanDate, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastPlanOps, ENT_QUOTES, 'UTF-8') ?>"><strong>Yapılacak:</strong> <?= htmlspecialchars($lastPlanOps, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($lastPlanBy, ENT_QUOTES, 'UTF-8') ?>"><strong>Planlayan:</strong> <?= htmlspecialchars($lastPlanBy, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
