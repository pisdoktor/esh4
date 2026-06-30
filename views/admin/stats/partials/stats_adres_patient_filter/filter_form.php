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
                                    <?php
                                    echo \App\Helpers\FormHelper::fieldSelect('ilce', 'İlçe', $eshAdresFilterOptions($ilceler, 'Bir ilçe seçin'), (string) $ilce, [
                                        'col' => 'col-12 col-md-6 col-xl-3',
                                        'id' => 'stats-adres-ilce',
                                        'labelClass' => 'form-label fw-semibold small mb-1',
                                        'class' => 'form-select-sm',
                                        'tomSelect' => false,
                                    ]);
                                    $eshMahalleExtra = $ilce === '' ? ['disabled' => 'disabled'] : [];
                                    echo \App\Helpers\FormHelper::fieldSelect('mahalle', 'Mahalle', $eshAdresFilterOptions($mahalleler, 'Bir mahalle seçin'), (string) $mahalle, [
                                        'col' => 'col-12 col-md-6 col-xl-3',
                                        'id' => 'stats-adres-mahalle',
                                        'labelClass' => 'form-label fw-semibold small mb-1',
                                        'class' => 'form-select-sm',
                                        'tomSelect' => false,
                                        'extraAttrs' => $eshMahalleExtra,
                                    ]);
                                    $eshSokakExtra = $mahalle === '' ? ['disabled' => 'disabled'] : [];
                                    echo \App\Helpers\FormHelper::fieldSelect('sokak', 'Sokak', $eshAdresFilterOptions($sokaklar, 'Bir sokak seçin'), (string) $sokak, [
                                        'col' => 'col-12 col-md-6 col-xl-3',
                                        'id' => 'stats-adres-sokak',
                                        'labelClass' => 'form-label fw-semibold small mb-1',
                                        'class' => 'form-select-sm',
                                        'tomSelect' => false,
                                        'extraAttrs' => $eshSokakExtra,
                                    ]);
                                    $eshKapinoExtra = $sokak === '' ? ['disabled' => 'disabled'] : [];
                                    echo \App\Helpers\FormHelper::fieldSelect('kapino', 'Kapı no', $eshAdresFilterOptions($kapinolar, 'Bir kapı no seçin'), (string) $kapino, [
                                        'col' => 'col-12 col-md-6 col-xl-3',
                                        'id' => 'stats-adres-kapino',
                                        'labelClass' => 'form-label fw-semibold small mb-1',
                                        'class' => 'form-select-sm',
                                        'tomSelect' => false,
                                        'extraAttrs' => $eshKapinoExtra,
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-4">
                            <div class="border rounded-3 p-3 h-100">
                                <?php
                                $eshAdresOzellikOptions = [\App\Helpers\FormHelper::makeOption('', 'Hasta özelliği seçin')];
                                foreach ($ozellikLabels as $key => $label) {
                                    $eshAdresOzellikOptions[] = \App\Helpers\FormHelper::makeOption((string) $key, (string) $label);
                                }
                                echo \App\Helpers\FormHelper::fieldSelect('ozellik', 'Hasta özelliği', $eshAdresOzellikOptions, (string) $ozellik, [
                                    'col' => '',
                                    'id' => 'stats-adres-ozellik',
                                    'labelClass' => 'form-label fw-semibold small mb-1',
                                    'class' => 'form-select-sm mb-3',
                                    'tomSelect' => false,
                                ]);
                                ?>
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