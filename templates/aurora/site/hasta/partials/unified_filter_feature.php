<?php
/**
 * Birleşik hasta listesi — admin özellik filtresi (G / N / E rozetleri).
 *
 * @var string $feature Seçili filtre anahtarı
 */
use App\Helpers\BadgeHelper;

if (empty($_SESSION['isadmin'])) {
    return;
}

$eshFeature = isset($feature) ? (string) $feature : '';
$eshFeatureChoices = BadgeHelper::patientFeatureFilterChoices();
?>
<div class="col-12 col-md-6 col-xl-3">
    <label class="form-label small text-muted mb-1" for="esh-unified-feature-filter">Özellik (rozet)</label>
    <select name="feature" id="esh-unified-feature-filter" class="form-select form-select-sm shadow-sm esh-filter-control">
        <?php foreach ($eshFeatureChoices as $k => $label) : ?>
            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"<?= ($eshFeature === (string) $k) ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
</div>
