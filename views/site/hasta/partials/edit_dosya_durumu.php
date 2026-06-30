<?php

declare(strict_types=1);



use App\Helpers\AuthHelper;
use App\Helpers\BadgeHelper;
use App\Helpers\FormHelper;
use App\Helpers\PatientCareHelper;

use App\Helpers\PatientKurumTransfer;

use App\Helpers\PatientNakilRequest;

use App\Models\Kurum;



/** @var object $patient */

$pnPasifPid = (int) ($patient->id ?? 0);

$pnSel = PatientCareHelper::normalizePasifNedeniForEdit($patient->pasifnedeni ?? '');

$pasifInt = is_numeric($patient->pasif ?? null) ? (int) $patient->pasif : 0;

$eshIsAdmin = !empty($eshEditFullAccess) || AuthHelper::sessionIsAdmin();

$eshPasifOptions = PatientCareHelper::dosyaDurumuPasifOptions($eshIsAdmin, $pasifInt);

$eshPasifDetailsVisible = $pasifInt === 1;

$eshPasifLocked = empty($eshEditFullAccess) && !$eshIsAdmin && !PatientCareHelper::canNormalUserChangePasifOnEdit($pasifInt);

$eshInPartialModal = !empty($eshInPartialModal);

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');

$eshPasifDetailsId = $eshFormIdPrefix !== '' ? $eshFormIdPrefix . 'pasifDetailsArea' : 'pasifDetailsArea';

$eshNakilHedefAreaId = $eshFormIdPrefix !== '' ? $eshFormIdPrefix . 'eshNakilHedefArea' : 'eshNakilHedefArea';

$eshNakilHedefVisible = $pnSel === PatientKurumTransfer::PASIF_NEDENI_NAKIL && $eshIsAdmin;

$eshNakilAllowed = $pasifInt === 0;

$eshGeriNakilKurumId = PatientNakilRequest::getReturnTargetKurumId($patient);

$eshNakilKurumlar = [];

$eshNakilHedefSelected = '';

if ($eshIsAdmin && Kurum::tableExists()) {

    $eshPatientKurumId = (int) ($patient->kurum_id ?? 0);

    foreach ((new Kurum())->getList(true) as $k) {

        $kid = (int) ($k->id ?? 0);

        if ($kid > 0 && $kid !== $eshPatientKurumId) {

            $eshNakilKurumlar[] = $k;

        }

    }

    $pendingNakil = PatientNakilRequest::findPendingForPatient($pnPasifPid);

    if ($pendingNakil !== null) {

        if ((string) ($pendingNakil->tip ?? '') === \App\Models\HastaNakil::TIP_IL_DISI) {

            $eshNakilHedefSelected = PatientNakilRequest::NAKIL_HEDEF_IL_DISI;

        } elseif (!empty($pendingNakil->hedef_kurum_id)) {

            $eshNakilHedefSelected = (string) (int) $pendingNakil->hedef_kurum_id;

        }

    }

}



$eshWrapOpen = $eshInPartialModal

    ? '<div class="patient-partial-edit-section patient-partial-edit-section--dosya-durumu">'

    : '                <div class="card shadow-sm mb-4 border-danger border-start border-4">';

$eshWrapClose = $eshInPartialModal ? '</div>' : '                </div>';

echo $eshWrapOpen;

if (!$eshInPartialModal): ?>

                    <div class="card-header bg-white text-danger fw-bold small border-0">Dosya Durumu</div>

                    <div class="card-body py-2">

