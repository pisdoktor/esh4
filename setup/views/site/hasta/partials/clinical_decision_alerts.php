<?php
/**
 * Klinik karar desteği uyarı paneli.
 *
 * @var list<array{code:string,severity:string,title:string,message:string,action_url?:string,action_label?:string}> $clinicalDecisionAlerts
 */
$clinicalDecisionAlerts = $clinicalDecisionAlerts ?? [];
$clinicalDecisionPatientId = isset($clinicalDecisionPatientId) ? (int) $clinicalDecisionPatientId : 0;
if ($clinicalDecisionAlerts === []) {
    return;
}
$severityIcons = [
    'danger' => 'fa-triangle-exclamation',
    'warning' => 'fa-circle-exclamation',
    'info' => 'fa-circle-info',
];
?>
<div class="card shadow-sm border-0 mb-4 border-start border-4 border-danger" role="region" aria-label="Klinik karar desteği uyarıları">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold text-danger">
            <i class="fa-solid fa-user-doctor me-2 opacity-75"></i>Klinik karar desteği
        </h6>
        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle"><?= count($clinicalDecisionAlerts) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($clinicalDecisionAlerts as $alert):
                $sev = (string) ($alert['severity'] ?? 'info');
                $icon = $severityIcons[$sev] ?? 'fa-circle-info';
                $alertClass = $sev === 'danger' ? 'list-group-item-danger' : ($sev === 'warning' ? 'list-group-item-warning' : 'list-group-item-info');
                ?>
                <div class="list-group-item <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?> py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold mb-1">
                                <i class="fa-solid <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-2"></i>
                                <?= htmlspecialchars((string) ($alert['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="small mb-0 text-body-secondary"><?= htmlspecialchars((string) ($alert['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <?php if (!empty($alert['action_url'])): ?>
                            <a href="<?= htmlspecialchars((string) $alert['action_url'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-sm btn-outline-dark rounded-pill flex-shrink-0">
                                <?= htmlspecialchars((string) ($alert['action_label'] ?? 'Git'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($clinicalDecisionPatientId > 0 && !empty($alert['code'])): ?>
                            <form method="post" action="<?= htmlspecialchars(esh_url('Patient', 'acknowledgeClinicalAlert'), ENT_QUOTES, 'UTF-8') ?>" class="ms-1">
                                <?= esh_csrf_field() ?>
                                <input type="hidden" name="patient_id" value="<?= $clinicalDecisionPatientId ?>">
                                <input type="hidden" name="alert_code" value="<?= htmlspecialchars((string) $alert['code'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">Uyariyi onayla</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
