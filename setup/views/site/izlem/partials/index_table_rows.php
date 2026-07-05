<?php
/**
 * Aktif izlem listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $visits
 */
use App\Helpers\AuthHelper;

if (!empty($visits)) {
    foreach ($visits as $row):
        $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($row->zaman ?? null);
        ?>
        <tr>
            <td class="text-center p-1">
                <?= \App\Helpers\UIHelper::patientSummaryButtons(
                    (string) ($row->hastatckimlik ?? ''),
                    (int) ($row->izlemsayisi ?? 0),
                    (int) ($row->yizlemsayisi ?? 0),
                    (int) ($row->totalplanli ?? 0)
                ) ?>
            </td>
            <td>
                <?php
                $eshViIsAdmin = AuthHelper::sessionIsAdmin();
                $eshMenuRows = \App\Helpers\BadgeHelper::visitIndexMenuEntries($row, $eshViIsAdmin);
                $eshViErkek = \App\Helpers\CinsiyetHelper::isErkek($row->cinsiyet ?? null);
                ?>
                <div class="dropdown">
                    <a class="patient-link dropdown-toggle d-inline-block text-decoration-none fw-semibold"
                       href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
                       style="color:<?= $eshViErkek ? '#0d6efd' : '#dc3545' ?>;">
                        <?= htmlspecialchars(trim(($row->isim ?? '') . ' ' . ($row->soyisim ?? ''))) ?>
                    </a>
                    <?php if (!empty($row->gecici)): ?>
                        <span class="badge bg-warning text-dark ms-1">G</span>
                    <?php endif; ?>
                    <?php include ROOT_PATH . '/views/site/partials/esh_dropdown_menu.php'; ?>
                </div>
            </td>
            <td class="font-monospace small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($row->hastatckimlik ?? '')) ?></td>
            <td class="small">
                <?= htmlspecialchars($row->mahalle ?? '') ?><br>
                <span class="text-primary"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($row->ilce ?? '') ?></span>
            </td>
            <td><?= !empty($row->izlemtarihi) ? date('d-m-Y', strtotime($row->izlemtarihi)) : '—' ?></td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text']) ?></span>
            </td>
            <td class="small font-monospace"><?= !empty($row->aracplaka) ? htmlspecialchars((string) $row->aracplaka, ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="small"><?= \App\Helpers\VisitIslemHelper::yapilanlarIndexCellHtml(
                (string) ($row->yapilanlar ?? ''),
                (string) ($row->yapilan ?? ''),
                (string) ($row->kons_brans_istek ?? ''),
                (string) ($row->brans ?? ''),
                (string) ($row->kons_istekler ?? '')
            ) ?></td>
            <td class="small"><i class="fa-solid fa-user-nurse text-muted me-1"></i><?= htmlspecialchars($row->yapanlar ?: '—') ?></td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="9" class="text-center text-muted py-5">Kriterlere uygun izlem bulunamadı.</td>
    </tr>
<?php } ?>
