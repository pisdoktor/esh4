            <?php $operationalUseSectionPrefix = true; ?>
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
                    <p class="small text-muted mb-3">Denetim kayıtlarının saklama süresi. <?= htmlspecialchars(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_SUPERADMIN), ENT_QUOTES, 'UTF-8') ?> «Eski kayıtları temizle» ile uygular.</p>
                    <div class="row g-3">
                        <?php $operationalFields = $auditLogFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
