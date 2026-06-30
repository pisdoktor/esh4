<?php
/**
 * e-Rapor hasta listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $rows
 */
/**
 * e-Rapor hasta listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $rows
 */
if ($rows === []) { ?>
    <tr>
        <td colspan="11" class="text-center py-5 text-muted border-0">
            <i class="fa-solid fa-users-slash fa-2x mb-3 d-block opacity-25"></i>
            <p class="mb-0 fw-semibold">Kayıt bulunamadı.</p>
        </td>
    </tr>
<?php } else {
    foreach ($rows as $r): ?>
        <tr>
            <td class="text-center p-1">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    (string) ($r->tckimlik ?? ''),
                    (int) ($r->izlemsayisi ?? 0),
                    (int) ($r->yizlemsayisi ?? 0),
                    (int) ($r->totalplanli ?? 0)
                ) ?>
            </td>
            <td class="text-center"><?= \App\Helpers\BadgeHelper::patientStatusBadgeHtml($r) ?></td>
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
            <td class="x-small">
                <?php if (!empty($r->ceptel1)): ?>
                    <a href="tel:<?= htmlspecialchars((string) $r->ceptel1, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark fw-bold">
                        <i class="fa-solid fa-mobile-screen text-success me-1"></i><?= htmlspecialchars((string) $r->ceptel1, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </td>
            <td class="x-small text-muted">
                <?= \App\Helpers\DateHelper::toTr($r->kayittarihi ?? '') ?>
            </td>
            <td class="x-small">
                <span class="<?= empty($r->randevutarihi) ? 'text-danger' : 'text-success' ?>">
                    <?= !empty($r->randevutarihi) ? \App\Helpers\DateHelper::toTr($r->randevutarihi) : '—' ?>
                </span>
            </td>
            <td class="pe-3 text-end">
                <span class="small fw-bold <?= empty($r->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
                    <?= !empty($r->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($r->sonizlemtarihi) : 'İzlem Yok' ?>
                </span>
            </td>
        </tr>
    <?php endforeach;
} ?>
