<?php
/**
 * Klinik karar desteği — yüksek riskli izlenmeyen hastalar.
 *
 * @var int $overdueDays
 * @var int $totalCount
 * @var list<object> $rows
 * @var \App\Services\Clinical\ClinicalDecisionSupportService $cdsService
 */
use App\Helpers\DateHelper;

$overdueDays = (int) ($overdueDays ?? 30);
$totalCount = (int) ($totalCount ?? 0);
$rows = $rows ?? [];
?>
<div class="esh-page esh-page--stats container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-doctor text-danger me-2"></i>Klinik karar desteği</h5>
                    <p class="small text-muted mb-0 mt-1">
                        Son <?= $overdueDays ?> günde yapılmış izlemi olmayan ve risk skoru yüksek aktif hastalar.
                    </p>
                </div>
                <a href="<?= htmlspecialchars(esh_url('Stats', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">İstatistik hub</a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($totalCount < 1): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fa-solid fa-circle-check text-success fa-2x mb-3"></i>
                    <p class="mb-0">Şu an eşikleri aşan yüksek riskli izlenmeyen hasta bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hasta</th>
                                <th>Risk skorları</th>
                                <th>Son yapılan izlem</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row):
                                $labels = $cdsService->riskLabelsForRow($row);
                                $sonIzlem = !empty($row->son_izlem) ? DateHelper::toTr((string) $row->son_izlem) : '—';
                                $gun = isset($row->gun_since_visit) && $row->gun_since_visit !== null
                                    ? (int) $row->gun_since_visit . ' gün'
                                    : 'Hiç izlenmemiş';
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => (int) $row->id]), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-decoration-none">
                                            <?= htmlspecialchars(trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="small text-muted font-monospace"><?= htmlspecialchars((string) ($row->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <?php foreach ($labels as $lbl): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1"><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($sonIzlem, ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-danger fw-semibold"><?= htmlspecialchars($gun, ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="<?= htmlspecialchars(esh_url('Visit', 'create', ['tc' => (string) ($row->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-success rounded-pill">İzlem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalCount > count($rows)): ?>
                    <div class="p-3 border-top small text-muted">
                        İlk <?= count($rows) ?> kayıt gösteriliyor (toplam <?= $totalCount ?>).
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
