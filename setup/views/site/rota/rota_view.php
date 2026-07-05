<div class="planning-wrapper bg-white p-3 border rounded-3 shadow-sm esh-page-rota">
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
        <span class="input-group-text bg-white border-end-0 text-muted" style="cursor: pointer;" data-esh-action="focus" data-esh-focus="#routeDatePicker">
            <i class="fa fa-calendar-day"></i>
        </span>
        <input type="text" 
               id="routeDatePicker" 
               class="form-control border-start-0 ps-0 datepicker" 
               style="font-size: 0.85rem; font-weight: 500; cursor: pointer;" 
               value="<?php
                    $routeDateVal = date('d-m-Y');
                    if (isset($_GET['date']) && is_string($_GET['date']) && $_GET['date'] !== '') {
                        $ts = strtotime($_GET['date']);
                        if ($ts !== false) {
                            $routeDateVal = date('d-m-Y', $ts);
                        }
                    }
                    echo htmlspecialchars($routeDateVal, ENT_QUOTES, 'UTF-8');
               ?>" 
               readonly>
    </div>
    
    <div class="btn-group shadow-sm">
        <?php 
            $currentDate = date('Y-m-d');
            if (isset($_GET['date']) && is_string($_GET['date']) && $_GET['date'] !== '') {
                $ts = strtotime($_GET['date']);
                if ($ts !== false) {
                    $currentDate = date('Y-m-d', $ts);
                }
            }
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
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-bold" style="font-size: 0.9rem;" id="esh-route-date-badge">
            <i class="fa fa-calendar-day me-2"></i><?php echo htmlspecialchars((string) ($data['tarih_basligi'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </div>

    <div id="esh-route-dynamic"
         data-esh-fetch-url="<?= htmlspecialchars($showRouteRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="text-center py-5 text-muted">
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Rota planı yükleniyor…
        </div>
    </div>
</div>

<div id="map" style="height: 550px; width: 100%; border-radius: 8px; margin-top: 20px; border:1px solid #ccc;"></div>

<?php require __DIR__ . '/rota_map_bootstrap.php'; ?>