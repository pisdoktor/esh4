<?php
declare(strict_types=1);
/** @var object $patient */
use App\Helpers\FormHelper;
use App\Helpers\PatientCareHelper;
use App\Helpers\UIHelper;
use App\Helpers\ZamanDilimiHelper;

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
$mPid = (string) ($patient->id ?? '');
$mamaCesitSel = PatientCareHelper::normalizeMamaCesit($patient->mamacesit ?? 0);
$mamaRaporYeriSel = PatientCareHelper::normalizeMamaRaporYeri($patient->mamaraporyeri ?? 0);
$bPid = (string) ($patient->id ?? '');
$bezRaporSel = !empty($patient->bezrapor) ? 1 : 0;
$pPid = (string) ($patient->id ?? '');
$pgSecili = PatientCareHelper::parsePgunleriToInts($patient->pgunleri ?? '');
$pgSeciliMap = array_fill_keys($pgSecili, true);
$pzSel = ZamanDilimiHelper::SABAH;
if (isset($patient->pzaman) && $patient->pzaman !== '' && is_numeric($patient->pzaman)) {
    $pzSel = ZamanDilimiHelper::normalize($patient->pzaman);
}
?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="esh-edit-care-supply-section rounded-3 px-3 py-3 h-100">
            <?= FormHelper::btnCheckYesNo('mama', $patient->mama ?? 0, $eshFormIdPrefix . 'mama', [
                'label' => 'Mama Kullanımı',
                'groupMb' => 'mb-3',
            ]) ?>
            <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>mamaDetailsArea" style="<?= !$patient->mama ? 'display: none;' : '' ?>">
                <label class="form-label x-small text-muted mb-0" for="<?= htmlspecialchars($eshFormIdPrefix . 'mamacesitSel' . $mPid, ENT_QUOTES, 'UTF-8') ?>">Mama çeşidi</label>
                <select name="mamacesit" id="<?= htmlspecialchars($eshFormIdPrefix . 'mamacesitSel' . $mPid, ENT_QUOTES, 'UTF-8') ?>" class="form-select form-select-sm mb-2">
                    <?php foreach (PatientCareHelper::mamaCesitOptions() as $mcVal => $mcLabel): ?>
                        <option value="<?= (int) $mcVal ?>"<?= $mamaCesitSel === (int) $mcVal ? ' selected' : '' ?>><?= htmlspecialchars($mcLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <?= FormHelper::fieldDate('mamaraporbitis', 'Mama raporu bitiş', $patient->mamaraporbitis ?? '', [
                    'labelClass' => 'form-label x-small text-muted d-block mb-1',
                    'class' => 'form-control-sm mb-2',
                ]) ?>
                <?= FormHelper::btnCheckRadioGroup('mamaraporyeri', [
                    [
                        'value' => '0',
                        'id' => $eshFormIdPrefix . 'mamaRyDis' . $mPid,
                        'label' => 'Dış kurum',
                        'btnClass' => 'btn btn-outline-secondary btn-sm',
                        'checked' => $mamaRaporYeriSel === 0,
                    ],
                    [
                        'value' => '1',
                        'id' => $eshFormIdPrefix . 'mamaRyKendi' . $mPid,
                        'label' => 'Kendi kurumu',
                        'btnClass' => 'btn btn-outline-primary btn-sm',
                        'checked' => $mamaRaporYeriSel === 1,
                    ],
                ], [
                    'label' => 'Mama raporu yeri',
                    'labelClass' => 'form-label x-small text-muted d-block mb-1',
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="esh-edit-care-supply-section rounded-3 px-3 py-3 h-100">
            <?= FormHelper::btnCheckYesNo('bez', $patient->bez ?? 0, $eshFormIdPrefix . 'bez', [
                'label' => 'Bez Kullanımı',
                'groupMb' => 'mb-3',
            ]) ?>
            <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>bezDetailsArea" style="<?= !$patient->bez ? 'display: none;' : '' ?>">
                <?= FormHelper::btnCheckYesNo('bezrapor', $bezRaporSel, $eshFormIdPrefix . 'bezrapor', [
                    'label' => 'Bez raporu',
                    'labelClass' => 'form-label small fw-bold text-muted d-block mb-1',
                    'groupMb' => 'mb-2',
                    'noSuffix' => 'Hayir' . $bPid,
                    'yesSuffix' => 'Evet' . $bPid,
                ]) ?>
                <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>bezRaporBitisArea" style="<?= (!$patient->bez || $bezRaporSel !== 1) ? 'display: none;' : '' ?>">
                    <?= FormHelper::fieldDate('bezraporbitis', 'Bez raporu bitiş', $patient->bezraporbitis ?? '', [
                        'labelClass' => 'form-label x-small text-muted d-block mb-1',
                        'class' => 'form-control-sm',
                        'id' => $eshFormIdPrefix . 'bezraporbitis' . $bPid,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12"><hr class="my-2"></div>
    <div class="col-md-6 border-start border-light">
        <?= FormHelper::btnCheckYesNo('pansuman', $patient->pansuman ?? 0, $eshFormIdPrefix . 'pansuman', [
            'label' => 'Pansuman',
            'groupMb' => 'mb-2',
        ]) ?>
        <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>pansumanDetailsArea" style="<?= !$patient->pansuman ? 'display: none;' : '' ?>">
            <label class="form-label x-small text-muted mb-1 d-block">Günler</label>
            <div class="btn-group btn-group-sm w-100 flex-wrap mb-2" role="group" aria-label="Pansuman günleri">
                <?php foreach (PatientCareHelper::pansumanGunOptions() as $gVal => $gLabel):
                    $gid = $eshFormIdPrefix . 'pgun-' . $pPid . '-' . (int) $gVal;
                    $checked = !empty($pgSeciliMap[(int) $gVal]);
                    ?>
                    <input type="checkbox" class="btn-check pansuman-edit-check" name="pgunleri[]" value="<?= (int) $gVal ?>"
                           id="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $checked ? 'checked' : '' ?>
                           data-esh-call="toggleBtnColor" data-esh-call-self="1" autocomplete="off">
                    <label class="btn <?= $checked ? 'btn-primary' : 'btn-outline-secondary' ?> pansuman-edit-gun-label mb-1"
                           for="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($gLabel, ENT_QUOTES, 'UTF-8') ?></label>
                <?php endforeach; ?>
            </div>
            <label class="form-label x-small text-muted mb-1">Pansuman zaman dilimi</label>
            <?= UIHelper::zamanDilimiRadios('pzaman', $eshFormIdPrefix . 'hasta-edit-pz-' . $pPid, $pzSel, false, true) ?>
        </div>
    </div>
    <div class="col-md-6">
        <?= FormHelper::btnCheckYesNo('yatak', $patient->yatak ?? 0, $eshFormIdPrefix . 'yatak', [
            'label' => 'Hasta Yatağı',
            'groupMb' => 'mb-2',
        ]) ?>
    </div>
</div>
