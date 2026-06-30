<?php



declare(strict_types=1);



namespace App\Controllers;



use App\Helpers\AppSettings;

use App\Helpers\AppSettingsStore;

use App\Helpers\AuthHelper;

use App\Helpers\IslemIdSettings;

use App\Helpers\KurumCorporateSettings;

use App\Helpers\OperationalSettings;

use App\Helpers\ThemeViewHelper;

use App\Models\Islem;



/**

 * Uygulama modül / işlem id (süper yönetici) ve kurum operasyonel ayarları (yönetici).

 */

class SettingsController

{

    /** @var list<string> */

    private const SUPERADMIN_TABS = [

        'guvenlik',

        'eimza',

        'kps',

    ];



    /** @var list<string> */

    private const KURUM_TABS = [

        'modules',

        'islem_ids',

        'harita',

        'planlama',

        'kurumsal',

        'nobet',

        'sms',

    ];



    /** @var list<string> */

    private const TABS = [

        'modules',

        'islem_ids',

        'harita',

        'planlama',

        'kurumsal',

        'nobet',

        'sms',

        'guvenlik',

        'eimza',

        'kps',

    ];



    public function __construct()

    {

        AuthHelper::requireAdmin();

    }



    /** @return list<string> */

    private function allowedTabs(): array

    {

        if (AuthHelper::sessionIsSuperAdmin()) {

            return self::TABS;

        }



        return self::KURUM_TABS;

    }



    private function defaultTab(): string

    {

        return 'modules';

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

        $tab = $_GET['tab'] ?? $_POST['active_tab'] ?? $default;

        $tab = is_string($tab) ? $tab : $default;

        if (!in_array($tab, self::TABS, true)) {

            return $default;

        }

        if (!in_array($tab, $this->allowedTabs(), true)) {

            return $default;

        }



        return $tab;

    }



    private function indexUrl(string $tab = 'modules'): string

    {

        if (!in_array($tab, $this->allowedTabs(), true)) {

            $tab = $this->defaultTab();

        }

        $url = esh_url('Settings', 'index');

        if ($tab !== $this->defaultTab() && in_array($tab, self::TABS, true)) {

            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'tab=' . rawurlencode($tab);

        }



        return $url;

    }



    public function index(): void

