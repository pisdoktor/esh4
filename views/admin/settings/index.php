<?php

declare(strict_types=1);

/**
 * @var array<string, list<array<string, mixed>>> $grouped
 * @var list<string> $groupOrder
 * @var string $settingsToken
 * @var string $pageTitle
 * @var string $activeTab
 * @var string $activeTabLead
 * @var list<array<string, mixed>> $islemIdRows
 * @var string $islemCatalogPath
 * @var string $appSettingsPath
 * @var string $operationalDefaultsPath
 * @var list<array<string, mixed>> $mapFields
 * @var list<array<string, mixed>> $planningFields
 * @var list<array<string, mixed>> $durationFields
 * @var list<array<string, mixed>> $corporateFields
 * @var list<array<string, mixed>> $publicLookupFields
 * @var list<array<string, mixed>> $maintenanceFields
 * @var list<array<string, mixed>> $debugFields
 * @var array{configured:bool,masked:string,source:string} $tomtomStatus
 * @var list<array{label:string,value:string,hint:string}> $eimzaInfoRows
 * @var list<array<string, mixed>> $kpsFields
 * @var array{configured:bool,username:string,masked_password:string,updated_at:string} $kpsCredentialStatus
 * @var array{configured:bool,value:string,source:string} $kpsFirmaStatus
 * @var string $configLocalExample
 * @var list<string> $nobetAllowedUnvanlar
 * @var array<string, int> $nobetUnvanSlots
 * @var array<string, string> $nobetUnvanChoices
 * @var list<object> $islemler
 * @var list<string> $allowedSettingsTabs
 * @var array<string, list<array{key:string,label:string,icon:string,description:string,badge?:string}>> $settingsNavGrouped
 * @var list<array{key:string,label:string,icon:string,description:string,href:string,category:string,badge?:string}> $overviewCards
 * @var array{mode:string,label:string,hint:string,kurum_id?:int,bolge_id?:int}|null $settingsScopeBanner
 */

use App\Helpers\AuthHelper;
use App\Helpers\SettingsNavCatalog;

$groupOrder = $groupOrder ?? SettingsNavCatalog::MODULE_GROUP_ORDER;
$activeTab = $activeTab ?? 'modules';
$pageTitle = $pageTitle ?? 'Uygulama ayarları';
$activeTabLead = $activeTabLead ?? '';
$islemIdRows = $islemIdRows ?? [];
$islemler = $islemler ?? [];
$islemCatalogPath = $islemCatalogPath ?? 'public/assets/data/islem-idleri.json';
$appSettingsPath = $appSettingsPath ?? 'public/assets/data/app-settings.json';
$operationalDefaultsPath = $operationalDefaultsPath ?? 'config/operational-settings.defaults.json';
$configLocalExample = $configLocalExample ?? 'config/config.local.example.php';
$tomtomStatus = $tomtomStatus ?? ['configured' => false, 'masked' => '', 'source' => ''];
$eimzaInfoRows = $eimzaInfoRows ?? [];
$kpsFields = $kpsFields ?? [];
$kpsCredentialStatus = $kpsCredentialStatus ?? ['configured' => false, 'username' => '', 'masked_password' => '', 'updated_at' => ''];
$kpsFirmaStatus = $kpsFirmaStatus ?? ['configured' => false, 'value' => '', 'source' => ''];
$nobetAllowedUnvanlar = $nobetAllowedUnvanlar ?? [];
$nobetUnvanSlots = $nobetUnvanSlots ?? [];
$nobetUnvanChoices = $nobetUnvanChoices ?? [];
$settingsScopeBanner = $settingsScopeBanner ?? null;
$settingsSaveAllowed = $settingsSaveAllowed ?? true;
$allowedSettingsTabs = $allowedSettingsTabs ?? SettingsNavCatalog::tabsForRole();
$settingsNavGrouped = $settingsNavGrouped ?? SettingsNavCatalog::navGroupedForRole();
$overviewCards = $overviewCards ?? [];
$settingsPageModifier = $settingsPageModifier ?? 'esh-page-settings--extended';
$showSaveButton = $showSaveButton ?? ($activeTab !== 'overview' && $settingsSaveAllowed);

$activeTabLabel = SettingsNavCatalog::tabLabel($activeTab);

$selectedCsv = static function (string $csvValue): array {
    $csvValue = trim($csvValue);
    if ($csvValue === '') {
        return [];
    }
    $out = [];
    foreach (preg_split('/\s*,\s*/', $csvValue, -1, PREG_SPLIT_NO_EMPTY) as $p) {
        $i = (int) $p;
        if ($i > 0) {
            $out[] = $i;
        }
    }

    return $out;
};

