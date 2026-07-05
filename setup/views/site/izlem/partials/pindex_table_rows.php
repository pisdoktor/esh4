<?php
/**
 * Planlı izlem listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $plans
 * @var array<string, array{text: string, class: string}> $oncelikConfig
 * @var string $planDeleteRetq
 */
use App\Helpers\AuthHelper;

if (!empty($plans)) {
    $bugun = date('Y-m-d');
    foreach ($plans as $p):
        $gecikme = ((int)($p->durum ?? 0) === 0 && !empty($p->planlanantarih) && $p->planlanantarih < $bugun);
        $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($p->zaman ?? null);
        $ok = (string)(int)($p->oncelik ?? 1);
        $onc = $oncelikConfig[$ok] ?? ['text' => '—', 'class' => 'secondary'];
        $tel = trim((string) ($p->ceptel1 ?? '')) !== ''
            ? trim((string) $p->ceptel1)
            : trim((string) ($p->ceptel2 ?? ''));
        ?>
        <tr class="<?= $gecikme ? 'table-danger' : '' ?>">
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
                $eshPvIsAdmin = AuthHelper::sessionIsAdmin();
                $eshMenuRows = \App\Helpers\BadgeHelper::plannedVisitIndexMenuEntries($p, $eshPvIsAdmin, $planDeleteRetq);
                $eshPvErkek = \App\Helpers\CinsiyetHelper::isErkek($p->cinsiyet ?? null);
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
            <td class="small">
                <?= htmlspecialchars($p->mahalle ?? '') ?><br>
                <span class="text-primary"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($p->ilce ?? '') ?></span>
            </td>
            <td class="fw-semibold"><?= !empty($p->planlanantarih) ? date('d-m-Y', strtotime($p->planlanantarih)) : '—' ?></td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text']) ?></span>
            </td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($onc['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($onc['text']) ?></span>
            </td>
            <td class="small"><?= htmlspecialchars($p->yapilacaklar ?: '—') ?></td>
            <td class="small"><i class="fa-solid fa-user-nurse text-muted me-1"></i><?= htmlspecialchars($p->planlayanlar ?: '—') ?></td>
            <td class="text-center">
                <?php if ((int)($p->durum ?? 0) === 1): ?>
                    <span class="badge bg-success">Yapıldı</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Bekleyen</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="10" class="text-center text-muted py-5">Kriterlere uygun planlı izlem bulunamadı.</td>
    </tr>
<?php } ?>
