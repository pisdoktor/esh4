            <div id="progressArea" class="mb-3 d-none">
                <div class="d-flex justify-content-between mb-1 small fw-bold">
                    <span>Durum: <span id="statusText">Hazır</span></span>
                    <span id="percentText">%0</span>
                </div>
                <div class="progress mb-2" style="height: 12px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="small text-muted" id="phaseDetail"></div>
            </div>

            <p class="small fw-semibold text-muted mb-2">İşlem günlüğü</p>
            <div class="border rounded bg-light p-2 small font-monospace" style="max-height: 360px; overflow-y: auto;" id="logBox">
                <div class="text-muted">Senkron başlayınca adımlar burada listelenir.</div>
            </div>

            <p class="small text-muted mb-0 mt-3">
                <a href="<?= htmlspecialchars(esh_url('AdresFetch', 'tree'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none me-3">
                    <i class="fa-solid fa-sitemap me-1"></i>Adres ağacı →
                </a>
                <a href="<?= htmlspecialchars(esh_url('AdresFetch', 'tarama'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Eksik ilçe taraması →
                </a>
            </p>