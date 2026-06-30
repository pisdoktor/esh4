<?php

/**

 * Kurum branş seçimi tablo satırları.

 * @var list<object> $items

 */

if (!empty($items)) {

    foreach ($items as $row):

        $id = (int) ($row->id ?? 0);

        $assigned = !empty($row->assigned);

        $kotaVal = $row->hasta_kotasi ?? null;

        $kotaInput = ($kotaVal !== null && (int) $kotaVal > 0) ? (int) $kotaVal : '';

        ?>

        <tr class="esh-brans-picker-row" data-brans-id="<?= $id ?>">

            <td class="text-center">

                <input type="checkbox"

                       class="form-check-input esh-brans-picker-check"

                       name="assigned[]"

                       value="<?= $id ?>"

                       <?= $assigned ? 'checked' : '' ?>

                       aria-label="Kurumda kullan">

            </td>

            <td><strong><?= htmlspecialchars((string) ($row->bransadi ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>

            <td>

                <input type="number"

                       class="form-control form-control-sm esh-brans-picker-kota"

                       name="hasta_kotasi[<?= $id ?>]"

                       value="<?= $kotaInput !== '' ? (int) $kotaInput : '' ?>"

                       min="0"

                       max="9999"

                       step="1"

                       placeholder="Sınırsız"

                       inputmode="numeric"

                       <?= $assigned ? '' : 'disabled' ?>

                       aria-label="Günlük hasta kotası">

            </td>

        </tr>

    <?php endforeach;

} else { ?>

    <tr>

        <td colspan="3" class="text-center py-4 text-muted">Platform kataloğunda branş bulunamadı.</td>

    </tr>

<?php } ?>

