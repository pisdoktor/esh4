<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\StokHelper;

/** @var list<object> $items */
$items = $items ?? [];
if ($items === []) {
    echo '<tr><td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>';
    return;
}
foreach ($items as $row) {
    $id = (int) ($row->id ?? 0);
    $mevcut = (float) ($row->mevcut_miktar ?? 0);
    ?>
    <tr>
        <td class="text-muted small"><?= htmlspecialchars((string) ($row->kod ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="fw-semibold"><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(StokHelper::kategoriLabel((string) ($row->kategori ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(StokHelper::birimLabel((string) ($row->birim ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end"><?= htmlspecialchars(StokHelper::formatMiktar($mevcut), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end text-muted"><?= htmlspecialchars(StokHelper::formatMiktar((float) ($row->min_stok ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <?php if (!empty($row->aktif)): ?>
                <span class="badge bg-success-subtle text-success">Aktif</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary">Pasif</span>
            <?php endif; ?>
        </td>
        <td class="text-end text-nowrap">
            <a href="<?= htmlspecialchars(esh_url('Stok', 'malzemeEdit', ['id' => $id]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                <i class="fas fa-pen"></i>
            </a>
            <?php if ($mevcut <= 0): ?>
            <form action="<?= htmlspecialchars(esh_url('Stok', 'malzemeDelete'), ENT_QUOTES, 'UTF-8') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bu malzeme kaydını silmek istediğinize emin misiniz?');">
                <?= esh_csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
