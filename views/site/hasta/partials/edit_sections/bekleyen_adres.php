<?php
declare(strict_types=1);
/** @var object|null $patient */
/** @var array<string, string> $lists */
use App\Helpers\FormHelper;
use App\Models\Address;

$eshBekleyenMode = (string) ($eshBekleyenMode ?? 'bedit');
$patient = $patient ?? (object) [];
$eshIsBedit = $eshBekleyenMode === 'bedit';
$kapinoCoordsDisplay = Address::resolveCoordsForPatient($patient);
?>
<div id="address-container">
    <div class="p-3 border rounded bg-light mb-3 address-row js-address-cascade">
        <?php if ($eshIsBedit): ?>
            <h6 class="small fw-bold text-success mb-3"><i class="fa-solid fa-house-user me-2"></i>Ana Adres</h6>
            <div class="row g-2">
                <div class="col-md-6"><?= $lists['ilce'] ?? '' ?></div>
                <div class="col-md-6"><?= $lists['mahalle'] ?? '' ?></div>
                <div class="col-md-6">
                    <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                        <div class="flex-grow-1 min-w-0"><?= $lists['sokak'] ?? '' ?></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle"<?= empty($patient->mahalle) ? ' disabled' : '' ?> aria-label="Yeni sokak ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                        <div class="flex-grow-1 min-w-0"><?= $lists['kapino'] ?? '' ?></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle"<?= empty($patient->sokak) ? ' disabled' : '' ?> aria-label="Yeni kapı no ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <label class="small fw-bold text-muted">Adres Açıklaması</label>
                    <?= $lists['adres_aciklama'] ?? '' ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-2">
                <div class="col-md-6 esh-tomselect-field">
                    <label class="small fw-bold">İlçe</label>
                    <select name="adres[0][ilce]" id="ilce" class="form-select ilce-trigger esh-tomselect" data-placeholder="Seçiniz..." required>
                        <option value="">Seçiniz...</option>
                        <?php foreach ($districts ?? [] as $d): ?>
                            <option value="<?= htmlspecialchars((string) $d->id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $d->adi, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold" for="mahalle">Mahalle</label>
                    <select name="adres[0][mahalle]" id="mahalle" class="form-select mahalle-target mahalle-trigger esh-tomselect" disabled required>
                        <option value="">İlçe Seçin...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Sokak/Cadde</label>
                    <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                        <div class="flex-grow-1 min-w-0">
                            <select name="adres[0][sokak]" id="sokak" class="form-select sokak-target sokak-trigger esh-tomselect" disabled>
                                <option value="">Mahalle Seçin...</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle" disabled aria-label="Yeni sokak ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Kapı No</label>
                    <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                        <div class="flex-grow-1 min-w-0">
                            <select name="adres[0][kapino]" id="kapino" class="form-select kapino-target esh-tomselect" disabled>
                                <option value="">Sokak Seçin...</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle" disabled aria-label="Yeni kapı no ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <?= FormHelper::fieldTextarea('adres[0][adres_aciklama]', 'Adres Açıklaması', '', [
                        'col' => '',
                        'noLabel' => true,
                        'rows' => 2,
                        'placeholder' => 'Adres Açıklaması...',
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($eshIsBedit && !empty($patient->diger_adres) && is_array($patient->diger_adres)): ?>
        <?php foreach ($patient->diger_adres as $index => $ekAdres): ?>
            <?php $adrIdx = $index + 1; ?>
            <div data-ilce="<?= htmlspecialchars((string)($ekAdres['ilce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                 data-mahalle="<?= htmlspecialchars((string)($ekAdres['mahalle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                 data-sokak="<?= htmlspecialchars((string)($ekAdres['sokak'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                 data-kapino="<?= htmlspecialchars((string)($ekAdres['kapino'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                 class="p-3 border extra-address-row js-removable-address js-address-cascade rounded bg-white mb-3 position-relative shadow-sm border-start border-success border-4 animate__animated animate__fadeIn">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-addr" title="Bu ek adresi kaldır" aria-label="Bu ek adresi kaldır"></button>
                <h6 class="mb-3 text-success fw-bold small"><i class="fa-solid fa-location-dot me-2"></i>Ek Adres #<?= (int) $index + 1; ?></h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">İlçe</label>
                        <select name="adres[<?= (int) $adrIdx; ?>][ilce]" class="form-select ilce-trigger esh-tomselect">
                            <option value="<?= htmlspecialchars((string)($ekAdres['ilce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" selected>Yükleniyor...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Mahalle</label>
                        <select name="adres[<?= (int) $adrIdx; ?>][mahalle]" class="form-select mahalle-target mahalle-trigger esh-tomselect">
                            <option value="<?= htmlspecialchars((string)($ekAdres['mahalle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" selected>Yükleniyor...</option>
                        </select>
                    </div>
                    <div class="col-md-6 mt-2">
                        <label class="small fw-bold text-muted">Sokak</label>
                        <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                            <div class="flex-grow-1 min-w-0">
                                <select name="adres[<?= (int) $adrIdx; ?>][sokak]" class="form-select sokak-target sokak-trigger esh-tomselect">
                                    <option value="<?= htmlspecialchars((string)($ekAdres['sokak'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" selected>Yükleniyor...</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle"<?= empty($ekAdres['mahalle']) ? ' disabled' : '' ?> aria-label="Yeni sokak ekle">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2">
                        <label class="small fw-bold text-muted">Kapı No</label>
                        <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                            <div class="flex-grow-1 min-w-0">
                                <select name="adres[<?= (int) $adrIdx; ?>][kapino]" class="form-select kapino-target esh-tomselect">
                                    <option value="<?= htmlspecialchars((string)($ekAdres['kapino'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" selected>Yükleniyor...</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle"<?= empty($ekAdres['sokak']) ? ' disabled' : '' ?> aria-label="Yeni kapı no ekle">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <?= FormHelper::fieldTextarea('adres[' . (int) $adrIdx . '][adres_aciklama]', 'Adres Açıklaması', (string) ($ekAdres['adres_aciklama'] ?? ''), [
                            'col' => '',
                            'labelClass' => 'small fw-bold text-muted',
                            'rows' => 2,
                            'placeholder' => 'Adres Açıklaması...',
                        ]) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="mt-2 p-3 bg-white border rounded shadow-sm">
    <label class="form-label fw-bold small text-muted" for="kapino-coords-display">Kapı koordinatı</label>
    <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="fa-solid fa-location-crosshairs text-danger" aria-hidden="true"></i></span>
        <input type="text"
               id="kapino-coords-display"
               class="form-control font-monospace bg-light"
               value="<?= htmlspecialchars($kapinoCoordsDisplay, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Enlem, Boylam"
               readonly
               tabindex="-1"
               aria-readonly="true">
    </div>
</div>
