<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
/**
 * @var array<int, object> $plans
 * @var int $totalItems
 * @var int $page
 * @var int $limit
 * @var string $search
 * @var string $pasif
 * @var string $filterQuery
 * @var string $ordering
 */
use App\Helpers\FormHelper;
use App\Helpers\PatientCareHelper;

$oncelikConfig = [
    '1' => ['text' => 'Normal', 'class' => 'success'],
    '2' => ['text' => 'Orta', 'class' => 'warning'],
    '3' => ['text' => 'Yüksek', 'class' => 'danger'],
    '0' => ['text' => '—', 'class' => 'secondary'],
];
$ordering = $ordering ?? '';
$listBase = \App\Helpers\UrlHelper::fromQueryString($filterQuery);
$deleteRetq = http_build_query([
    'search' => $search ?? '',
    'pasif' => $pasif ?? 'all',
    'page' => (int) ($page ?? 1),
    'limit' => (int) ($limit ?? 50),
    'ordering' => (string) ($ordering ?? ''),
]);

$pasifFilterOptions = ['all' => 'Tüm pasif durumlar'] + array_map(
    static fn (array $o) => $o['label'],
    PatientCareHelper::dosyaDurumuPasifOptions(true)
);
$filterExpanded = trim((string) ($search ?? '')) !== '' || (string) ($pasif ?? 'all') !== 'all';
$eshPassiveSortBase = [];
parse_str((string) ($filterQuery ?? ''), $eshPassiveSortBase);
$eshPassiveSortCfg = ['mode' => 'ordering', 'base' => $eshPassiveSortBase];
?>
<div class="container-fluid py-4 plan-list-page">
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-user-clock text-danger me-2"></i>Pasif hastalarda bekleyen planlı izlemler
            </h4>
            <p class="text-muted small mb-0">
                Aktif olmayan hastalara bağlı, henüz tamamlanmamış (<code>durum = 0</code>) plan kayıtları.
                Pasif tarihinden <strong>önce</strong> olanları yapılmadı izlem olarak kapatabilir, <strong>sonra</strong> olanları silebilirsiniz.
            </p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-calendar-check me-1"></i>Aktif plan listesi
        </a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card border-0 esh-list-table-card mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-filter"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Arama ve pasif durum filtresi ile listeyi daraltın.</span>
                </div>
            </div>
            <button
                id="passive-plan-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#passive-plan-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="passive-plan-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="passive-plan-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 bg-body-tertiary bg-opacity-25 border-bottom">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('PlannedVisit', 'passivePendingPlans'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
                <?= esh_form_route_hiddens('PlannedVisit', 'passivePendingPlans') ?>
                    <?php
                    echo FormHelper::fieldInput('search', 'Arama', (string) ($search ?? ''), [
                        'col' => 'col-md-5',
                        'labelClass' => 'form-label small fw-semibold',
                        'class' => 'form-control-sm',
                        'placeholder' => 'Ad, soyad veya TC',
                    ]);
                    $eshPassivePlanOptions = [];
                    foreach ($pasifFilterOptions as $val => $label) {
                        $eshPassivePlanOptions[] = FormHelper::makeOption((string) $val, (string) $label);
                    }
                    echo FormHelper::fieldSelect('pasif', 'Kayıt durumu (pasif)', $eshPassivePlanOptions, (string) ($pasif ?? 'all'), [
                        'col' => 'col-md-4',
                        'labelClass' => 'form-label small fw-semibold',
                        'class' => 'form-select-sm',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill flex-grow-1">Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'passivePendingPlans'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>

        <form method="post" action="<?= htmlspecialchars(esh_url('PlannedVisit', 'deletePassivePendingBulk'), ENT_QUOTES, 'UTF-8') ?>" id="form-passive-pending-bulk">
            <input type="hidden" name="retq" value="<?= htmlspecialchars($deleteRetq, ENT_QUOTES, 'UTF-8') ?>">
            <div class="px-3 py-2 bg-body-tertiary border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <span class="small text-muted">
                        Toplam <strong><?= (int) ($totalItems ?? 0) ?></strong> kayıt
                        <span class="text-muted">·</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis">Önce</span> pasif tarihinden önce
                        <span class="badge bg-danger-subtle text-danger-emphasis">Sonra</span> pasif tarihinden sonra
                    </span>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="passive-plan-select-before" title="Pasif tarihinden önceki planları seç">
                            <i class="fa-solid fa-check-double me-1"></i>Öncekileri seç
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="passive-plan-select-after" title="Pasif tarihinden sonraki planları seç">
                            <i class="fa-solid fa-check-double me-1"></i>Sonrakileri seç
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="passive-plan-select-none">
                            Seçimi kaldır
                        </button>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="submit" class="btn btn-warning btn-sm rounded-pill"
                            formaction="<?= htmlspecialchars(esh_url('PlannedVisit', 'markPassivePendingMissedBulk'), ENT_QUOTES, 'UTF-8') ?>"
                            onclick="return confirm('Seçili planlar (pasif tarihinden önce olanlar) yapılmadı izlem kaydı ile kapatılacak. Devam edilsin mi?');">
                        <i class="fa-solid fa-hourglass-half me-1"></i>Seçilenleri yapılmadı işaretle
                    </button>
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill"
                            formaction="<?= htmlspecialchars(esh_url('PlannedVisit', 'deletePassivePendingAfterBulk'), ENT_QUOTES, 'UTF-8') ?>"
                            onclick="return confirm('Seçili planlar (pasif tarihinden sonra olanlar) kalıcı olarak silinecek. Devam edilsin mi?');">
                        <i class="fa-solid fa-trash-can me-1"></i>Sonrakileri sil
                    </button>
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill"
                            onclick="return confirm('Seçili planlı izlem kayıtları kalıcı olarak silinecek (tarih ayrımı yapılmaz). Devam edilsin mi?');">
                        <i class="fa-solid fa-trash me-1"></i>Seçilenleri sil (tümü)
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="text-center" style="width:2.5rem;">
                                <input type="checkbox" class="form-check-input" id="passive-plan-check-all" title="Tümünü seç">
                            </th>
                            <th class="text-center">Özet</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta', 'h.isim', (string) ($ordering ?? ''), $eshPassiveSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', (string) ($ordering ?? ''), $eshPassiveSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Durum', 'h.pasif', (string) ($ordering ?? ''), $eshPassiveSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Pasif tarihi', 'h.pasiftarihi', (string) ($ordering ?? ''), $eshPassiveSortCfg) ?>
                            <th>Adres</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Plan tarihi', 'p.planlanantarih', (string) ($ordering ?? ''), $eshPassiveSortCfg) ?>
                            <th class="text-center">Ayrım</th>
                            <th class="text-center">Zaman</th>
                            <th class="text-center">Öncelik</th>
                            <th>İşlemler</th>
                            <th>Planlayan</th>
                        </tr>
                    </thead>
                    <tbody id="esh-passive-pending-plans-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($passivePendingRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-passive-pending-loading-row">
                            <td colspan="13" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Bekleyen planlar yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>

        <?php $pagelink = $listBase . '&ordering=' . rawurlencode($ordering); ?>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) ($totalItems ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50)) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 50), $pagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) ($totalItems ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50), $pagelink) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    function setChecksBySplit(split) {
        document.querySelectorAll('.passive-plan-check').forEach(function (cb) {
            cb.checked = split === '' ? false : (cb.getAttribute('data-split') === split);
        });
        var all = document.getElementById('passive-plan-check-all');
        if (all) {
            all.checked = false;
        }
    }

    var all = document.getElementById('passive-plan-check-all');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.passive-plan-check').forEach(function (cb) {
                cb.checked = all.checked;
            });
        });
    }

    var selBefore = document.getElementById('passive-plan-select-before');
    if (selBefore) {
        selBefore.addEventListener('click', function () { setChecksBySplit('before'); });
    }
    var selAfter = document.getElementById('passive-plan-select-after');
    if (selAfter) {
        selAfter.addEventListener('click', function () { setChecksBySplit('after'); });
    }
    var selNone = document.getElementById('passive-plan-select-none');
    if (selNone) {
        selNone.addEventListener('click', function () { setChecksBySplit(''); });
    }

    var filter = document.getElementById('passive-plan-filter-collapse');
    var toggleText = document.querySelector('#passive-plan-filter-toggle .js-filter-toggle-text');
    if (filter && toggleText) {
        filter.addEventListener('shown.bs.collapse', function () {
            toggleText.textContent = 'Filtreleri Gizle';
        });
        filter.addEventListener('hidden.bs.collapse', function () {
            toggleText.textContent = 'Filtreleri Göster';
        });
    }
})();
</script>
</div>