<?php
/**
 * Hastalık kütüphanesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
if (!empty($items)) {
    foreach ($items as $row): ?>
        <tr>
            <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars((string) ($row->icd ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><strong><?= htmlspecialchars((string) ($row->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><span class="text-muted small"><?= htmlspecialchars((string) ($row->kategori_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="text-end pe-4">
                <div class="btn-group">
                    <a href="<?= htmlspecialchars(esh_url('Hastalik', 'edit', ["id" => (int) ($row->id ?? 0)]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary" title="Düzenle"><i class="fa-solid fa-pen-to-square"></i></a>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Hastalik', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" onsubmit="return confirm('Bu tanı silinsin mi?')">
                        <input type="hidden" name="id" value="<?= (int) ($row->id ?? 0) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="4" class="text-center py-5 text-muted">Kayıtlı tanı bulunamadı.</td>
    </tr>
<?php } ?>
