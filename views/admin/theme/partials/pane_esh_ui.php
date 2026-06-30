                    <div class="tab-pane fade show active" id="esh-pane-esh-ui" role="tabpanel">
                        <p class="text-muted small mb-2">
                            Ortak sayfa dili: <code>esh-page</code> + <code>--esh-ui-*</code>
                            (<code>esh-page-language.css</code>).
                            Her satırda <strong>nerede / ne işe yarar</strong> açıklaması vardır; teknik jeton adı kayıt için arka planda tutulur (satır üzerine gelince ipucu).
                        </p>
                        <?php if (!$eshUiBridgePresent): ?>
                            <div class="alert alert-warning py-2 small">
                                <strong>theme.css içinde <code>--esh-ui-text</code> köprüsü bulunamadı.</strong>
                                Kayıt sırasında düzenlediğiniz jetonlar gövde seçicisine eklenir.
                                Önerilen blok:
                                <pre class="small bg-white border rounded p-2 mt-2 mb-0 overflow-auto" style="max-height:10rem;"><?= htmlspecialchars($eshUiBridgeSuggestion, ENT_QUOTES, 'UTF-8') ?></pre>
                            </div>
                        <?php elseif ($eshUiMissingCount > 0): ?>
                            <div class="alert alert-info py-2 small mb-2">
                                <?= (int) $eshUiMissingCount ?> jeton dosyada henüz tanımlı değil; kayıt ile <code>theme.css</code> gövde kuralına yazılır.
                            </div>
                        <?php endif; ?>
                        <?php if ($eshUiModuleFilters !== []): ?>
                        <div class="esh-esh-ui-module-strip-wrap mb-2" id="esh-esh-ui-module-strip-wrap">
                            <div class="esh-esh-ui-module-strip" id="esh-esh-ui-module-strip" role="tablist" aria-label="Sayfa standardı modülleri">
                                <?php foreach ($eshUiModuleFilters as $moduleFilter): ?>
                                    <?php
                                    $moduleId = (string) ($moduleFilter['id'] ?? '');
                                    $moduleLabel = (string) ($moduleFilter['label'] ?? $moduleId);
                                    $moduleCount = (int) ($moduleFilter['count'] ?? 0);
                                    $isAll = $moduleId === 'all';
                                    ?>
                                    <button type="button"
                                            class="btn btn-sm esh-esh-ui-module-pill<?= $isAll ? ' active' : '' ?>"
                                            role="tab"
                                            aria-selected="<?= $isAll ? 'true' : 'false' ?>"
                                            data-esh-ui-module="<?= htmlspecialchars($moduleId, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?>
                                        <span class="badge rounded-pill esh-esh-ui-module-pill__badge"><?= $moduleCount ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="esh-esh-ui-var-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nerede / ne işe yarar</th>
                                        <th style="width:3.5rem;">Önizleme</th>
                                        <th>Değer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eshUiTokenGroups as $groupName => $groupTokens): ?>
                                        <?php
                                        $eshUiModule = \App\Helpers\EshUiTokenCatalog::moduleForGroup((string) $groupName);
                                        ?>
                                        <tr class="esh-token-group-row"
                                            data-esh-ui-group="<?= htmlspecialchars((string) $groupName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-esh-ui-module="<?= htmlspecialchars($eshUiModule, ENT_QUOTES, 'UTF-8') ?>">
                                            <td colspan="3" class="small fw-semibold text-secondary py-2">
                                                <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        </tr>
                                        <?php foreach ($groupTokens as $token): ?>
                                            <?php
                                            $varName = (string) ($token['name'] ?? '');
                                            $varValue = (string) ($token['value'] ?? '');
                                            $pickerHex = $token['picker_hex'] ?? null;
                                            $missing = !empty($token['missing_in_file']);
                                            $tokenModule = (string) ($token['module'] ?? $eshUiModule);
                                            ?>
                                            <?php
                                            $eshUiLabel = trim((string) ($token['label'] ?? ''));
                                            if ($eshUiLabel === '') {
                                                $eshUiLabel = $varName;
                                            }
                                            ?>
                                            <tr data-token-id="<?= htmlspecialchars('var:' . $varName, ENT_QUOTES, 'UTF-8') ?>" data-token-kind="esh-ui" data-var-name="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>" data-esh-ui-module="<?= htmlspecialchars($tokenModule, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>"<?= $missing ? ' class="table-warning"' : '' ?>>
                                                <td class="small"><?= htmlspecialchars($eshUiLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if ($pickerHex !== null): ?>
                                                        <input type="color" class="form-control form-control-color esh-color-picker" value="<?= htmlspecialchars($pickerHex, ENT_QUOTES, 'UTF-8') ?>" title="Renk seçici">
                                                    <?php else: ?>
                                                        <span class="d-inline-block rounded border esh-color-swatch" style="width:2rem;height:2rem;background:<?= htmlspecialchars($varValue, ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm esh-token-value font-monospace" value="<?= htmlspecialchars($varValue, ENT_QUOTES, 'UTF-8') ?>" spellcheck="false" autocomplete="off">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

