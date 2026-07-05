<?php

declare(strict_types=1);

/**
 * @var string $activeTab
 * @var list<string> $allowedSettingsTabs
 * @var array<string, list<array{key:string,label:string,icon:string,description:string,badge?:string}>> $settingsNavGrouped
 */

$activeTab = $activeTab ?? 'modules';
$allowedSettingsTabs = $allowedSettingsTabs ?? [];
$settingsNavGrouped = $settingsNavGrouped ?? \App\Helpers\SettingsNavCatalog::navGroupedForRole();
$categoryLabels = \App\Helpers\SettingsNavCatalog::CATEGORY_LABELS;

$mobileOptions = [];
foreach ($settingsNavGrouped as $categoryKey => $items) {
    foreach ($items as $item) {
        if (!in_array($item['key'], $allowedSettingsTabs, true)) {
            continue;
        }
        $mobileOptions[] = [
            'key' => $item['key'],
            'label' => ($categoryLabels[$categoryKey] ?? '') . ' — ' . ($item['label'] ?? $item['key']),
        ];
    }
}
?>
<nav class="esh-settings-sidebar d-none d-lg-block" aria-label="Ayar sekmeleri">
    <?php foreach ($settingsNavGrouped as $categoryKey => $items): ?>
        <?php if ($items === []) {
            continue;
        } ?>
        <div class="esh-settings-sidebar__group mb-3">
            <div class="esh-settings-sidebar__heading small text-uppercase fw-bold text-muted px-2 mb-2">
                <?= htmlspecialchars($categoryLabels[$categoryKey] ?? $categoryKey, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <ul class="nav nav-pills flex-column gap-1 esh-settings-sidebar__nav" role="tablist">
                <?php foreach ($items as $item):
                    $tabKey = (string) ($item['key'] ?? '');
                    if (!in_array($tabKey, $allowedSettingsTabs, true)) {
                        continue;
                    }
                    $tabHref = \App\Helpers\SettingsNavCatalog::tabUrl($tabKey);
                    ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link d-flex align-items-center justify-content-between gap-2 esh-settings-sidebar__link<?= $activeTab === $tabKey ? ' active' : '' ?>"
                           href="<?= htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8') ?>"
                           data-esh-settings-tab-link="1"
                           role="tab"
                           aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                            <span class="d-flex align-items-center gap-2 min-w-0">
                                <i class="fa-solid <?= htmlspecialchars((string) ($item['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?> opacity-75"></i>
                                <span class="text-truncate"><?= htmlspecialchars((string) ($item['label'] ?? $tabKey), ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle esh-settings-sidebar__badge"><?= htmlspecialchars((string) $item['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</nav>

<div class="esh-settings-mobile-nav d-lg-none mb-3">
    <label class="form-label small fw-semibold mb-1" for="eshSettingsMobileTab">Ayar bölümü</label>
    <select class="form-select form-select-sm" id="eshSettingsMobileTab" data-esh-settings-mobile-tab="1">
        <?php foreach ($mobileOptions as $opt): ?>
            <option value="<?= htmlspecialchars(\App\Helpers\SettingsNavCatalog::tabUrl((string) $opt['key']), ENT_QUOTES, 'UTF-8') ?>"
                    <?= $activeTab === $opt['key'] ? 'selected' : '' ?>>
                <?= htmlspecialchars((string) $opt['label'], ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
