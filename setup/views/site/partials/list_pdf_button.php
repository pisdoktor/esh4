<?php

/** @var string $eshListPdfBtnId */

/** @var string|null $eshListExcelBtnId */

/** @var bool $eshListPdfBtnDisabled */

if (!\App\Helpers\AuthHelper::sessionIsAdmin()) {
    return;
}

$eshListPdfBtnId = $eshListPdfBtnId ?? 'esh-list-pdf-btn';

$eshListPdfBtnDisabled = !empty($eshListPdfBtnDisabled);

$eshListExcelBtnId = $eshListExcelBtnId ?? null;

if ($eshListExcelBtnId === null || $eshListExcelBtnId === '') {

    if (preg_match('/-pdf-btn$/', $eshListPdfBtnId)) {

        $eshListExcelBtnId = preg_replace('/-pdf-btn$/', '-excel-btn', $eshListPdfBtnId);

    } else {

        $eshListExcelBtnId = $eshListPdfBtnId . '-excel';

    }

}

$eshExportDisabledAttr = $eshListPdfBtnDisabled ? ' disabled' : '';

?>

<div class="d-flex gap-1 flex-shrink-0">

    <button type="button"

            class="btn btn-outline-danger btn-sm shadow-sm rounded-pill"

            id="<?= htmlspecialchars($eshListPdfBtnId, ENT_QUOTES, 'UTF-8') ?>"

            <?= $eshExportDisabledAttr ?>

            title="Mevcut sayfadaki kayıtları PDF olarak indirir"

            aria-label="Mevcut sayfadaki kayıtları PDF olarak indirir">

        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i>PDF (bu sayfa)

    </button>

    <button type="button"

            class="btn btn-outline-success btn-sm shadow-sm rounded-pill"

            id="<?= htmlspecialchars($eshListExcelBtnId, ENT_QUOTES, 'UTF-8') ?>"

            <?= $eshExportDisabledAttr ?>

            title="Mevcut sayfadaki kayıtları Excel olarak indirir"

            aria-label="Mevcut sayfadaki kayıtları Excel olarak indirir">

        <i class="fa-solid fa-file-excel me-1" aria-hidden="true"></i>Excel (bu sayfa)

    </button>

</div>

