            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">İşlem id eşlemeleri</h5>
                </div>
                <div class="card-body">
                    <div class="esh-islem-id-intro text-muted small mb-4">
                        Tanımlar <code><?= htmlspecialchars($islemCatalogPath, ENT_QUOTES, 'UTF-8') ?></code> dosyasından okunur.
                        <?php if (is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'kurum'): ?>
                            Liste seçenekleri kurumunuza atanmış işlemlerden gelir (<code>esh_kurum_islem</code>).
                            Eşleme değerleri kurum kaydına (<code>esh_kurumlar.ayarlar_json</code> → <code>islem_ids</code>) yazılır.
                        <?php elseif (is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'bolge'): ?>
                            Liste seçenekleri platform işlem kataloğundan gelir (<code>esh_islemler</code>).
                            Eşleme değerleri bölge kaydına (<code>esh_federation_regions.ayarlar_json</code> → <code>islem_ids</code>) yazılır.
                        <?php elseif (\App\Helpers\AuthHelper::sessionIsPlatformOwner() && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'platform'): ?>
                            Liste seçenekleri platform işlem kataloğundan gelir (<code>esh_islemler</code>, kurum_id=0).
                            Seçimler platform varsayılanı olarak <code><?= htmlspecialchars($appSettingsPath, ENT_QUOTES, 'UTF-8') ?></code> içindeki <code>islem_ids</code> alanına yazılır.
                        <?php elseif (\App\Helpers\AuthHelper::sessionIsSuperAdmin()): ?>
                            Liste seçenekleri kuruma atanmış işlemlerden gelir (<code>esh_kurum_islem</code>).
                            Seçimler kurum veya bölge kaydına yazılır.
                        <?php else: ?>
                            Liste seçenekleri kurumunuza atanmış işlemlerden gelir (<code>esh_kurum_islem</code>).
                            Eşleme değerleri kurum kaydına kaydedilir; tanımlı olmayan alanlar platform varsayılanını kullanır.
                        <?php endif; ?>
                    </div>
                    <?php if ($islemler === [] && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'kurum'): ?>
                        <div class="alert alert-warning small mb-4">
                            Bu kuruma henüz işlem atanmamış. Önce
                            <a href="<?= htmlspecialchars(esh_url('Islem', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="alert-link">İşlem seçimi</a>
                            ekranından platform kataloğundan atama yapın; ardından burada semantik eşlemeleri tanımlayın.
                        </div>
                    <?php endif; ?>
                    <?php if ($islemIdRows === []): ?>
                        <div class="alert alert-warning small mb-0">İşlem id tanım dosyası bulunamadı veya boş.</div>
                    <?php else: ?>
                        <div class="row g-3 g-lg-4 esh-islem-id-grid">
                            <?php foreach ($islemIdRows as $row): ?>
                                <?php
                                $iKey = (string) ($row['key'] ?? '');
                                $iType = (string) ($row['type'] ?? 'int');
                                $iValue = (string) ($row['value'] ?? '');
                                $iDefault = (string) ($row['default'] ?? '');
                                $fieldId = 'islem-id-' . preg_replace('/[^a-z0-9_-]+/i', '-', $iKey);
                                $isCsv = $iType === 'csv';
                                ?>
                                <div class="col-12 col-lg-6">
                                    <div class="esh-islem-id-field">
                                        <div class="esh-islem-id-field__head">
                                            <label class="form-label fw-semibold mb-0" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars((string) ($row['label'] ?? $iKey), ENT_QUOTES, 'UTF-8') ?>
                                            </label>
                                            <span class="badge rounded-pill esh-islem-id-field__type <?= $isCsv ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                                                <?= $isCsv ? 'Çoklu' : 'Tekil' ?>
                                            </span>
                                        </div>
                                        <?php if ((string) ($row['description'] ?? '') !== ''): ?>
                                            <p class="esh-islem-id-field__desc small text-muted mb-0"><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <div class="esh-tomselect-field mt-2">
                                            <?php if ($isCsv): ?>
                                                <?php $picked = $selectedCsv($iValue); ?>
                                                <select class="form-select esh-tomselect" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                                        name="islem_ids[<?= htmlspecialchars($iKey, ENT_QUOTES, 'UTF-8') ?>][]"
                                                        multiple data-placeholder="İşlem seçin…">
                                                    <?php foreach ($islemler as $islem): ?>
                                                        <?php
                                                        $optId = (int) ($islem->id ?? 0);
                                                        if ($optId < 1) {
                                                            continue;
                                                        }
                                                        $optLabel = trim((string) ($islem->islemadi ?? ''));
                                                        ?>
                                                        <option value="<?= $optId ?>"<?= in_array($optId, $picked, true) ? ' selected' : '' ?>>
                                                            <?= htmlspecialchars($optId . ' — ' . ($optLabel !== '' ? $optLabel : '?'), ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <?php $min = array_key_exists('min', $row) ? (int) $row['min'] : 1; ?>
                                                <select class="form-select esh-tomselect" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                                        name="islem_ids[<?= htmlspecialchars($iKey, ENT_QUOTES, 'UTF-8') ?>]"
                                                        data-placeholder="İşlem seçin…">
                                                    <?php if ($min > 0 && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'kurum'): ?>
                                                        <option value=""<?= trim($iValue) === '' || (int) $iValue < 1 ? ' selected' : '' ?>>— Seçin —</option>
                                                    <?php endif; ?>
                                                    <?php if ($min === 0): ?>
                                                        <option value="0"<?= (int) $iValue === 0 ? ' selected' : '' ?>>0 — ön seçim yok</option>
                                                    <?php endif; ?>
                                                    <?php foreach ($islemler as $islem): ?>
                                                        <?php
                                                        $optId = (int) ($islem->id ?? 0);
                                                        if ($optId < 1) {
                                                            continue;
                                                        }
                                                        if ($min > 0 && $optId < $min) {
                                                            continue;
                                                        }
                                                        $optLabel = trim((string) ($islem->islemadi ?? ''));
                                                        ?>
                                                        <option value="<?= $optId ?>"<?= (int) $iValue === $optId ? ' selected' : '' ?>>
                                                            <?= htmlspecialchars($optId . ' — ' . ($optLabel !== '' ? $optLabel : '?'), ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                        <div class="esh-islem-id-field__meta text-muted">
                                            JSON varsayılan: <code><?= htmlspecialchars($iDefault !== '' ? $iDefault : '—', ENT_QUOTES, 'UTF-8') ?></code>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

