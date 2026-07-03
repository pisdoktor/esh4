<?php

/**

 * Platform kataloğu tablo satırları (süper yönetici CRUD).

 * @var list<object> $items

 */

if (!empty($items)) {

    foreach ($items as $row): ?>

        <tr>

            <td><?= (int) ($row->id ?? 0) ?></td>

            <td><strong><?= htmlspecialchars((string) ($row->bransadi ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>

            <td class="text-center">

                <div class="btn-group btn-group-sm">

                    <a href="<?= htmlspecialchars(esh_url('Brans', 'edit', ['id' => (int) ($row->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Düzenle">

                        <i class="fas fa-edit"></i>

                    </a>

                    <form method="post" action="<?= htmlspecialchars(esh_url('Brans', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" onsubmit="return confirm('Bu branşı silmek istediğinize emin misiniz?')">

                        <input type="hidden" name="id" value="<?= (int) ($row->id ?? 0) ?>">

                        <button type="submit" class="btn btn-outline-danger" title="Sil"><i class="fas fa-trash"></i></button>

                    </form>

                </div>

            </td>

        </tr>

    <?php endforeach;

} else { ?>

    <tr>

        <td colspan="3" class="text-center py-4 text-muted">Kayıtlı branş bulunamadı.</td>

    </tr>

<?php } ?>

