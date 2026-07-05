            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-mobile-screen-button text-success me-2 opacity-75"></i>Saha ziyareti (mobil)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Saha personeli izlem formlarında GPS konum kaydı ve çevrimdışı günlük plan önbelleği.
                    </p>
                    <div class="row g-3">
                        <?php $operationalFields = $fieldVisitFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
