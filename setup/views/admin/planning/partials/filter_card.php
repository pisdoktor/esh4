<div class="container-fluid py-4 admin-list-page">
    <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">İlçe ve bölge seçip «Filtrele» ile uygulayın.</span>
                </div>
            </div>
            <button
                id="planning-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#planning-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="planning-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="planning-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('Planning', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 g-xl-4 align-items-end esh-planning-filter">
                <?= esh_form_route_hiddens('Planning', 'index') ?>
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="limit" value="<?= (int) $limit ?>">

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <label for="planning-filter-ilce" class="form-label fw-semibold small text-secondary mb-1">İlçe</label>
                        <select name="ilce" id="planning-filter-ilce" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value="">Tüm ilçeler</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= htmlspecialchars((string) $d->id, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $ilce_id === (string) $d->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $d->adi, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <label for="planning-filter-bolge" class="form-label fw-semibold small text-secondary mb-1">Bölge</label>
                        <select name="bolge" id="planning-filter-bolge" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value="">Tüm bölgeler</option>
                            <option value="0" <?= $bolge_filter === '0' ? 'selected' : '' ?>>— Atanmamış —</option>
                            <?php for ($i = 1; $i <= $bolge_max; $i++): ?>
                                <option value="<?= $i ?>" <?= $bolge_filter === (string) $i ? 'selected' : '' ?>><?= $i ?>. bölge</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4 col-xl-6 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('Planning', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>