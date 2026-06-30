            <?php $nobetSelectedCount = count($nobetAllowedUnvanlar); ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-calendar-week text-primary me-2 opacity-75"></i>Nöbet modülü — geçerli ünvanlar
                    </h5>
                    <?php if ($nobetUnvanChoices !== []): ?>
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle esh-nobet-selected-badge">
                            <?= (int) $nobetSelectedCount ?> / <?= count($nobetUnvanChoices) ?> seçili
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="esh-nobet-intro text-muted small mb-4">
                        <strong class="text-body">Nöbet havuzu.</strong>
                        Seçilen ünvanlara sahip aktif personel nöbet planı listesinde yer alır; otomatik dağıtım ve yıllık istatistikler bu listeyi kullanır.
                        Her seçili ünvan için günlük kaç nöbetçi atanacağını belirleyin.
                        Personel «İzin/Mazeret» ekranına da yalnızca bu ünvanlar erişir.
                        Kayıt: <code><?= htmlspecialchars($appSettingsPath, ENT_QUOTES, 'UTF-8') ?></code> → <code>nobet.allowed_unvanlar</code>, <code>nobet.unvan_slots</code>.
                        Varsayılan şablon: <code><?= htmlspecialchars($operationalDefaultsPath, ENT_QUOTES, 'UTF-8') ?></code>
                    </div>
                    <?php if ($nobetUnvanChoices === []): ?>
                        <div class="alert alert-warning small mb-0">Ünvan listesi yüklenemedi.</div>
                    <?php else: ?>
                        <div class="row g-3 g-lg-4 esh-nobet-unvan-grid">
                            <?php foreach ($nobetUnvanChoices as $uCode => $uLabel): ?>
                                <?php
                                $uCodeStr = (string) $uCode;
                                $checked = in_array($uCodeStr, $nobetAllowedUnvanlar, true);
                                $fieldId = 'nobet-unvan-' . preg_replace('/[^a-z0-9_-]+/i', '-', $uCodeStr);
                                $slotVal = (int) ($nobetUnvanSlots[$uCodeStr] ?? 1);
                                if ($slotVal < 1) {
                                    $slotVal = 1;
                                }
                                ?>
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="esh-nobet-unvan-field">
                                        <label class="esh-nobet-unvan-field__head" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                                            <input class="form-check-input esh-nobet-unvan-field__check" type="checkbox"
                                                   name="nobet[allowed_unvanlar][]"
                                                   value="<?= htmlspecialchars($uCodeStr, ENT_QUOTES, 'UTF-8') ?>"
                                                   id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= $checked ? 'checked' : '' ?>>
                                            <span class="esh-nobet-unvan-field__body">
                                                <span class="esh-nobet-unvan-field__title"><?= htmlspecialchars((string) $uLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="esh-nobet-unvan-field__meta">
                                                    Kod: <code><?= htmlspecialchars($uCodeStr, ENT_QUOTES, 'UTF-8') ?></code>
                                                </span>
                                            </span>
                                        </label>
                                        <div class="esh-nobet-unvan-field__slot">
                                            <label class="esh-nobet-unvan-field__slot-label" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-slot">Günlük nöbetçi</label>
                                            <input type="number"
                                                   class="form-control form-control-sm esh-nobet-unvan-field__slot-input"
                                                   name="nobet[unvan_slots][<?= htmlspecialchars($uCodeStr, ENT_QUOTES, 'UTF-8') ?>]"
                                                   id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>-slot"
                                                   value="<?= $slotVal ?>"
                                                   min="1"
                                                   max="20"
                                                   step="1"
                                                   inputmode="numeric"
                                                   aria-label="<?= htmlspecialchars((string) $uLabel, ENT_QUOTES, 'UTF-8') ?> günlük nöbetçi sayısı">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="esh-nobet-footnote small text-muted mt-4 mb-0">
                            <i class="fa-solid fa-wand-magic-sparkles me-1 text-primary opacity-75"></i>
                            <strong>Otomatik dağıtım:</strong> her gün için seçili ünvanların «Günlük nöbetçi» değeri kadar personel atanır (1–20 arası).
                            Varsayılan şablonda hemşire 2, tekniker 1; diğer ünvanlar 1 nöbetçi ile planlanır.
                            Hiç ünvan seçilmezse kayıt reddedilir; boş veya geçersiz JSON dosyasında varsayılan <strong>hemşire + tekniker</strong> kullanılır.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
