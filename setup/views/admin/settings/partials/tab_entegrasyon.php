            <?php $operationalUseSectionPrefix = true; ?>
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
