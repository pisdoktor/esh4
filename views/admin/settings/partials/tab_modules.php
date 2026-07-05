            <?php if (is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'kurum'): ?>
                <div class="alert alert-info small mb-3">
                    Modül aç/kapa durumu yalnızca <strong><?= htmlspecialchars((string) $settingsScopeBanner['label'], ENT_QUOTES, 'UTF-8') ?></strong> kurumuna uygulanır.
                    Tanımlı olmayan modüller bölge veya platform varsayılanını kullanır.
                </div>
            <?php elseif (is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'bolge'): ?>
                <div class="alert alert-primary small mb-3">
                    Modül aç/kapa durumu <strong><?= htmlspecialchars((string) $settingsScopeBanner['label'], ENT_QUOTES, 'UTF-8') ?></strong> bölgesinin varsayılanı olarak kaydedilir.
                    Belirli bir kurum için üst menüden kurum seçin.
                </div>
            <?php elseif (\App\Helpers\AuthHelper::sessionIsSuperAdmin() && !\App\Helpers\AuthHelper::sessionIsPlatformOwner() && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'denied'): ?>
                <div class="alert alert-warning small mb-3">
                    Kurum veya bölge kapsamı seçilmedi — kayıt yapılamaz. Üst menüden kurum seçin veya bölge atanmış <?= htmlspecialchars(mb_strtolower(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_SUPERADMIN), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?> olarak giriş yapın.
                </div>
            <?php elseif (\App\Helpers\AuthHelper::sessionIsPlatformOwner() && is_array($settingsScopeBanner) && ($settingsScopeBanner['mode'] ?? '') === 'platform'): ?>
                <div class="alert alert-secondary small mb-3">
                    Kurum veya bölge filtresi seçili değil — kayıtlar platform geneli varsayılan modül listesine yazılır.
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1" for="eshSettingsModuleFilter">Modül ara</label>
                <input type="search" class="form-control form-control-sm" id="eshSettingsModuleFilter"
                       placeholder="Ad veya açıklama ile filtrele…" autocomplete="off" data-esh-module-filter="1">
            </div>
            <p class="small text-muted mb-3">
                Nöbet, harita, UHDS, SMS, KPS, e-imza, misafir ve gelişmiş özellikler sekmelerinde modül durumu üstte yönetilir; burada yalnızca kalan modüller listelenir.
            </p>
            <?php foreach ($groupOrder as $groupKey): ?>
                <?php
                if (empty($grouped[$groupKey])) {
                    continue;
                }
                $visibleMods = array_filter($grouped[$groupKey], static function (array $mod): bool {
                    return !\App\Helpers\SettingsNavCatalog::isModuleOnDedicatedTab((string) ($mod['key'] ?? ''));
                });
                if ($visibleMods === []) {
                    continue;
                }
                ?>
                <div class="card shadow-sm border-0 mb-4 esh-settings-module-group" data-esh-module-group="<?= htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars(\App\Helpers\AppSettings::groupLabel($groupKey), ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($grouped[$groupKey] as $mod): ?>
                                <?php
                                $mKey = (string) ($mod['key'] ?? '');
                                if (\App\Helpers\SettingsNavCatalog::isModuleOnDedicatedTab($mKey)) {
                                    continue;
                                }
                                $mEnabled = !empty($mod['enabled']);
                                $mDefault = !empty($mod['default']);
                                $mLabel = (string) ($mod['label'] ?? $mKey);
                                $mDesc = (string) ($mod['description'] ?? '');
                                $mToggleLocked = !\App\Helpers\AppSettings::canToggleModuleInAdminScope($mKey, is_array($settingsScopeBanner) ? $settingsScopeBanner : null);
                                ?>
                                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 esh-settings-module-row"
                                    data-mod-label="<?= htmlspecialchars(mb_strtolower($mLabel . ' ' . $mDesc, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="flex-grow-1" style="min-width: 12rem;">
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($mod['label'] ?? $mKey), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars((string) ($mod['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($mToggleLocked): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border mt-1">Bölge / platform kapsamında yönetilir</span>
                                        <?php elseif (!$mDefault): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border mt-1">Varsayılan: kapalı</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="modules[<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>]"
                                               id="mod-<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>"
                                               value="1"
                                               <?= $mEnabled ? 'checked' : '' ?>
                                               <?= $mToggleLocked ? 'disabled' : '' ?>>
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
