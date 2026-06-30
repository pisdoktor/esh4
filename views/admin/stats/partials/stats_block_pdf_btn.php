<?php

/**

 * İstatistik rapor sayfası — kart/panel PDF ve Excel düğmeleri.

 *

 * @var string $eshStatsPdfBlock Blok kimliği (StatsReportPdfBlocks)

 * @var string|null $eshStatsPdfTitle Tooltip / aria (PDF)

 */

if (!\App\Helpers\AuthHelper::sessionIsAdmin()) {
    return;
}

$eshStatsPdfBlock = trim((string) ($eshStatsPdfBlock ?? ''));

if ($eshStatsPdfBlock === '') {

    return;

}

$eshStatsPdfTitle = trim((string) ($eshStatsPdfTitle ?? 'Bu kartı PDF olarak indir'));

$eshStatsExcelTitle = $eshStatsPdfTitle . ' — Excel';

?>

<div class="d-flex gap-1 flex-shrink-0">

    <button type="button"

            class="esh-stats-block-pdf-btn btn btn-outline-danger btn-sm"

            data-esh-block="<?= htmlspecialchars($eshStatsPdfBlock, ENT_QUOTES, 'UTF-8') ?>"

            title="<?= htmlspecialchars($eshStatsPdfTitle, ENT_QUOTES, 'UTF-8') ?>"

            aria-label="<?= htmlspecialchars($eshStatsPdfTitle, ENT_QUOTES, 'UTF-8') ?>">

        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i><span class="d-none d-md-inline">PDF</span>

    </button>

    <button type="button"

            class="esh-stats-block-excel-btn btn btn-outline-success btn-sm"

            data-esh-block="<?= htmlspecialchars($eshStatsPdfBlock, ENT_QUOTES, 'UTF-8') ?>"

            title="<?= htmlspecialchars($eshStatsExcelTitle, ENT_QUOTES, 'UTF-8') ?>"

            aria-label="<?= htmlspecialchars($eshStatsExcelTitle, ENT_QUOTES, 'UTF-8') ?>">

        <i class="fa-solid fa-file-excel me-1" aria-hidden="true"></i><span class="d-none d-md-inline">Excel</span>

    </button>

</div>

