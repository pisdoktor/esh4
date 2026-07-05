<?php
/** @var object $hasta */
/** @var list<object> $hastaIlaclar */
/** @var list<object> $ilacRaporOzet */

use App\Helpers\AppSettings;
use App\Helpers\DateHelper;
use App\Helpers\HastaIlacRaporStatusHelper;

if (!AppSettings::isModuleEnabled('hasta_ilac_rapor')) {
    return;
}

$patientId = (string) ($hasta->id ?? '');
$ilaclar = is_array($hastaIlaclar ?? null) ? $hastaIlaclar : [];
$raporOzet = is_array($ilacRaporOzet ?? null) ? $ilacRaporOzet : [];
$hasIlac = $ilaclar !== [];
$hasRapor = $raporOzet !== [];

if (!$hasIlac && !$hasRapor) {
    return;
}
?>
<div class="esh-kimlik-field mt-2">
    <div class="fw-bold text-muted small mb-1">
        <i class="fa-solid fa-pills me-1 opacity-75"></i>İlaç / tanı raporu
        <?php if ($patientId > 0): ?>
            <a href="<?= htmlspecialchars(esh_url('HastaIlacRapor', 'index', ['id' => $patientId]), ENT_QUOTES, 'UTF-8') ?>"
               class="ms-1 fw-normal">Tümünü gör</a>
        <?php endif; ?>
    </div>
    <?php if ($hasIlac): ?>
        <ul class="list-unstyled small mb-1 lh-sm">
            <?php foreach (array_slice($ilaclar, 0, 6) as $il): ?>
                <?php
                $ilAd = htmlspecialchars((string) ($il->ilac_adi ?? ''), ENT_QUOTES, 'UTF-8');
                $ilNot = trim((string) ($il->not ?? ''));
                $ilRecete = trim((string) ($il->recete_turu ?? ''));
                $ilMeta = $ilRecete !== '' ? [$ilRecete] : [];
                $tanAd = trim((string) ($il->hastalik_adi ?? ''));
                ?>
                <li>
                    <span class="fw-semibold"><?= $ilAd ?></span>
                    <?php if ($ilMeta !== []): ?>
                        <span class="d-block text-muted" style="font-size: 0.85em;"><?= htmlspecialchars(implode(' · ', $ilMeta), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if ($tanAd !== ''): ?>
                        <span class="text-muted">(<?= htmlspecialchars($tanAd, ENT_QUOTES, 'UTF-8') ?>)</span>
                    <?php endif; ?>
                    <?php if ($ilNot !== ''): ?>
                        <span class="text-muted">— <?= htmlspecialchars($ilNot, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (count($ilaclar) > 6): ?>
                <li class="text-muted"><em>+<?= (int) (count($ilaclar) - 6) ?> ilaç daha…</em></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
    <?php if ($hasRapor): ?>
        <ul class="list-unstyled small mb-0 lh-sm">
            <?php foreach ($raporOzet as $ro): ?>
                <?php
                $tanAd = htmlspecialchars((string) ($ro->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8');
                $bitisTr = !empty($ro->bitistarihi) ? DateHelper::toTrOrEmpty((string) $ro->bitistarihi) : '';
                $ry = (int) ($ro->raporyeri ?? 0);
                $ryTxt = $ry === 1 ? 'Bu kurum' : 'Dış merkez';
                $ozetStyle = HastaIlacRaporStatusHelper::ozetRowPresentation($ro);
                $liClass = trim('esh-ilac-rapor-ozet-row ' . ($ozetStyle['liClass'] ?? ''));
                $badgeClass = 'badge me-1 ' . ($ozetStyle['badge'] ?? 'bg-success-subtle text-success border border-success-subtle');
                $badgeLabel = (string) ($ozetStyle['badgeLabel'] ?? 'R');
                $isExpired = ($ozetStyle['liClass'] ?? '') === 'esh-ilac-rapor-ozet-row--expired';
                ?>
                <li class="<?= htmlspecialchars($liClass, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="<?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="fw-semibold"><?= $tanAd ?></span>
                    <?php if ($bitisTr !== ''): ?>
                        <span class="<?= $isExpired ? 'text-danger' : 'text-muted' ?>">bitiş <?= htmlspecialchars($bitisTr, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <span class="text-muted">· <?= htmlspecialchars($ryTxt, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
