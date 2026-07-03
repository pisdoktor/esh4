<?php
/**
 * Pasif hastalarda bekleyen planlar — tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $plans
 * @var array<string, array{text: string, class: string}> $oncelikConfig
 * @var string $deleteRetq
 */
use App\Helpers\PatientCareHelper;

if (!empty($plans)) {
    $bugun = date('Y-m-d');
    foreach ($plans as $p):
        $gecikme = !empty($p->planlanantarih) && $p->planlanantarih < $bugun;
        $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($p->zaman ?? null);
        $ok = (string) (int) ($p->oncelik ?? 1);
        $onc = $oncelikConfig[$ok] ?? ['text' => '—', 'class' => 'secondary'];
        $pasifMeta = PatientCareHelper::pasifBadgeMeta($p->pasif ?? 1);
        $tel = trim((string) ($p->ceptel1 ?? '')) !== ''
            ? trim((string) $p->ceptel1)
            : trim((string) ($p->ceptel2 ?? ''));
        $planId = (int) ($p->id ?? 0);
        $splitSide = \App\Models\PlannedVisit::passivePendingSplitSide(
            (string) ($p->planlanantarih ?? ''),
            (string) ($p->pasiftarihi ?? '')
        );
        $splitBadge = match ($splitSide) {
            'before' => ['Önce', 'warning', 'table-warning'],
            'after' => ['Sonra', 'danger', ''],
            'same' => ['Pasif günü', 'secondary', 'table-secondary'],
            default => ['—', 'secondary', ''],
        };
        $rowClass = trim(($gecikme ? 'table-danger' : '') . ' ' . ($splitBadge[2] ?? ''));
        ?>
        <tr class="<?= htmlspecialchars(trim($rowClass), ENT_QUOTES, 'UTF-8') ?>" data-passive-split="<?= htmlspecialchars($splitSide, ENT_QUOTES, 'UTF-8') ?>">
            <td class="text-center">
                <input type="checkbox" class="form-check-input passive-plan-check" name="ids[]" value="<?= $planId ?>" data-split="<?= htmlspecialchars($splitSide, ENT_QUOTES, 'UTF-8') ?>">
            </td>
            <td class="text-center p-1">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    (string) ($p->hastatckimlik ?? ''),
                    (int) ($p->izlemsayisi ?? 0),
                    (int) ($p->yizlemsayisi ?? 0),
                    (int) ($p->totalplanli ?? 0)
                ) ?>
            </td>
            <td>
                <?php
                $eshMenuRows = \App\Helpers\BadgeHelper::plannedVisitPassivePendingMenuEntries($p, $deleteRetq);
                $eshPvErkek = ($p->cinsiyet ?? '') === 'E' || ($p->cinsiyet ?? '') === '1';
                ?>
                <div class="dropdown">
                    <a class="patient-link dropdown-toggle d-inline-block text-decoration-none fw-semibold"
                       href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
                       style="color:<?= $eshPvErkek ? '#0d6efd' : '#dc3545' ?>;">
                        <?= htmlspecialchars(trim(($p->isim ?? '') . ' ' . ($p->soyisim ?? ''))) ?>
                    </a>
                    <?php if (!empty($p->gecici)): ?>
                        <span class="badge bg-warning text-dark ms-1">G</span>
                    <?php endif; ?>
                    <?php include ROOT_PATH . '/views/site/partials/esh_dropdown_menu.php'; ?>
                </div>
                <?php if ($tel !== ''): ?>
                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($tel) ?></small>
                <?php endif; ?>
            </td>
            <td class="font-monospace small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($p->hastatckimlik ?? '')) ?></td>
            <td>
                <span class="badge bg-<?= htmlspecialchars($pasifMeta['outline'], ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($pasifMeta['outline'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($pasifMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </td>
            <td class="small font-monospace">
                <?php if (!empty($p->pasiftarihi) && (string) $p->pasiftarihi !== '0000-00-00'): ?>
                    <?= date('d-m-Y', strtotime((string) $p->pasiftarihi)) ?>
                <?php else: ?>
                    <span class="text-danger">—</span>
                <?php endif; ?>
            </td>
            <td class="small">
                <?= htmlspecialchars($p->mahalle ?? '') ?><br>
                <span class="text-primary"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($p->ilce ?? '') ?></span>
            </td>
            <td class="fw-semibold"><?= !empty($p->planlanantarih) ? date('d-m-Y', strtotime((string) $p->planlanantarih)) : '—' ?></td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($splitBadge[1], ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($splitBadge[1], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($splitBadge[0], ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text']) ?></span>
            </td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($onc['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($onc['text']) ?></span>
            </td>
            <td class="small"><?= htmlspecialchars($p->yapilacaklar ?: '—') ?></td>
            <td class="small"><i class="fa-solid fa-user-nurse text-muted me-1"></i><?= htmlspecialchars($p->planlayanlar ?: '—') ?></td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="13" class="text-center text-muted py-5">Kriterlere uygun bekleyen plan bulunamadı.</td>
    </tr>
<?php } ?>