<?php endif; ?>

                        <div class="row g-2">

                            <div class="col-12">

                                <label class="form-label small fw-bold text-muted d-block mb-1">Kayıt durumu</label>

                                <?php if ($eshPasifLocked): ?>

                                    <input type="hidden" name="pasif" value="<?= $pasifInt ?>">

                                    <p class="mb-0 small text-muted">

                                        Mevcut durum:

                                        <?= BadgeHelper::patientStatusBadgeHtml($patient) ?>

                                        <span class="d-block mt-1">Bu durumu yalnızca yönetici değiştirebilir.</span>

                                    </p>

                                <?php else: ?>

                                <?php if (!$eshIsAdmin && $pasifInt === -3): ?>

                                    <p class="small text-muted mb-2">Kayıt şu an <strong>bekleyen</strong>. Dosyayı <strong>aktif</strong> veya <strong>pasif</strong> yapmak için seçin.</p>

                                <?php endif; ?>

                                <?php
                                $eshPasifRadioOptions = [];
                                foreach ($eshPasifOptions as $pasifVal => $pasifMeta) {
                                    $eshPasifRadioOptions[] = [
                                        'value' => (string) $pasifVal,
                                        'id' => 'pasif-' . $pnPasifPid . '-' . $pasifVal,
                                        'label' => $pasifMeta['label'],
                                        'btnClass' => 'btn btn-outline-' . $pasifMeta['outline'] . ' btn-sm',
                                        'checked' => $pasifInt === (int) $pasifVal,
                                    ];
                                }
                                echo FormHelper::btnCheckRadioGroup('pasif', $eshPasifRadioOptions, [
                                    'groupClass' => 'btn-group flex-wrap',
                                    'ariaLabel' => 'Kayıt durumu',
                                    'autocomplete' => 'off',
                                ]);
                                ?>

                                <?php endif; ?>

                            </div>

                            <div class="col-12">

                                <div id="<?= htmlspecialchars($eshPasifDetailsId, ENT_QUOTES, 'UTF-8') ?>" class="mt-2 pt-2 border-top border-light-subtle" style="<?= $eshPasifDetailsVisible ? '' : 'display: none;' ?>">

                                    <div class="row g-3">

                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">

                                        <?= FormHelper::fieldDate('pasiftarihi', 'Pasif tarihi', $patient->pasiftarihi ?? '', [
                                            'id' => 'pasiftarihi' . $pnPasifPid,
                                            'labelClass' => 'form-label small text-muted mb-1',
                                            'labelHtml' => 'Pasif tarihi <span class="text-danger esh-pasif-tarihi-required-mark"' . ($eshPasifDetailsVisible ? '' : ' style="display:none;"') . ' aria-hidden="true">*</span>',
                                            'class' => 'form-control-sm esh-pasif-tarihi-input',
                                            'required' => $eshPasifDetailsVisible,
                                            'invalidFeedback' => 'Pasif tarihi zorunludur.',
                                            'invalidFeedbackClass' => 'invalid-feedback small',
                                        ]) ?>

                                        </div>

                                        <div class="col-12">

                                            <span class="form-label small text-muted mb-2 d-block">Pasif nedeni</span>

                                            <div class="border rounded bg-white px-2 py-1">

                                                <?php

                                                $__pnDefs = PatientCareHelper::pasifDosyaNedeniDefinitionsForEdit($eshIsAdmin);

                                                if (!$eshNakilAllowed) {
                                                    unset($__pnDefs[PatientKurumTransfer::PASIF_NEDENI_NAKIL]);
                                                }

                                                $__pnLast = array_key_last($__pnDefs);

                                                foreach ($__pnDefs as $pnK => $pnMeta):

                                                    $pnId = 'pasifnedeni-' . $pnPasifPid . '-' . $pnK;

                                                    ?>

                                                    <div class="form-check py-1 mb-0<?= $pnK !== $__pnLast ? ' border-bottom border-light' : '' ?>">

                                                        <input class="form-check-input esh-pasif-nedeni-radio" type="radio" name="pasifnedeni" id="<?= htmlspecialchars($pnId, ENT_QUOTES, 'UTF-8') ?>"

                                                               value="<?= (int) $pnK ?>"<?= $pnSel === (int) $pnK ? ' checked' : '' ?> autocomplete="off"

                                                               data-esh-nakil-nedeni="<?= (int) $pnK === PatientKurumTransfer::PASIF_NEDENI_NAKIL ? '1' : '0' ?>">

                                                        <label class="form-check-label small" for="<?= htmlspecialchars($pnId, ENT_QUOTES, 'UTF-8') ?>">

                                                            <?= htmlspecialchars($pnMeta['label'], ENT_QUOTES, 'UTF-8') ?>

                                                        </label>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                        <?php if ($eshIsAdmin && $eshNakilAllowed && $eshNakilKurumlar !== []): ?>

                                        <span class="d-none" data-esh-nakil-eligible="1" aria-hidden="true"></span>

                                        <div class="col-12" id="<?= htmlspecialchars($eshNakilHedefAreaId, ENT_QUOTES, 'UTF-8') ?>" style="<?= $eshNakilHedefVisible ? '' : 'display: none;' ?>">

                                            <span class="form-label small text-muted mb-2 d-block">Nakil hedefi</span>

                                            <div class="border rounded bg-white px-2 py-1">

                                                <div class="form-check py-1 mb-0 border-bottom border-light">

                                                    <input class="form-check-input" type="radio" name="nakil_hedef" id="<?= htmlspecialchars($eshFormIdPrefix . 'nakil-il-disi-' . $pnPasifPid, ENT_QUOTES, 'UTF-8') ?>"

                                                           value="<?= htmlspecialchars(PatientNakilRequest::NAKIL_HEDEF_IL_DISI, ENT_QUOTES, 'UTF-8') ?>"

                                                        <?= $eshNakilHedefSelected === PatientNakilRequest::NAKIL_HEDEF_IL_DISI ? ' checked' : '' ?>>

                                                    <label class="form-check-label small fw-semibold" for="<?= htmlspecialchars($eshFormIdPrefix . 'nakil-il-disi-' . $pnPasifPid, ENT_QUOTES, 'UTF-8') ?>">

                                                        İl dışı nakil

                                                    </label>

                                                </div>

                                                <?php foreach ($eshNakilKurumlar as $nk):

                                                    $nkId = (int) ($nk->id ?? 0);

                                                    $nkRadioId = $eshFormIdPrefix . 'nakil-kurum-' . $pnPasifPid . '-' . $nkId;

                                                    ?>

                                                <div class="form-check py-1 mb-0<?= $nk !== $eshNakilKurumlar[array_key_last($eshNakilKurumlar)] ? ' border-bottom border-light' : '' ?>">

                                                    <input class="form-check-input" type="radio" name="nakil_hedef" id="<?= htmlspecialchars($nkRadioId, ENT_QUOTES, 'UTF-8') ?>"

                                                           value="<?= $nkId ?>"

                                                        <?= $eshNakilHedefSelected === (string) $nkId ? ' checked' : '' ?>>

                                                    <label class="form-check-label small" for="<?= htmlspecialchars($nkRadioId, ENT_QUOTES, 'UTF-8') ?>">

                                                        <?= htmlspecialchars((string) ($nk->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>

                                                        <?php if (!empty($nk->kod)): ?>

                                                            <span class="text-muted">(<?= htmlspecialchars((string) $nk->kod, ENT_QUOTES, 'UTF-8') ?>)</span>

                                                        <?php endif; ?>

                                                        <?php if ($eshGeriNakilKurumId !== null && $nkId === $eshGeriNakilKurumId): ?>

                                                            <span class="text-primary fw-semibold">(önceki kurum — geri nakil)</span>

                                                        <?php endif; ?>

                                                    </label>

                                                </div>

                                                <?php endforeach; ?>

                                            </div>

                                            <div class="form-text"><?= $eshGeriNakilKurumId !== null
                                                ? 'Önceki kurum seçilirse geri nakil talebi oluşur; onay sonrası hasta önceki kurumda bekleyen kayıt olarak açılır.'
                                                : 'Kurum seçilirse hedef kurum onayı sonrası hasta kurumunuzda bekleyen (ön kayıt) olarak açılır. İzlem geçmişi kaynak kurumda kalır.' ?></div>

                                        </div>

                                        <?php endif; ?>

                                        <?php if ($eshIsAdmin):
                                            $nakilStatus = PatientNakilRequest::nakilViewSummaryForPatient($patient);
                                            if ($nakilStatus !== null): ?>
                                                <div class="col-12">
                                                <div class="alert alert-info border-0 small py-2 mt-2 mb-0">
                                                    <?= htmlspecialchars($nakilStatus, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php if ($pendingNakil !== null
                                                        && in_array((string) ($pendingNakil->tip ?? ''), [\App\Models\HastaNakil::TIP_KURUM_ICI, \App\Models\HastaNakil::TIP_GERI_NAKIL], true)
                                                        && \App\Helpers\PatientNakilRequest::canCancelNakil((int) ($pendingNakil->id ?? 0), (int) ($_SESSION['user_id'] ?? 0))): ?>
                                                        <form method="post" action="<?= htmlspecialchars(esh_url('PatientNakil', 'cancel'), ENT_QUOTES, 'UTF-8') ?>" class="mt-2 mb-0">
                                                            <?= esh_csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= (int) ($pendingNakil->id ?? 0) ?>">
                                                            <input type="hidden" name="redirect" value="<?= htmlspecialchars(esh_url('Patient', 'edit', ['id' => $pnPasifPid]), ENT_QUOTES, 'UTF-8') ?>">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Bekleyen nakil talebi iptal edilsin mi?');">Talebi iptal et</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                </div>
                                            <?php endif;
                                        endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

<?php if (!$eshInPartialModal): ?>

                    </div>

<?php endif;

echo $eshWrapClose;

