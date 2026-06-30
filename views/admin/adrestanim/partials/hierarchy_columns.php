            <input type="hidden" id="adrestanim-can-manage-ilce" value="<?= $eshAdrestanimCanManageIlce ? '1' : '0' ?>">

            <div class="row g-3">
                <?php
                $cols = [
                    'ilce' => 'İlçeler',
                    'mahalle' => 'Mahalleler',
                    'sokak' => 'Sokak / Cadde',
                    'kapino' => 'Kapı no',
                ];
                foreach ($cols as $key => $label):
                    $disabled = ($key === 'ilce') ? '' : ' disabled';
                ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="hier-column">
                        <div class="hier-header">
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($key !== 'ilce' || $eshAdrestanimCanManageIlce): ?>
                            <button type="button" id="btn-add-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="btn btn-success btn-sm btn-add-tier" data-tier="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"<?= $disabled; ?>>
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="px-2 py-2 border-bottom bg-white">
                            <input type="search" class="form-control form-control-sm search-box"
                                   data-tip="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> ara…"
                                   autocomplete="off">
                        </div>
                        <div id="box-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="hier-list">
                            <?php if ($key !== 'ilce'): ?>
                                <div class="empty-msg">Üst birim seçiniz…</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>