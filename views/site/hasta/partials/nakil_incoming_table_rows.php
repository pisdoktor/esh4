<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Models\HastaNakil;

/** @var list<object> $talepler */
/** @var array<int, string> $kurumlar */

$colspan = 8;

if ($talepler === []): ?>
    <tr>
        <td colspan="<?= $colspan ?>" class="text-center text-muted py-5 border-0">
            <i class="fa-solid fa-inbox fa-2x text-muted mb-3 d-block opacity-50"></i>
            Bekleyen nakil talebi yok.
        </td>
    </tr>
<?php else:
    foreach ($talepler as $t):
        $tid = (string) ($t->id ?? '');
        $ad = trim((string) (($t->hasta_isim ?? '') . ' ' . ($t->hasta_soyisim ?? '')));
        $tc = (string) ($t->hasta_tckimlik ?? '');
        $kaynakAd = (string) ($t->kaynak_kurum_ad ?? '—');
        $tip = (string) ($t->tip ?? '');
        $isGeriNakil = $tip === HastaNakil::TIP_GERI_NAKIL;
        $isIlDisi = $tip === HastaNakil::TIP_IL_DISI;
        $hedefKid = (int) ($t->hedef_kurum_id ?? 0);
        $hedefLabel = $isIlDisi
            ? trim((string) ($t->hedef_bolge_ad ?? ''))
            : ($kurumlar[$hedefKid] ?? ('#' . $hedefKid));
        if ($hedefLabel === '' && $isIlDisi) {
            $hedefLabel = '—';
        }
        $tel1 = trim((string) ($t->hasta_ceptel1 ?? ''));
        $tel2 = trim((string) ($t->hasta_ceptel2 ?? ''));
        $telDisplay = $tel1 !== '' ? $tel1 : ($tel2 !== '' ? $tel2 : '—');
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
            <?php if ($isIlDisi): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">İl dışı / bölge</span>
            <?php elseif ($isGeriNakil): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Geri nakil</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border">Kurum içi</span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($kaynakAd, ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($hedefLabel !== '' ? $hedefLabel : '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small">
            <?php if ($telDisplay !== '—'): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $telDisplay) ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none"><?= htmlspecialchars($telDisplay, ENT_QUOTES, 'UTF-8') ?></a>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>
        <td class="small text-muted"><?= htmlspecialchars($talepTr, ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end">
            <?php if ($isIlDisi && AuthHelper::sessionIsSuperAdmin()): ?>
                <a href="<?= htmlspecialchars(esh_url('PatientNakil', 'review', ['id' => $tid]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>İncele
                </a>
            <?php else: ?>
            <div class="d-flex flex-wrap gap-1 justify-content-end">
                <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'approve'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <?= esh_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-success btn-sm" data-esh-confirm="<?= htmlspecialchars($onayConfirm, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-check me-1"></i>Onayla
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#nakilRed-<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-xmark me-1"></i>Reddet
                </button>
            </div>
            <div class="collapse mt-2 text-start" id="nakilRed-<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
                <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'reject'), ENT_QUOTES, 'UTF-8') ?>" class="border rounded p-2 bg-light">
                    <?= esh_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
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
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach;
endif;
