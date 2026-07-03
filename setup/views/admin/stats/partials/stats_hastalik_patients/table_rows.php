<?php
declare(strict_types=1);

/** @var list<object> $rows */
/** @var array<int, string> $ayAdlar */

if (empty($rows)): ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Kayıt yok.</td></tr>
<?php else:
    foreach ($rows as $row):
        $tc = (string) ($row->tckimlik ?? '');
        $tcEsc = htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8');
        $ym = \App\Helpers\DateHelper::yearMonth(isset($row->kayittarihi) ? (string) $row->kayittarihi : null);
        $kayitayKey = $ym['month'] ?? 0;
        $ayAd = $kayitayKey >= 1 && $kayitayKey <= 12 ? ($ayAdlar[$kayitayKey] ?? '') : '';
        $izlemN = (int) ($row->toplamizlem ?? 0);
        $histUrl = esh_url('Visit', 'history', ['tc' => $tc]);
        ?>
    <tr>
        <td class="text-center">
            <a href="<?= htmlspecialchars($histUrl, ENT_QUOTES, 'UTF-8') ?>" class="badge <?= $izlemN > 0 ? 'text-bg-info' : 'text-bg-warning' ?> text-decoration-none" title="İzlem geçmişi"><?= $izlemN ?></a>
        </td>
        <td><?= \App\Helpers\UIHelper::patientStatsCardLink($row) ?><?= !empty($row->gecici) ? ' <sub class="text-muted">(G)</sub>' : '' ?></td>
        <td><small><?= $tcEsc ?></small></td>
        <td><small><?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="badge text-bg-success"><?= htmlspecialchars((string) ($row->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span></small></td>
        <td><small><?= (int) ($ym['year'] ?? 0) ?> <?= htmlspecialchars($ayAd, ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><small><?= htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><?= htmlspecialchars((string) \App\Helpers\DateHelper::calculateAge($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><small><?= $row->sonizlem ? htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty((string) $row->sonizlem), ENT_QUOTES, 'UTF-8') : 'Yok' ?></small></td>
    </tr>
    <?php endforeach;
endif;
