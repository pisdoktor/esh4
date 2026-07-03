<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/admin/stats/partials/bir_izlemliler_table_rows.php
 *
 * Controller : StatsController
 * Action     : birIzlemlilerRows (JSON)
 * Rota       : Stats/birIzlemlilerRows?(JSON)
 * Canonical  : views/admin/stats/partials/bir_izlemliler_table_rows.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   (StatsController::render — extract ile kapsama alınır)
 *  $pageTitle (string)
 *  $statsIntro (string) — StatsIntroHelper::forAction
 *  $statsBreadcrumb (list) — StatsNavHelper::breadcrumbTrail
 *  $eshStatsHubCss (bool) — true
 *  + aşağıdaki rapora özel değişkenler
 *  $rows — XHR partial; birIzlemliler.php tbody
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 * Not: Doğrudan render edilmez; StatsController::birIzlemlilerRows JSON döner.
 */
/**
 * Bir izlemliler tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $rows
 */
if (empty($rows)) { ?>
    <tr><td colspan="9" class="text-center text-muted py-4">Kayıt yok.</td></tr>
<?php } else {
    foreach ($rows as $row):
        $tc = (string) ($row->tckimlik ?? '');
        $tcEsc = htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8');
        $kayitTr = \App\Helpers\DateHelper::toTrOrEmpty($row->kayittarihi ?? '');
        $kayitStr = $kayitTr !== '' ? htmlspecialchars($kayitTr, ENT_QUOTES, 'UTF-8') : '—';
        ?>
        <tr class="<?= !empty($row->pasif) && (string) $row->pasif !== '0' ? 'table-warning' : '' ?>">
            <td class="text-center p-1" style="width:76px;">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    $tc,
                    (int) ($row->izlemsayisi ?? 0),
                    (int) ($row->yizlemsayisi ?? 0),
                    (int) ($row->totalplanli ?? 0)
                ) ?>
            </td>
            <td>
                <?= \App\Helpers\UIHelper::patientStatsCardLink($row) ?>
                <span class="ms-1 align-middle"><?= \App\Helpers\BadgeHelper::patientFeatures($row) ?></span>
            </td>
            <td><small><?= $tcEsc ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="badge text-bg-success"><?= htmlspecialchars((string) ($row->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span></small></td>
            <td class="small text-muted">
                <span class="d-block">A: <?= htmlspecialchars((string) ($row->anneAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="d-block">B: <?= htmlspecialchars((string) ($row->babaAdi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><small><?= $kayitStr ?></small></td>
            <td class="small">
                <?php $dogumTr = \App\Helpers\DateHelper::toTrOrEmpty($row->dogumtarihi ?? ''); ?>
                <?php if ($dogumTr !== ''): ?>
                    <span class="d-block text-dark"><?= htmlspecialchars($dogumTr, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars((string) \App\Helpers\DateHelper::calculateAge($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?> yaş</span>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td><small><?= htmlspecialchars((string) ($row->ceptel1 ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= !empty($row->sonizlem) ? htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty((string) $row->sonizlem), ENT_QUOTES, 'UTF-8') : '—' ?></small></td>
        </tr>
    <?php endforeach;
} ?>
