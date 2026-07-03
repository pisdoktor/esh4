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
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-house-chimney-user text-primary me-2 opacity-75"></i>Hasta / bakım veren portalı</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">TC + kayıtlı telefon ile oturum; yaklaşan planlar, ziyaret özeti ve SMS bilgilendirme onayı.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $patientPortalFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-bridge text-secondary me-2 opacity-75"></i>ESYS / AHBS köprüsü</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Dosya tabanlı JSON dışa/içe aktarma ve gelecekteki API köprüsü ayarları.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $esysBridgeFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-heart-pulse text-info me-2 opacity-75"></i>USBS / e-Nabız köprüsü</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">İzlem bildirimi JSON dışa/içe aktarma, otomatik kuyruk ve gelecekteki API köprüsü ayarları.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $usbsBridgeFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-diagram-project text-primary me-2 opacity-75"></i>Federasyon / çok bölgeli SaaS</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Bölge grupları, düğüm referansı, dosya köprüsü ve merkezi güncelleme manifest hazırlığı.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $federationFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-brands fa-vuejs text-success me-2 opacity-75"></i>Modern frontend (kademeli)</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Vue 3 pilot bileşenleri — dashboard ve plan listesi. Mevcut sayfalar korunur; npm/Composer isteğe bağlı (<code>frontend/modern</code>).</p>
                    <div class="row g-3">
                        <?php $operationalFields = $modernFrontendFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-doctor text-danger me-2 opacity-75"></i>Klinik karar desteği</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Braden, İTAKİ, Harizmi ve MNA skorlarına göre otomatik uyarılar; yüksek riskli izlenmeyen hasta listesi.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $clinicalDecisionFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-clipboard-list text-secondary me-2 opacity-75"></i>İşlem günlüğü (KVKK)</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Denetim kayıtlarının saklama süresi. Süper yönetici «Eski kayıtları temizle» ile uygular.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $auditLogFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>

