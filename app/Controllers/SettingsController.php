<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\AppSettingsStore;
use App\Helpers\AuthHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\KurumCorporateSettings;
use App\Helpers\OperationalSettings;
use App\Helpers\SettingsNavCatalog;
use App\Helpers\SettingsWriteScope;
use App\Helpers\ThemeViewHelper;
use App\Models\Islem;

/**
 * Uygulama modül / işlem id (süper yönetici) ve kurum operasyonel ayarları (yönetici).
 */
class SettingsController
{
    public function __construct()
    {
        AuthHelper::requireAdmin();
    }

    /** @return list<string> */
    private function allowedTabs(): array
    {
        return SettingsNavCatalog::tabsForRole();
    }

    private function defaultTab(): string
    {
        return SettingsNavCatalog::DEFAULT_TAB;
    }

    private function assertTabAccess(string $tab): void
    {
        if (!in_array($tab, $this->allowedTabs(), true)) {
            $_SESSION['error'] = 'Bu ayar sekmesine erişim yetkiniz bulunmamaktadır.';
            header('Location: ' . $this->indexUrl($this->defaultTab()));
            exit;
        }
    }

    private function ensureToken(): string
    {
        if (empty($_SESSION['app_settings_token'])) {
            $_SESSION['app_settings_token'] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION['app_settings_token'];
    }

    private function verifyToken(): bool
    {
        $t = $_POST['settings_token'] ?? '';

        return is_string($t) && $t !== ''
            && isset($_SESSION['app_settings_token'])
            && hash_equals((string) $_SESSION['app_settings_token'], $t);
    }

    private function activeTab(): string
    {
        $default = $this->defaultTab();
        $raw = $_GET['tab'] ?? $_POST['active_tab'] ?? $default;
        $raw = is_string($raw) ? trim($raw) : $default;
        $tab = SettingsNavCatalog::resolveTab($raw);
        if (!in_array($tab, SettingsNavCatalog::allTabKeys(), true)) {
            return $default;
        }
        if (!in_array($tab, $this->allowedTabs(), true)) {
            return $default;
        }

        return $tab;
    }

    private function indexUrl(string $tab = 'modules'): string
    {
        $tab = SettingsNavCatalog::resolveTab($tab);
        if (!in_array($tab, $this->allowedTabs(), true)) {
            $tab = $this->defaultTab();
        }

        return SettingsNavCatalog::tabUrl($tab);
    }

    public function index(): void
    {
        $rawTab = isset($_GET['tab']) ? trim((string) $_GET['tab']) : '';
        if ($rawTab !== '' && SettingsNavCatalog::isAliasRedirect($rawTab)) {
            header('Location: ' . SettingsNavCatalog::tabUrl(SettingsNavCatalog::resolveTab($rawTab)));
            exit;
        }

        $activeTab = $this->activeTab();
        $this->assertTabAccess($activeTab);

        $modules = AppSettings::allForAdmin();
        $grouped = [];
        foreach ($modules as $row) {
            $g = (string) ($row['group'] ?? 'site');
            $grouped[$g][] = $row;
        }
        $groupOrder = SettingsNavCatalog::MODULE_GROUP_ORDER;

        $settingsToken = $this->ensureToken();
        $pageTitle = AuthHelper::sessionIsPlatformOwner() ? 'Uygulama ayarları' : 'Kurum ayarları';
        $allowedSettingsTabs = $this->allowedTabs();
        $settingsNavGrouped = SettingsNavCatalog::navGroupedForRole();
        $settingsScopeBanner = KurumCorporateSettings::adminScopeBanner();
        $activeTabMeta = SettingsNavCatalog::tabMeta($activeTab);
        $activeTabLead = is_array($activeTabMeta) ? (string) ($activeTabMeta['description'] ?? '') : '';
        $overviewCards = $activeTab === 'overview' ? SettingsNavCatalog::overviewCardsForRole() : [];

        $islemIdRows = [];
        $islemCatalogPath = 'public/assets/data/islem-idleri.json';
        $appSettingsPath = AppSettingsStore::runtimeRelPath();
        $operationalDefaultsPath = 'config/operational-settings.defaults.json';
        $configLocalExample = 'config/config.local.example.php';
        $settingsSaveAllowed = SettingsWriteScope::canSaveTab($activeTab);
        $showSaveButton = !SettingsNavCatalog::isReadOnlyTab($activeTab) && $settingsSaveAllowed;

        $mapFields = [];
        $planningFields = [];
        $durationFields = [];
        $fieldVisitFields = [];
        $uhdsTelehealthFields = [];
        $corporateFields = [];
        $publicLookupFields = [];
        $patientPortalFields = [];
        $esysBridgeFields = [];
        $usbsBridgeFields = [];
        $federationFields = [];
        $modernFrontendFields = [];
        $clinicalDecisionFields = [];
        $auditLogFields = [];
        $maintenanceFields = [];
        $debugFields = [];
        $tomtomStatus = ['configured' => false, 'masked' => '', 'source' => ''];
        $mapProviderStatuses = [];
        $activeMapProvider = [];
        $eimzaInfoRows = [];
        $kpsFields = [];
        $kpsCredentialStatus = ['configured' => false, 'username' => '', 'masked_password' => '', 'updated_at' => ''];
        $kpsFirmaStatus = ['configured' => false, 'value' => '', 'source' => ''];
        $smsFields = [];
        $smsCredentialStatus = ['configured' => false, 'provider' => 'mock', 'api_user' => '', 'masked_password' => '', 'masked_api_key' => '', 'sender_id' => '', 'test_phone' => '', 'updated_at' => ''];
        $smsModuleEnabled = false;
        $smsIsSuperAdmin = AuthHelper::sessionIsPlatformOwner();
        $patientPortalModuleEnabled = false;
        $modernFrontendModuleEnabled = false;
        $nobetAllowedUnvanlar = [];
        $nobetUnvanSlots = [];
        $nobetUnvanChoices = [];
        $vardiyaSlots = [];
        $islemler = [];

        switch ($activeTab) {
            case 'islem_ids':
                $islemIdRows = IslemIdSettings::allForAdmin();
                $islemler = (new Islem())->getListForSettingsPicker();
                break;
            case 'harita':
                $mapFields = OperationalSettings::fieldsForAdmin('map');
                $tomtomStatus = OperationalSettings::tomtomKeyStatusForAdmin();
                $mapProviderStatuses = OperationalSettings::mapProviderStatusesForAdmin();
                $activeMapProvider = OperationalSettings::activeMapProviderStatusForAdmin();
                break;
            case 'vardiya':
                $vardiyaSlots = OperationalSettings::vardiyaSlotsForAdmin();
                break;
            case 'planlama':
                $planningFields = OperationalSettings::fieldsForAdmin('planning');
                $durationFields = OperationalSettings::fieldsForAdmin('durations');
                break;
            case 'saha':
                $fieldVisitFields = OperationalSettings::fieldsForAdmin('field_visit');
                break;
            case 'uhds':
                $uhdsTelehealthFields = OperationalSettings::fieldsForAdmin('uhds_telehealth');
                break;
            case 'kurumsal':
                $corporateFields = OperationalSettings::fieldsForAdmin('corporate');
                break;
            case 'nobet':
                $nobetAllowedUnvanlar = OperationalSettings::nobetAllowedUnvanlarForAdmin();
                $nobetUnvanSlots = OperationalSettings::nobetUnvanSlotsForAdmin();
                $nobetUnvanChoices = \App\Models\User::unvanChoices();
                unset($nobetUnvanChoices['']);
                break;
            case 'sms':
                $smsFields = OperationalSettings::fieldsForAdmin('sms');
                $smsCredentialStatus = \App\Helpers\SmsCredentialsStore::statusForAdmin();
                $smsModuleEnabled = AppSettings::isModuleEnabled('sms_bildirim');
                break;
            case 'bakim':
                $maintenanceFields = OperationalSettings::fieldsForAdmin('maintenance');
                $debugFields = OperationalSettings::fieldsForAdmin('debug');
                break;
            case 'misafir':
                $publicLookupFields = OperationalSettings::fieldsForAdmin('public_hastaarama');
                $patientPortalFields = OperationalSettings::fieldsForAdmin('patient_portal');
                $patientPortalModuleEnabled = AppSettings::isModuleEnabled('patient_portal');
                break;
            case 'entegrasyon':
                $esysBridgeFields = OperationalSettings::fieldsForAdmin('esys_bridge');
                $usbsBridgeFields = OperationalSettings::fieldsForAdmin('usbs_bridge');
                $federationFields = OperationalSettings::fieldsForAdmin('federation');
                break;
            case 'gelismis':
                $modernFrontendFields = OperationalSettings::fieldsForAdmin('modern_frontend');
                $clinicalDecisionFields = OperationalSettings::fieldsForAdmin('clinical_decision_support');
                $auditLogFields = OperationalSettings::fieldsForAdmin('audit_log');
                $modernFrontendModuleEnabled = AppSettings::isModuleEnabled('modern_frontend');
                break;
            case 'kps':
                $kpsFields = OperationalSettings::fieldsForAdmin('kps');
                $kpsCredentialStatus = \App\Helpers\KpsCredentialsStore::statusForAdmin();
                $kpsFirmaStatus = OperationalSettings::kpsFirmaKoduStatusForAdmin();
                break;
            case 'eimza':
                $eimzaInfoRows = OperationalSettings::eimzaInfoRowsForAdmin();
                break;
            default:
                break;
        }

        $settingsPageModifier = match ($activeTab) {
            'islem_ids' => 'esh-page-settings--islem-ids',
            'nobet' => 'esh-page-settings--nobet',
            'overview' => 'esh-page-settings--overview',
            default => 'esh-page-settings--extended',
        };

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'settings/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek veya oturum süresi doldu.';
            header('Location: ' . $this->indexUrl($this->activeTab()));
            exit;
        }

        $activeTab = $this->activeTab();
        $this->assertTabAccess($activeTab);

        if (SettingsNavCatalog::isReadOnlyTab($activeTab)) {
            $_SESSION['error'] = 'Bu sekme salt okunurdur; kayıt yapılmadı.';
            header('Location: ' . $this->indexUrl($activeTab));
            exit;
        }

        if (!SettingsWriteScope::canSaveTab($activeTab)) {
            $_SESSION['error'] = is_array($settingsScopeBanner = KurumCorporateSettings::adminScopeBanner())
                && ($settingsScopeBanner['mode'] ?? '') === 'denied'
                ? (string) ($settingsScopeBanner['hint'] ?? 'Bu ayarları kaydetme yetkiniz bulunmamaktadır.')
                : 'Bu ayarları kaydetme yetkiniz bulunmamaktadır.';
            header('Location: ' . $this->indexUrl($activeTab));
            exit;
        }

        $errors = [];
        $saved = false;

        if ($activeTab === 'islem_ids') {
            $posted = $_POST['islem_ids'] ?? [];
            if (!is_array($posted)) {
                $_SESSION['error'] = 'İşlem id verisi alınamadı.';
                header('Location: ' . $this->indexUrl('islem_ids'));
                exit;
            }
            $result = IslemIdSettings::saveValues($posted);
            if ($result === true) {
                $_SESSION['success'] = 'İşlem id ayarları kaydedildi. Değişiklikler yeni isteklerde geçerlidir.';
                $saved = true;
            } else {
                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';
            }
        } elseif ($activeTab === 'modules') {
            $posted = $_POST['modules'] ?? [];
            if (!is_array($posted)) {
                $_SESSION['error'] = 'Modül verisi alınamadı.';
                header('Location: ' . $this->indexUrl('modules'));
                exit;
            }
            $enabledMap = [];
            foreach (AppSettings::allForAdmin() as $row) {
                $key = (string) ($row['key'] ?? '');
                if ($key === '' || SettingsNavCatalog::isModuleOnDedicatedTab($key)) {
                    continue;
                }
                if (!AppSettings::canToggleModuleInAdminScope($key, KurumCorporateSettings::adminScopeBanner())) {
                    continue;
                }
                $enabledMap[$key] = isset($posted[$key]) && (string) $posted[$key] === '1';
            }
            $result = AppSettings::saveModules($enabledMap);
            if ($result === true) {
                $_SESSION['success'] = 'Modül ayarları kaydedildi.';
                $saved = true;
            } else {
                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';
            }
        } elseif ($activeTab === 'harita') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('harita');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'Harita modülleri kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $result = OperationalSettings::saveSection('map', $posted);
            if ($result === true) {
                $_SESSION['success'] = 'Harita ve merkez ayarları kaydedildi. Koordinatlar yeni isteklerde geçerlidir.';
                $saved = $moduleResult === true;
            } else {
                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';
            }
        } elseif ($activeTab === 'vardiya') {
            $vardiyaPosted = $_POST['vardiya'] ?? [];
            if (!is_array($vardiyaPosted)) {
                $vardiyaPosted = [];
            }
            $result = OperationalSettings::saveVardiyaSettings($vardiyaPosted);
            if ($result === true) {
                $_SESSION['success'] = 'Vardiya ayarları kaydedildi. Yeni isteklerde geçerlidir.';
                $saved = true;
            } else {
                $errors[] = is_string($result) ? $result : 'Vardiya ayarları kaydedilemedi.';
            }
        } elseif ($activeTab === 'planlama') {
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $r1 = OperationalSettings::saveSection('planning', $posted);
            if ($r1 !== true) {
                $errors[] = is_string($r1) ? $r1 : 'Planlama skorları kaydedilemedi.';
            } else {
                $r2 = OperationalSettings::saveSection('durations', $posted);
                if ($r2 === true) {
                    $_SESSION['success'] = 'Rota ve süre ayarları kaydedildi. Yeni isteklerde geçerlidir.';
                    $saved = true;
                } else {
                    $errors[] = is_string($r2) ? $r2 : 'Süre ayarları kaydedilemedi.';
                }
            }
        } elseif ($activeTab === 'saha') {
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $result = OperationalSettings::saveSection('field_visit', $posted);
            if ($result === true) {
                $_SESSION['success'] = 'Saha ziyareti ayarları kaydedildi.';
                $saved = true;
            } else {
                $errors[] = is_string($result) ? $result : 'Saha ziyareti ayarları kaydedilemedi.';
            }
        } elseif ($activeTab === 'uhds') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('uhds');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'UHDS modülü kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $result = OperationalSettings::saveSection('uhds_telehealth', $posted);
            if ($result === true) {
                $_SESSION['success'] = 'UHDS görüntülü görüşme ayarları kaydedildi.';
                $saved = $moduleResult === true;
            } else {
                $errors[] = is_string($result) ? $result : 'UHDS görüntülü görüşme ayarları kaydedilemedi.';
            }
        } elseif ($activeTab === 'kurumsal') {
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $result = OperationalSettings::saveSection('corporate', $posted);
            if ($result === true) {
                $_SESSION['success'] = 'Kurumsal ayarlar kaydedildi.';
                $saved = true;
            } else {
                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';
            }
        } elseif ($activeTab === 'nobet') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('nobet');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'Nöbet modülü kaydedilemedi.';
            }
            $posted = $_POST['nobet'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $result = OperationalSettings::saveNobetAllowedUnvanlar($posted);
            if ($result === true) {
                $_SESSION['success'] = 'Nöbet ayarları kaydedildi.';
                $saved = $moduleResult === true;
            } else {
                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';
            }
        } elseif ($activeTab === 'bakim') {
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rMaintenance = OperationalSettings::saveSection('maintenance', OperationalSettings::postedForSection('maintenance', $posted));
            if ($rMaintenance !== true) {
                $errors[] = is_string($rMaintenance) ? $rMaintenance : 'Bakım modu ayarları kaydedilemedi.';
            } else {
                $rDebug = OperationalSettings::saveSection('debug', OperationalSettings::postedForSection('debug', $posted));
                if ($rDebug === true) {
                    $_SESSION['success'] = 'Bakım ve sistem ayarları kaydedildi.';
                    $saved = true;
                } else {
                    $errors[] = is_string($rDebug) ? $rDebug : 'Hata ayıklama ayarları kaydedilemedi.';
                }
            }
        } elseif ($activeTab === 'misafir') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('misafir');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'Portal modülü kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rPublic = OperationalSettings::saveSection('public_hastaarama', OperationalSettings::postedForSection('public_hastaarama', $posted));
            if ($rPublic !== true) {
                $errors[] = is_string($rPublic) ? $rPublic : 'Kamu sorgu ayarları kaydedilemedi.';
            }
            $rPortal = OperationalSettings::saveSection('patient_portal', OperationalSettings::postedForSection('patient_portal', $posted));
            if ($rPortal !== true) {
                $errors[] = is_string($rPortal) ? $rPortal : 'Hasta portalı ayarları kaydedilemedi.';
            }
            if ($moduleResult === true && $rPublic === true && $rPortal === true) {
                $_SESSION['success'] = 'Misafir erişimi ayarları kaydedildi.';
                $saved = true;
            }
        } elseif ($activeTab === 'entegrasyon') {
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rEsys = OperationalSettings::saveSection('esys_bridge', OperationalSettings::postedForSection('esys_bridge', $posted));
            $rUsbs = OperationalSettings::saveSection('usbs_bridge', OperationalSettings::postedForSection('usbs_bridge', $posted));
            $rFed = OperationalSettings::saveSection('federation', OperationalSettings::postedForSection('federation', $posted));
            if ($rEsys !== true) {
                $errors[] = is_string($rEsys) ? $rEsys : 'ESYS köprüsü kaydedilemedi.';
            }
            if ($rUsbs !== true) {
                $errors[] = is_string($rUsbs) ? $rUsbs : 'USBS köprüsü kaydedilemedi.';
            }
            if ($rFed !== true) {
                $errors[] = is_string($rFed) ? $rFed : 'Federasyon ayarları kaydedilemedi.';
            }
            if ($rEsys === true && $rUsbs === true && $rFed === true) {
                $_SESSION['success'] = 'Entegrasyon ayarları kaydedildi.';
                $saved = true;
            }
        } elseif ($activeTab === 'gelismis') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('gelismis');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'Gelişmiş modüller kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rModern = OperationalSettings::saveSection('modern_frontend', OperationalSettings::postedForSection('modern_frontend', $posted));
            $rClinical = OperationalSettings::saveSection('clinical_decision_support', OperationalSettings::postedForSection('clinical_decision_support', $posted));
            $rAudit = OperationalSettings::saveSection('audit_log', OperationalSettings::postedForSection('audit_log', $posted));
            if ($rModern !== true) {
                $errors[] = is_string($rModern) ? $rModern : 'Modern frontend ayarları kaydedilemedi.';
            }
            if ($rClinical !== true) {
                $errors[] = is_string($rClinical) ? $rClinical : 'Klinik karar desteği kaydedilemedi.';
            }
            if ($rAudit !== true) {
                $errors[] = is_string($rAudit) ? $rAudit : 'Denetim günlüğü kaydedilemedi.';
            }
            if ($moduleResult === true && $rModern === true && $rClinical === true && $rAudit === true) {
                $_SESSION['success'] = 'Gelişmiş özellik ayarları kaydedildi.';
                $saved = true;
            }
        } elseif ($activeTab === 'sms') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('sms');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'SMS modülü kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rSms = OperationalSettings::saveSection('sms', $posted);
            if ($rSms !== true) {
                $errors[] = is_string($rSms) ? $rSms : 'SMS operasyonel ayarları kaydedilemedi.';
            } elseif (AuthHelper::sessionIsPlatformOwner()) {
                $rCred = \App\Helpers\SmsCredentialsStore::save([
                    'provider' => (string) ($_POST['sms_provider'] ?? 'mock'),
                    'api_user' => (string) ($_POST['sms_api_user'] ?? ''),
                    'api_password' => (string) ($_POST['sms_api_password'] ?? ''),
                    'api_key' => (string) ($_POST['sms_api_key'] ?? ''),
                    'sender_id' => (string) ($_POST['sms_sender_id'] ?? ''),
                    'test_phone' => (string) ($_POST['sms_test_phone'] ?? ''),
                ]);
                if ($rCred !== true) {
                    $errors[] = is_string($rCred) ? $rCred : 'SMS kimlik bilgileri kaydedilemedi.';
                } elseif ($moduleResult === true && $rSms === true) {
                    $_SESSION['success'] = 'SMS ayarları kaydedildi.';
                    $saved = true;
                }
            } elseif ($moduleResult === true && $rSms === true) {
                $_SESSION['success'] = 'SMS ayarları kaydedildi.';
                $saved = true;
            }
        } elseif ($activeTab === 'kps') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('kps');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'KPS modülü kaydedilemedi.';
            }
            $posted = $_POST['operational'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }
            $rKps = OperationalSettings::saveSection('kps', $posted);
            if ($rKps !== true) {
                $errors[] = is_string($rKps) ? $rKps : 'KPS operasyonel ayarları kaydedilemedi.';
            } else {
                $username = trim((string) ($_POST['kps_username'] ?? ''));
                $password = (string) ($_POST['kps_password'] ?? '');
                if ($username !== '' || $password !== '') {
                    $rCred = \App\Helpers\KpsCredentialsStore::save($username, $password);
                    if ($rCred !== true) {
                        $errors[] = is_string($rCred) ? $rCred : 'KPS kimlik bilgileri kaydedilemedi.';
                    } elseif ($moduleResult === true) {
                        $_SESSION['success'] = 'KPS ayarları kaydedildi.';
                        $saved = true;
                    }
                } elseif ($moduleResult === true) {
                    $_SESSION['success'] = 'KPS ayarları kaydedildi.';
                    $saved = true;
                }
            }
        } elseif ($activeTab === 'eimza') {
            $moduleResult = $this->saveDedicatedModuleTogglesForTab('eimza');
            if ($moduleResult !== true) {
                $errors[] = is_string($moduleResult) ? $moduleResult : 'E-imza modülü kaydedilemedi.';
            } elseif ($moduleResult === true) {
                $_SESSION['success'] = 'E-imza modül ayarı kaydedildi.';
                $saved = true;
            }
        } else {
            $_SESSION['error'] = 'Bu sekme salt okunurdur; kayıt yapılmadı.';
            header('Location: ' . $this->indexUrl($activeTab));
            exit;
        }

        if (!$saved && $errors !== []) {
            $_SESSION['error'] = implode(' ', $errors);
        }
        if ($saved) {
            \App\Helpers\AuditLogHelper::settingsUpdate($activeTab);
        }

        header('Location: ' . $this->indexUrl($activeTab));
        exit;
    }

    /** @return true|string */
    private function saveDedicatedModuleTogglesForTab(string $tab): bool|string
    {
        $keys = SettingsNavCatalog::dedicatedModulesForTab($tab);
        if ($keys === []) {
            return true;
        }
        $posted = $_POST['modules'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        $enabledMap = [];
        foreach ($keys as $key) {
            $enabledMap[$key] = isset($posted[$key]) && (string) $posted[$key] === '1';
        }

        return AppSettings::saveModules($enabledMap);
    }
}
