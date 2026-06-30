            <?php
            $vardiyaSlots = $vardiyaSlots ?? \App\Helpers\OperationalSettings::vardiyaSlotsForAdmin();
            $vardiyaLabels = ['1' => 'Sabah', '2' => 'Öğle', '3' => 'Akşam'];
            $vardiyaIcons = ['1' => 'fa-sun', '2' => 'fa-cloud-sun', '3' => 'fa-moon'];
            $vardiyaActiveCount = 0;
            foreach ($vardiyaSlots as $slot) {
                if (!empty($slot['active'])) {
                    $vardiyaActiveCount++;
                }
            }
            ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-clock text-primary me-2 opacity-75"></i>Zaman dilimleri (vardiya)
                    </h5>
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                        <?= (int) $vardiyaActiveCount ?> / 3 aktif
                    </span>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-4">
                        <strong class="text-body">Kurum vardiyaları.</strong>
                        Aktif dilimler randevu/izlem formlarında, Dashboard «Günün planı», rota ve ekip atamasında görünür.
                        Saat aralığı yeni kayıtlarda otomatik zaman seçimini belirler; ekip başlangıç saati rota planlamasında kullanılır.
                        Pasif vardiyadaki mevcut planlar günün planında listelenmez.
                    </div>
                    <div class="row g-3 g-lg-4">
                        <?php foreach ($vardiyaLabels as $code => $label):
                            $slot = $vardiyaSlots[$code] ?? [];
                            $active = !empty($slot['active']);
                            $hourStart = (int) ($slot['hour_start'] ?? 8);
                            $hourEnd = (int) ($slot['hour_end'] ?? 12);
                            $ekipBaslangic = (string) ($slot['ekip_baslangic'] ?? '09:00');
                            $fieldId = 'vardiya-slot-' . $code;
                            ?>
                            <div class="col-12 col-lg-4">
                                <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                                    <label class="d-flex align-items-start gap-2 mb-3 fw-semibold" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                                        <input class="form-check-input mt-1" type="checkbox"
                                               name="vardiya[slots][<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>][active]"
                                               value="1"
                                               id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                               <?= $active ? 'checked' : '' ?>>
                                        <span>
                                            <i class="fa <?= htmlspecialchars($vardiyaIcons[$code] ?? 'fa-clock', ENT_QUOTES, 'UTF-8') ?> me-1 text-primary"></i>
                                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small mb-1" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-start">Saat başlangıç</label>
                                            <input type="number" class="form-control form-control-sm"
                                                   name="vardiya[slots][<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>][hour_start]"
                                                   id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-start"
                                                   value="<?= $hourStart ?>" min="0" max="23" step="1" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small mb-1" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-end">Saat bitiş</label>
                                            <input type="number" class="form-control form-control-sm"
                                                   name="vardiya[slots][<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>][hour_end]"
                                                   id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-end"
                                                   value="<?= $hourEnd ?>" min="0" max="23" step="1" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small mb-1" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-ekip">Ekip başlangıç saati</label>
                                            <input type="time" class="form-control form-control-sm"
                                                   name="vardiya[slots][<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>][ekip_baslangic]"
                                                   id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-ekip"
                                                   value="<?= htmlspecialchars($ekipBaslangic, ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Rota skor parametreleri</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $planningFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">İzlem süre varsayılanları (dk)</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $durationFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
