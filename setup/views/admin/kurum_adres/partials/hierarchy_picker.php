            <div class="row g-3 mb-4">
                <?php
                $cols = [
                    'bolge' => 'Bölgeler',
                    'ilce' => 'İlçeler',
                    'mahalle' => 'Mahalleler',
                    'sokak' => 'Sokak / Cadde',
                ];
                foreach ($cols as $key => $label):
                    $disabled = ($key === 'bolge') ? '' : ' disabled';
                ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="hier-column border rounded-4 bg-white overflow-hidden h-100">
                        <div class="hier-header px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-semibold"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="px-2 py-2 border-bottom">
                            <input type="search" class="form-control form-control-sm esh-kurum-adres-search"
                                   data-tip="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> ara…"
                                   autocomplete="off"<?= $disabled ?>>
                        </div>
                        <div id="box-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="hier-list" style="max-height:320px;overflow-y:auto">
                            <?php if ($key !== 'bolge'): ?>
                                <div class="empty-msg text-muted small p-3">Üst birim seçiniz…</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
