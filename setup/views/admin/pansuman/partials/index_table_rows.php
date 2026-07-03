<?php

/**

 * Pansuman planlama tablo satırları (tbody içi; yalnızca <tr>…</tr>).

 * @var list<object> $rows

 * @var array<int, string> $gunler

 */

if (empty($rows)): ?>

    <tr><td colspan="6" class="text-center text-muted py-5">Kayıt bulunamadı.</td></tr>

<?php else: ?>

    <?php foreach ($rows as $row):

        $hastaGunleri = !empty($row->pgunleri) ? array_map('trim', explode(',', (string) $row->pgunleri)) : [];

        ?>

        <tr>

            <td class="ps-4 esh-pansuman-td-hasta">

                <div class="fw-bold text-dark text-break"><?= htmlspecialchars(trim($row->isim . ' ' . $row->soyisim), ENT_QUOTES, 'UTF-8') ?></div>

            </td>

            <td class="font-monospace esh-pansuman-td-tc"><?= \App\Helpers\ValidationHelper::formatTc((string) $row->tckimlik) ?></td>

            <td class="small esh-pansuman-td-mahalle">

                <?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?>

                <?php if (!empty($row->ilce)): ?>

                    <br><span class="text-primary"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars((string) $row->ilce, ENT_QUOTES, 'UTF-8') ?></span>

                <?php endif; ?>

            </td>

            <td class="esh-pansuman-td-gunler">

                <div class="d-flex flex-wrap gap-1 pansuman-group" role="group">

                    <?php foreach ($gunler as $val => $label):

                        $isActive = in_array((string) $val, $hastaGunleri, true);

                        $btnId = 'btn-' . (int) $row->id . '-' . $val;

                        ?>

                        <input type="checkbox"

                               class="btn-check pansuman-check"

                               name="pgunleri[<?= (int) $row->id ?>][]"

                               value="<?= (int) $val ?>"

                               id="<?= htmlspecialchars($btnId, ENT_QUOTES, 'UTF-8') ?>"

                               <?= $isActive ? 'checked' : '' ?>

                               autocomplete="off">

                        <label class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?> border pansuman-label"

                               for="<?= htmlspecialchars($btnId, ENT_QUOTES, 'UTF-8') ?>"

                               style="min-width: 42px;"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>

                    <?php endforeach; ?>

                </div>

            </td>

            <td class="align-top esh-pansuman-td-zaman">

                <?= \App\Helpers\UIHelper::zamanDilimiRadios('pzaman[' . (int) $row->id . ']', 'pans-' . (int) $row->id, $row->pzaman ?? 0, false, true) ?>

            </td>

            <td class="text-end pe-4 esh-pansuman-td-kaydet">

                <button type="submit" name="single_id" value="<?= (int) $row->id ?>" class="btn btn-sm btn-success shadow-sm" title="Bu satırı kaydet">

                    <i class="fa-solid fa-floppy-disk"></i>

                </button>

            </td>

        </tr>

    <?php endforeach; ?>

<?php endif; ?>

