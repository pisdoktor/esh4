<?php
/**
 * Adres hasta filtresi tablo satırları (tbody içi).
 * @var list<object> $rows
 */
use App\Helpers\AuthHelper;
if (empty($rows)) { ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
<?php } else {
    foreach ($rows as $row):
        $tc = (string) ($row->tckimlik ?? '');
        $sonIzlem = !empty($row->sonizlemtarihi)
            ? \App\Helpers\DateHelper::toTrOrEmpty((string) $row->sonizlemtarihi)
            : '';
        ?>
        <tr>
            <td class="text-center" style="width:76px;">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    $tc,
                    (int) ($row->izlemsayisi ?? 0),
                    (int) ($row->yizlemsayisi ?? 0),
                    (int) ($row->totalplanli ?? 0)
                ) ?>
            </td>
            <td>
                <div class="dropdown">
                    <a class="dropdown-toggle d-inline-block text-decoration-none fw-bold"
                       href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                       style="color:<?= \App\Helpers\CinsiyetHelper::nameColor($row->cinsiyet ?? null) ?>; font-size: 0.82rem;">
                        <?= htmlspecialchars(trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php
                    $unifiedMenuIsAdmin = AuthHelper::sessionIsAdmin();
                    $eshMenuRows = \App\Helpers\BadgeHelper::patientUnifiedMenuEntries($row, $unifiedMenuIsAdmin);
                    include ROOT_PATH . '/views/site/partials/esh_dropdown_menu.php';
                    ?>
                </div>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                    <?= \App\Helpers\BadgeHelper::patientFeatures($row) ?>
                </div>
            </td>
            <td><small><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->ilceadi ?? '—'), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->mahalleadi ?? '—'), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->sokakadi ?? '—'), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?= htmlspecialchars((string) ($row->kapinoadi ?? '—'), ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><small><?php if ($sonIzlem !== ''): ?><?= htmlspecialchars($sonIzlem, ENT_QUOTES, 'UTF-8') ?><?php else: ?><span class="text-danger">Yok</span><?php endif; ?></small></td>
        </tr>
    <?php endforeach;
}
