<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
use App\Helpers\FormHelper;

/** @var string $izlemFilterQuery */
/** @var array $izlemSortBase */
/** @var string $izlemPagelink */
$ordering = $ordering ?? 'h.isim-ASC';
$izlemSortOrdering = \App\Helpers\UIHelper::normalizeOrdering((string) $ordering);
$izlemSortBase = $izlemSortBase ?? [];
$eshIzlemSortCfg = ['mode' => 'ordering', 'base' => $izlemSortBase];
?>
<div class="container-fluid py-4 izlem-list-page" id="esh-visit-index-root"
     data-esh-pdf-url="<?= htmlspecialchars($visitIndexPdfDataUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-notes-medical text-success me-2"></i>Aktif izlem listesi
            </h4>
            <p class="text-muted small mb-0">Evde yapılan izlem kayıtlarını tarih ve işleme göre listeler; yapılan veya yapılmayan kayıtları filtreleyebilirsiniz.</p>
        </div>
        <div class="flex-shrink-0">
            <?php
            $eshListPdfBtnId = 'esh-visit-index-pdf-btn';
            $eshListPdfBtnDisabled = false;
            include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
            ?>
        </div>
    </div>

    <div class="card border-0 esh-list-filter-card mb-4">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                <i class="fa-solid fa-sliders"></i>
            </span>
            <div class="min-w-0">
                <span class="fw-semibold text-dark d-block">Filtreler</span>
            </div>
            </div>
            <button id="visit-filter-toggle" class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#visit-filter-collapse" aria-expanded="false" aria-controls="visit-filter-collapse">
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text">Filtreleri Göster</span>
            </button>
        </div>
        <div id="visit-filter-collapse" class="collapse">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form id="form-visit-index-filter" action="<?= htmlspecialchars(esh_form_action('Visit', 'index'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="row g-3 g-xl-4 align-items-end esh-izlem-filter">
                <?= esh_form_route_hiddens('Visit', 'index') ?>
                <input type="hidden" name="ordering" value="<?= htmlspecialchars($ordering, ENT_QUOTES, 'UTF-8') ?>">

                <?= FormHelper::fieldDateRangeFilter('date_from', 'date_to', $dateFrom ?? date('Y-m-d'), $dateTo ?? date('Y-m-d'), [
                    'col' => 'col-12',
                    'label' => 'Tarih aralığı',
                    'fromId' => 'visit-filter-date-from',
                    'toId' => 'visit-filter-date-to',
                    'prefixIconClass' => 'bg-white text-success border-end-0',
                    'fromRawValue' => \App\Helpers\DateHelper::toTrOrEmpty($dateFrom ?? date('Y-m-d')),
                    'toRawValue' => \App\Helpers\DateHelper::toTrOrEmpty($dateTo ?? date('Y-m-d')),
                    'class' => 'form-control-sm border-start-0 esh-filter-control',
                ]) ?>

                <?php
                $eshIzlemIslemOptions = [FormHelper::makeOption('0', 'Tüm işlemler')];
                foreach ($islemler ?? [] as $is) {
                    $eshIzlemIslemOptions[] = FormHelper::makeOption((string) (int) $is->id, (string) $is->islemadi);
                }
                echo FormHelper::fieldSelect('secim', 'İşlem', $eshIzlemIslemOptions, (string) (int) ($secim ?? 0), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldSelect('yap', 'Kayıt türü', [
                    FormHelper::makeOption('1', 'Yapılan'),
                    FormHelper::makeOption('0', 'Yapılmayan'),
                ], (string) ($yap ?? '1'), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                $eshIzlemLimitOptions = [];
                foreach ([20, 50, 100, 200] as $L) {
                    $eshIzlemLimitOptions[] = FormHelper::makeOption((string) $L, $L . ' kayıt');
                }
                echo FormHelper::fieldSelect('limit', 'Sayfa başı', $eshIzlemLimitOptions, (string) (int) ($limit ?? 50), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldInput('search', 'Arama', (string) ($search ?? ''), [
                    'col' => 'col-12 col-xl-4',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'labelHtml' => 'Arama <span class="fw-normal text-muted">(TC, ad, soyad)</span>',
                    'class' => 'form-control-sm shadow-sm esh-filter-control',
                    'placeholder' => 'İsteğe bağlı…',
                ]);
                ?>

                <div class="col-12 pt-1 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-success btn-sm shadow-sm px-4 rounded-pill esh-filter-control">
                        <i class="fa-solid fa-filter me-1"></i>Getir
                    </button>
                    <a href="<?= htmlspecialchars(esh_url('Visit', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>

    <div class="card border-0 esh-list-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width:88px" title="Hasta bazlı özet">
                                <i class="fa-solid fa-chart-line text-primary"></i><br><small class="text-muted">Özet</small>
                            </th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $izlemSortOrdering, $eshIzlemSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $izlemSortOrdering, $eshIzlemSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'a2.adi', $izlemSortOrdering, $eshIzlemSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('İzlem tarihi', 'i.izlemtarihi', $izlemSortOrdering, $eshIzlemSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Zaman', 'i.zaman', $izlemSortOrdering, array_merge($eshIzlemSortCfg, ['thClass' => 'text-center'])) ?>
                            <th class="small">Araç</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Yapılan işlem', 'i.yapilan', $izlemSortOrdering, $eshIzlemSortCfg) ?>
                            <th>İzlemi yapan(lar)</th>
                        </tr>
                    </thead>
                    <tbody id="esh-visit-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-visit-list-loading-row">
                            <td colspan="9" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>İzlem listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $visitPagelink = $izlemPagelink ?? \App\Helpers\UrlHelper::fromQueryString($izlemFilterQuery); ?>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) ($totalVisits ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50)) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 50), $visitPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) ($totalVisits ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50), $visitPagelink) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/views/site/partials/list_pdf_assets.php'; ?>
</div>