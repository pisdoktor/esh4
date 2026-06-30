        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-filter me-2 text-primary"></i>Arşiv Filtresi</h5>
                    <button
                        id="archive-filter-toggle"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $archiveFilterExpanded ? '' : ' collapsed' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#archive-filter-collapse"
                        aria-expanded="<?= $archiveFilterExpanded ? 'true' : 'false' ?>"
                        aria-controls="archive-filter-collapse"
                    >
                        <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $archiveFilterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
                    </button>
                </div>
                <div id="archive-filter-collapse" class="collapse<?= $archiveFilterExpanded ? ' show' : '' ?>">
                <form method="GET" action="<?= htmlspecialchars(esh_form_action('Archive', 'index'), ENT_QUOTES, 'UTF-8') ?>" id="archiveFilterForm" class="card-body p-3">
                <?= esh_form_route_hiddens('Archive', 'index') ?>
<div class="row g-3 align-items-start">
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="small fw-bold text-muted text-uppercase">İsim Baş Harfi</label>
                            <select name="isim" class="form-select form-select-sm">
                                <option value="">Tümü</option>
                                <?php foreach($alfabe as $alp): ?>
                                    <option value="<?= $alp ?>" <?= ($filters['isim'] == $alp) ? 'selected' : '' ?>><?= $alp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="small fw-bold text-muted text-uppercase">Soyisim Baş Harfi</label>
                            <select name="soyisim" class="form-select form-select-sm">
                                <option value="">Tümü</option>
                                <?php foreach($alfabe as $alp): ?>
                                    <option value="<?= $alp ?>" <?= ($filters['soyisim'] == $alp) ? 'selected' : '' ?>><?= $alp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-xl-6">
                            <label class="small fw-bold text-muted mb-2 text-uppercase">Bölgeler / Mahalleler</label>
                            <div class="form-check mb-2 border-bottom pb-2">
                                <input class="form-check-input" type="checkbox" id="masterCheck">
                                <label class="form-check-label fw-bold text-primary small" for="masterCheck">TÜMÜNÜ SEÇ</label>
                            </div>
                            <div class="mahalle-scroll-container esh-archive-mahalle-scroll">
    <?php 
    $seciliMahalleler = is_array($filters['mahalle'] ?? []) ? $filters['mahalle'] : [];
    
    // $locations: [ 'İlçe Adı' => ['name' => '...', 'mahalleler' => [...] ] ]
    foreach ($locations as $ilceId => $data): 
        $ilceAdi = $data['name'];
        $mahalleler = $data['mahalleler'];
        
        // Bu ilçeye ait mahalle ID'lerini alıyoruz
        $ilceMahalleIdleri = array_column($mahalleler, 'id');
        
        // Seçili mahalleler ile bu ilçenin mahallelerini karşılaştır
        $hasSelected = array_intersect($ilceMahalleIdleri, $seciliMahalleler);
        $isAllChecked = (count($hasSelected) === count($mahalleler) && count($mahalleler) > 0);
    ?>
        <div class="ilce-group mb-1">
            <div class="ilce-header d-flex align-items-center bg-light p-2 border rounded cursor-pointer shadow-sm">
                <input class="form-check-input ilce-master-check me-2" type="checkbox" 
                       data-target="ilce-box-<?= $ilceId ?>" 
                       id="ilce-<?= $ilceId ?>"
                       <?= $isAllChecked ? 'checked' : '' ?>>
                
                <label class="fw-bold small mb-0 flex-grow-1 cursor-pointer" for="ilce-<?= $ilceId ?>">
                    <?= $ilceAdi ?>
                </label>

                <i class="fa-solid <?= !empty($hasSelected) ? 'fa-chevron-up' : 'fa-chevron-down' ?> text-muted small ms-2 toggle-icon" 
                   style="cursor:pointer;"
                   data-bs-toggle="collapse" 
                   data-bs-target="#ilce-box-<?= $ilceId ?>"></i>
            </div>

            <div class="collapse p-2 bg-white border border-top-0 rounded-bottom <?= !empty($hasSelected) ? 'show' : '' ?>" 
                 id="ilce-box-<?= $ilceId ?>">
                
                <?php foreach ($mahalleler as $m): ?>
                    <div class="form-check">
                        <input class="form-check-input mahalle-check" 
                               type="checkbox" 
                               name="mahalle[]" 
                               value="<?= $m['id'] ?>" 
                               id="m-<?= $m['id'] ?>"
                               <?= in_array($m['id'], $seciliMahalleler) ? 'checked' : '' ?>>
                        <label class="form-check-label small cursor-pointer" for="m-<?= $m['id'] ?>">
                            <?= $m['adi'] ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12 col-xl-2 d-grid gap-2">
                            <button type="submit" class="btn btn-primary w-100 shadow">
                                <i class="fa-solid fa-magnifying-glass me-2"></i>Sorgula
                            </button>
                            <a href="<?= htmlspecialchars(esh_url('Archive', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm w-100">Filtreleri Temizle</a>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
