            <?php $operationalUseSectionPrefix = true; ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Kamu hasta TC sorgusu</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Misafir erişimli hasta arama sayfası ayarları.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $publicLookupFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-house-chimney-user text-primary me-2 opacity-75"></i>Hasta portalı ayarları</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Oturum süresi, görünür planlar ve SMS bilgilendirme metni.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $patientPortalFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
