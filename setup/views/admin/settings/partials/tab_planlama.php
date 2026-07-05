            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Rota skor parametreleri</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $planningFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">İzlem süre varsayılanları (dk)</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $durationFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
