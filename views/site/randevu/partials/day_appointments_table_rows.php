<?php
declare(strict_types=1);

use App\Helpers\IdHelper;
use App\Helpers\ValidationHelper;
use App\Models\KonsRandevu;

/** @var list<object> $dayRows */
/** @var int $y */
/** @var int $m */
/** @var string $selectedDate */
/** @var string $prefillTc */

if (empty($dayRows)): ?>
    <tr><td colspan="6" class="text-center text-muted py-4 small">Kayıt yok.</td></tr>
<?php else:
    foreach ($dayRows as $row):
        $rawHg = $row->hasta_geldi ?? null;
        $hgSel = ($rawHg === null || $rawHg === '') ? null : (int) $rawHg;
        $hastaAdFull = trim((string) ($row->hasta_isim ?? '') . ' ' . (string) ($row->hasta_soyisim ?? ''));
        $hastaIdRow = IdHelper::normalizeRequestId($row->hasta_id ?? null) ?? '';
        ?>
    <tr>
        <td class="small">
            <div class="fw-semibold">
                <?php if ($hastaIdRow !== ''): ?>
                    <a class="link-primary text-decoration-none" href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $hastaIdRow]), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($hastaAdFull, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <?= htmlspecialchars($hastaAdFull, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div class="text-muted"><?= htmlspecialchars(ValidationHelper::formatTc((string) ($row->hastatckimlik ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
            <?php if (!empty($row->notlar)): ?>
                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars((string) $row->notlar, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </td>
        <td class="small"><?= htmlspecialchars((string) ($row->kons_istekler_adlari ?? '') !== '' ? (string) $row->kons_istekler_adlari : '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small"><?= htmlspecialchars((string) ($row->bransadi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small"><?= htmlspecialchars(KonsRandevu::zamanLabel((int) ($row->zaman ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small p-1">
            <form method="post" action="<?= htmlspecialchars(esh_url('Randevu', 'updateGeldi'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="y" value="<?= (int) $y ?>">
                <input type="hidden" name="m" value="<?= (int) $m ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($prefillTc !== '' && preg_match('/^\d{11}$/', $prefillTc)): ?>
                    <input type="hidden" name="tc" value="<?= htmlspecialchars($prefillTc, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <label class="visually-hidden" for="esh-randevu-hg-<?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?>">Katılım</label>
                <select name="hasta_geldi" id="esh-randevu-hg-<?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-select form-select-sm" title="<?= htmlspecialchars(KonsRandevu::hastaGeldiLabel($rawHg), ENT_QUOTES, 'UTF-8') ?>" data-esh-auto-submit>
                    <option value=""<?= $hgSel === null ? ' selected' : '' ?>>—</option>
                    <option value="1"<?= $hgSel === 1 ? ' selected' : '' ?>>Geldi</option>
                    <option value="0"<?= $hgSel === 0 ? ' selected' : '' ?>>Gelmedi</option>
                </select>
            </form>
        </td>
        <td class="text-end">
            <form method="post" action="<?= htmlspecialchars(esh_url('Randevu', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-esh-confirm="Bu randevuyu silmek istiyor musunuz?">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="y" value="<?= (int) $y ?>">
                <input type="hidden" name="m" value="<?= (int) $m ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($prefillTc !== '' && preg_match('/^\d{11}$/', $prefillTc)): ?>
                    <input type="hidden" name="tc" value="<?= htmlspecialchars($prefillTc, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Sil">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach;
endif;
