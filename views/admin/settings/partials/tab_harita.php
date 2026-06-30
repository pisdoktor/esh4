            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Operasyon merkezi</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Koordinatlar <code><?= htmlspecialchars($appSettingsPath, ENT_QUOTES, 'UTF-8') ?></code> → <code>map</code> bölümüne kaydedilir.
                        Varsayılan şablon: <code><?= htmlspecialchars($operationalDefaultsPath, ENT_QUOTES, 'UTF-8') ?></code>
                    </p>
                    <div class="row g-3">
                        <?php $operationalFields = $mapFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">TomTom API anahtarı</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">API anahtarı güvenlik nedeniyle JSON dosyasına <strong>yazılmaz</strong>. <code><?= htmlspecialchars($configLocalExample, ENT_QUOTES, 'UTF-8') ?></code> içinde <code>tomtom_key</code> tanımlayın veya sunucuda <code>TOMTOM_KEY</code> ortam değişkeni kullanın.</p>
                    <?php if (!empty($tomtomStatus['configured'])): ?>
                        <div class="alert alert-success small mb-0">
                            <i class="fa-solid fa-circle-check me-1"></i> Anahtar yapılandırılmış
                            <?php if ($tomtomStatus['masked'] !== ''): ?>
                                — <code><?= htmlspecialchars($tomtomStatus['masked'], ENT_QUOTES, 'UTF-8') ?></code>
                            <?php endif; ?>
                            <?php if ($tomtomStatus['source'] !== ''): ?>
                                <span class="text-muted">(<?= htmlspecialchars($tomtomStatus['source'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning small mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> TomTom anahtarı bulunamadı. Harita ve adres koordinat özellikleri çalışmayabilir.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

