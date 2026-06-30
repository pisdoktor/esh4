                    <div class="tab-pane fade" id="esh-pane-gradients" role="tabpanel">
                        <p class="text-muted small">Gradyan CSS değişkenleri ve <code>body</code> arka plan gradyan satırları. Uzun değerler metin kutusunda düzenlenir.</p>

                        <?php if ($gradientVarTokens !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted mt-2">Gradyan değişkenleri</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0" id="esh-gradient-var-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Değişken</th>
                                            <th style="width:5rem;">Önizleme</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gradientVarTokens as $token): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $token['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="gradient-var">
                                                <td><code class="small"><?= htmlspecialchars((string) $token['name'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td><span class="d-block rounded border esh-gradient-swatch" style="height:2rem;background:<?= htmlspecialchars((string) $token['value'], ENT_QUOTES, 'UTF-8') ?>;"></span></td>
                                                <td>
                                                    <textarea class="form-control form-control-sm esh-token-value font-monospace" rows="2" spellcheck="false"><?= htmlspecialchars((string) $token['value'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($gradientPropEntries !== []): ?>
                            <h6 class="small fw-bold text-uppercase text-muted">Body arka plan gradyanları</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="esh-gradient-prop-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Konum</th>
                                            <th style="width:5rem;">Önizleme</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gradientPropEntries as $entry): ?>
                                            <tr data-token-id="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>" data-token-kind="gradient-prop" data-token-property="<?= htmlspecialchars((string) $entry['property'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td class="small"><code><?= htmlspecialchars((string) $entry['label'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                                <td><span class="d-block rounded border esh-gradient-swatch" style="height:2rem;background:<?= htmlspecialchars((string) $entry['value'], ENT_QUOTES, 'UTF-8') ?>;"></span></td>
                                                <td>
                                                    <textarea class="form-control form-control-sm esh-token-value font-monospace" rows="3" spellcheck="false"><?= htmlspecialchars((string) $entry['value'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (!$hasGradients): ?>
                            <div class="alert alert-warning mb-0">Gradyan satırı yok.</div>
                        <?php endif; ?>
                    </div>

