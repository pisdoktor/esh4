            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="site">Kaynak site</label>
                    <input class="form-control form-control-sm" id="site" value="<?= htmlspecialchars((string) $defaults['site'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="sleep_ms">İstek arası bekleme (ms)</label>
                    <input type="number" class="form-control form-control-sm" id="sleep_ms" min="50" max="10000" value="<?= (int) $defaults['sleep_ms'] ?>">
                    <div class="form-text">Varsayılan 50 ms. Daha düşük hızlıdır; rate limit riski artar.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="progress_every">İlerleme log sıklığı</label>
                    <input type="number" class="form-control form-control-sm" id="progress_every" min="0" max="5000" value="<?= (int) $defaults['progress_every'] ?>">
                    <div class="form-text">0 = kapalı</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="discovery">Keşif modu</label>
                    <select class="form-select form-select-sm" id="discovery">
                        <option value="both"<?= $defaults['discovery'] === 'both' ? ' selected' : '' ?>>both (tohum + id)</option>
                        <option value="seeds"<?= $defaults['discovery'] === 'seeds' ? ' selected' : '' ?>>seeds</option>
                        <option value="ids"<?= $defaults['discovery'] === 'ids' ? ' selected' : '' ?>>ids</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="id_max">ID tarama üst sınırı</label>
                    <input type="number" class="form-control form-control-sm" id="id_max" min="1" max="500000" value="<?= (int) $defaults['id_max'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="id_empty_stop">Ardışık boş ID durdurma</label>
                    <input type="number" class="form-control form-control-sm" id="id_empty_stop" min="1" max="5000" value="<?= (int) $defaults['id_empty_stop'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="limit_etken">Limit etken (test)</label>
                    <input type="number" class="form-control form-control-sm" id="limit_etken" min="0" max="500000" value="<?= (int) $defaults['limit_etken'] ?>">
                    <div class="form-text">0 = sınırsız</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="limit_ilac">Limit ilaç (test)</label>
                    <input type="number" class="form-control form-control-sm" id="limit_ilac" min="0" max="500000" value="<?= (int) $defaults['limit_ilac'] ?>">
                </div>
            </div>