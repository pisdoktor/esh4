            <?php if (is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'kurum'): ?>
                <div class="alert alert-info small mb-3">
                    Modül aç/kapa durumu yalnızca <strong><?= htmlspecialchars((string) $settingsScopeBanner['label'], ENT_QUOTES, 'UTF-8') ?></strong> kurumuna uygulanır.
                    Tanımlı olmayan modüller platform varsayılanını kullanır.
                </div>
            <?php elseif (\App\Helpers\AuthHelper::sessionIsSuperAdmin() && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'global'): ?>
                <div class="alert alert-secondary small mb-3">
                    Kurum filtresi seçili değil — kayıtlar platform geneli varsayılan modül listesine yazılır.
                    Belirli bir kurum için özelleştirmek üst menüden kurum seçin.
                </div>
            <?php endif; ?>
            <?php foreach ($groupOrder as $groupKey): ?>
                <?php if (empty($grouped[$groupKey])) {
                    continue;
                } ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars(\App\Helpers\AppSettings::groupLabel($groupKey), ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($grouped[$groupKey] as $mod): ?>
                                <?php
                                $mKey = (string) ($mod['key'] ?? '');
                                $mEnabled = !empty($mod['enabled']);
                                $mDefault = !empty($mod['default']);
                                ?>
                                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
                                    <div class="flex-grow-1" style="min-width: 12rem;">
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($mod['label'] ?? $mKey), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars((string) ($mod['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!$mDefault): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border mt-1">Varsayılan: kapalı</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="modules[<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>]"
                                               id="mod-<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>"
                                               value="1"
                                               <?= $mEnabled ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="mod-<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= $mEnabled ? 'Açık' : 'Kapalı' ?>
                                        </label>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>

