                        <div class="col-lg-4 vardiya-grubu" data-vardiya-id="<?= (int) $vKey; ?>">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header text-white py-3" style="background:<?= htmlspecialchars($vVal['color'], ENT_QUOTES, 'UTF-8'); ?>;">
                                    <i class="fa <?= htmlspecialchars($vVal['icon'], ENT_QUOTES, 'UTF-8'); ?> me-1"></i><?= htmlspecialchars($vVal['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="card-body" id="vardiya-container-<?= (int) $vKey; ?>" style="background:<?= htmlspecialchars($vVal['bg'], ENT_QUOTES, 'UTF-8'); ?>;">
                                    <label class="form-label small fw-bold">Başlangıç saati</label>
                                    <input type="time" name="saatler[<?= (int) $vKey; ?>]" class="form-control form-control-sm mb-2" value="<?= htmlspecialchars($display_time, ENT_QUOTES, 'UTF-8'); ?>">
                                    <hr class="my-2">
                                    <div class="ekip-listesi">
                                        <?php for ($eIdx = 1; $eIdx <= $vardiya_ekip_sayilari[$vKey]; $eIdx++): ?>
<?php include __DIR__ . '/ekip_member_row.php'; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm w-100 mt-2" data-esh-call="ekipEkle" data-esh-call-arg="<?= (int) $vKey; ?>">Ekip ekle</button>
                                </div>
                            </div>
                        </div>
