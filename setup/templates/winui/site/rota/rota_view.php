<div class="fluent-page fluent-rota fluent-rota-shell planning-wrapper fluent-layer-card bg-white p-3 border rounded-3 shadow-sm esh-page-rota">
    <div class="row mb-3 align-items-center g-2">
        <div class="col-md-4">
            <h6 class="fw-bold text-secondary mb-0">
                <i class="fa fa-route me-2 text-primary"></i>ROTA PLANLAMASI
            </h6>
        </div>
        <div class="col-md-8">
            <div class="d-flex justify-content-md-end align-items-center gap-2">
    <label for="dateSelect" class="small fw-bold text-muted mb-0">Tarih:</label>
    <div class="input-group input-group-sm shadow-sm" style="width: 160px;">
        <span class="input-group-text bg-white border-end-0 text-muted" style="cursor: pointer;" onclick="$('#routeDatePicker').focus();">
            <i class="fa fa-calendar-day"></i>
        </span>
        <input type="text" 
               id="routeDatePicker" 
               class="form-control border-start-0 ps-0 datepicker" 
               style="font-size: 0.85rem; font-weight: 500; cursor: pointer;" 
               value="<?php echo isset($_GET['date']) ? date('d-m-Y', strtotime($_GET['date'])) : date('d-m-Y'); ?>" 
               readonly>
    </div>
    
    <div class="btn-group shadow-sm">
        <?php 
            $currentDate = $_GET['date'] ?? date('Y-m-d');
            $prevDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        ?>
        <a href="<?= htmlspecialchars(esh_url('Dashboard', 'showRoute', ['date' => $prevDate]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm" title="Önceki Gün">
            <i class="fa fa-chevron-left small"></i>
        </a>
        <a href="<?= htmlspecialchars(esh_url('Dashboard', 'showRoute', ['date' => date('Y-m-d')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm small fw-bold">
            Bugün
        </a>
        <a href="<?= htmlspecialchars(esh_url('Dashboard', 'showRoute', ['date' => $nextDate]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm" title="Sonraki Gün">
            <i class="fa fa-chevron-right small"></i>
        </a>
    </div>
</div>
        </div>
    </div>

    <div class="text-center mb-3">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-bold" style="font-size: 0.9rem;">
            <i class="fa fa-calendar-day me-2"></i><?php echo $data['tarih_basligi']; ?>
        </span>
    </div>

    <ul class="nav nav-tabs nav-fill esh-route-tabs border-bottom-0 gap-2" id="routeTabs" role="tablist">
        <?php
        $tabs = [
            0 => ['id' => 'sabah', 'label' => 'Sabah', 'icon' => 'fa-sun', 'mod' => 'sabah', 'color' => '#f59e0b'],
            1 => ['id' => 'ogle', 'label' => 'Öğle', 'icon' => 'fa-cloud-sun', 'mod' => 'ogle', 'color' => '#3b82f6'],
            2 => ['id' => 'aksam', 'label' => 'Akşam', 'icon' => 'fa-moon', 'mod' => 'aksam', 'color' => '#6366f1'],
        ];
        foreach ($tabs as $key => $t):
            $v_verisi = $data['tum_vardiya_verisi'][$key] ?? [];
            $hasta_sayisi = 0;
            if (!empty($v_verisi)) {
                foreach ($v_verisi as $e) {
                    $hasta_sayisi += count($e['hastalar']);
                }
            }
            $isActive = ($key === 0);
            $badgeClass = $hasta_sayisi > 0 ? 'esh-route-tab-badge--on' : 'esh-route-tab-badge--empty';
            $badgeClass .= ' esh-route-tab-badge--' . $t['mod'];
        ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link esh-route-tab esh-route-tab--<?php echo $t['mod']; ?> <?php echo $isActive ? 'active' : ''; ?>"
                        id="<?php echo $t['id']; ?>-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#<?php echo $t['id']; ?>_pane"
                        type="button"
                        role="tab"
                        aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>">
                    <span class="esh-route-tab__inner">
                        <i class="fa <?php echo $t['icon']; ?> esh-route-tab__icon" aria-hidden="true"></i>
                        <span class="esh-route-tab__label"><?php echo $t['label']; ?></span>
                        <span class="badge rounded-pill esh-route-tab-badge <?php echo $badgeClass; ?>"><?php echo (int) $hasta_sayisi; ?></span>
                    </span>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content border rounded bg-light-subtle shadow-sm" style="margin-top: -1px; min-height: 400px;">
        <?php foreach($tabs as $key => $t): 
            $isActive = ($key === 0);
        ?>
            <div class="tab-pane fade <?php echo $isActive ? 'show active' : ''; ?> p-3" 
                 id="<?php echo $t['id']; ?>_pane" 
                 role="tabpanel" 
                 aria-labelledby="<?php echo $t['id']; ?>-tab">
                
                <?php if(empty($data['tum_vardiya_verisi'][$key])): ?>
                    <div class="text-center py-5 bg-white rounded border border-dashed">
                        <i class="fa fa-calendar-times fa-3x mb-3 text-muted opacity-25"></i>
                        <p class="text-muted small fw-bold">Bu vardiya için planlanmış bir ziyaret bulunmamaktadır.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach($data['tum_vardiya_verisi'][$key] as $eID => $eData): ?>
                            <div class="col-xl-6 col-lg-12">
                                <?php include 'ekip_karti.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!empty($data['ai_analiz'])): ?>
    <div class="alert alert-secondary border-0 shadow-sm mb-4">
        <h5 class="fw-bold text-dark mb-3">
            <i class="fa-solid fa-microchip me-2 text-primary"></i>Akıllı Operasyon Analizi
        </h5>
        <div class="row">
            <?php foreach ($data['ai_analiz'] as $uyari): ?>
                <?php
                $tipRaw = (string) ($uyari['tip'] ?? 'secondary');
                $tipAllowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'];
                $tip = in_array($tipRaw, $tipAllowed, true) ? $tipRaw : 'secondary';
                $ekipEsc = htmlspecialchars((string) ($uyari['ekip'] ?? ''), ENT_QUOTES, 'UTF-8');
                $mesajEsc = htmlspecialchars((string) ($uyari['mesaj'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-md-6 mb-2">
                    <div class="p-2 border-start border-4 border-<?php echo $tip; ?> bg-white small shadow-sm">
                        <span class="badge bg-<?php echo $tip; ?> mb-1"><?php echo $ekipEsc; ?></span><br>
                        <?php echo $mesajEsc; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div id="map" style="height: 550px; width: 100%; border-radius: 8px; margin-top: 20px; border:1px solid #ccc;"></div>

<?php require ROOT_PATH . '/views/site/rota/rota_map_bootstrap.php'; ?>