            <?php $operationalUseSectionPrefix = true; ?>
            <div class="card shadow-sm border-0 mb-4 border-warning">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-screwdriver-wrench text-warning me-2"></i>Bakım modu</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Açıkken yalnızca <strong>süper yönetici</strong> erişebilir; personel, admin ve misafir sayfaları kapanır.
                        Giriş sayfası görünür kalır; süper yönetici dışı oturum açma reddedilir.
                        Kayıt: <code><?= htmlspecialchars($appSettingsPath, ENT_QUOTES, 'UTF-8') ?></code> → <code>maintenance</code>.
                    </p>
                    <div class="row g-3">
                        <?php $operationalFields = $maintenanceFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="alert alert-warning border-0 small mb-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <strong>Hata ayıklama modu</strong> açıkken PHP hataları ve SQL sorgu özeti tarayıcıda görünebilir.
                Canlı (üretim) sunucularda her iki seçeneği de <strong>kapalı</strong> tutun.
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Hata ayıklama</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Öncelik: bu panel → <code>config.local.php</code> → ortam değişkenleri
                        (<code>ESH_DISPLAY_ERRORS</code>, <code>ESH_DB_DEBUG</code>) → varsayılan (kapalı).
                    </p>
                    <div class="row g-3">
                        <?php $operationalFields = $debugFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Kamu hasta TC sorgusu</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $publicLookupFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>

