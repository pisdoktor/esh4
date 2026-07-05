<?php
/**
 * Alan doluluk placeholder hasta listesi tablo satırları.
 * @var list<object> $rows
 */
use App\Models\Stats;

$ayAdlar = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];

$renderParentCell = static function (mixed $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '<span class="text-muted">—</span>';
    }
    $esc = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    if (Stats::isParentNamePlaceholderValue($raw)) {
        return '<code class="text-warning-emphasis">' . $esc . '</code>';
    }

    return $esc;
};

if (empty($rows)) { ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Bu kriterde aktif hasta yok.</td></tr>
<?php } else {
    foreach ($rows as $row):
        $tc = (string) ($row->tckimlik ?? '');
        $tcEsc = htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8');
        $ym = \App\Helpers\DateHelper::yearMonth(isset($row->kayittarihi) ? (string) $row->kayittarihi : null);
        $kayitayKey = $ym['month'] ?? 0;
        $ayAd = $kayitayKey >= 1 && $kayitayKey <= 12 ? $ayAdlar[$kayitayKey] : '';
        $pid = (string) ($row->id ?? '');
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
            <td><small><?= $renderParentCell($row->anneAdi ?? '') ?></small></td>
            <td><small><?= $renderParentCell($row->babaAdi ?? '') ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="badge text-bg-success"><?= htmlspecialchars((string) ($row->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span></small></td>
            <td><small><?= (int) ($ym['year'] ?? 0) ?> <?= htmlspecialchars($ayAd, ENT_QUOTES, 'UTF-8') ?></small></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Görüntüle</a>
                <a class="btn btn-sm btn-outline-secondary py-0" href="<?= htmlspecialchars(esh_url('Patient', 'edit', ["id" => $pid]), ENT_QUOTES, "UTF-8") ?>">Düzenle</a>
            </td>
        </tr>
    <?php endforeach;
}
