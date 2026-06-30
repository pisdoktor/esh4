<?php
/**
 * @var string $ilce
 * @var string $mahalle
 * @var string $sokak
 * @var string $kapino
 * @var string $ozellik
 * @var array<string, string> $ozellikLabels
 * @var list<object> $ilceler
 * @var list<object> $mahalleler
 * @var list<object> $sokaklar
 * @var list<object> $kapinolar
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var string $orderby
 * @var string $orderdir
 * @var string $adresPatientFilterRowsFetchUrl
 * @var string $adresFilterAjaxUrl
 * @var bool $filterExpanded
 */
$filterExpanded = !empty($filterExpanded);
$linkParams = [
    'controller' => 'Stats',
    'action' => 'adresPatientFilter',
    'limit' => (string) $limit,
];
foreach (['ilce', 'mahalle', 'sokak', 'kapino', 'ozellik'] as $fk) {
    if (!empty($$fk)) {
        $linkParams[$fk] = $$fk;
    }
}
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($linkParams);
$ordering = trim($orderby . ' ' . $orderdir);
$sortPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

$renderAdresOption = static function (object $row, string $selectedId): string {
    $id = (string) ($row->id ?? '');
    $label = (string) ($row->adi ?? '');
    $sayi = (int) ($row->sayi ?? 0);
    $text = $label . ' [' . $sayi . ']';
    $sel = ($selectedId !== '' && $selectedId === $id) ? ' selected' : '';

    return '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</option>';
};
?>
<div class="container-fluid mt-3 esh-stats-adres-filter-page pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>

<h4 class="fw-bold mb-2"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Adrese göre hastalar</h4>
    <?php require dirname(__DIR__, 2) . '/partials/admin/stats_page_intro.php'; ?>

    <form method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'adresPatientFilter'), ENT_QUOTES, 'UTF-8') ?>" id="stats-adres-filter-form" class="row g-3"
          data-adres-ajax-url="<?= htmlspecialchars($adresFilterAjaxUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?= esh_form_route_hiddens('Stats', 'adresPatientFilter') ?>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>Filtreleme</h6>
                    <button
                        id="stats-adres-filter-toggle"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#stats-adres-filter-collapse"
                        aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                        aria-controls="stats-adres-filter-collapse"
                    >
                        <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
                    </button>
                </div>
                <div id="stats-adres-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
                <div class="card-body">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-12 col-xl-8">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small fw-bold text-secondary mb-2">
                                    <i class="fa-solid fa-road me-1"></i>Adres filtreleri
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <label class="form-label fw-semibold small mb-1" for="stats-adres-ilce">İlçe</label>
                                        <select name="ilce" id="stats-adres-ilce" class="form-select form-select-sm">
                                            <option value="">Bir ilçe seçin</option>
                                            <?php foreach ($ilceler as $ic): ?>
                                                <?= $renderAdresOption($ic, $ilce) ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <label class="form-label fw-semibold small mb-1" for="stats-adres-mahalle">Mahalle</label>
                                        <select name="mahalle" id="stats-adres-mahalle" class="form-select form-select-sm"<?= $ilce === '' ? ' disabled' : '' ?>>
                                            <option value="">Bir mahalle seçin</option>
                                            <?php foreach ($mahalleler as $mh): ?>
                                                <?= $renderAdresOption($mh, $mahalle) ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <label class="form-label fw-semibold small mb-1" for="stats-adres-sokak">Sokak</label>
                                        <select name="sokak" id="stats-adres-sokak" class="form-select form-select-sm"<?= $mahalle === '' ? ' disabled' : '' ?>>
                                            <option value="">Bir sokak seçin</option>
                                            <?php foreach ($sokaklar as $sk): ?>
                                                <?= $renderAdresOption($sk, $sokak) ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <label class="form-label fw-semibold small mb-1" for="stats-adres-kapino">Kapı no</label>
                                        <select name="kapino" id="stats-adres-kapino" class="form-select form-select-sm"<?= $sokak === '' ? ' disabled' : '' ?>>
                                            <option value="">Bir kapı no seçin</option>
                                            <?php foreach ($kapinolar as $kp): ?>
                                                <?= $renderAdresOption($kp, $kapino) ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-4">
                            <div class="border rounded-3 p-3 h-100">
                                <label class="form-label fw-semibold small mb-1" for="stats-adres-ozellik">Hasta özelliği</label>
                                <select name="ozellik" id="stats-adres-ozellik" class="form-select form-select-sm mb-3">
                                    <option value="">Hasta özelliği seçin</option>
                                    <?php foreach ($ozellikLabels as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"<?= $ozellik === $key ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill">
                                        <i class="fa-solid fa-filter me-1"></i>Kayıtları getir
                                    </button>
                                    <a href="<?= htmlspecialchars(esh_url('Stats', 'adresPatientFilter'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Sıfırla</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users text-success me-2"></i>Hasta listesi</h6>
                    <span class="badge text-bg-light border text-secondary"><?= (int) $total ?> kayıt</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:76px"><span class="small text-muted">İzlem</span></th>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('İlçe', 'ilceadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle', 'mahalleadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Sokak', 'sokakadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Kapı no', 'kapinoadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, $eshSortCfg) ?>
                                </tr>
                            </thead>
                            <tbody id="esh-adres-patient-filter-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($adresPatientFilterRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <tr class="esh-adres-patient-filter-loading-row">
                                    <td colspan="8" class="border-0 py-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                            <span>Liste yükleniyor…</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-1">
                        <div class="d-flex align-items-center gap-3">
                            <div class="small text-muted">
                                <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                            </div>
                            <div>
                                <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $sortPagelink) ?>
                            </div>
                        </div>
                        <div>
                            <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $sortPagelink) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
