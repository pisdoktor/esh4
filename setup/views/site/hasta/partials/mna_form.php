<?php
/**
 * MNA-SF — yeni değerlendirme formu.
 *
 * @var array $mnaFields
 * @var array $mnaBmiField
 * @var array $mnaCalfField
 * @var array $mnaBmiSuggest
 * @var bool $hasBmi
 * @var int $defaultBmiScore
 */
use App\Helpers\FormHelper;
use App\Helpers\MnaScaleHelper;

$todayYmd = date('Y-m-d');
$bmiValue = $mnaBmiSuggest['bmi'] ?? null;
?>
<div class="card shadow-sm mb-0 border-0 esh-mna-form-card">
    <div class="card-header bg-success text-white fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-utensils me-2"></i>Yeni MNA-SF Değerlendirmesi</span>
        <span class="badge bg-light text-dark shadow-sm" id="mna-total-badge">Toplam: 0 / 14</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <?= FormHelper::fieldDateGroup('degerlendirme_tarihi', 'Değerlendirme tarihi', $todayYmd, [
                'col' => 'col-md-4',
                'id' => 'mna-degerlendirme-tarihi',
                'fallbackToday' => true,
                'required' => true,
                'labelClass' => 'form-label small fw-bold text-muted',
            ]) ?>
            <div class="col-md-8 d-flex align-items-end">
                <div class="alert alert-warning py-2 mb-0 w-100 small mb-0">
                    <span class="fw-bold">Durum:</span>
                    <span id="mna-status-badge" class="badge bg-danger ms-1">Malnütrisyon</span>
                    <span class="text-muted ms-2" id="mna-summary-text">0 puan</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($mnaFields as $key => $field): ?>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted" for="mna-<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($field['label'] ?? $key), ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <?php if (!empty($field['intro'])): ?>
                        <p class="small text-muted mb-1"><?= htmlspecialchars((string) $field['intro'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <select name="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" id="mna-<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" class="form-select form-select-sm mna-input" required>
                        <?php foreach ($field['options'] as $score => $label): ?>
                            <option value="<?= (int) $score ?>" data-score="<?= (int) $score ?>">
                                <?= (int) $score ?> — <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <div class="col-12">
                <div class="border rounded p-3 bg-light-subtle">
                    <div class="fw-bold small mb-2">F — Antropometri (6. madde)</div>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input mna-olcum-toggle" type="radio" name="bmi_olcum_tipi" id="mna-olcum-bmi" value="<?= htmlspecialchars(MnaScaleHelper::OLcum_BMI, ENT_QUOTES, 'UTF-8') ?>"<?= $hasBmi ? ' checked' : '' ?>>
                            <label class="form-check-label small" for="mna-olcum-bmi">VKİ (BMI)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input mna-olcum-toggle" type="radio" name="bmi_olcum_tipi" id="mna-olcum-baldirc" value="<?= htmlspecialchars(MnaScaleHelper::OLcum_BALDIR, ENT_QUOTES, 'UTF-8') ?>"<?= !$hasBmi ? ' checked' : '' ?>>
                            <label class="form-check-label small" for="mna-olcum-baldirc">Baldır çevresi</label>
                        </div>
                    </div>
                    <?php if ($hasBmi): ?>
                        <p class="small text-muted mb-2">
                            Hasta kaydından önerilen VKİ: <strong><?= htmlspecialchars((string) $bmiValue, ENT_QUOTES, 'UTF-8') ?></strong>
                            (<?= htmlspecialchars((string) ($mnaBmiSuggest['kilo'] ?? ''), ENT_QUOTES, 'UTF-8') ?> kg /
                            <?= htmlspecialchars((string) ($mnaBmiSuggest['boy'] ?? ''), ENT_QUOTES, 'UTF-8') ?> cm)
                        </p>
                    <?php endif; ?>
                    <div id="mna-bmi-block" class="<?= $hasBmi ? '' : 'd-none' ?>">
                        <select name="bmi_skor" id="mna-bmi-skor" class="form-select form-select-sm mna-bmi-input">
                            <?php foreach ($mnaBmiField['options'] as $score => $label): ?>
                                <option value="<?= (int) $score ?>" data-score="<?= (int) $score ?>"<?= (int) $score === $defaultBmiScore ? ' selected' : '' ?>>
                                    <?= (int) $score ?> — <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="mna-calf-block" class="<?= $hasBmi ? 'd-none' : '' ?>">
                        <select name="bmi_skor_calf" id="mna-calf-skor" class="form-select form-select-sm mna-bmi-input">
                            <?php foreach ($mnaCalfField['options'] as $score => $label): ?>
                                <option value="<?= (int) $score ?>" data-score="<?= (int) $score ?>">
                                    <?= (int) $score ?> — <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="small text-muted mt-1 mb-0">Kayıt için baldır çevresi seçeneği kullanıldığında skor bu listeden alınır.</p>
                    </div>
                </div>
            </div>

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
