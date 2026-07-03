<?php
/**
 * Hasta kartı — Barthel indeksi özet kartı.
 *
 * @var object $hasta
 * @var object|null $barthelLatest
 * @var int $barthelCount
 */
use App\Helpers\BarthelScaleHelper;

$hastaId = (int) ($hasta->id ?? 0);
$barthelUrl = esh_url('Patient', 'barthel', ['id' => $hastaId]);
$count = (int) ($barthelCount ?? 0);
$latest = $barthelLatest ?? null;
$latestTotal = (int) ($latest->toplam_skor ?? 0);
$latestStatus = (string) ($latest->bagimlilik_duzeyi ?? '');
$statusMeta = BarthelScaleHelper::resolveDependencyLevel($latestTotal);
$tarihTr = $latest && !empty($latest->degerlendirme_tarihi)
    ? \App\Helpers\DateHelper::toTrOrEmpty((string) $latest->degerlendirme_tarihi)
    : '';
?>
<div class="card esh-barthel-summary-card shadow-sm border-0 border-top border-primary border-3 rounded mb-0">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h6 class="mb-1 fw-bold text-primary">
                    <i class="fa-solid fa-chart-line me-2"></i>Barthel İndeksi
                </h6>
                <p class="small text-muted mb-0">
                    <?php if ($count > 0): ?>
                        Son değerlendirme: <strong class="text-dark"><?= $latestTotal ?> / 100 puan</strong>
                        <?php if ($tarihTr !== ''): ?>
                            · <?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Henüz değerlendirme kaydı yok
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= htmlspecialchars($barthelUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                <i class="fa-solid fa-clipboard-list me-1"></i>Tümünü gör
            </a>
        </div>

        <?php if ($count > 0): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge <?= htmlspecialchars((string) $statusMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?> fs-6 px-3 py-2">
                    <?= htmlspecialchars($latestStatus !== '' ? $latestStatus : (string) $statusMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="small text-muted"><?= (int) $count ?> kayıtlı değerlendirme</span>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-2">
                <i class="fa-solid fa-info-circle me-1 opacity-50"></i>
                Fonksiyonel bağımsızlık için Barthel değerlendirmesi ekleyebilirsiniz.
            </p>
        <?php endif; ?>

        <?php if (empty($pasifDosyaKapali ?? false)): ?>
            <a href="<?= htmlspecialchars($barthelUrl, ENT_QUOTES, 'UTF-8') ?>#patientBarthelForm" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Yeni değerlendirme
            </a>
        <?php else: ?>
            <span class="small text-muted"><i class="fa-solid fa-lock me-1"></i>Pasif dosyada yeni kayıt kapalı.</span>
        <?php endif; ?>
    </div>
</div>
