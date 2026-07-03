    <section class="esh-page__panel mb-3 esh-preview-mod-target esh-page-dashboard" id="esh-preview-mod-dashboard" aria-label="Dashboard takvim">

        <div class="esh-page__panel-head">

            <div>

                <span class="esh-page__panel-title d-block">Dashboard takvim</span>

                <span class="esh-page__panel-subtitle"><code>.esh-page-dashboard</code> · <code>--esh-ui-calendar-*</code> · günün planı sekmeleri</span>

            </div>

        </div>

        <div class="esh-page__panel-body">

            <div class="row g-3">

                <div class="col-lg-7">

                    <div class="card border-0 shadow-sm">

                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">

                            <span class="small fw-bold text-primary"><i class="fa fa-calendar-check me-1"></i>Mayıs 2026</span>

                            <div class="btn-group btn-group-sm">

                                <span class="btn btn-outline-primary disabled px-2"><i class="fa fa-chevron-left"></i></span>

                                <span class="btn btn-primary fw-bold px-2">31</span>

                                <span class="btn btn-outline-primary disabled px-2"><i class="fa fa-chevron-right"></i></span>

                            </div>

                        </div>

                        <div class="card-body p-0">

                            <table class="table table-bordered mb-0 align-middle">

                                <thead class="table-light">

                                    <tr class="text-center small">

                                        <th>Pzt</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cmt</th><th>Paz</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td class="bg-light border-0"></td>

                                        <td class="bg-light border-0"></td>

                                        <td class="bg-light border-0"></td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">1</span></div>

                                            <span class="badge bg-success esh-cal-task">İzlem 2</span>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">2</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">3</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day bg-primary-subtle border border-primary" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">4</span></div>

                                            <span class="badge bg-info esh-cal-task">Pansuman</span>

                                        </td>

                                    </tr>

                                    <tr>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">5</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">6</span></div>

                                            <span class="badge bg-warning text-dark esh-cal-task">Nakil</span>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">7</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">8</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">9</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">10</span></div>

                                        </td>

                                        <td class="calendar-day p-1 esh-theme-preview-cal-day" style="height:4.5rem;vertical-align:top;cursor:pointer">

                                            <div class="text-end mb-1"><span class="fw-bold small">11</span></div>

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                        <div class="card-footer bg-light py-2 small text-muted">Gün hücresi hover — takvim jetonları</div>

                    </div>

                </div>

                <div class="col-lg-5">

                    <div class="card shadow-sm border-0 h-100 mr-dash-plan d-flex flex-column esh-theme-preview-dash-plan">

                        <div class="card-header bg-white py-3 border-bottom-0 mr-dash-plan__head">

                            <div class="d-flex align-items-center">

                                <div class="flex-shrink-0">

                                    <div class="bg-warning-subtle p-2 rounded-3 text-warning">

                                        <i class="fa fa-list-check fs-4"></i>

                                    </div>

                                </div>

                                <div class="flex-grow-1 ms-3 d-flex flex-wrap align-items-start justify-content-between gap-2">

                                    <div>

                                        <h6 class="mb-0 fw-bold text-dark">Günün Planı</h6>

                                        <small class="text-muted fw-bold" id="esh-preview-selected-date-label">

                                            <i class="fa fa-clock me-1"></i>04-05-2026

                                        </small>

                                    </div>

                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill flex-shrink-0" disabled title="Önizleme — MERNİS">

                                        <i class="fa-solid fa-heart-pulse me-1"></i><span class="d-none d-md-inline">MERNİS</span>

                                    </button>

                                </div>

                            </div>

                        </div>

                        <div id="esh-preview-daily-events-tabs" class="mr-dash-plan__tabs px-3">

                            <ul class="nav nav-tabs nav-fill esh-route-tabs border-bottom-0 gap-2 mb-2" id="taskTab" role="tablist">

                                <li class="nav-item" role="presentation">

                                    <button class="nav-link esh-route-tab esh-route-tab--sabah active" id="sabah-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-sabah" aria-selected="true">

                                        <span class="esh-route-tab__inner">

                                            <i class="fa fa-sun esh-route-tab__icon" aria-hidden="true"></i>

                                            <span class="esh-route-tab__label text-uppercase">Sabah</span>

                                            <span class="badge esh-route-tab-badge rounded-pill esh-route-tab-badge--on esh-route-tab-badge--sabah">2</span>

                                        </span>

                                    </button>

                                </li>

                                <li class="nav-item" role="presentation">

                                    <button class="nav-link esh-route-tab esh-route-tab--ogle" id="ogle-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-ogle" aria-selected="false">

                                        <span class="esh-route-tab__inner">

                                            <i class="fa fa-cloud-sun esh-route-tab__icon" aria-hidden="true"></i>

                                            <span class="esh-route-tab__label text-uppercase">Öğle</span>

                                            <span class="badge esh-route-tab-badge rounded-pill esh-route-tab-badge--empty esh-route-tab-badge--ogle">0</span>

                                        </span>

                                    </button>

                                </li>

                                <li class="nav-item" role="presentation">

                                    <button class="nav-link esh-route-tab esh-route-tab--aksam" id="aksam-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-aksam" aria-selected="false">

                                        <span class="esh-route-tab__inner">

                                            <i class="fa fa-moon esh-route-tab__icon" aria-hidden="true"></i>

                                            <span class="esh-route-tab__label text-uppercase">Akşam</span>

                                            <span class="badge esh-route-tab-badge rounded-pill esh-route-tab-badge--on esh-route-tab-badge--aksam">1</span>

                                        </span>

                                    </button>

                                </li>

                            </ul>

                        </div>

                        <div class="card-body p-3 mr-dash-plan__scroll" id="esh-preview-daily-events-body">

                            <div id="esh-preview-daily-events-plan">

                                <div class="tab-content border rounded bg-body-secondary shadow-sm esh-task-tab-panels" id="taskTabContent" style="min-height:120px;">

                                    <div class="tab-pane fade show active p-3" id="tab-sabah" role="tabpanel" aria-labelledby="sabah-tab">

                                        <div class="text-muted small fw-bold mb-2 px-2 text-uppercase esh-daily-section">

                                            <i class="fa fa-calendar-check me-1 text-secondary"></i> Planlı izlemler

                                        </div>

                                        <div class="list-group-item p-2 border rounded mb-2 shadow-sm border-start-lg esh-daily-plan-card border-primary">

                                            <div class="d-flex justify-content-between align-items-start">

                                                <div class="fw-bold text-uppercase small">

                                                    <span class="text-primary"><i class="fa fa-user-circle me-1 text-secondary"></i> Ayşe Yılmaz</span>

                                                </div>

                                                <span class="badge border shadow-sm esh-daily-plan-card__badge">Periyodik izlem</span>

                                            </div>

                                            <div class="mt-1 d-flex align-items-center flex-wrap small esh-daily-plan-card__meta">

                                                <span class="me-2"><i class="fa fa-id-card me-1"></i><span class="text-primary">123 456 789 01</span></span>

                                                <span class="me-2"><i class="fa fa-map-marker-alt me-1 text-danger"></i>Kadıköy / Caferağa · 2. bölge</span>

                                            </div>

                                        </div>

                                        <div class="list-group-item p-2 border rounded mb-0 shadow-sm border-start-lg esh-daily-plan-card border-primary">

                                            <div class="d-flex justify-content-between align-items-start">

                                                <div class="fw-bold text-uppercase small">

                                                    <span class="text-danger"><i class="fa fa-user-circle me-1 text-secondary"></i> Fatma Demir</span>

                                                </div>

                                                <span class="badge border shadow-sm esh-daily-plan-card__badge">Kontrol</span>

                                            </div>

                                            <div class="mt-1 d-flex align-items-center flex-wrap small esh-daily-plan-card__meta">

                                                <span class="me-2"><i class="fa fa-id-card me-1"></i><span class="text-primary">987 654 321 09</span></span>

                                                <span class="me-2"><i class="fa fa-map-marker-alt me-1 text-danger"></i>Üsküdar / Altunizade · 1. bölge</span>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="tab-pane fade p-3" id="tab-ogle" role="tabpanel" aria-labelledby="ogle-tab">

                                        <div class="p-5 text-center text-muted small border rounded-3 bg-light">Bu vaktin planlı görevi bulunmuyor.</div>

                                    </div>

                                    <div class="tab-pane fade p-3" id="tab-aksam" role="tabpanel" aria-labelledby="aksam-tab">

                                        <div class="text-muted small fw-bold mb-2 px-2 text-uppercase esh-daily-section">

                                            <i class="fa fa-plus-square me-1 text-secondary"></i> Planlı pansumanlar

                                        </div>

                                        <div class="list-group-item p-2 border rounded mb-0 shadow-sm border-start-lg esh-daily-plan-card border-warning">

                                            <div class="d-flex justify-content-between align-items-start">

                                                <div class="fw-bold text-uppercase small">

                                                    <span class="text-primary"><i class="fa fa-user-circle me-1 text-secondary"></i> Mehmet Kaya</span>

                                                </div>

                                                <span class="badge border shadow-sm esh-daily-plan-card__badge">Pansuman</span>

                                            </div>

                                            <div class="mt-1 d-flex align-items-center flex-wrap small esh-daily-plan-card__meta">

                                                <span class="me-2"><i class="fa fa-id-card me-1"></i><span class="text-primary">111 222 333 44</span></span>

                                                <span class="me-2"><i class="fa fa-map-marker-alt me-1 text-danger"></i>Maltepe / Başıbüyük · 3. bölge</span>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div class="mt-4 border-top pt-3 shadow-none">

                                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">

                                        <h6 class="mb-0 fw-bold text-muted text-uppercase"><i class="fa fa-ambulance me-2 text-secondary"></i> Nakiller</h6>

                                        <span class="badge bg-light text-dark border rounded-pill">1</span>

                                    </div>

                                    <div class="nakil-list px-1">

                                        <div class="list-group-item p-2 border rounded mb-0 shadow-sm border-start-lg esh-daily-plan-card border-danger">

                                            <div class="d-flex justify-content-between align-items-start">

                                                <div class="fw-bold text-uppercase small">

                                                    <span class="text-dark"><i class="fa fa-user-circle me-1 text-secondary"></i> Ali Veli</span>

                                                </div>

                                                <span class="badge border shadow-sm esh-daily-plan-card__badge">Nakil</span>

                                            </div>

                                            <div class="mt-1 d-flex align-items-center flex-wrap small esh-daily-plan-card__meta">

                                                <span class="me-2"><i class="fa fa-id-card me-1"></i><span class="text-primary">555 666 777 88</span></span>

                                                <span class="me-2"><i class="fa fa-map-marker-alt me-1 text-danger"></i>Ataşehir / İçerenköy</span>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="card-footer bg-white border-top-0 p-3 mr-dash-plan__foot">

                            <a href="#" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-3" tabindex="-1" onclick="return false;">

                                <i class="fa fa-map-marked-alt me-2"></i>GÜNÜN ROTASINI ÇİZ

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>



