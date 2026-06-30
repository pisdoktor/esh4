<?php

use App\Helpers\AuthHelper;

use App\Helpers\PageShellHelper;



$etkenAjaxUrl = (string) ($etkenAjaxUrl ?? '');

$ilacAjaxUrl = (string) ($ilacAjaxUrl ?? '');

?>

<?php

PageShellHelper::pageOpen([

    'kind' => 'list',

    'module' => 'ilac_rehber',

    'id' => 'esh-ilac-rehber-search',

    'attrs' => [

        'data-etken-ajax-url' => $etkenAjaxUrl,

        'data-ilac-ajax-url' => $ilacAjaxUrl,

    ],

]);

PageShellHelper::pageHeader(

    'İlaç Rehberi',

    'Etken madde veya ticari ilaç adı ile arayın.',

    AuthHelper::sessionIsAdmin()

        ? '<a href="' . htmlspecialchars(esh_url('IlacRehber', 'about'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-secondary btn-sm">Veri kaynağı</a>'

        : '',

    ['icon' => 'fa-solid fa-magnifying-glass']

);

?>



<div class="row g-4">

    <div class="col-lg-5">

        <section class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <ul class="nav nav-tabs mb-3" id="esh-rehber-search-tabs" role="tablist">

                    <li class="nav-item" role="presentation">

                        <button class="nav-link active" id="esh-rehber-tab-etken-btn" data-bs-toggle="tab"

                                data-bs-target="#esh-rehber-tab-etken" type="button" role="tab"

                                aria-controls="esh-rehber-tab-etken" aria-selected="true"

                                data-esh-rehber-mode="etken">

                            <i class="fa-solid fa-flask me-1 text-primary" aria-hidden="true"></i>Etken madde

                        </button>

                    </li>

                    <li class="nav-item" role="presentation">

                        <button class="nav-link" id="esh-rehber-tab-ilac-btn" data-bs-toggle="tab"

                                data-bs-target="#esh-rehber-tab-ilac" type="button" role="tab"

                                aria-controls="esh-rehber-tab-ilac" aria-selected="false"

                                data-esh-rehber-mode="ilac">

                            <i class="fa-solid fa-pills me-1 text-success" aria-hidden="true"></i>İlaç ismi

                        </button>

                    </li>

                </ul>



                <div class="tab-content" id="esh-rehber-search-tab-content">

                    <div class="tab-pane fade show active" id="esh-rehber-tab-etken" role="tabpanel"

                         aria-labelledby="esh-rehber-tab-etken-btn" tabindex="0">

                        <label for="esh-rehber-search-etken-q" class="form-label visually-hidden">Etken madde ara</label>

                        <input type="search" id="esh-rehber-search-etken-q" class="form-control form-control-lg"

                               placeholder="Örn. parasetamol, amoksisilin"

                               autocomplete="off" spellcheck="false">

                        <p class="small text-muted mb-0 mt-2">En az 1 karakter yazın; sonuçlar sağda listelenir.</p>

                    </div>

                    <div class="tab-pane fade" id="esh-rehber-tab-ilac" role="tabpanel"

                         aria-labelledby="esh-rehber-tab-ilac-btn" tabindex="0">

                        <label for="esh-rehber-search-ilac-q" class="form-label visually-hidden">İlaç ismi ara</label>

                        <input type="search" id="esh-rehber-search-ilac-q" class="form-control form-control-lg"

                               placeholder="Örn. arveles, augmentin"

                               autocomplete="off" spellcheck="false">

                        <p class="small text-muted mb-0 mt-2">Ticari ürün adı; sonuçta etken sayfasına gidebilirsiniz.</p>

                    </div>

                </div>

            </div>

        </section>

    </div>



    <div class="col-lg-7">

        <section class="card border-0 shadow-sm h-100 d-flex flex-column">

            <div class="card-header bg-white border-bottom py-3">

                <h2 class="h6 mb-0">Sonuçlar</h2>

                <p class="small text-muted mb-0 mt-1" id="esh-rehber-search-results-hint">Etken madde araması</p>

            </div>

            <div class="list-group list-group-flush flex-grow-1" id="esh-rehber-search-results" role="list">

                <div class="list-group-item text-muted small py-4 text-center" id="esh-rehber-search-placeholder"

                     data-etken-msg="Etken madde adı yazarak arayın."

                     data-ilac-msg="İlaç adı yazarak arayın.">

                    Etken madde adı yazarak arayın.

                </div>

            </div>

        </section>

    </div>

</div>



<?php PageShellHelper::pageClose(); ?>

