<?php
/**
 * Barthel indeksi formu (edit ve barthel sayfası).
 *
 * @var object $patient
 * @var array $barthelFields
 * @var array $barthelscore
 * @var string $bopt
 */
?>
<div class="card shadow-sm mb-0 border-0 esh-barthel-form-card">
    <div class="card-header bg-dark fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-chart-line me-2"></i> Barthel İndeksi</span>
        <span class="badge bg-light text-dark shadow-sm" id="barthel-total-badge">Toplam Skor: <?= (int) ($barthelscore['score'] ?? 0) ?></span>
    </div>
    <div class="card-body">
        <div class="row g-2" id="barthel-fields-container">
            <?php foreach ($barthelFields as $key => $data): ?>
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text w-50 small fw-bold esh-barthel-tt-trigger"
                              tabindex="0"
                              data-esh-barthel-tt-b64="<?= base64_encode((string) ($data['tooltip_html'] ?? '')) ?>"
                              aria-label="<?= htmlspecialchars((string) $data['label'] . ' puan açıklaması', ENT_QUOTES, 'UTF-8') ?>"
                              style="cursor: help;">
                            <?= htmlspecialchars((string) $data['label'], ENT_QUOTES, 'UTF-8') ?>
                            <i class="fa-solid fa-circle-info ms-1 text-muted opacity-50"></i>
                        </span>
                        <input type="number" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" id="barthel-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="form-control barthel-input"
                               value="<?= (int) ($patient->$key ?? 0) ?>" min="0" max="<?= (int) $data['max'] ?>"
                               aria-label="<?= htmlspecialchars($data['label'] . ' (0–' . $data['max'] . ' puan)', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-12 mt-3">
                <div class="alert alert-info py-2 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small fw-bold">Bağımlılık Durumu:</span>
                    <?= $bopt ?>
                </div>
                <label class="small fw-bold">Hesaplanan skor özeti</label>
                <input type="text" id="bagimlilik-input" class="form-control"
                       value="<?= (int) ($barthelscore['score'] ?? 0) ?> Puan - <?= htmlspecialchars((string) ($barthelscore['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Skor otomatik hesaplanır..." readonly>
            </div>
        </div>
    </div>
</div>
