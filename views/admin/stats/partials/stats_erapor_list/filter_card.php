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
                id="stats-erapor-list-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#stats-erapor-list-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="stats-erapor-list-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="stats-erapor-list-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'eraporList'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'eraporList') ?>
                <?php
                $eshEraporIlceOptions = [\App\Helpers\FormHelper::makeOption('', 'Tümü')];
                foreach ($ilceler as $ic) {
                    $eshEraporIlceOptions[] = \App\Helpers\FormHelper::makeOption((string) $ic->id, (string) $ic->adi);
                }
                echo \App\Helpers\FormHelper::fieldSelect('ilce', 'İlçe', $eshEraporIlceOptions, (string) $ilce, [
                    'col' => 'col-12 col-md-4',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                    'extraAttrs' => ['onchange' => "this.form.mahalle.value=''; this.form.submit();"],
                ]);
                $eshEraporMahalleOptions = [\App\Helpers\FormHelper::makeOption('', 'Tümü')];
                foreach ($mahalleler as $mh) {
                    $eshEraporMahalleOptions[] = \App\Helpers\FormHelper::makeOption((string) $mh->id, (string) $mh->adi);
                }
                echo \App\Helpers\FormHelper::fieldSelect('mahalle', 'Mahalle', $eshEraporMahalleOptions, (string) $mahalle, [
                    'col' => 'col-12 col-md-4',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                ]);
                ?>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'eraporList'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>