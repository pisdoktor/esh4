<?php
/** @var string $activeTab */
$tabs = [
    'ozet' => ['label' => 'Özet', 'icon' => 'fa-gauge-high'],
    'tablolar' => ['label' => 'Tablolar', 'icon' => 'fa-table'],
    'bakim' => ['label' => 'Bakım', 'icon' => 'fa-screwdriver-wrench'],
    'yedekler' => ['label' => 'Yedekler', 'icon' => 'fa-cloud-arrow-down'],
    'geri' => ['label' => 'Geri yükle', 'icon' => 'fa-rotate-left'],
];
$baseUrl = esh_url('DbMaintenance', 'index');
?>
<ul class="nav nav-tabs mb-3 flex-wrap esh-db-maint-tabs" role="tablist">
    <?php foreach ($tabs as $tabKey => $tabMeta): ?>
        <?php
        $href = $baseUrl;
        if ($tabKey !== 'ozet') {
            $href .= (strpos($href, '?') !== false ? '&' : '?') . 'tab=' . rawurlencode($tabKey);
        }
        ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= $activeTab === $tabKey ? ' active' : '' ?><?= $tabKey === 'geri' ? ' text-danger' : '' ?>"
               href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
               role="tab"
               aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                <i class="fa-solid <?= htmlspecialchars($tabMeta['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($tabMeta['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
