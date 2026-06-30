        <div class="card-header bg-dark d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0"><i class="fa-solid fa-location-crosshairs me-2"></i>Adres koordinat bulma (TomTom)</h5>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="startBtn"<?= empty($tomtomConfigured) ? ' disabled' : '' ?>>
                    <i class="fa fa-play me-1"></i> Taramayı başlat
                </button>
                <button type="button" class="btn btn-danger btn-sm d-none" id="stopBtn" disabled>
                    <i class="fa fa-stop me-1"></i> Taramayı durdur
                </button>
            </div>
        </div>