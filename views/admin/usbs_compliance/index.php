<?php
declare(strict_types=1);

use App\Helpers\UsbsComplianceHelper;

/** @var array<string, mixed> $mapping */
$mapping = $mapping ?? [];
/** @var list<array{key: string, label: string, data: array<string, mixed>}> $sections */
$sections = $sections ?? [];
$columnsReady = (bool) ($columnsReady ?? false);
?>
<div class="esh-page esh-page--list esh-page-usbs container-fluid py-4">
    <?php if (!$columnsReady): ?>
        <div class="alert alert-warning" role="alert">
            USBS referans sütunları henüz kurulmamış. Yönetici olarak
            <code>database/migrate_esh_usbs_refs.sql</code> dosyasını çalıştırın.
        </div>
    <?php endif; ?>

    <header class="esh-page__header mb-4">
        <h1 class="esh-page__heading h4 mb-1">
            <i class="fa-solid fa-heart-pulse me-2" aria-hidden="true"></i>
            <?= htmlspecialchars(UsbsComplianceHelper::mappingTitle(), ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <?php if (UsbsComplianceHelper::mappingDescription() !== ''): ?>
            <p class="esh-page__lead small text-muted mb-0">
                <?= htmlspecialchars(UsbsComplianceHelper::mappingDescription(), ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if ($columnsReady): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ref eksik hasta</div>
                    <div class="fs-4 fw-bold text-danger"><?= (int) ($kpis['patients_missing'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Bildirim bekliyor</div>
                    <div class="fs-4 fw-bold text-warning"><?= (int) ($kpis['bildirim_pending'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Gönderildi</div>
                    <div class="fs-4 fw-bold text-success"><?= (int) ($kpis['bildirim_sent'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Başarısız</div>
                    <div class="fs-4 fw-bold text-danger"><?= (int) ($kpis['bildirim_failed'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Atlandı</div>
                    <div class="fs-4 fw-bold text-secondary"><?= (int) ($kpis['bildirim_skipped'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ref eksik izlem</div>
                    <div class="fs-4 fw-bold text-info"><?= (int) ($kpis['visits_missing_ref'] ?? 0) ?></div>
                    <?php if (!empty($lastSyncRow)): ?>
                    <div class="small text-muted mt-2">Son köprü: <?= htmlspecialchars((string) ($lastSyncRow->created_at ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars((string) ($lastSyncRow->direction ?? ''), ENT_QUOTES, 'UTF-8') ?>)</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="alert alert-info border-0 shadow-sm mb-4" role="note">
        <strong>Manuel köprü:</strong> Hasta ve izlem formlarındaki
        <em>e-Nabız / USBS referans no</em> alanları, resmi entegrasyon gelene kadar çift kaydı azaltmak için kullanılır.
        Eşleme tanımı <code>config/usbs-field-mapping.json</code> dosyasından okunur.
        <?php if (\App\Helpers\UsbsBridgeHelper::isReady()): ?>
            <div class="mt-2">
                <a href="<?= htmlspecialchars(esh_url('UsbsBridge', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fa-solid fa-file-export me-1"></i>USBS / e-Nabız dosya köprüsü
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php foreach ($sections as $section): ?>
        <?php
        $data = $section['data'];
        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $refCols = is_array($data['usbs_ref_columns'] ?? null) ? $data['usbs_ref_columns'] : [];
        $eshTable = (string) ($data['esh_table'] ?? '');
        $sectionNotes = trim((string) ($data['notes'] ?? ''));
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h6 mb-0 fw-bold">
                    <?= htmlspecialchars((string) $section['label'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($eshTable !== ''): ?>
                        <span class="text-muted fw-normal small">— <?= htmlspecialchars($eshTable, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($sectionNotes !== ''): ?>
                    <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars($sectionNotes, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if ($refCols !== []): ?>
                <div class="px-3 py-2 bg-light border-bottom">
                    <span class="small fw-semibold text-secondary">Referans sütunları:</span>
                    <ul class="small mb-0 mt-1">
                        <?php foreach ($refCols as $col => $desc): ?>
                            <li><code><?= htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8') ?></code>
                                — <?= htmlspecialchars((string) $desc, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($fields === []): ?>
                    <p class="text-muted small p-3 mb-0">Alan tanımı yok.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ESH alanı</th>
                                <th scope="col">USBS alanı</th>
                                <th scope="col">Açıklama</th>
                                <th scope="col">Not</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $field): ?>
                                <?php if (!is_array($field)) {
                                    continue;
                                } ?>
                                <tr>
                                    <td class="font-monospace small"><?= htmlspecialchars((string) ($field['esh'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="font-monospace small"><?= htmlspecialchars((string) ($field['usbs'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars((string) ($field['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($field['required'])): ?>
                                            <span class="badge text-bg-danger ms-1">zorunlu</span>
                                        <?php endif; ?>
                                        <?php if (!empty($field['lookup'])): ?>
                                            <br><span class="text-muted">Lookup: <?= htmlspecialchars((string) $field['lookup'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php
                                        $notes = [];
                                        if (!empty($field['format'])) {
                                            $notes[] = 'Format: ' . (string) $field['format'];
                                        }
                                        if (!empty($field['notes'])) {
                                            $notes[] = (string) $field['notes'];
                                        }
                                        echo htmlspecialchars(implode(' · ', $notes), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($sections === []): ?>
        <div class="alert alert-secondary">Eşleme dosyası okunamadı veya boş.</div>
    <?php endif; ?>
</div>
