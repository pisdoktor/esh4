                    <div class="tab-pane fade" id="esh-pane-typography" role="tabpanel">
                        <p class="text-muted small mb-2">
                            Yazı ailesi ve boyutlar: <code>--esh-ui-font-*</code> (sayfa standardı) ve tema <code>body</code> tipografi kuralları.
                            Değişiklikler iframe’de ve oturum önizlemesinde anında yansır.
                        </p>
                        <div class="esh-theme-typo-preview mb-3 p-3 border rounded bg-white" id="esh-typo-live-preview" aria-live="polite">
                            <div class="esh-typo-preview-heading mb-1">Başlık önizlemesi</div>
                            <div class="esh-typo-preview-lead text-muted mb-0">Alt açıklama ve gövde metni örneği — tipografi jetonlarını burada görürsünüz.</div>
                        </div>

                        <?php if ($eshUiTypographyTokens !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted mt-2">Sayfa standardı (<code>--esh-ui-*</code>)</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0" id="esh-typography-esh-ui-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nerede / ne işe yarar</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eshUiTypographyTokens as $token): ?>
                                            <?php
                                            $varName = (string) ($token['name'] ?? '');
                                            $varValue = (string) ($token['value'] ?? '');
                                            $missing = !empty($token['missing_in_file']);
                                            $eshUiTypoLabel = trim((string) ($token['label'] ?? ''));
                                            if ($eshUiTypoLabel === '') {
                                                $eshUiTypoLabel = $varName;
                                            }
                                            ?>
                                            <tr data-token-id="<?= htmlspecialchars('var:' . $varName, ENT_QUOTES, 'UTF-8') ?>" data-token-kind="esh-ui-typography" data-var-name="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>"<?= $missing ? ' class="table-warning"' : '' ?>>
                                                <td class="small"><?= htmlspecialchars($eshUiTypoLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm esh-token-value font-monospace" value="<?= htmlspecialchars($varValue, ENT_QUOTES, 'UTF-8') ?>" spellcheck="false" autocomplete="off">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($typographyVarTokens !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted mt-2">Tema tipografi değişkenleri</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0" id="esh-typography-var-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Değişken</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($typographyVarTokens as $token): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $token['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="typography-var" data-var-name="<?= htmlspecialchars((string) $token['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td><code class="small"><?= htmlspecialchars((string) $token['name'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm esh-token-value font-monospace" value="<?= htmlspecialchars((string) $token['value'], ENT_QUOTES, 'UTF-8') ?>" spellcheck="false" autocomplete="off">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($typographyPropEntries !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted">Body tipografi kuralları</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="esh-typography-prop-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Konum</th>
                                            <th>Özellik</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($typographyPropEntries as $entry): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="typography-prop" data-token-property="<?= htmlspecialchars((string) $entry['property'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td class="small"><code><?= htmlspecialchars((string) $entry['label'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td><code class="small"><?= htmlspecialchars((string) $entry['property'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td>
                                                    <textarea class="form-control form-control-sm esh-token-value font-monospace" rows="2" spellcheck="false"><?= htmlspecialchars((string) $entry['value'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (!$hasTypography): ?>
                            <div class="alert alert-warning mb-0">Tipografi girdisi yok.</div>
                        <?php endif; ?>
                    </div>

