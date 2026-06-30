    <ul class="nav nav-tabs mb-3 flex-wrap esh-settings-tabs" role="tablist">
        <?php foreach ($settingsTabs as $tabKey => $tabMeta): ?>
            <?php
            if (!in_array($tabKey, $allowedSettingsTabs, true)) {
                continue;
            }
            $defaultTab = 'modules';
            $tabHref = esh_url('Settings', 'index');
            if ($tabKey !== $defaultTab) {
                $tabHref .= (strpos($tabHref, '?') !== false ? '&' : '?') . 'tab=' . rawurlencode($tabKey);
            }
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link<?= $activeTab === $tabKey ? ' active' : '' ?>"
                   href="<?= htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8') ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                    <i class="fa-solid <?= htmlspecialchars($tabMeta['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($tabMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
