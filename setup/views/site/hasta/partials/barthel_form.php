<?php
/**
 * Barthel indeksi — yeni değerlendirme formu.
 *
 * @var object $hasta
 * @var array $barthelFields
 */
use App\Helpers\FormHelper;
use App\Helpers\BarthelScaleHelper;

$todayYmd = date('Y-m-d');
?>
<div class="card shadow-sm mb-0 border-0 esh-barthel-form-card">
    <div class="card-header bg-primary text-white fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-chart-line me-2"></i>Yeni Barthel Değerlendirmesi</span>
        <span class="badge bg-light text-dark shadow-sm" id="barthel-total-badge">Toplam: 0 / 100</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <?= FormHelper::fieldDateGroup('degerlendirme_tarihi', 'Değerlendirme tarihi', $todayYmd, [
                'col' => 'col-md-4',
                'id' => 'barthel-degerlendirme-tarihi',
                'fallbackToday' => true,
                'required' => true,
                'labelClass' => 'form-label small fw-bold text-muted',
            ]) ?>
            <div class="col-md-8 d-flex align-items-end">
                <div class="alert alert-info py-2 mb-0 w-100 small mb-0">
                    <span class="fw-bold">Bağımlılık düzeyi:</span>
                    <span id="barthel-status" class="fw-bold small text-success ms-1">Bağımsız</span>
                    <span class="text-muted ms-2" id="barthel-summary-text">0 puan</span>
                </div>
            </div>
        </div>

        <div class="row g-2" id="barthel-fields-container">
            <?php foreach ($barthelFields as $key => $data): ?>
                <?php
                $eshBarthelKey = (string) $key;
                $eshBarthelId = 'barthel-' . $eshBarthelKey;
                $eshBarthelLabel = (string) ($data['label'] ?? '');
                $eshBarthelMax = (int) ($data['max'] ?? 0);
                $eshBarthelPrefix = htmlspecialchars($eshBarthelLabel, ENT_QUOTES, 'UTF-8')
                    . '<i class="fa-solid fa-circle-info ms-1 text-muted opacity-50"></i>';
                ?>
                <div class="col-md-6">
                    <?= FormHelper::fieldInputGroup($eshBarthelKey, '', 0, [
                        'col' => '',
                        'noLabel' => true,
                        'id' => $eshBarthelId,
                        'type' => 'number',
                        'inputGroupSm' => true,
                        'prefixHtml' => $eshBarthelPrefix,
                        'prefixIconClass' => 'w-50 small fw-bold esh-barthel-tt-trigger',
                        'prefixExtraAttrs' => [
                            'tabindex' => '0',
                            'data-esh-barthel-tt-b64' => base64_encode((string) ($data['tooltip_html'] ?? '')),
                            'aria-label' => $eshBarthelLabel . ' puan açıklaması',
                            'style' => 'cursor: help;',
                        ],
                        'class' => 'barthel-input',
                        'min' => 0,
                        'max' => $eshBarthelMax,
                        'extraAttrs' => [
                            'aria-label' => $eshBarthelLabel . ' (0–' . $eshBarthelMax . ' puan)',
                        ],
                    ]) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <?= FormHelper::fieldTextarea('notlar', 'Notlar', '', [
                'col' => '',
                'id' => 'barthel-notlar',
                'rows' => 2,
                'placeholder' => 'İsteğe bağlı klinik not…',
                'labelClass' => 'form-label small fw-bold text-muted',
            ]) ?>
        </div>
    </div>
</div>
