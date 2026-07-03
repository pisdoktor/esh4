<?php
/**
 * İTAKİ / Harizmi — yeni düşme riski değerlendirme formu.
 *
 * @var array $scaleParameters
 * @var class-string $scaleHelperClass
 * @var string $scaleFormPrefix
 * @var string|null $suggestedAgeOptionId
 */
use App\Helpers\FormHelper;

$todayYmd = date('Y-m-d');
$reasons = $scaleHelperClass::EVALUATION_REASONS;
$inputClass = $scaleFormPrefix . '-risk-input';
?>
<div class="card shadow-sm mb-0 border-0 esh-fall-risk-form-card" data-scale-prefix="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-clipboard-list me-2"></i>Yeni Değerlendirme</span>
        <span class="badge bg-light text-dark shadow-sm" id="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>-total-badge">Toplam: 0 puan</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <?= FormHelper::fieldDateGroup('degerlendirme_tarihi', 'Değerlendirme tarihi', $todayYmd, [
                'col' => 'col-md-4',
                'id' => $scaleFormPrefix . '-degerlendirme-tarihi',
                'fallbackToday' => true,
                'required' => true,
                'labelClass' => 'form-label small fw-bold text-muted',
            ]) ?>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted" for="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>-gerekce">Değerlendirme gerekçesi</label>
                <select name="degerlendirme_gerekcesi" id="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>-gerekce" class="form-select form-select-sm" required>
                    <?php foreach ($reasons as $code => $label): ?>
                        <option value="<?= (int) $code ?>"<?= $code === 1 ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="alert alert-warning py-2 mb-0 w-100 small mb-0">
                    <span class="fw-bold">Risk:</span>
                    <span id="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>-risk-badge" class="badge bg-success-subtle text-success border ms-1">Düşük risk</span>
                    <span class="text-muted ms-2" id="<?= htmlspecialchars($scaleFormPrefix, ENT_QUOTES, 'UTF-8') ?>-summary-text">0 puan</span>
                </div>
            </div>
        </div>

        <p class="small text-muted mb-3">
            <i class="fa-solid fa-circle-info me-1"></i>
            Kesme noktası: <strong>10 puan ve üzeri yüksek risk</strong>. Uygun risk faktörlerini işaretleyin.
        </p>

        <div class="row g-3">
            <?php foreach ($scaleParameters as $groupKey => $group): ?>
                <?php
                $groupType = (string) ($group['type'] ?? 'single');
                $groupLabel = (string) ($group['label'] ?? $groupKey);
                $groupIntro = (string) ($group['intro'] ?? '');
                ?>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light-subtle">
                        <div class="fw-bold small mb-1"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($groupIntro !== ''): ?>
                            <p class="small text-muted mb-2"><?= htmlspecialchars($groupIntro, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($group['options'] as $optId => $opt): ?>
                                <?php
                                $optLabel = (string) ($opt['label'] ?? $optId);
                                $optScore = (int) ($opt['score'] ?? 0);
                                $inputId = $scaleFormPrefix . '-' . $optId;
                                $isSuggested = ($suggestedAgeOptionId ?? '') === $optId;
                                $isCheckbox = ($groupType === 'multi' || $groupType === 'toggle');
                                $inputName = $isCheckbox ? 'secimler[]' : 'secimler_' . $groupKey;
                                ?>
                                <div class="form-check small">
                                    <input
                                        class="form-check-input <?= htmlspecialchars($inputClass, ENT_QUOTES, 'UTF-8') ?>"
                                        type="<?= $isCheckbox ? 'checkbox' : 'radio' ?>"
                                        name="<?= htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8') ?>"
                                        value="<?= htmlspecialchars((string) $optId, ENT_QUOTES, 'UTF-8') ?>"
                                        id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-score="<?= $optScore ?>"
                                        data-group="<?= htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') ?>"
                                        data-input-type="<?= htmlspecialchars($groupType, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isSuggested ? ' checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?>
                                        <span class="badge bg-secondary-subtle text-secondary ms-1"><?= $optScore ?> puan</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <?= FormHelper::fieldTextarea('notlar', 'Notlar', '', [
                    'col' => '',
                    'rows' => 2,
                    'placeholder' => 'İsteğe bağlı klinik not...',
                ]) ?>
            </div>
        </div>
    </div>
</div>
