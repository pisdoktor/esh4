<?php
declare(strict_types=1);

use App\Helpers\StokHelper;

/** @var list<object> $items */
$items = $items ?? [];
if ($items === []) {
    echo '<tr><td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>';
    return;
}
foreach ($items as $row) {
    $mevcut = (float) ($row->mevcut_miktar ?? 0);
    $min = (float) ($row->min_stok ?? 0);
    $kritik = $mevcut < $min;
    ?>
    <tr<?= $kritik ? ' class="table-warning"' : '' ?>>
        <td class="text-muted small"><?= htmlspecialchars((string) ($row->kod ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="fw-semibold"><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(StokHelper::kategoriLabel((string) ($row->kategori ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(StokHelper::birimLabel((string) ($row->birim ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end fw-bold"><?= htmlspecialchars(StokHelper::formatMiktar($mevcut), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end text-muted"><?= htmlspecialchars(StokHelper::formatMiktar($min), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <?php if ($kritik): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Kritik</span>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Normal</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
