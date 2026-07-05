            <?php
            $activeMapProvider = $activeMapProvider ?? \App\Helpers\OperationalSettings::activeMapProviderStatusForAdmin();
            $mapProviderConfigured = $mapProviderConfigured ?? \App\Helpers\MapRoutingGeocodeHelper::isActiveProviderConfigured();
            $keyStatus = $keyStatus ?? \App\Services\MapRouting\MapRoutingProviderFactory::keyStatusForProvider($activeMapProvider['code'] ?? 'tomtom');
            $providerLabel = (string) ($quota['provider_label'] ?? $activeMapProvider['label'] ?? 'Harita');
            ?>
            <?php if (empty($mapProviderConfigured)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                Aktif sağlayıcı (<?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?>) için API anahtarı tanımlı değil.
                <code><?= htmlspecialchars((string) ($keyStatus['config_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                veya ortam değişkeni <code><?= htmlspecialchars((string) ($keyStatus['env_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                ile <code>config/config.local.php</code> içinde ayarlayın.
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Koordinatsız kapı</div>
                        <div class="fs-4 fw-bold text-danger" id="statMissing"><?= number_format((int) ($missingCount ?? 0), 0, ',', '.') ?></div>
                        <div class="small text-muted">/ <?= number_format((int) ($totalKapino ?? 0), 0, ',', '.') ?> kapı</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Koordinatlı kapı</div>
                        <div class="fs-4 fw-bold text-success" id="statHasCoords"><?= number_format((int) ($hasCoordsCount ?? 0), 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Bugünkü <?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?> kullanımı</div>
                        <div class="fs-4 fw-bold" id="statQuotaUsed"><?= (int) ($quota['used'] ?? 0) ?></div>
                        <div class="small text-muted">/ <?= (int) ($quota['limit'] ?? 2500) ?> (<?= htmlspecialchars((string) ($quota['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Bugün kalan sorgu</div>
                        <div class="fs-4 fw-bold text-primary" id="statQuotaRemaining"><?= (int) ($quota['remaining'] ?? 0) ?></div>
                    </div>
                </div>
            </div>