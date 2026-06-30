<?php
declare(strict_types=1);

/** @var list<object> $rows */

if (empty($rows)): ?>
    <tr>
        <td colspan="11" class="text-center py-5 text-muted border-0">
            <i class="fa-solid fa-users-slash fa-2x mb-3 d-block opacity-25"></i>
            <p class="mb-0 fw-semibold">Kayıt bulunamadı.</p>
        </td>
    </tr>
<?php else:
    foreach ($rows as $row):
        $toplamGun = 0;
        $oran = 0;
        $ti = (int) ($row->toplam_izlem ?? 0);
        try {
            if (!empty($row->kayittarihi) && $row->kayittarihi !== '0000-00-00') {
                $kayitTarihi = new \DateTimeImmutable((string) $row->kayittarihi);
            } else {
                throw new \InvalidArgumentException('kayittarihi yok');
            }
            $bugun = new \DateTimeImmutable('today');
            $toplamGun = (int) $bugun->diff($kayitTarihi)->days;
            if ($toplamGun < 1) {
                $toplamGun = 1;
            }
            $oran = $ti > 0 ? round($toplamGun / $ti, 1) : 0;
        } catch (\Throwable $e) {
            $toplamGun = 0;
            $oran = 0;
        }
        ?>
    <tr>
        <td class="text-center p-1">
            <?= \App\Helpers\UIHelper::patientSummaryButtons(
                (string) ($row->tckimlik ?? ''),
                (int) ($row->izlemsayisi ?? 0),
                (int) ($row->yizlemsayisi ?? 0),
                (int) ($row->totalplanli ?? 0)
            ) ?>
        </td>
        <td class="text-center"><?= \App\Helpers\BadgeHelper::patientStatusBadgeHtml($row) ?></td>
        <td><?= \App\Helpers\UIHelper::patientStatsCardLink($row) ?></td>
        <td>
            <code class="text-dark" style="font-size: 0.72rem;"><?= \App\Helpers\ValidationHelper::formatTc((string) ($row->tckimlik ?? '')) ?></code>
        </td>
        <td>
            <div class="d-flex flex-column" style="line-height: 1.2;">
                <span class="small fw-semibold text-dark"><?= htmlspecialchars((string) ($row->mahalle_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="x-small text-muted"><?= htmlspecialchars((string) ($row->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </td>
        <td class="x-small text-muted">
            <span class="d-block">A: <?= htmlspecialchars((string) ($row->anneAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="d-block">B: <?= htmlspecialchars((string) ($row->babaAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td>
            <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($row->dogumtarihi ?? '') ?></span><br>
            <span class="badge bg-light text-secondary border x-small"><?= \App\Helpers\DateHelper::calculateAge($row->dogumtarihi ?? '') ?> Y</span>
        </td>
        <td class="pe-3 text-end">
            <span class="small fw-bold <?= empty($row->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
                <?= !empty($row->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($row->sonizlemtarihi) : 'İzlem Yok' ?>
            </span>
        </td>
        <td class="text-center fw-bold"><?= $ti ?></td>
        <td><span class="badge bg-info text-dark"><?= (int) $toplamGun ?> gün</span></td>
        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars((string) $oran, ENT_QUOTES, 'UTF-8') ?> günde bir</span></td>
    </tr>
    <?php endforeach;
endif;
