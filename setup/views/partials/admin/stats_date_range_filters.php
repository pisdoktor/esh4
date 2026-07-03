<?php
declare(strict_types=1);
/**
 * İstatistik raporları — başlangıç/bitiş tarihi (collapse «Liste filtreleri»).
 *
 * @var string $statsDateFilterAction   Stats action (ör. exitReasons)
 * @var string $statsDateFilterIdPrefix Benzersiz id öneki (ör. stats-exit-reasons)
 * @var string $statsDateFilterAccent   danger|info|secondary|primary
 * @var string $date_from
 * @var string $date_to
 * @var bool   $filterExpanded
 */
$statsDateFilterAction = (string) ($statsDateFilterAction ?? '');
$statsDateFilterIdPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($statsDateFilterIdPrefix ?? 'stats-filter'));
if ($statsDateFilterIdPrefix === '') {
    $statsDateFilterIdPrefix = 'stats-filter';
}
$accent = (string) ($statsDateFilterAccent ?? 'secondary');
$allowedAccents = ['danger', 'info', 'secondary', 'primary', 'success', 'warning'];
if (!in_array($accent, $allowedAccents, true)) {
    $accent = 'secondary';
}
$filterExpanded = !empty($filterExpanded);
$date_from = (string) ($date_from ?? '');
$date_to = (string) ($date_to ?? '');
$collapseId = $statsDateFilterIdPrefix . '-filter-collapse';
$toggleId = $statsDateFilterIdPrefix . '-filter-toggle';
$resetUrl = esh_url('Stats', $statsDateFilterAction);
?>
<div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden esh-stats-date-filter">
    <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="rounded-circle bg-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                <i class="fa-solid fa-sliders"></i>
            </span>
            <div class="min-w-0">
                <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
            </div>
        </div>
        <button
            id="<?= htmlspecialchars($toggleId, ENT_QUOTES, 'UTF-8') ?>"
            class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>"
            aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
            aria-controls="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>"
        >
            <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
        </button>
    </div>
    <div id="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', $statsDateFilterAction), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', $statsDateFilterAction) ?>
                <?= \App\Helpers\FormHelper::fieldDateRangeFilter('date_from', 'date_to', $date_from, $date_to, [
                    'col' => 'col-12 col-md-6',
                    'prefixIconClass' => 'bg-white text-' . $accent . ' border-end-0',
                ]) ?>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> btn-sm rounded-pill px-4 shadow-sm<?= $accent === 'info' || $accent === 'primary' ? ' text-white' : '' ?>"><i class="fa-solid fa-filter me-1"></i>Uygula</button>
                    <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$(function () {
    $('.esh-stats-date-filter .datepicker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        language: 'tr'
    });
    var $collapse = $('#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>');
    var $toggleText = $('#<?= htmlspecialchars($toggleId, ENT_QUOTES, 'UTF-8') ?> .js-filter-toggle-text');
    if ($collapse.length && $toggleText.length) {
        $collapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $collapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }
});
</script>
