            <?php
            $eshAdrestanimCanManageIlce = $eshAdrestanimCanManageIlce ?? \App\Helpers\AuthHelper::sessionIsSuperAdmin();
            $eshAdrestanimCanManageBolge = $eshAdrestanimCanManageBolge ?? $eshAdrestanimCanManageIlce;
            $eshAdrestanimPreselectBolgeId = $eshAdrestanimPreselectBolgeId ?? '';
            ?>
            <input type="hidden" id="adrestanim-can-manage-bolge" value="<?= $eshAdrestanimCanManageBolge ? '1' : '0' ?>">
            <input type="hidden" id="adrestanim-can-manage-ilce" value="<?= $eshAdrestanimCanManageIlce ? '1' : '0' ?>">
            <input type="hidden" id="adrestanim-preselect-bolge-id" value="<?= htmlspecialchars($eshAdrestanimPreselectBolgeId ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="overflow-x-auto">
            <div class="row g-3 flex-nowrap" style="min-width: 56rem;">
                <?php
                $cols = [
                    'bolge' => 'Bölgeler',
                    'ilce' => 'İlçeler',
                    'mahalle' => 'Mahalleler',
                    'sokak' => 'Sokak / Cadde',
                    'kapino' => 'Kapı no',
                ];
                foreach ($cols as $key => $label):
                    $disabled = ($key === 'bolge') ? '' : ' disabled';
                    $canAdd = ($key === 'bolge' && $eshAdrestanimCanManageBolge)
                        || ($key === 'ilce' && $eshAdrestanimCanManageIlce)
                        || !in_array($key, ['bolge', 'ilce'], true);
                ?>
                <div class="col">
                    <div class="hier-column">
                        <div class="hier-header">
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($canAdd): ?>
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
                                   autocomplete="off"<?= $key !== 'bolge' ? ' disabled' : '' ?>>
                        </div>
                        <div id="box-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="hier-list">
                            <?php if ($key !== 'bolge'): ?>
                                <div class="empty-msg">Üst birim seçiniz…</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            </div>