?>
<div class="esh-page esh-page--list esh-page-settings <?= htmlspecialchars($settingsPageModifier, ENT_QUOTES, 'UTF-8') ?> container-fluid py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>">Yönetim paneli</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(SettingsNavCatalog::tabUrl('modules'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php if ($activeTab !== 'modules'): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($activeTabLabel, ENT_QUOTES, 'UTF-8') ?></li>
            <?php else: ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-sliders text-primary me-2"></i><?= htmlspecialchars($activeTab === 'modules' ? $pageTitle : $activeTabLabel, ENT_QUOTES, 'UTF-8') ?>
            </h3>
            <?php if ($activeTabLead !== ''): ?>
                <p class="text-muted mb-0 small"><?= htmlspecialchars($activeTabLead, ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <p class="text-muted mb-0 small"><?= AuthHelper::sessionIsPlatformOwner() ? 'Modül, saha, iletişim ve platform ayarları.' : 'Kurumunuza özel modül, planlama ve operasyonel ayarlar.' ?></p>
            <?php endif; ?>
        </div>
        <?php if (AuthHelper::sessionIsPlatformOwner() && $activeTab !== 'overview'): ?>
            <a href="<?= htmlspecialchars(SettingsNavCatalog::tabUrl('overview'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-gauge-high me-1"></i>Genel bakış
            </a>
        <?php endif; ?>
    </div>

    <?php if (is_array($settingsScopeBanner) && ($settingsScopeBanner['label'] ?? '') !== '' && in_array($activeTab, SettingsNavCatalog::KURUM_SCOPE_BANNER_TABS, true)): ?>
        <?php
        $scopeMode = (string) ($settingsScopeBanner['mode'] ?? '');
        $scopeAlert = match ($scopeMode) {
            'kurum' => 'alert-info',
            'bolge' => 'alert-primary',
            'denied' => 'alert-warning',
            default => 'alert-secondary',
        };
        ?>
        <div class="alert <?= htmlspecialchars($scopeAlert, ENT_QUOTES, 'UTF-8') ?> small mb-3">
            <strong><?= match ($scopeMode) {
                'kurum' => 'Kurum kapsamı:',
                'bolge' => 'Bölge kapsamı:',
                'denied' => 'Kayıt yapılamaz:',
                default => 'Kayıt hedefi:',
            } ?></strong>
            <?= htmlspecialchars((string) $settingsScopeBanner['label'], ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($settingsScopeBanner['hint'])): ?>
                <span class="d-block mt-1 mb-0"><?= htmlspecialchars((string) $settingsScopeBanner['hint'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-4 esh-settings-layout">
        <div class="col-lg-3">
            <?php include __DIR__ . '/partials/sidebar_nav.php'; ?>
        </div>
        <div class="col-lg-9">
            <?php if ($activeTab === 'overview'): ?>
                <?php include __DIR__ . '/partials/tab_overview.php'; ?>
            <?php else: ?>
            <form method="post" action="<?= htmlspecialchars(esh_url('Settings', 'save'), ENT_QUOTES, 'UTF-8') ?>" class="esh-settings-form" data-esh-settings-form="1">
                <input type="hidden" name="settings_token" value="<?= htmlspecialchars($settingsToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="active_tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (SettingsNavCatalog::tabHasDedicatedModules($activeTab)): ?>
                    <?php
                    $moduleSwitchTab = $activeTab;
                    include __DIR__ . '/partials/section_tab_module_switches.php';
                    ?>
                <?php endif; ?>
                <?php if ($activeTab === 'modules'): ?>
<?php include __DIR__ . '/partials/tab_modules.php'; ?>
                <?php elseif ($activeTab === 'islem_ids'): ?>
<?php include __DIR__ . '/partials/tab_islem_ids.php'; ?>
                <?php elseif ($activeTab === 'harita'): ?>
<?php include __DIR__ . '/partials/tab_harita.php'; ?>
                <?php elseif ($activeTab === 'vardiya'): ?>
<?php include __DIR__ . '/partials/tab_vardiya.php'; ?>
                <?php elseif ($activeTab === 'planlama'): ?>
<?php include __DIR__ . '/partials/tab_planlama.php'; ?>
                <?php elseif ($activeTab === 'saha'): ?>
<?php include __DIR__ . '/partials/tab_saha.php'; ?>
                <?php elseif ($activeTab === 'uhds'): ?>
<?php include __DIR__ . '/partials/tab_uhds.php'; ?>
                <?php elseif ($activeTab === 'kurumsal'): ?>
<?php include __DIR__ . '/partials/tab_kurumsal.php'; ?>
                <?php elseif ($activeTab === 'nobet'): ?>
<?php include __DIR__ . '/partials/tab_nobet.php'; ?>
                <?php elseif ($activeTab === 'sms'): ?>
<?php include __DIR__ . '/partials/tab_sms.php'; ?>
                <?php elseif ($activeTab === 'bakim'): ?>
<?php include __DIR__ . '/partials/tab_bakim.php'; ?>
                <?php elseif ($activeTab === 'misafir'): ?>
<?php include __DIR__ . '/partials/tab_misafir.php'; ?>
                <?php elseif ($activeTab === 'entegrasyon'): ?>
<?php include __DIR__ . '/partials/tab_entegrasyon.php'; ?>
                <?php elseif ($activeTab === 'gelismis'): ?>
<?php include __DIR__ . '/partials/tab_gelismis.php'; ?>
                <?php elseif ($activeTab === 'kps'): ?>
<?php include __DIR__ . '/partials/tab_kps.php'; ?>
                <?php elseif ($activeTab === 'eimza'): ?>
<?php include __DIR__ . '/partials/tab_eimza.php'; ?>
                <?php endif; ?>
                <div class="esh-settings-savebar sticky-bottom mt-4">
                    <div class="d-flex justify-content-end gap-2 py-3 px-3 px-lg-0 bg-body border-top border-lg-0">
                        <a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" data-esh-settings-leave="1">Vazgeç</a>
                        <?php if ($showSaveButton): ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
