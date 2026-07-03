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
                    <h5 class="mb-0 fw-bold">Harita sağlayıcı API anahtarları</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">API anahtarları güvenlik nedeniyle JSON dosyasına <strong>yazılmaz</strong>. <code><?= htmlspecialchars($configLocalExample, ENT_QUOTES, 'UTF-8') ?></code> içinde ilgili anahtarı tanımlayın veya sunucuda ortam değişkeni kullanın.</p>
                    <?php
                    $providerStatuses = $mapProviderStatuses ?? \App\Helpers\OperationalSettings::mapProviderStatusesForAdmin();
                    $activeProvider = $activeMapProvider ?? \App\Helpers\OperationalSettings::activeMapProviderStatusForAdmin();
                    ?>
                    <div class="alert alert-info small">
                        Aktif sağlayıcı: <strong><?= htmlspecialchars((string) ($activeProvider['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if (!empty($activeProvider['configured'])): ?>
                            <span class="text-success">(yapılandırılmış)</span>
                        <?php else: ?>
                            <span class="text-danger">(anahtar eksik)</span>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sağlayıcı</th>
                                    <th>Durum</th>
                                    <th>Anahtar</th>
                                    <th>Kaynak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($providerStatuses as $ps): ?>
                                    <tr<?= (($ps['code'] ?? '') === ($activeProvider['code'] ?? '')) ? ' class="table-primary"' : '' ?>>
                                        <td><?= htmlspecialchars((string) ($ps['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if (!empty($ps['configured'])): ?>
                                                <span class="badge bg-success">Yapılandırılmış</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Eksik</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ps['masked'])): ?>
                                                <code><?= htmlspecialchars((string) $ps['masked'], ENT_QUOTES, 'UTF-8') ?></code>
                                            <?php else: ?>
                                                <code><?= htmlspecialchars((string) ($ps['config_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                                                <span class="text-muted"> / <?= htmlspecialchars((string) ($ps['env_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars((string) ($ps['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (($activeProvider['code'] ?? '') === 'google'): ?>
                        <p class="text-muted small mt-3 mb-0">Google Maps için Cloud Console’da Directions API, Distance Matrix API, Geocoding API ve Maps JavaScript API etkin olmalıdır.</p>
                    <?php endif; ?>
                </div>
            </div>
