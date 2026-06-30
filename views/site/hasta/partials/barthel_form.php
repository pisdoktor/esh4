<?php
/**
 * Barthel indeksi formu (edit ve barthel sayfası).
 *
 * @var object $patient
 * @var array $barthelFields
 * @var array $barthelscore
 * @var string $bopt
 */
use App\Helpers\FormHelper;
?>
<div class="card shadow-sm mb-0 border-0 esh-barthel-form-card">
    <div class="card-header bg-dark fw-bold py-3 small d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-chart-line me-2"></i> Barthel İndeksi</span>
        <span class="badge bg-light text-dark shadow-sm" id="barthel-total-badge">Toplam Skor: <?= (int) ($barthelscore['score'] ?? 0) ?></span>
    </div>
    <div class="card-body">
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
                    <?= FormHelper::fieldInputGroup($eshBarthelKey, '', (int) ($patient->$key ?? 0), [
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
            <div class="col-12 mt-3">
                <div class="alert alert-info py-2 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small fw-bold">Bağımlılık Durumu:</span>
                    <?= $bopt ?>
                </div>
                <?= FormHelper::fieldInput('bagimlilik-input', 'Hesaplanan skor özeti', (int) ($barthelscore['score'] ?? 0) . ' Puan - ' . (string) ($barthelscore['status'] ?? ''), [
                    'col' => '',
                    'id' => 'bagimlilik-input',
                    'omitName' => true,
                    'labelClass' => 'small fw-bold',
                    'placeholder' => 'Skor otomatik hesaplanır...',
                    'extraAttrs' => ['readonly' => 'readonly'],
                ]) ?>
            </div>
        </div>
    </div>
</div>
