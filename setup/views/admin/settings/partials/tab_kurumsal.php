            <?php
            $kurumsalFormOrnekAd = '';
            $kurumId = \App\Helpers\KurumCorporateSettings::readKurumId();
            if ($kurumId !== null && $kurumId > 0) {
                $kurumsalFormOrnekAd = \App\Helpers\KurumCorporateSettings::displayName($kurumId);
            }
            ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Kurumsal görünüm</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-info border-0 small mb-0">
                                Form üst başlıklarında <code>{KURUMADI}</code> yer tutucusu kurum adı ile değiştirilir.
                                <?php if ($kurumsalFormOrnekAd !== ''): ?>
                                    Örnek: <strong><?= htmlspecialchars($kurumsalFormOrnekAd, ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php $operationalFields = $corporateFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
