<?php
/**
 * Hasta kartı — Harizmi II özet kartı.
 *
 * @var object $hasta
 * @var object|null $harizmiLatest
 * @var int $harizmiCount
 */
use App\Helpers\HarizmiScaleHelper;

$hastaId = (string) ($hasta->id ?? '');
$harizmiUrl = esh_url('Patient', 'harizmi', ['id' => $hastaId]);
$count = (int) ($harizmiCount ?? 0);
$latest = $harizmiLatest ?? null;
$latestTotal = (int) ($latest->toplam_skor ?? 0);
$latestRisk = (string) ($latest->risk_duzeyi ?? '');
$riskMeta = HarizmiScaleHelper::resolveRisk($latestTotal);
$tarihTr = $latest && !empty($latest->degerlendirme_tarihi)
    ? \App\Helpers\DateHelper::toTrOrEmpty((string) $latest->degerlendirme_tarihi)
    : '';
?>
<div class="card esh-harizmi-summary-card shadow-sm border-0 border-top border-info border-3 rounded mb-0">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h6 class="mb-1 fw-bold text-info">
                    <i class="fa-solid fa-child-reaching me-2"></i>Harizmi II
                </h6>
                <p class="small text-muted mb-0">
                    <?php if ($count > 0): ?>
                        Son değerlendirme: <strong class="text-dark"><?= $latestTotal ?> puan</strong>
                        <?php if ($tarihTr !== ''): ?>
                            · <?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Henüz değerlendirme kaydı yok
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= htmlspecialchars($harizmiUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-info btn-sm text-white fw-semibold shadow-sm">
                <i class="fa-solid fa-clipboard-list me-1"></i>Tümünü gör
            </a>
        </div>

        <?php if ($count > 0): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge <?= htmlspecialchars((string) $riskMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?> fs-6 px-3 py-2">
                    <?= htmlspecialchars($latestRisk !== '' ? $latestRisk : (string) $riskMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="small text-muted"><?= (int) $count ?> kayıtlı değerlendirme</span>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-2">
                <i class="fa-solid fa-info-circle me-1 opacity-50"></i>
                Çocuk hasta düşme riski için Harizmi değerlendirmesi ekleyebilirsiniz.
            </p>
        <?php endif; ?>

        <?php if (empty($pasifDosyaKapali ?? false)): ?>
            <a href="<?= htmlspecialchars($harizmiUrl, ENT_QUOTES, 'UTF-8') ?>#patientHarizmiForm" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Yeni değerlendirme
            </a>
        <?php else: ?>
            <span class="small text-muted"><i class="fa-solid fa-lock me-1"></i>Pasif dosyada yeni kayıt kapalı.</span>
        <?php endif; ?>
    </div>
</div>
