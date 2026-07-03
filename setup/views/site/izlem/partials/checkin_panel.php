<?php
declare(strict_types=1);

use App\Helpers\OperationalSettings;

$fieldVisitMode = OperationalSettings::fieldVisitCheckinMode();
$checkinLat = isset($visitCheckinLat) && is_numeric($visitCheckinLat) ? (float) $visitCheckinLat : null;
$checkinLon = isset($visitCheckinLon) && is_numeric($visitCheckinLon) ? (float) $visitCheckinLon : null;
$hasExisting = $checkinLat !== null && $checkinLon !== null
    && $checkinLat >= -90 && $checkinLat <= 90
    && $checkinLon >= -180 && $checkinLon <= 180;
?>
<div class="esh-visit-checkin-panel mb-3" role="region" aria-label="Saha konum kaydı">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
        <div>
            <h6 class="mb-1 fw-semibold small text-uppercase text-secondary">
                <i class="fa-solid fa-location-crosshairs me-1 text-success" aria-hidden="true"></i>Saha konumu
            </h6>
            <p class="small text-muted mb-0" data-esh-visit-checkin-hint></p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-esh-visit-checkin-refresh>
            <i class="fa-solid fa-crosshairs me-1" aria-hidden="true"></i>Konumu al
        </button>
    </div>
    <div class="alert py-2 px-3 mb-0 small shadow-sm border-0<?= $hasExisting ? ' alert-success' : ' alert-secondary d-none' ?>"
         data-esh-visit-checkin-status
         data-state="<?= $hasExisting ? 'ok' : 'idle' ?>"
         role="status"
         aria-live="polite">
        <?php if ($hasExisting): ?>
            <i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Mevcut konum:
            <?= htmlspecialchars(number_format($checkinLat, 5, '.', '') . ', ' . number_format($checkinLon, 5, '.', ''), ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </div>
    <div class="alert py-2 px-3 mb-0 mt-2 small shadow-sm border-0 alert-warning d-none"
         data-esh-visit-geofence-warning
         role="alert"
         aria-live="polite"></div>
    <?php if ($fieldVisitMode === 'required_completed'): ?>
        <p class="form-text small mb-0 mt-2 text-danger">
            <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Yapıldı olarak işaretlenen izlemlerde GPS konumu zorunludur.
        </p>
    <?php endif; ?>
</div>
