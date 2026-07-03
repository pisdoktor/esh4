<?php
/**
 * Hasta bakım sürekliliği tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var array $rows
 * @var string $activeGroup
 * @var int $workloadColspan
 */
/**
 * Hasta bakım sürekliliği tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var array $rows
 * @var string $activeGroup
 * @var int $workloadColspan
 */
$workloadColspan = (int) ($workloadColspan ?? 9);
$rowClass = $activeGroup === 'KRITIK' ? 'table-danger' : ($activeGroup === 'KRONIK' ? 'table-warning' : '');

if (empty($rows)) { ?>
    <tr>
        <td colspan="<?= $workloadColspan ?>" class="text-center py-5 text-muted border-0">
            <p class="mb-0 fw-semibold">Bu grupta hasta yok.</p>
        </td>
    </tr>
<?php } else { ?>
    <?php foreach ($rows as $r): ?>
        <tr class="<?= htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') ?>">
            <td class="text-center p-1">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    (string) ($r->tckimlik ?? ''),
                    (int) ($r->izlemsayisi ?? 0),
                    (int) ($r->yizlemsayisi ?? 0),
                    (int) ($r->totalplanli ?? 0)
                ) ?>
            </td>
            <td><?= \App\Helpers\UIHelper::patientStatsCardLink($r) ?></td>
            <td>
                <code class="text-dark" style="font-size: 0.72rem;"><?= \App\Helpers\ValidationHelper::formatTc((string) ($r->tckimlik ?? '')) ?></code>
            </td>
            <td>
                <div class="d-flex flex-column" style="line-height: 1.2;">
                    <span class="small fw-semibold text-dark"><?= htmlspecialchars((string) ($r->mahalle_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="x-small text-muted"><?= htmlspecialchars((string) ($r->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </td>
            <td class="x-small text-muted">
                <span class="d-block">A: <?= htmlspecialchars((string) ($r->anneAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="d-block">B: <?= htmlspecialchars((string) ($r->babaAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td>
                <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($r->dogumtarihi ?? '') ?></span><br>
                <span class="badge bg-light text-secondary border x-small"><?= \App\Helpers\DateHelper::calculateAge($r->dogumtarihi ?? '') ?> Y</span>
            </td>
            <td class="x-small text-muted">
                <?= \App\Helpers\DateHelper::toTr($r->kayittarihi ?? ($r->kayit_tarihi ?? '')) ?>
            </td>
            <td class="pe-3 text-end">
                <span class="small fw-bold <?= empty($r->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
                    <?= !empty($r->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($r->sonizlemtarihi) : 'İzlem Yok' ?>
                </span>
            </td>
            <td>
                <span class="badge bg-secondary"><?= (int) ($r->hizmet_suresi_gun ?? 0) ?></span>
            </td>
        </tr>
    <?php endforeach; ?>
<?php } ?>

