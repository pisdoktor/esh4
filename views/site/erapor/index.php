<?php
use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;

$activeOrderBy = (string) ($orderby ?? 'basvurutarihi');
$activeOrderDir = strtoupper((string) ($orderdir ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
$ordering = $activeOrderBy . ' ' . $activeOrderDir;
$eshEraporSortCfg = [
    'mode' => 'merge',
    'mergeBase' => ['controller' => 'Erapor', 'action' => 'index'],
];
$eraporNedenOzet = static function (?string $neden, int $maxLen = 50): array {
    $plain = trim(preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n"], ' ', (string) $neden)));
    if ($plain === '') {
        return ['text' => '', 'title' => '', 'empty' => true];
    }
    $len = mb_strlen($plain, 'UTF-8');
    if ($len <= $maxLen) {
        return ['text' => $plain, 'title' => '', 'empty' => false];
    }

    return [
        'text' => mb_substr($plain, 0, $maxLen, 'UTF-8') . '…',
        'title' => $plain,
        'empty' => false,
    ];
};

ob_start();
$eshListPdfBtnId = 'esh-erapor-index-pdf-btn';
$eshListPdfBtnDisabled = true;
include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
?>
<a href="<?= htmlspecialchars(esh_url('Erapor', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary shadow-sm">
    <i class="fas fa-plus me-1"></i>Yeni Rapor Verisi Ekle
</a>
<?php
$eraporToolbarHtml = ob_get_clean();
?>
<?php
PageShellHelper::pageOpen([
    'kind' => 'list',
    'module' => 'erapor',
    'id' => 'esh-erapor-index-root',
    'attrs' => [
        'data-esh-pdf-url' => (string) ($indexPdfDataUrl ?? ''),
    ],
]);
PageShellHelper::pageHeader(
    'e-Rapor Havuzu',
    'Sisteme girilen rapor verilerinin dökümü; branş ve sayfa ile süzebilirsiniz.',
    $eraporToolbarHtml,
    ['icon' => 'fas fa-chart-pie']
);
?>

<section class="esh-page__panel esh-page__panel--filter mb-4" aria-labelledby="esh-erapor-filter-heading">
    <div class="esh-page__panel-head">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0 esh-page__panel-icon" style="width:42px;height:42px;">
                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <span id="esh-erapor-filter-heading" class="esh-page__panel-title d-block">Liste filtreleri</span>
                <span class="esh-page__panel-subtitle d-block">Kriterleri seçip «Filtrele» ile uygulayın.</span>
            </div>
        </div>
        <button id="erapor-filter-toggle" class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#erapor-filter-collapse" aria-expanded="false" aria-controls="erapor-filter-collapse">
            <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text">Filtreleri Göster</span>
        </button>
    </div>
    <div id="erapor-filter-collapse" class="collapse">
        <div class="esh-page__panel-body">
            <form method="get" action="<?= htmlspecialchars(esh_form_action('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 g-xl-4 align-items-end esh-page__filter-form esh-erapor-filter">
                <?= esh_form_route_hiddens('Erapor', 'index') ?>
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="limit" value="<?= (int) $limit ?>">

                <?php
                $eshEraporBransOptions = [FormHelper::makeOption('0', 'Tüm branşlar')];
                foreach ($branslar as $b) {
                    $eshEraporBransOptions[] = FormHelper::makeOption((string) (int) $b->id, (string) $b->bransadi);
                }
                echo FormHelper::fieldInput('search', 'Arama', (string) ($search ?? ''), [
                    'col' => 'col-12 col-sm-6 col-xl-4',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'labelHtml' => 'Arama <span class="fw-normal text-muted">(TC, ad soyad, branş)</span>',
                    'class' => 'form-control-sm shadow-sm esh-ui-filter-control',
                    'placeholder' => 'İsteğe bağlı...',
                ]);
                echo FormHelper::fieldSelect('brans', 'Branş', $eshEraporBransOptions, (string) (int) ($bransFilter ?? 0), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                    'id' => 'erapor_brans',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-ui-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldSelect('kayitlimi', 'Durum', [
                    FormHelper::makeOption('', 'Tümü'),
                    FormHelper::makeOption('1', 'Sistemde Kayıtlı'),
                    FormHelper::makeOption('0', 'Yeni Veri'),
                ], (string) ($durumFilter ?? ''), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-ui-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldSelect('yenilendimi', 'Yenilendi mi', [
                    FormHelper::makeOption('', 'Tümü'),
                    FormHelper::makeOption('1', 'Evet'),
                    FormHelper::makeOption('0', 'Hayır'),
                ], (string) ($yenilendiFilter ?? ''), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-ui-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldDateRangeFilter('date_from', 'date_to', $dateFromTr ?? '', $dateToTr ?? '', [
                    'col' => 'col-12',
                    'fromId' => 'erapor-filter-date-from',
                    'toId' => 'erapor-filter-date-to',
                    'fromRawValue' => (string) ($dateFromTr ?? ''),
                    'toRawValue' => (string) ($dateToTr ?? ''),
                    'class' => 'form-control-sm border-start-0 datepicker esh-ui-filter-control',
                ]);
                ?>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-ui-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                    <a href="<?= htmlspecialchars(esh_url('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-ui-filter-control">Sıfırla</a>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="card border-0 esh-list-table-card">
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow: visible !important;">
            <table class="table table-hover align-middle mb-0" id="eraporTable">
                <thead class="bg-light">
                    <tr>
                        <?php include ROOT_PATH . '/views/site/erapor/partials/index_sort_head_cells.php'; ?>
                    </tr>
                </thead>
                <tbody id="esh-erapor-list-tbody"
                       data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       data-esh-pdf-url="<?= htmlspecialchars($indexPdfDataUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <tr class="esh-erapor-list-loading-row">
                        <td colspan="7" class="border-0 py-5 text-center text-muted">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                <span>e-Rapor listesi yükleniyor…</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white border-top-0 py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="small text-muted">
                    <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $pagelink) ?>
                </div>
            </div>
            <div>
                <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $pagelink) ?>
            </div>
        </div>
    </div>
</div>

<?php
PageShellHelper::pageClose();
include ROOT_PATH . '/views/site/partials/list_pdf_assets.php';
