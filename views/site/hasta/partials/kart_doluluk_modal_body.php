<?php
/**
 * Hasta kartı — doluluk modal gövdesi (bölüm kırılımı + eksik alanlar).
 *
 * @var object $hasta
 */
use App\Helpers\PatientCompletenessHelper;

$eshDoluluk = PatientCompletenessHelper::cardContext($hasta);
$pctInt = (int) ($eshDoluluk['pct_int'] ?? 0);
$color = (string) ($eshDoluluk['color'] ?? 'secondary');
$barW = max(0, min(100, $pctInt));
$groups = $eshDoluluk['groups'] ?? [];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span class="badge bg-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?> esh-hasta-doluluk__badge">
        %<?= $pctInt ?>
        <span class="fw-normal opacity-75">(<?= (int) ($eshDoluluk['filled'] ?? 0) ?>/<?= (int) ($eshDoluluk['total'] ?? 0) ?>)</span>
    </span>
</div>
<div class="progress esh-hasta-doluluk__bar esh-hasta-doluluk__bar--card mb-3" role="progressbar"
     aria-valuenow="<?= $pctInt ?>" aria-valuemin="0" aria-valuemax="100"
     aria-label="Kart doluluk yüzde <?= $pctInt ?>">
    <div class="progress-bar bg-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>" style="width:<?= $barW ?>%"></div>
</div>
<div class="small text-muted mb-3">
    <i class="fa-solid fa-microchip me-1" aria-hidden="true"></i><?= htmlspecialchars((string) ($eshDoluluk['clinical_info'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</div>
<div class="esh-hasta-doluluk__groups esh-hasta-doluluk__groups--modal">
    <?php foreach ($groups as $group):
        $gPct = (int) round((float) ($group['pct'] ?? 0));
        $gColor = PatientCompletenessHelper::pctColorClass((float) ($group['pct'] ?? 0));
        $gBarW = max(0, min(100, $gPct));
        $section = (string) ($group['section'] ?? '');
        $missing = $group['missing'] ?? [];
    ?>
    <div class="esh-hasta-doluluk__group mb-2">
        <div class="d-flex justify-content-between align-items-center small mb-1">
            <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($group['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="text-muted">
                <?= (int) ($group['filled'] ?? 0) ?>/<?= (int) ($group['total'] ?? 0) ?>
                <span class="text-<?= htmlspecialchars($gColor, ENT_QUOTES, 'UTF-8') ?>">(%<?= $gPct ?>)</span>
                <?php if ($section !== '' && $missing !== []): ?>
                <button type="button"
                        class="btn btn-link btn-sm p-0 ms-1 align-baseline esh-hasta-doluluk__edit-btn"
                        data-bs-dismiss="modal"
                        data-bs-toggle="modal"
                        data-bs-target="#patientEditModal-<?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>"
                        title="Eksik alanları düzenle">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
            </span>
        </div>
        <div class="progress esh-hasta-doluluk__bar esh-hasta-doluluk__bar--group" role="progressbar"
             aria-valuenow="<?= $gPct ?>" aria-valuemin="0" aria-valuemax="100"
             aria-label="<?= htmlspecialchars((string) ($group['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?> doluluk">
            <div class="progress-bar bg-<?= htmlspecialchars($gColor, ENT_QUOTES, 'UTF-8') ?>" style="width:<?= $gBarW ?>%"></div>
        </div>
        <?php if ($missing !== []): ?>
        <ul class="list-unstyled small text-muted mb-0 mt-1 ps-1 esh-hasta-doluluk__missing">
            <?php foreach ($missing as $missLabel): ?>
            <li><i class="fa-solid fa-circle-xmark text-danger me-1 opacity-75" aria-hidden="true"></i><?= htmlspecialchars((string) $missLabel, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
