                    <div class="tab-pane fade" id="esh-pane-other" role="tabpanel">
                        <p class="text-muted small">Köşe yarıçapı, gölge, animasyon, <code>var()</code> referansları ve body efekt kuralları (tipografi ayrı sekmede).</p>

                        <?php if ($otherVarTokens !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted mt-2">Diğer CSS değişkenleri</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0" id="esh-other-var-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Değişken</th>
                                            <th>Tür</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($otherVarTokens as $token): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $token['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="other-var" data-var-name="<?= htmlspecialchars((string) $token['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td><code class="small"><?= htmlspecialchars((string) $token['name'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td><?= $eshThemeHintBadge((string) ($token['hint'] ?? 'jeton')) ?></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm esh-token-value font-monospace" value="<?= htmlspecialchars((string) $token['value'], ENT_QUOTES, 'UTF-8') ?>" spellcheck="false" autocomplete="off">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($otherPropEntries !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted">Body efektleri</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="esh-other-prop-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Konum</th>
                                            <th>Tür</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($otherPropEntries as $entry): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="other-prop" data-token-property="<?= htmlspecialchars((string) $entry['property'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td class="small"><code><?= htmlspecialchars((string) $entry['label'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td><?= $eshThemeHintBadge((string) ($entry['hint'] ?? 'jeton')) ?></td>
                                                <td>
                                                    <textarea class="form-control form-control-sm esh-token-value font-monospace" rows="2" spellcheck="false"><?= htmlspecialchars((string) $entry['value'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (!$hasOther): ?>
                            <div class="alert alert-warning mb-0">Diğer düzenlenebilir girdi yok.</div>
                        <?php endif; ?>
                    </div>
                </div>
