                                            <div class="ekip-box">
                                                <?php if ($eIdx > 1): ?>
                                                    <i class="fa fa-times remove-ekip" title="Kaldır" onclick="jQuery(this).closest('.ekip-box').remove(); if (typeof updateDisabledOptions === 'function') updateDisabledOptions(<?= (int) $vKey; ?>);"></i>
                                                <?php endif; ?>
                                                <div class="small fw-semibold mb-1"><?= (int) $eIdx; ?>. Ekip</div>
                                                <select name="ekipler[<?= (int) $vKey; ?>][<?= (int) $eIdx; ?>][]" class="form-select form-select-sm esh-tomselect ekip-select" multiple data-placeholder="Personel seçin">
                                                    <?php foreach ($users as $u):
                                                        $sel = (!empty($secili_dizi[$vKey][$eIdx]) && in_array((string) $u->id, array_map('strval', $secili_dizi[$vKey][$eIdx]), true)) ? ' selected' : '';
                                                        ?>
                                                        <option value="<?= (int) $u->id; ?>"<?= $sel; ?>><?= htmlspecialchars((string) $u->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
