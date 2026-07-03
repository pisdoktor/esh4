<?php
declare(strict_types=1);
/** @var object $patient */
/** @var array<string, string> $lists */
use App\Helpers\FormHelper;

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
$eshAddrBtnId = $eshFormIdPrefix !== '' ? $eshFormIdPrefix . 'btn-add-address' : 'btn-add-address';
?>
<div class="d-flex justify-content-end mb-2">
    <button type="button" class="btn btn-light btn-sm fw-bold js-btn-add-address" id="<?= htmlspecialchars($eshAddrBtnId, ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa-solid fa-plus me-1"></i> Yeni Adres
    </button>
</div>
<div class="js-address-container" id="<?= $eshFormIdPrefix ?>address-container">
    <div class="p-3 border rounded bg-light mb-3 address-row js-address-cascade">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="small fw-bold text-success mb-0"><i class="fa-solid fa-house-user me-2"></i>Ana Adres</h6>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="ana_adres_index" id="<?= $eshFormIdPrefix ?>anaAddressMain" value="0" checked>
                <label class="form-check-label small fw-semibold" for="<?= $eshFormIdPrefix ?>anaAddressMain">Ana adres olarak kullan</label>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 esh-tomselect-field"><?= $lists['ilce'] ?? '' ?></div>
            <div class="col-md-6 esh-tomselect-field"><?= $lists['mahalle'] ?? '' ?></div>
            <div class="col-md-6 esh-tomselect-field">
                <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                    <div class="flex-grow-1 min-w-0 esh-tomselect-field"><?= $lists['sokak'] ?? '' ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle"<?= empty($patient->mahalle) ? ' disabled' : '' ?> aria-label="Yeni sokak ekle">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6 esh-tomselect-field">
                <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                    <div class="flex-grow-1 min-w-0 esh-tomselect-field"><?= $lists['kapino'] ?? '' ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle"<?= empty($patient->sokak) ? ' disabled' : '' ?> aria-label="Yeni kapı no ekle">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 mt-2">
                <?php if (!empty($lists['adres_aciklama'])): ?>
                    <?= $lists['adres_aciklama'] ?>
                <?php else: ?>
                    <?= FormHelper::fieldTextarea('adres[0][adres_aciklama]', 'Adres Açıklaması', (string) ($patient->adres_aciklama ?? ''), [
                        'col' => '',
                        'labelClass' => 'small fw-bold text-muted',
                        'rows' => 2,
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    if (!empty($patient->diger_adres) && is_array($patient->diger_adres)):
        foreach ($patient->diger_adres as $index => $ekAdres):
            $addrIdx = $index + 1;
            ?>
            <div data-ilce="<?= htmlspecialchars((string)($ekAdres['ilce'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 data-mahalle="<?= htmlspecialchars((string)($ekAdres['mahalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 data-sokak="<?= htmlspecialchars((string)($ekAdres['sokak'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 data-kapino="<?= htmlspecialchars((string)($ekAdres['kapino'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 class="p-3 border extra-address-row js-removable-address js-address-cascade rounded bg-white mb-3 position-relative shadow-sm border-start border-success border-4">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-addr" title="Bu ek adresi kaldır" aria-label="Bu ek adresi kaldır"></button>
                <div class="d-flex justify-content-between align-items-center mb-3 me-4">
                    <h6 class="text-success fw-bold small mb-0"><i class="fa-solid fa-location-dot me-2"></i>Ek Adres #<?= $index + 1 ?></h6>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="ana_adres_index" id="<?= $eshFormIdPrefix ?>anaAddress<?= $addrIdx ?>" value="<?= $addrIdx ?>">
                        <label class="form-check-label small" for="<?= $eshFormIdPrefix ?>anaAddress<?= $addrIdx ?>">Ana adres yap</label>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6 esh-tomselect-field">
                        <label class="small fw-bold text-muted">İlçe</label>
                        <select name="adres[<?= $addrIdx ?>][ilce]" class="form-select ilce-trigger esh-tomselect">
                            <option value="<?= htmlspecialchars((string)($ekAdres['ilce'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" selected>Yükleniyor...</option>
                        </select>
                    </div>
                    <div class="col-md-6 esh-tomselect-field">
                        <label class="small fw-bold text-muted">Mahalle</label>
                        <select name="adres[<?= $addrIdx ?>][mahalle]" class="form-select mahalle-target mahalle-trigger esh-tomselect">
                            <option value="<?= htmlspecialchars((string)($ekAdres['mahalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" selected>Yükleniyor...</option>
                        </select>
                    </div>
                    <div class="col-md-6 mt-2 esh-tomselect-field">
                        <label class="small fw-bold text-muted">Sokak</label>
                        <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                            <div class="flex-grow-1 min-w-0 esh-tomselect-field">
                                <select name="adres[<?= $addrIdx ?>][sokak]" class="form-select sokak-target sokak-trigger esh-tomselect">
                                    <option value="<?= htmlspecialchars((string)($ekAdres['sokak'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" selected>Yükleniyor...</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle"<?= empty($ekAdres['mahalle']) ? ' disabled' : '' ?> aria-label="Yeni sokak ekle">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2 esh-tomselect-field">
                        <label class="small fw-bold text-muted">Kapı No</label>
                        <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                            <div class="flex-grow-1 min-w-0 esh-tomselect-field">
                                <select name="adres[<?= $addrIdx ?>][kapino]" class="form-select kapino-target esh-tomselect">
                                    <option value="<?= htmlspecialchars((string)($ekAdres['kapino'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" selected>Yükleniyor...</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle"<?= empty($ekAdres['sokak']) ? ' disabled' : '' ?> aria-label="Yeni kapı no ekle">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <?= FormHelper::fieldTextarea('adres[' . $addrIdx . '][adres_aciklama]', 'Adres Açıklaması', (string) ($ekAdres['adres_aciklama'] ?? ''), [
                            'col' => '',
                            'labelClass' => 'small fw-bold text-muted',
                            'rows' => 2,
                            'placeholder' => 'Adres Açıklaması...',
                        ]) ?>
                    </div>
                </div>
            </div>
            <?php
        endforeach;
    endif;
    ?>
</div>
