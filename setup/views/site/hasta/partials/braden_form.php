<?php
/**
 * Braden ölçeği — yeni değerlendirme formu.
 *
 * @var int $hastaId
 * @var array $bradenFields
 */
use App\Helpers\FormHelper;

$todayYmd = date('Y-m-d');
?>
<div class="card shadow-sm mb-0 border-0 esh-barthel-form-card esh-braden-form-card">
    <div class="card-header bg-danger fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2 text-white">
        <span><i class="fa-solid fa-bed-pulse me-2"></i>Yeni Braden Değerlendirmesi</span>
        <span class="badge bg-light text-dark shadow-sm" id="braden-total-badge">Toplam Skor: 6</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <?= FormHelper::fieldDateGroup('degerlendirme_tarihi', 'Değerlendirme tarihi', $todayYmd, [
                'col' => 'col-md-4',
                'id' => 'braden-degerlendirme-tarihi',
                'fallbackToday' => true,
                'required' => true,
                'labelClass' => 'form-label small fw-bold text-muted',
            ]) ?>
            <div class="col-md-8 d-flex align-items-end">
                <div class="alert alert-warning py-2 mb-0 w-100 small mb-0">
                    <span class="fw-bold">Risk özeti:</span>
                    <span id="braden-risk-badge" class="badge bg-danger ms-1">Çok yüksek risk</span>
                    <span class="text-muted ms-2" id="braden-summary-text">6 puan</span>
                </div>
            </div>
        </div>

        <div class="row g-2" id="braden-fields-container">
            <?php foreach ($bradenFields as $key => $data): ?>
                <?php
                $eshKey = (string) $key;
                $eshId = 'braden-' . $eshKey;
                $eshLabel = (string) ($data['label'] ?? '');
                $eshMin = (int) ($data['min'] ?? 1);
                $eshMax = (int) ($data['max'] ?? 4);
                $eshPrefix = htmlspecialchars($eshLabel, ENT_QUOTES, 'UTF-8')
                    . '<i class="fa-solid fa-circle-info ms-1 text-muted opacity-50"></i>';
                ?>
                <div class="col-md-6">
                    <?= FormHelper::fieldInputGroup($eshKey, '', $eshMin, [
                        'col' => '',
                        'noLabel' => true,
                        'id' => $eshId,
                        'type' => 'number',
                        'inputGroupSm' => true,
                        'prefixHtml' => $eshPrefix,
                        'prefixIconClass' => 'w-50 small fw-bold esh-barthel-tt-trigger',
                        'prefixExtraAttrs' => [
                            'tabindex' => '0',
                            'data-esh-barthel-tt-b64' => base64_encode((string) ($data['tooltip_html'] ?? '')),
                            'aria-label' => $eshLabel . ' puan açıklaması',
                            'style' => 'cursor: help;',
                        ],
                        'class' => 'braden-input',
                        'min' => $eshMin,
                        'max' => $eshMax,
                        'required' => true,
                        'extraAttrs' => [
                            'aria-label' => $eshLabel . ' (' . $eshMin . '–' . $eshMax . ' puan)',
                            'data-braden-max' => (string) $eshMax,
                        ],
                    ]) ?>
                </div>
            <?php endforeach; ?>
            <div class="col-12 mt-2">
                <?= FormHelper::fieldTextarea('notlar', 'Notlar', '', [
                    'col' => '',
                    'rows' => 2,
                    'placeholder' => 'İsteğe bağlı klinik not...',
                ]) ?>
            </div>
        </div>
    </div>
</div>
