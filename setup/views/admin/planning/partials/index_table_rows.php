<?php
/**
 * Mahalle planlama tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $rows
 * @var array<string, string> $gunler
 * @var int $bolge_max
 */
$gunSirasi = ['1', '2', '3', '4', '5', '6', '0'];

if (empty($rows)): ?>
    <tr><td colspan="4" class="text-center text-muted py-5">Mahalle kaydı bulunamadı.</td></tr>
<?php else: ?>
    <?php foreach ($rows as $row):
        $secili = !empty($row->gun) ? array_map('trim', explode(',', (string) $row->gun)) : [];
        ?>
        <tr>
            <td class="ps-4">
                <div class="small text-muted"><?= htmlspecialchars((string) ($row->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="fw-bold"><?= htmlspecialchars((string) ($row->adi ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td>
                <select name="bolge[<?= htmlspecialchars((string) $row->id, ENT_QUOTES, 'UTF-8') ?>]" class="form-select form-select-sm shadow-sm">
                    <?php for ($i = 0; $i <= $bolge_max; $i++): ?>
                        <option value="<?= $i ?>" <?= ((int) ($row->bolge ?? 0) === $i) ? 'selected' : '' ?>>
                            <?= $i === 0 ? '— Atanmadı —' : ($i . '. bölge') ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($gunSirasi as $val):
                        $label = $gunler[$val] ?? $val;
                        $isChecked = in_array((string) $val, array_map('strval', $secili), true);
                        $uid = 'g-' . substr(md5((string) $row->id), 0, 12) . '-' . $val;
                        ?>
                        <input type="checkbox" class="btn-check" name="gun[<?= htmlspecialchars((string) $row->id, ENT_QUOTES, 'UTF-8') ?>][]" value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                               id="<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') ?>" <?= $isChecked ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-sm <?= $isChecked ? 'btn-primary' : 'btn-outline-secondary' ?> border"
                               for="<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endforeach; ?>
                </div>
            </td>
            <td class="text-end pe-4">
                <button type="submit" name="single_id" value="<?= htmlspecialchars((string) $row->id, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-success shadow-sm" title="Bu satırı kaydet">
                    <i class="fa-solid fa-floppy-disk"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
