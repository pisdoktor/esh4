            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="resume" checked>
                <label class="form-check-label" for="resume">Kaldığı yerden devam (checkpoint)</label>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="fresh">
                <label class="form-check-label" for="fresh">Sıfırdan başla (checkpoint sil)</label>
            </div>
            <div class="small text-muted mb-2" id="checkpointLine">Checkpoint: —</div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="insecure_ssl">
                <label class="form-check-label" for="insecure_ssl">SSL doğrulamasını kapat (yalnızca geliştirme)</label>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="truncate">
                <label class="form-check-label text-danger" for="truncate">
                    <strong>Tabloları temizle (TRUNCATE)</strong> — <code>esh_rehber_*</code> verileri silinir
                </label>
            </div>