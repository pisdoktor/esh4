<?php

declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\ValidationHelper;

/** @var object $nakilRow */
/** @var list<object> $eshReviewKurumlar */

$tid = (string) ($nakilRow->id ?? '');
$ad = trim((string) (($nakilRow->hasta_isim ?? '') . ' ' . ($nakilRow->hasta_soyisim ?? '')));
$tc = (string) ($nakilRow->hasta_tckimlik ?? '');
$tel1 = trim((string) ($nakilRow->hasta_ceptel1 ?? ''));
$tel2 = trim((string) ($nakilRow->hasta_ceptel2 ?? ''));
$bolgeAd = trim((string) ($nakilRow->hedef_bolge_ad ?? ''));
$bolgeKod = trim((string) ($nakilRow->hedef_bolge_kod ?? ''));
$kaynakAd = trim((string) ($nakilRow->kaynak_kurum_ad ?? '—'));
$talepTr = !empty($nakilRow->talep_tarihi)
    ? \App\Helpers\DateHelper::toTr((string) $nakilRow->talep_tarihi)
    : '—';

$kurumOptions = [FormHelper::makeOption('', 'Kurum seçiniz…')];
foreach ($eshReviewKurumlar as $k) {
    $kid = (int) ($k->id ?? 0);
    if ($kid <= 0) {
        continue;
    }
    $label = trim((string) ($k->ad ?? ''));
    if (!empty($k->kod)) {
        $label = $label !== '' ? $label . ' (' . (string) $k->kod . ')' : (string) $k->kod;
    }
    $kurumOptions[] = FormHelper::makeOption((string) $kid, $label !== '' ? $label : ('Kurum #' . $kid));
}

$rejectRedirect = esh_url('PatientNakil', 'incoming');
?>
<div class="esh-page container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 mb-1 text-primary">
                <i class="fa-solid fa-map-location-dot me-2"></i>İl dışı nakil inceleme
            </h1>
            <p class="text-muted small mb-0">Hasta ile iletişime geçin ve uygun kuruma bekleyen hasta olarak yönlendirin.</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('PatientNakil', 'incoming'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Gelen talepler
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Hasta ve talep bilgisi</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Hasta</dt>
                        <dd class="col-sm-8 fw-semibold"><?= htmlspecialchars($ad !== '' ? $ad : '—', ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt class="col-sm-4 text-muted">TC Kimlik</dt>
                        <dd class="col-sm-8"><code><?= htmlspecialchars(ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></code></dd>

                        <dt class="col-sm-4 text-muted">Cep telefonu 1</dt>
                        <dd class="col-sm-8">
                            <?php if ($tel1 !== ''): ?>
                                <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $tel1) ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tel1, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Cep telefonu 2</dt>
                        <dd class="col-sm-8">
                            <?php if ($tel2 !== ''): ?>
                                <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $tel2) ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tel2, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Kaynak kurum</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($kaynakAd, ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt class="col-sm-4 text-muted">Hedef bölge</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($bolgeAd !== '' ? $bolgeAd : '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($bolgeKod !== ''): ?>
                                <span class="text-muted">(<?= htmlspecialchars($bolgeKod, ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Talep tarihi</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($talepTr, ENT_QUOTES, 'UTF-8') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold text-success">Onay — kuruma yönlendir</div>
                <div class="card-body">
                    <?php if ($eshReviewKurumlar === []): ?>
                        <p class="text-muted small mb-0">Hedef bölgede atanabilecek aktif kurum bulunamadı.</p>
                    <?php else: ?>
                        <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'approveIlDisi'), ENT_QUOTES, 'UTF-8') ?>" data-esh-confirm="Hasta seçilen kurumda bekleyen (ön kayıt) olarak açılacak. Onaylıyor musunuz?">
                            <?= esh_csrf_field() ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
                            <?= FormHelper::fieldSelect('hedef_kurum_id', 'Hedef kurum', $kurumOptions, '', [
                                'labelClass' => 'form-label small fw-semibold',
                                'class' => 'form-select form-select-sm',
                                'col' => '',
                                'required' => true,
                            ]) ?>
                            <button type="submit" class="btn btn-success btn-sm w-100 mt-2">
                                <i class="fa-solid fa-check me-1"></i>Onayla ve bekleyen kayıt aç
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold text-danger">Reddet</div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'reject'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($rejectRedirect, ENT_QUOTES, 'UTF-8') ?>">
                        <?= FormHelper::fieldTextarea('red_nedeni', 'Red nedeni (isteğe bağlı)', '', [
                            'col' => '',
                            'labelClass' => 'form-label small mb-1',
                            'class' => 'form-control-sm mb-2',
                            'rows' => 3,
                            'maxlength' => '500',
                        ]) ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" data-esh-confirm="Nakil talebi reddedilsin mi?">
                            <i class="fa-solid fa-xmark me-1"></i>Reddet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
