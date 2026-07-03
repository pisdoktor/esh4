<?php
declare(strict_types=1);

use App\Helpers\BadgeHelper;
use App\Helpers\DateHelper;

/** @var string $eshGuestSonucState invalid|not_found|found */
/** @var object|null $eshGuestSonucRow */

$__base = rtrim((string) SITEURL, '/') . '/public/index.php';
$__back = htmlspecialchars($__homeUrl, ENT_QUOTES, 'UTF-8');
$__login = htmlspecialchars($__base . '?controller=Auth&action=login', ENT_QUOTES, 'UTF-8');
$state = (string) ($eshGuestSonucState ?? 'invalid');

$phaMod = 'invalid';
$phaIcon = 'fa-circle-exclamation';
$phaTitle = 'Geçersiz numara';
$phaText = 'Lütfen 11 haneli, yalnızca rakamlardan oluşan bir TC kimlik numarası girin.';

if ($state === 'not_found') {
    $phaMod = 'not-found';
    $phaIcon = 'fa-user-slash';
    $phaTitle = 'Kayıt bulunamadı';
    $phaText = 'Bu TC kimlik numarasına ait hasta kaydı sistemde yer almıyor.';
} elseif ($state === 'found' && $eshGuestSonucRow) {
    $phaMod = BadgeHelper::patientPublicLookupResultCssMod($eshGuestSonucRow);
    $statusLabel = BadgeHelper::patientPublicLookupStatusLabel($eshGuestSonucRow);
    $phaIcon = 'fa-circle-check';
    $phaTitle = 'Kayıt bulundu';
    $phaText = 'Bu kimlik numarası ile eşleşen bir hasta dosyası mevcut. Kayıt durumu: ' . $statusLabel . '.';
    if ($phaMod !== 'found') {
        $phaIcon = 'fa-folder-open';
    }
}
?>
<article class="pha-card">
    <div class="pha-card__body">
        <div class="pha-result pha-result--<?= htmlspecialchars($phaMod, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="pha-result__badge" aria-hidden="true">
                <i class="fa-solid <?= htmlspecialchars($phaIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <h1 class="pha-result__title"><?= htmlspecialchars($phaTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="pha-result__text"><?= htmlspecialchars($phaText, ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if ($state === 'found' && $eshGuestSonucRow): ?>
                <?php
                $isim = htmlspecialchars((string) ($eshGuestSonucRow->isim ?? ''), ENT_QUOTES, 'UTF-8');
                $soy = htmlspecialchars((string) ($eshGuestSonucRow->soyisim ?? ''), ENT_QUOTES, 'UTF-8');
                $kayitEsc = htmlspecialchars(DateHelper::toTr($eshGuestSonucRow->kayittarihi ?? null), ENT_QUOTES, 'UTF-8');
                $statusBadge = BadgeHelper::patientStatusBadgeHtml($eshGuestSonucRow);
                $pasifTarih = trim((string) ($eshGuestSonucRow->pasiftarihi ?? ''));
                $pasifTarihEsc = $pasifTarih !== '' && $pasifTarih !== '0000-00-00'
                    ? htmlspecialchars(DateHelper::toTr($pasifTarih), ENT_QUOTES, 'UTF-8')
                    : '';
                ?>
                <p class="pha-result__name"><?= $isim ?> <?= $soy ?></p>
                <dl class="pha-dl">
                    <dt>Kayıt tarihi</dt>
                    <dd><?= $kayitEsc ?></dd>
                    <dt>Kayıt durumu</dt>
                    <dd><?= $statusBadge ?></dd>
                    <?php if ($pasifTarihEsc !== ''): ?>
                    <dt>Durum tarihi</dt>
                    <dd><?= $pasifTarihEsc ?></dd>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
        </div>

        <div class="pha-actions">
            <a href="<?= htmlspecialchars($__back, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i> Yeni sorgu
            </a>
            <a href="<?= htmlspecialchars($__login, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary pha-btn-submit border-0">
                <i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i> Personel girişi
            </a>
        </div>
    </div>
</article>
