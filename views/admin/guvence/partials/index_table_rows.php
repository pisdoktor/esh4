<?php
/**
 * Sağlık güvencesi tanımları tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
if (!empty($items)) {
    foreach ($items as $row): ?>
        <tr>
            <td><?= (int) $row->id ?></td>
            <td><span class="fw-bold"><?= htmlspecialchars((string) ($row->guvenceadi ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <a href="<?= htmlspecialchars(esh_url('Guvence', 'edit', ["id" => (int) $row->id]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary" title="Düzenle">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Guvence', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" onsubmit="return confirm('Bu güvence tanımını silmek istediğinize emin misiniz?')">
                        <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Sil"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="3" class="text-center py-4 text-muted">Kayıtlı güvence bulunamadı.</td>
    </tr>
<?php } ?>
