<?php
/**
 * e-Rapor ↔ hasta uyum liste satırları.
 * @var list<object> $rows
 * @var bool $patientPrimary
 */
use App\Helpers\BadgeHelper;
use App\Helpers\DateHelper;
use App\Helpers\UIHelper;
use App\Helpers\ValidationHelper;

$colspan = $patientPrimary ? 7 : 9;
if (empty($rows)) { ?>
    <tr><td colspan="<?= $colspan ?>" class="text-center text-muted py-4">Bu kriterde kayıt yok.</td></tr>
<?php
    return;
}

foreach ($rows as $row):
    if ($patientPrimary):
        $tc = (string) ($row->tckimlik ?? '');
        $pid = (int) ($row->id ?? 0);
        $eraporAdet = (int) ($row->erapor_adet ?? 0);
        $kartErapor = (trim((string) ($row->erapor ?? '')) === '1' || (int) ($row->erapor ?? 0) === 1);
        ?>
        <tr>
            <td class="text-center small">
                <?= UIHelper::patientSummaryButtons(
                    $tc,
                    (int) ($row->izlemsayisi ?? 0),
                    (int) ($row->yizlemsayisi ?? 0),
                    (int) ($row->totalplanli ?? 0)
                ) ?>
            </td>
            <td><?= UIHelper::patientStatsCardLink($row) ?></td>
            <td><small><?= htmlspecialchars(ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= BadgeHelper::patientStatusBadgeHtml($row) ?></td>
            <td><?= $kartErapor ? '<span class="badge bg-info">Evet</span>' : '<span class="badge bg-secondary">Hayır</span>' ?></td>
            <td>
                <?php if ($eraporAdet > 0): ?>
                    <span class="badge bg-primary"><?= $eraporAdet ?> kayıt</span>
                    <?php if (!empty($row->erapor_basvuru)): ?>
                        <small class="text-muted d-block"><?= htmlspecialchars(DateHelper::toTrOrEmpty((string) $row->erapor_basvuru), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-nowrap">
                <?php if ($pid > 0): ?>
                    <a class="btn btn-sm btn-outline-primary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Kart</a>
                <?php endif; ?>
                <?php if (!empty($row->erapor_id)): ?>
                    <a class="btn btn-sm btn-outline-info py-0" href="<?= htmlspecialchars(esh_url('Erapor', 'view', ["id" => (int) $row->erapor_id]), ENT_QUOTES, "UTF-8") ?>">Havuz</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php
    else:
        $tc = (string) ($row->hastatckimlik ?? '');
        $pid = (int) ($row->hastaid ?? 0);
        $adet = (int) ($row->erapor_adet ?? 1);
        $hastaLabel = trim((string) ($row->hasta_isim ?? '') . ' ' . (string) ($row->hasta_soyisim ?? ''));
        ?>
        <tr>
            <td><small><?= htmlspecialchars(DateHelper::toTrOrEmpty((string) ($row->basvurutarihi ?? '')), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td>
                <?= htmlspecialchars(trim((string) ($row->erapor_isim ?? '') . ' ' . (string) ($row->erapor_soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                <?php if ($adet > 1): ?>
                    <span class="badge bg-warning text-dark ms-1" title="Aynı TC ile <?= $adet ?> havuz kaydı">×<?= $adet ?></span>
                <?php endif; ?>
            </td>
            <td><small><?= htmlspecialchars(ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= !empty($row->kayitlimi) ? '<span class="badge bg-success">Kayıtlı</span>' : '<span class="badge bg-secondary">Kayıtlı değil</span>' ?></td>
            <td><?= !empty($row->yenilendimi) ? '<span class="badge bg-info">Evet</span>' : '<span class="text-muted small">—</span>' ?></td>
            <td><small><?= htmlspecialchars((string) ($row->bransadi ?? $row->brans ?? '—'), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td>
                <?php if ($pid > 0): ?>
                    <small><?= htmlspecialchars($hastaLabel, ENT_QUOTES, 'UTF-8') ?></small>
                <?php else: ?>
                    <span class="text-danger small">Kart yok</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($pid > 0): ?>
                    <?= BadgeHelper::patientStatusBadgeHtml($row) ?>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-info py-0" href="<?= htmlspecialchars(esh_url('Erapor', 'view', ["id" => (int) ($row->erapor_id ?? 0)]), ENT_QUOTES, "UTF-8") ?>">Havuz</a>
                <?php if ($pid > 0): ?>
                    <a class="btn btn-sm btn-outline-primary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Kart</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php
    endif;
endforeach;
