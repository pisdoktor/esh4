            <?php $operationalUseSectionPrefix = true; ?>
            <div class="card shadow-sm border-0 mb-4 border-warning">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-screwdriver-wrench text-warning me-2"></i>Bakım modu</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Açıkken yalnızca <strong><?= htmlspecialchars(mb_strtolower(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></strong> erişebilir; bölge yöneticisi, kurum yöneticisi, personel ve misafir sayfaları kapanır.
                        Giriş sayfası görünür kalır; <?= htmlspecialchars(mb_strtolower(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?> dışı oturum açma reddedilir.
                    </p>
                    <details class="small text-muted mb-3">
                        <summary class="cursor-pointer">Teknik kayıt yolu</summary>
                        <code class="d-block mt-1"><?= htmlspecialchars($appSettingsPath, ENT_QUOTES, 'UTF-8') ?> → maintenance</code>
                    </details>
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