    {

        $activeTab = $this->activeTab();

        $this->assertTabAccess($activeTab);



        $modules = AppSettings::allForAdmin();

        $grouped = [];

        foreach ($modules as $row) {

            $g = (string) ($row['group'] ?? 'site');

            $grouped[$g][] = $row;

        }

        $groupOrder = ['site', 'auth', 'admin'];

        $settingsToken = $this->ensureToken();

        $pageTitle = AuthHelper::sessionIsSuperAdmin() ? 'Uygulama ayarları' : 'Kurum ayarları';

        $allowedSettingsTabs = $this->allowedTabs();

        $settingsScopeBanner = KurumCorporateSettings::adminScopeBanner();

        $islemIdRows = IslemIdSettings::allForAdmin();

        $islemCatalogPath = 'public/assets/data/islem-idleri.json';

        $appSettingsPath = AppSettingsStore::runtimeRelPath();

        $operationalDefaultsPath = 'config/operational-settings.defaults.json';

        $mapFields = OperationalSettings::fieldsForAdmin('map');

        $planningFields = OperationalSettings::fieldsForAdmin('planning');

        $durationFields = OperationalSettings::fieldsForAdmin('durations');

        $corporateFields = OperationalSettings::fieldsForAdmin('corporate');

        $publicLookupFields = OperationalSettings::fieldsForAdmin('public_hastaarama');

        $maintenanceFields = OperationalSettings::fieldsForAdmin('maintenance');

        $debugFields = OperationalSettings::fieldsForAdmin('debug');

        $tomtomStatus = OperationalSettings::tomtomKeyStatusForAdmin();

        $eimzaInfoRows = OperationalSettings::eimzaInfoRowsForAdmin();

        $kpsFields = OperationalSettings::fieldsForAdmin('kps');

        $kpsCredentialStatus = \App\Helpers\KpsCredentialsStore::statusForAdmin();

        $kpsFirmaStatus = OperationalSettings::kpsFirmaKoduStatusForAdmin();

        $kpsModuleEnabled = AppSettings::isModuleEnabled('kps_tc_sorgu');

        $smsFields = OperationalSettings::fieldsForAdmin('sms');

        $smsCredentialStatus = \App\Helpers\SmsCredentialsStore::statusForAdmin();

        $smsModuleEnabled = AppSettings::isModuleEnabled('sms_bildirim');

        $smsIsSuperAdmin = AuthHelper::sessionIsSuperAdmin();

        $configLocalExample = 'config/config.local.example.php';

        $nobetAllowedUnvanlar = OperationalSettings::nobetAllowedUnvanlarForAdmin();

        $nobetUnvanSlots = OperationalSettings::nobetUnvanSlotsForAdmin();

        $nobetUnvanChoices = \App\Models\User::unvanChoices();

        unset($nobetUnvanChoices['']);

        $vardiyaSlots = OperationalSettings::vardiyaSlotsForAdmin();



        $islemler = (new Islem())->getListForSettingsPicker();



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

                if ($key === '') {

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

            $posted = $_POST['operational'] ?? [];

            if (!is_array($posted)) {

                $posted = [];

            }

            $result = OperationalSettings::saveSection('map', $posted);

            if ($result === true) {

                $_SESSION['success'] = 'Harita ve merkez ayarları kaydedildi. Koordinatlar yeni isteklerde geçerlidir.';

                $saved = true;

            } else {

                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';

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

                if ($r2 !== true) {

                    $errors[] = is_string($r2) ? $r2 : 'Süre ayarları kaydedilemedi.';

                } else {

                    $vardiyaPosted = $_POST['vardiya'] ?? [];

                    if (!is_array($vardiyaPosted)) {

                        $vardiyaPosted = [];

                    }

                    $r3 = OperationalSettings::saveVardiyaSettings($vardiyaPosted);

                    if ($r3 === true) {

                        $_SESSION['success'] = 'Planlama, süre ve vardiya ayarları kaydedildi. Yeni isteklerde geçerlidir.';

                        $saved = true;

                    } else {

                        $errors[] = is_string($r3) ? $r3 : 'Vardiya ayarları kaydedilemedi.';

                    }

                }

            }

        } elseif ($activeTab === 'kurumsal') {

            $posted = $_POST['operational'] ?? [];

            if (!is_array($posted)) {

                $posted = [];

            }

            $result = OperationalSettings::saveSection('corporate', $posted);

            if ($result === true) {

                $_SESSION['success'] = 'Kurumsal ayarlar kaydedildi. Kurumsal ad, EK-3 ve hekim değerlendirme form başlıkları yeni isteklerde güncellenir.';

                $saved = true;

            } else {

                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';

            }

        } elseif ($activeTab === 'nobet') {

            $posted = $_POST['nobet'] ?? [];

            if (!is_array($posted)) {

                $posted = [];

            }

            $result = OperationalSettings::saveNobetAllowedUnvanlar($posted);

            if ($result === true) {

                $_SESSION['success'] = 'Nöbet ünvan ve günlük nöbetçi ayarları kaydedildi. Personel havuzu, otomatik dağıtım ve İzin/Mazeret yeni isteklerde güncellenir.';

                $saved = true;

            } else {

                $errors[] = is_string($result) ? $result : 'Kayıt başarısız.';

            }

        } elseif ($activeTab === 'guvenlik') {

            $posted = $_POST['operational'] ?? [];

            if (!is_array($posted)) {

                $posted = [];

            }

            $rMaintenance = OperationalSettings::saveSection('maintenance', OperationalSettings::postedForSection('maintenance', $posted));

            if ($rMaintenance !== true) {

                $errors[] = is_string($rMaintenance) ? $rMaintenance : 'Bakım modu ayarları kaydedilemedi.';

            }

            $rPublic = OperationalSettings::saveSection('public_hastaarama', OperationalSettings::postedForSection('public_hastaarama', $posted));

            if ($rPublic !== true) {

                $errors[] = is_string($rPublic) ? $rPublic : 'Kamu sorgu ayarları kaydedilemedi.';

            }

            if ($rMaintenance === true && $rPublic === true) {

                $rDebug = OperationalSettings::saveSection('debug', OperationalSettings::postedForSection('debug', $posted));

                if ($rDebug === true) {

                    $_SESSION['success'] = 'Güvenlik ayarları kaydedildi. Yeni isteklerde geçerlidir.';

                    $saved = true;

                } else {

                    $errors[] = is_string($rDebug) ? $rDebug : 'Hata ayıklama ayarları kaydedilemedi.';

                }

            }

        } elseif ($activeTab === 'sms') {

            $posted = $_POST['operational'] ?? [];

            if (!is_array($posted)) {

                $posted = [];

            }

            $rSms = OperationalSettings::saveSection('sms', $posted);

            if ($rSms !== true) {

                $errors[] = is_string($rSms) ? $rSms : 'SMS operasyonel ayarları kaydedilemedi.';

            } else {

                if (AuthHelper::sessionIsSuperAdmin()) {

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

                    } else {

                        $_SESSION['success'] = 'SMS ayarları kaydedildi.';

                        $saved = true;

                    }

                } else {

                    $_SESSION['success'] = 'SMS ayarları kaydedildi.';

                    $saved = true;

                }

            }

        } elseif ($activeTab === 'kps') {

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

                    } else {

                        $_SESSION['success'] = 'KPS ayarları ve kimlik bilgileri kaydedildi.';

                        $saved = true;

                    }

                } else {

                    $_SESSION['success'] = 'KPS operasyonel ayarları kaydedildi.';

                    $saved = true;

                }

            }

        } else {

            $_SESSION['error'] = 'Bu sekme salt okunurdur; kayıt yapılmadı.';

            header('Location: ' . $this->indexUrl($activeTab));

            exit;

        }



        if (!$saved && $errors !== []) {

            $_SESSION['error'] = implode(' ', $errors);

        }



        header('Location: ' . $this->indexUrl($activeTab));

        exit;

    }

}

