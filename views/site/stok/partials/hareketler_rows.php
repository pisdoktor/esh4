<?php
declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\StokHelper;

/** @var list<object> $items */
$items = $items ?? [];
if ($items === []) {
    echo '<tr><td colspan="8" class="text-center text-muted py-4">Hareket bulunamadı.</td></tr>';
    return;
}
foreach ($items as $row) {
    $tip = (string) ($row->hareket_tipi ?? '');
    $tipClass = match ($tip) {
        'giris' => 'text-success',
        'cikis' => 'text-danger',
        'iade' => 'text-info',
        default => 'text-muted',
    };
    $hastaAd = trim((string) ($row->hasta_isim ?? '') . ' ' . (string) ($row->hasta_soyisim ?? ''));
    $ekipLbl = '';
    if (!empty($row->ekip_tarih)) {
        $ekipLbl = DateHelper::toTrOrEmpty($row->ekip_tarih);
        if (!empty($row->ekip_no)) {
            $ekipLbl .= ' #' . (int) $row->ekip_no;
        }
    }
    ?>
    <tr>
        <td class="text-nowrap"><?= htmlspecialchars(DateHelper::toTrOrEmpty($row->hareket_tarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="<?= $tipClass ?> fw-semibold"><?= htmlspecialchars(StokHelper::hareketTipiLabel($tip), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($row->malzeme_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($row->miktar ?? 0), ENT_QUOTES, 'UTF-8') ?>
            <span class="text-muted small"><?= htmlspecialchars(StokHelper::birimLabel((string) ($row->malzeme_birim ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td class="small"><?= $hastaAd !== '' ? htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small text-muted"><?= $ekipLbl !== '' ? htmlspecialchars($ekipLbl, ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small text-muted"><?= htmlspecialchars((string) ($row->kullanici_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small text-muted"><?= htmlspecialchars((string) ($row->aciklama ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php
}
