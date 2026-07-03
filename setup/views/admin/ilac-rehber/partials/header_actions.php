        <div class="card-header bg-success d-flex flex-wrap justify-content-between align-items-center gap-2 text-white">
            <h5 class="mb-0"><i class="fa-solid fa-pills me-2"></i>İlaç rehberi — veri aktarımı</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars(esh_url('IlacRehber', 'about'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-circle-info me-1"></i>Rehber hakkında
                </a>
                <button type="button" class="btn btn-light btn-sm" id="startBtn">
                    <i class="fa-solid fa-play me-1"></i>Scrape başlat
                </button>
                <button type="button" class="btn btn-danger btn-sm d-none" id="stopBtn">
                    <i class="fa-solid fa-stop me-1"></i>Durdur
                </button>
            </div>
        </div>