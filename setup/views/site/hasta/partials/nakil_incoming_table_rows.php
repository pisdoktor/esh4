<?php
declare(strict_types=1);

use App\Models\HastaNakil;

/** @var list<object> $talepler */
/** @var array<int, string> $kurumlar */

$isSuperAdmin = \App\Helpers\AuthHelper::sessionIsSuperAdmin();
$colspan = $isSuperAdmin ? 7 : 6;

if ($talepler === []): ?>
    <tr>
        <td colspan="<?= $colspan ?>" class="text-center text-muted py-5 border-0">
            <i class="fa-solid fa-inbox fa-2x text-muted mb-3 d-block opacity-50"></i>
            Bekleyen nakil talebi yok.
        </td>
    </tr>
<?php else:
    foreach ($talepler as $t):
        $tid = (int) ($t->id ?? 0);
        $ad = trim((string) (($t->hasta_isim ?? '') . ' ' . ($t->hasta_soyisim ?? '')));
        $tc = (string) ($t->hasta_tckimlik ?? '');
        $kaynakAd = (string) ($t->kaynak_kurum_ad ?? '—');
        $isGeriNakil = (string) ($t->tip ?? '') === HastaNakil::TIP_GERI_NAKIL;
        $hedefKid = (int) ($t->hedef_kurum_id ?? 0);
        $onayConfirm = $isGeriNakil
            ? 'Bu geri nakil talebini onaylıyor musunuz? Hasta önceki kurumda bekleyen kayıt olarak açılacak.'
            : 'Bu nakil talebini onaylıyor musunuz? Hasta kurumunuzda bekleyen (ön kayıt) olarak açılacak.';
        $talepTr = !empty($t->talep_tarihi)
            ? \App\Helpers\DateHelper::toTr((string) $t->talep_tarihi)
            : '—';
        ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($ad !== '' ? $ad : '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><code class="small"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></code></td>
        <td>
            <?php if ($isGeriNakil): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Geri nakil</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border">Kurum içi</span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($kaynakAd, ENT_QUOTES, 'UTF-8') ?></td>
        <?php if ($isSuperAdmin): ?>
            <td><?= htmlspecialchars($kurumlar[$hedefKid] ?? ('#' . $hedefKid), ENT_QUOTES, 'UTF-8') ?></td>
        <?php endif; ?>
        <td class="small text-muted"><?= htmlspecialchars($talepTr, ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end">
            <div class="d-flex flex-wrap gap-1 justify-content-end">
                <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'approve'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <?= esh_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $tid ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm(<?= json_encode($onayConfirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>);">
                        <i class="fa-solid fa-check me-1"></i>Onayla
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#nakilRed<?= $tid ?>">
                    <i class="fa-solid fa-xmark me-1"></i>Reddet
                </button>
            </div>
            <div class="collapse mt-2 text-start" id="nakilRed<?= $tid ?>">
                <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'reject'), ENT_QUOTES, 'UTF-8') ?>" class="border rounded p-2 bg-light">
                    <?= esh_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $tid ?>">
                    <?= \App\Helpers\FormHelper::fieldTextarea('red_nedeni', 'Red nedeni (isteğe bağlı)', '', [
                        'col' => '',
                        'labelClass' => 'form-label small mb-1',
                        'class' => 'form-control-sm mb-2',
                        'rows' => 2,
                        'maxlength' => '500',
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm">Reddet</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach;
endif;
