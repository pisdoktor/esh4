    <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                </div>
            </div>
            <button
                id="stats-ay-movement-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#stats-ay-movement-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="stats-ay-movement-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="stats-ay-movement-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'ayMovement'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'ayMovement') ?>
                    <?php
                    $eshAyMovementMonthOptions = [];
                    foreach ($turkce_aylar as $ayNo => $ayAdi) {
                        $eshAyMovementMonthOptions[] = \App\Helpers\FormHelper::makeOption((string) $ayNo, $ayAdi);
                    }
                    echo \App\Helpers\FormHelper::fieldSelect('month', 'Ay', $eshAyMovementMonthOptions, (string) (int) $month, [
                        'col' => 'col-12 col-md-4 col-xl-3',
                        'id' => 'stats-ay-movement-month',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-2',
                        'class' => 'form-select-sm shadow-sm',
                        'tomSelect' => false,
                    ]);
                    $eshAyMovementYearOptions = [];
                    for ($y = $yearMax; $y >= $yearMin; $y--) {
                        $eshAyMovementYearOptions[] = \App\Helpers\FormHelper::makeOption((string) $y, (string) $y);
                    }
                    echo \App\Helpers\FormHelper::fieldSelect('year', 'Yıl', $eshAyMovementYearOptions, (string) (int) $year, [
                        'col' => 'col-12 col-md-4 col-xl-3',
                        'id' => 'stats-ay-movement-year',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-2',
                        'class' => 'form-select-sm shadow-sm',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <div class="col-12 col-md-4 col-xl-3 d-flex gap-2">
                        <button type="submit" class="btn btn-info btn-sm rounded-pill px-4 shadow-sm text-white"><i class="fa-solid fa-filter me-1"></i>Uygula</button>
                        <a href="<?= htmlspecialchars(esh_url('Stats', 'ayMovement'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>