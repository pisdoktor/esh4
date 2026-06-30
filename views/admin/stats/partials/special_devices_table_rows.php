<?php
/**
 * Cihaz/ozel durum listesi tablo satirlari (tbody ici; yalnizca <tr>…</tr>).
 * @var list<object> $rows
 */
if (!empty($rows)) {
    foreach ($rows as $r): ?>
        <tr>
            <td class="text-center" style="width: 76px;">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    (string) ($r->tckimlik ?? ''),
                    (int) ($r->izlemsayisi ?? 0),
                    (int) ($r->yizlemsayisi ?? 0),
                    (int) ($r->totalplanli ?? 0)
                ) ?>
            </td>
            <td><?= \App\Helpers\UIHelper::patientStatsCardLink($r) ?></td>
            <td><code class="text-dark" style="font-size: 0.72rem;"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc((string) ($r->tckimlik ?? '')), ENT_QUOTES, 'UTF-8') ?></code></td>
            <td>
                <div class="d-flex flex-column" style="line-height: 1.2;">
                    <span class="small fw-semibold text-dark"><?= htmlspecialchars((string) ($r->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="x-small text-muted"><?= htmlspecialchars((string) ($r->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
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
            <td class="pe-3 text-end">
                <span class="small fw-bold <?= empty($r->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
                    <?= !empty($r->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($r->sonizlemtarihi) : 'Izlem Yok' ?>
                </span>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr><td colspan="9" class="text-center text-muted py-3">Kayit bulunamadi.</td></tr>
<?php } ?>
