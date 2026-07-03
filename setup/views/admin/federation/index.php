<?php
declare(strict_types=1);

use App\Helpers\FederationBridgeHelper;
use App\Helpers\FederationHelper;
use App\Helpers\OperationalSettings;

/** @var list<array{bolge: object, kurum_count: int, active_patients: int}> $overviewRows */
$overviewRows = $overviewRows ?? [];
$federationReady = (bool) ($federationReady ?? false);
?>
<div class="esh-page esh-page--list container-fluid py-4">
    <header class="esh-page__header mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="esh-page__heading h4 mb-1">
                <i class="fa-solid fa-diagram-project me-2"></i>Federasyon özeti
            </h1>
            <p class="esh-page__lead small text-muted mb-0">
                Çok bölgeli SaaS hazırlığı — kurumları bölgelere gruplayın, düğüm anlık görüntüsü alın, hub eşlemesi yapın.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fa-solid fa-map me-1"></i>Bölgeler
            </a>
            <?php if ($federationReady): ?>
            <a href="<?= htmlspecialchars(esh_url('FederationBridge', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm rounded-pill">
                <i class="fa-solid fa-file-export me-1"></i>Federasyon köprüsü
            </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="alert alert-info border-0 shadow-sm mb-4 small" role="note">
        <strong>Düğüm:</strong> <code><?= htmlspecialchars(OperationalSettings::federationNodeRef(), ENT_QUOTES, 'UTF-8') ?></code>
        · Sürüm: <code><?= htmlspecialchars(function_exists('esh_app_version') ? esh_app_version() : '', ENT_QUOTES, 'UTF-8') ?></code>
        · Köprü: <?= $federationReady ? 'Açık' : 'Kapalı' ?>
        <?php if (FederationBridgeHelper::apiConfigured()): ?>
            · Hub API yapılandırıldı
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 mb-0 fw-bold">Bölgeler ve kurum dağılımı</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bölge</th>
                        <th>Kod</th>
                        <th>Kurum</th>
                        <th>Aktif hasta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($overviewRows === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4 small">Bölge tanımı yok.</td></tr>
                    <?php else: foreach ($overviewRows as $row):
                        $b = $row['bolge']; ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($b->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars((string) ($b->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= (int) ($row['kurum_count'] ?? 0) ?></td>
                        <td><?= (int) ($row['active_patients'] ?? 0) ?></td>
                        <td class="text-end">
                            <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'edit', ['id' => (int) ($b->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
