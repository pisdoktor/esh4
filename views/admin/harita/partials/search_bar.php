    <div class="search-bar rounded-top mb-0">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" id="hastAra" class="form-control" placeholder="İsim veya TC No..." autocomplete="off">
                    <button class="btn btn-info btn-round ms-2" id="btnAra" type="button">Ara</button>
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-light btn-round" id="btnSifirla" type="button">Tümünü Göster</button>
            </div>
            <div class="col-md-6 text-md-end">
                <?php
                $activeMapProvider = $activeMapProvider ?? \App\Helpers\OperationalSettings::activeMapProviderStatusForAdmin();
                $providerLabel = (string) ($activeMapProvider['label'] ?? 'Harita');
                $eshHaritaScopeLabel = $eshHaritaScopeLabel ?? 'Tüm bölgeler';
                $eshHaritaPatientCount = (int) ($eshHaritaPatientCount ?? 0);
                ?>
                <h2 class="search-title h5 mb-0">Hasta haritası <span class="badge bg-info ms-1"><?= $eshHaritaPatientCount ?> koordinatlı</span></h2>
                <p class="small text-white-50 mb-0 d-none d-md-block">
                    Küme, ısı, nokta veya hibrit görünüm; filtre ve rota —
                    kapsam: <strong><?= htmlspecialchars($eshHaritaScopeLabel, ENT_QUOTES, 'UTF-8') ?></strong> —
                    harita: <?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>