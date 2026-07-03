    <section class="mb-3 esh-preview-mod-target" id="esh-preview-mod-ortak" aria-label="Jeton önizleme">

        <p class="esh-theme-preview-section-label mb-2">--esh-ui-* renk özeti</p>

        <div class="esh-theme-preview-swatch-grid">

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--text"><div class="esh-theme-preview-swatch__box"></div>metin</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--muted"><div class="esh-theme-preview-swatch__box"></div>soluk</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--accent"><div class="esh-theme-preview-swatch__box"></div>vurgu</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--surface"><div class="esh-theme-preview-swatch__box"></div>yüzey</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--surface-muted"><div class="esh-theme-preview-swatch__box"></div>yüzey 2</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--border"><div class="esh-theme-preview-swatch__box"></div>kenar</div>

            <div class="esh-theme-preview-swatch esh-theme-preview-swatch--head"><div class="esh-theme-preview-swatch__box"></div>tablo başlık</div>

        </div>

    </section>



    <section class="esh-page__panel esh-page__panel--filter mb-3" aria-label="Datepicker örneği">

        <div class="esh-page__panel-head">

            <div>

                <span class="esh-page__panel-title d-block">Datepicker</span>

                <span class="esh-page__panel-subtitle">bootstrap-datepicker · tema <code>datepicker-<?= htmlspecialchars($previewThemeSlug, ENT_QUOTES, 'UTF-8') ?>.css</code></span>

            </div>

        </div>

        <div class="esh-page__panel-body">

            <div class="row g-2 align-items-end">

                <div class="col-sm-4">

                    <label class="form-label small fw-semibold text-secondary mb-1" for="esh-preview-dp-start">Başlangıç</label>

                    <div class="input-group input-group-sm">

                        <span class="input-group-text"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i></span>

                        <input type="text" id="esh-preview-dp-start" class="form-control esh-ui-filter-control datepicker" placeholder="gg.aa.yyyy" autocomplete="off" value="01.05.2026">

                    </div>

                </div>

                <div class="col-sm-4">

                    <label class="form-label small fw-semibold text-secondary mb-1" for="esh-preview-dp-end">Bitiş</label>

                    <div class="input-group input-group-sm">

                        <span class="input-group-text"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i></span>

                        <input type="text" id="esh-preview-dp-end" class="form-control esh-ui-filter-control datepicker" placeholder="gg.aa.yyyy" autocomplete="off">

                    </div>

                </div>

                <div class="col-sm-4">

                    <label class="form-label small fw-semibold text-secondary mb-1" for="esh-preview-dp-single">Tek tarih</label>

                    <input type="text" id="esh-preview-dp-single" class="form-control form-control-sm datepicker" placeholder="Takvimi açın" autocomplete="off">

                </div>

            </div>

            <p class="esh-theme-preview-datepicker-hint mb-0">Alana tıklayınca açılan takvim ve tema datepicker stillerini burada test edin.</p>

        </div>

    </section>



    <div class="row g-2 mb-3">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-3">

                    <div class="text-muted small text-uppercase fw-semibold mb-1">Bootstrap kart</div>

                    <div class="fs-4 fw-bold text-primary">128</div>

                    <div class="small text-muted">Eski view’larda hâlâ <code>.card</code></div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <section class="esh-page__panel esh-page__panel--data h-100 mb-0">

                <div class="esh-page__panel-body py-3">

                    <div class="esh-page__panel-title">ESH panel</div>

                    <div class="fs-4 fw-bold" style="color: var(--esh-ui-accent);">42</div>

                    <div class="esh-page__panel-subtitle">esh-page__panel--data</div>

                </div>

            </section>

        </div>

        <div class="col-md-4">

            <nav class="esh-theme-preview-mini-nav d-flex gap-1" aria-label="Örnek sekme">

                <span class="nav-link active">Aktif</span>

                <span class="nav-link">Pasif</span>

                <a href="#" class="nav-link">Bağlantı</a>

            </nav>

            <p class="small mt-2 mb-0"><a href="#">Örnek bağlantı</a> · <span class="text-muted">muted metin</span></p>

        </div>

    </div>



    <section class="esh-page__panel esh-page__panel--filter mb-3">

        <div class="esh-page__panel-head">

            <div>

                <span class="esh-page__panel-title d-block">Liste filtreleri</span>

                <span class="esh-page__panel-subtitle">esh-page__panel--filter · esh-ui-filter-control</span>

            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" disabled>Filtreler</button>

        </div>

        <div class="esh-page__panel-body">

            <div class="row g-2 align-items-end">

                <div class="col-sm-4">

                    <label class="form-label small fw-semibold text-secondary mb-1">Arama</label>

                    <input type="text" class="form-control form-control-sm esh-ui-filter-control" value="Örnek metin" readonly>

                </div>

                <div class="col-sm-3">

                    <label class="form-label small fw-semibold text-secondary mb-1">Durum</label>

                    <select class="form-select form-select-sm esh-ui-filter-control" disabled>

                        <option>Tümü</option>

                        <option selected>Aktif</option>

                    </select>

                </div>

                <div class="col-sm-3">

                    <label class="form-label small fw-semibold text-secondary mb-1" for="esh-preview-dp-filter">Tarih (filtre)</label>

                    <div class="input-group input-group-sm">

                        <span class="input-group-text"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i></span>

                        <input type="text" id="esh-preview-dp-filter" class="form-control esh-ui-filter-control datepicker" placeholder="gg.aa.yyyy" autocomplete="off">

                    </div>

                </div>

                <div class="col-sm-2 d-flex gap-1">

                    <button type="button" class="btn btn-primary btn-sm esh-ui-filter-control w-100">Filtrele</button>

                </div>

            </div>

        </div>

    </section>



    <section class="esh-page__panel esh-page__panel--filter mb-3" aria-label="Tom Select örneği">

        <div class="esh-page__panel-head">

            <div>

                <span class="esh-page__panel-title d-block">Tom Select (filtre select)</span>

                <span class="esh-page__panel-subtitle"><code>global-tomselect.css</code> (yapı) + tema <code>--esh-ts-*</code> jetonları · <code>.esh-tomselect</code></span>

            </div>

        </div>

        <div class="esh-page__panel-body">

            <div class="row g-2 align-items-end">

                <div class="col-md-6">

                    <label class="form-label small fw-semibold text-secondary mb-1" for="esh-preview-tomselect">Durum (Tom Select)</label>

                    <select id="esh-preview-tomselect" class="form-select form-select-sm esh-tomselect esh-ui-filter-control">

                        <option value=""></option>

                        <option value="aktif" selected>Aktif</option>

                        <option value="pasif">Pasif</option>

                        <option value="beklemede">Beklemede</option>

                    </select>

                </div>

                <div class="col-md-6">

                    <p class="esh-theme-preview-hint mb-0">Alana tıklayınca arama kutulu açılır liste; tema Tom Select stillerini burada test edin.</p>

                </div>

            </div>

        </div>

    </section>



