<?php

/**

 * Kurum istek seçimi tablo satırları.

 * @var list<object> $items

 */

if (!empty($items)) {

    foreach ($items as $row):

        $id = (int) ($row->id ?? 0);

        $assigned = !empty($row->assigned);

        ?>

        <tr>

            <td class="text-center">

                <input type="checkbox"

                       class="form-check-input"

                       name="assigned[]"

                       value="<?= $id ?>"

                       <?= $assigned ? 'checked' : '' ?>

                       aria-label="Kurumda kullan">

            </td>

            <td><?= htmlspecialchars((string) ($row->istek_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

        </tr>

    <?php endforeach;

} else { ?>

    <tr><td colspan="2" class="text-center py-4 text-muted">Platform kataloğunda kayıt bulunamadı.</td></tr>

<?php } ?>

