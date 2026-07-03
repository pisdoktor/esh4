<?php
/**
 * Veri sağlığı hasta listesi tablo satırları (tbody içi; yalnızca <tr>...</tr>).
 * @var list<object> $rows
 */
/**
 * Veri sağlığı hasta listesi tablo satırları (tbody içi; yalnızca <tr>...</tr>).
 * @var list<object> $rows
 */
$ayAdlar = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
if (empty($rows)) { ?>
    <tr><td colspan="9" class="text-center text-muted py-4">Bu kriterde aktif hasta yok.</td></tr>
<?php } else {
    foreach ($rows as $row):
        $tc = (string) ($row->tckimlik ?? '');
        $tcEsc = htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8');
        $ym = \App\Helpers\DateHelper::yearMonth(isset($row->kayittarihi) ? (string) $row->kayittarihi : null);
        $kayitayKey = $ym['month'] ?? 0;
        $ayAd = $kayitayKey >= 1 && $kayitayKey <= 12 ? $ayAdlar[$kayitayKey] : '';
        $pid = (int) ($row->id ?? 0);
        ?>
        <tr>
            <td class="text-center small">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    $tc,
                    (int) ($row->izlemsayisi ?? 0),
                    (int) ($row->yizlemsayisi ?? 0),
                    (int) ($row->totalplanli ?? 0)
                ) ?>
            </td>
            <td><?= \App\Helpers\UIHelper::patientStatsCardLink($row) ?><?= !empty($row->gecici) ? ' <sub class="text-muted">(G)</sub>' : '' ?></td>
            <td><small><?= $tcEsc ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="badge text-bg-success"><?= htmlspecialchars((string) ($row->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span></small></td>
            <td><small><?= (int) ($ym['year'] ?? 0) ?> <?= htmlspecialchars($ayAd, ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars((string) \App\Helpers\DateHelper::calculateAge($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><small><?= $row->sonizlem ? htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty((string) $row->sonizlem), ENT_QUOTES, 'UTF-8') : 'Yok' ?></small></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Görüntüle</a>
                <a class="btn btn-sm btn-outline-secondary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'edit', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Düzenle</a>
            </td>
        </tr>
    <?php endforeach;
}
