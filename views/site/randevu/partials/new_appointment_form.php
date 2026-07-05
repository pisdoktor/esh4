<?php
use App\Helpers\FormHelper;
?>
<h3 class="h6 fw-bold border-bottom pb-2 mb-2">Yeni randevu</h3>
<form method="post" action="<?= htmlspecialchars(esh_url('Randevu', 'store'), ENT_QUOTES, 'UTF-8') ?>" id="esh-randevu-new-form" class="border rounded-3 p-3 bg-light-subtle">
    <input type="hidden" name="y" value="<?= (int) $y ?>">
    <input type="hidden" name="m" value="<?= (int) $m ?>">
    <input type="hidden" name="randevu_tarihi" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-2">
        <label class="form-label small fw-semibold">Hasta ara (TC veya ad)</label>
        <input type="text" id="esh-randevu-patient-q" class="form-control form-control-sm" placeholder="En az 2 karakter…" autocomplete="off" <?= ($prefillTc !== '' ? ' disabled' : '') ?>>
        <div id="esh-randevu-patient-results" class="list-group mt-1 shadow-sm" style="display:none; max-height: 12rem; overflow-y: auto; z-index: 5;"></div>
        <input type="hidden" name="hastatckimlik" id="esh-randevu-hastatckimlik" value="<?= htmlspecialchars($prefillTc, ENT_QUOTES, 'UTF-8') ?>" data-esh-prefill-label="<?= htmlspecialchars($prefillHastaLabel, ENT_QUOTES, 'UTF-8') ?>" required>
        <div id="esh-randevu-patient-picked" class="small text-success mt-1 min-h-20"></div>
    </div>
    <?php if (!empty($istekler)): ?>
    <div class="mb-2 esh-tomselect-field">
        <label class="form-label small fw-semibold" for="esh-randevu-istekler">İstek</label>
        <?= FormHelper::selectList(
            $istekler,
            'istekler[]',
            'multiple="multiple" required class="form-select form-select-sm" data-placeholder="İstek seçiniz…"',
            'id',
            'istek_adi',
            null,
            'esh-randevu-istekler'
        ) ?>
    </div>
    <?php else: ?>
        <div class="alert alert-warning small py-2 mb-2">İstek tanımı yok. Önce <a href="<?= htmlspecialchars(esh_url('Istek', 'index'), ENT_QUOTES, 'UTF-8') ?>">konsültasyon istekleri</a> ekleyin.</div>
    <?php endif; ?>
    <div class="mb-2 esh-randevu-brans-block">
        <div class="esh-tomselect-field">
            <?php
            $eshRandevuBransOptions = [];
            foreach ($branslar as $b) {
                $eshRandevuBransOptions[] = FormHelper::makeOption((string) (int) ($b->id ?? 0), (string) ($b->bransadi ?? ''));
            }
            echo FormHelper::fieldSelect('brans_id', 'Branş', $eshRandevuBransOptions, '', [
                'col' => '',
                'id' => 'esh-randevu-brans',
                'labelClass' => 'form-label small fw-semibold',
                'class' => 'form-select-sm esh-tomselect',
                'tomSelect' => true,
                'required' => true,
                'placeholder' => 'Branş seçiniz…',
            ]);
            ?>
        </div>
        <div id="esh-randevu-brans-kota" class="esh-randevu-kota-slot" role="status" aria-live="polite"></div>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold d-block">Zaman dilimi</label>
        <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', 'randevu-new', 1) ?>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold d-block">Hasta geldi mi?</label>
        <?= \App\Helpers\UIHelper::hastaGeldiRadios('hasta_geldi', 'randevu-new', null, true, false) ?>
    </div>
    <div class="mb-3">
        <?= FormHelper::fieldTextarea('notlar', 'Not (isteğe bağlı)', '', [
            'col' => '',
            'labelClass' => 'form-label small fw-semibold',
            'class' => 'form-control-sm esh-randevu-not',
            'rows' => 3,
            'maxlength' => '500',
            'placeholder' => 'Kısa not',
        ]) ?>
    </div>
    <button type="submit" id="esh-randevu-submit" class="btn btn-primary btn-sm w-100">
        <i class="fa-solid fa-plus me-1"></i> Randevu ekle
    </button>
</form>
<script<?= esh_csp_nonce_attr() ?> type="application/json" id="esh-randevu-kota-config"><?= json_encode([
    'date' => $selectedDate,
    'branches' => $bransKotaMap,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
