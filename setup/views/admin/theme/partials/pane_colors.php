                    <div class="tab-pane fade" id="esh-pane-colors" role="tabpanel">
                        <p class="text-muted small mb-2">
                            Tema paleti (<code>--mr-*</code>, <code>--au-*</code>, <code>--fluent-*</code> vb.; <code>--esh-ui-*</code> hariç — onlar <strong>Sayfa standardı</strong> sekmesinde).
                            Sağ panelde anında önizlenir; jeton adı satır ipucunda.
                        </p>
                        <?php if ($colorTokens === []): ?>
                            <div class="alert alert-warning mb-0">Renk jetonu yok.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="esh-color-token-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nerede / ne işe yarar</th>
                                            <th style="width:3.5rem;">Önizleme</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($colorTokenGroups as $groupName => $groupTokens): ?>
                                            <tr class="esh-token-group-row">
                                                <td colspan="3" class="small fw-semibold text-secondary py-2">
                                                    <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                            <?php foreach ($groupTokens as $token): ?>
                                                <?php
                                                $varName = (string) $token['name'];
                                                $varValue = (string) $token['value'];
                                                $pickerHex = $token['picker_hex'] ?? null;
                                                $colorLabel = trim((string) ($token['label'] ?? ''));
                                                if ($colorLabel === '') {
                                                    $colorLabel = $varName;
                                                }
                                                ?>
                                                <tr data-var-name="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>" data-token-kind="color" title="<?= htmlspecialchars($varName, ENT_QUOTES, 'UTF-8') ?>">
                                                    <td class="small"><?= htmlspecialchars($colorLabel, ENT_QUOTES, 'UTF-8') ?></td>
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
                        <?php endif; ?>
                    </div>

