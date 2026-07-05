<?php
declare(strict_types=1);

/**
 * config/app-modules.registry.php üretir — controller action listesi otomatik taranır.
 *   php tools/build_app_modules_registry.php
 *   php tools/build_app_modules_registry.php --check   (dosya güncel mi?)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$outFile = $root . '/config/app-modules.registry.php';
$checkOnly = in_array('--check', $argv, true);

require_once $root . '/app/Helpers/StatsCrossTabRegistry.php';

/** @return array<string, list<string>> */
function scan_controller_actions(string $controllersDir): array
{
    $skip = ['__construct', '__call'];
    $map = [];
    foreach (glob($controllersDir . '/*Controller.php') ?: [] as $file) {
        $name = basename($file, 'Controller.php');
        $code = (string) file_get_contents($file);
        preg_match_all('/public function (\w+)\s*\(/', $code, $m);
        $actions = array_values(array_filter(
            $m[1],
            static fn(string $a): bool => !in_array($a, $skip, true)
        ));
        sort($actions);
        $map[$name] = $actions;
    }
    ksort($map);

    return $map;
}

/** @param list<string> $actions */
function php_export_actions(array $actions): string
{
    if ($actions === []) {
        return '[]';
    }
    $lines = ["[\n"];
    foreach ($actions as $a) {
        $lines[] = "                '" . addslashes($a) . "',\n";
    }
    $lines[] = '            ]';

    return implode('', $lines);
}

/** @param array<string, list<string>> $routes */
function php_export_routes(array $routes): string
{
    if ($routes === []) {
        return '[]';
    }
    $lines = ["[\n"];
    foreach ($routes as $controller => $actions) {
        $lines[] = "            '" . addslashes((string) $controller) . "' => " . php_export_actions($actions) . ",\n";
    }
    $lines[] = '        ]';

    return implode('', $lines);
}

$allActions = scan_controller_actions($root . '/app/Controllers');

/** @var list<array<string, mixed>> */
$moduleDefs = [
    [
        'key' => 'dashboard',
        'label' => 'Ana panel',
        'description' => 'Dashboard, takvim, günlük plan ve rota ekranları.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Dashboard' => $allActions['Dashboard'] ?? []],
    ],
    [
        'key' => 'modern_frontend',
        'label' => 'Modern frontend pilot',
        'description' => 'Vue 3 dashboard / plan özeti — oturum tabanlı JSON.',
        'group' => 'core',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['ModernFrontend'],
        'routes' => ['ModernFrontend' => $allActions['ModernFrontend'] ?? []],
    ],
    [
        'key' => 'patient',
        'label' => 'Hasta yönetimi',
        'description' => 'Hasta listeleri, kayıt, düzenleme, Barthel, pansuman fotoğrafı, adres AJAX ve kurumlar arası nakil onay akışı.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => [
            'Patient' => $allActions['Patient'] ?? [],
            'Address' => $allActions['Address'] ?? [],
            'PatientNakil' => $allActions['PatientNakil'] ?? [],
        ],
    ],
    [
        'key' => 'visit',
        'label' => 'Ev ziyareti (izlem)',
        'description' => 'Tamamlanan izlemler, EK-3 konsültasyon ve izlem CRUD.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Visit' => $allActions['Visit'] ?? []],
    ],
    [
        'key' => 'planned_visit',
        'label' => 'Planlanan ziyaret',
        'description' => 'Planlı ev ziyaretleri, pasif hasta plan temizliği.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['PlannedVisit' => $allActions['PlannedVisit'] ?? []],
    ],
    [
        'key' => 'pansuman',
        'label' => 'Pansuman planı',
        'description' => 'Hasta pansuman günleri listesi ve kayıt.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Pansuman' => $allActions['Pansuman'] ?? []],
    ],
    [
        'key' => 'planning',
        'label' => 'Günlük planlama',
        'description' => 'Günlük ziyaret plan tablosu ve skor kaydı.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Planning' => $allActions['Planning'] ?? []],
    ],
    [
        'key' => 'stats',
        'label' => 'İstatistikler',
        'description' => 'Rapor hub, KPI kartları ve çapraz tablo istatistikleri.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => [
            'Stats' => array_values(array_unique(array_merge(
                $allActions['Stats'] ?? [],
                array_map(
                    static fn(string $id): string => \App\Helpers\StatsCrossTabRegistry::actionFor($id),
                    array_keys(\App\Helpers\StatsCrossTabRegistry::all())
                )
            ))),
        ],
    ],
    [
        'key' => 'role',
        'label' => 'Rol ve izin yönetimi',
        'description' => 'Rol tanımları ve izin atamaları (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Role' => $allActions['Role'] ?? []],
    ],
    [
        'key' => 'user',
        'label' => 'Kullanıcı ve profil',
        'description' => 'Profil, istatistik, fotoğraf ve yönetici kullanıcı CRUD.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => ['User' => $allActions['User'] ?? []],
    ],
    [
        'key' => 'auth',
        'label' => 'Oturum açma',
        'description' => 'Kullanıcı adı/parola ile giriş ve çıkış.',
        'group' => 'auth',
        'default' => true,
        'toggleable' => false,
        'routes' => [
            'Auth' => ['login', 'doLogin', 'logout'],
        ],
    ],
    [
        'key' => 'eimza_login',
        'label' => 'E-imza ile giriş',
        'description' => 'Oturum açma ekranında e-imza challenge ve giriş uç noktaları.',
        'group' => 'auth',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Auth'],
        'actions' => ['eimzaChallenge', 'eimzaLogin'],
        'routes' => [
            'Auth' => ['eimzaChallenge', 'eimzaLogin'],
        ],
    ],
    [
        'key' => 'public_hastaarama',
        'label' => 'Hasta TC sorgulama (misafir)',
        'description' => 'Oturumsuz kayıtlı hasta TC arama (OperationalSettings ile ayrıca kapatılabilir).',
        'group' => 'public',
        'default' => true,
        'toggleable' => false,
        'routes' => ['PublicHastaarama' => $allActions['PublicHastaarama'] ?? []],
    ],
    [
        'key' => 'patient_portal',
        'label' => 'Hasta / bakım veren portalı',
        'description' => 'TC + telefon ile oturum; plan, ziyaret özeti, SMS onayı ve yönetici randevu talebi kuyruğu.',
        'group' => 'public',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['PatientPortal', 'PortalAppointment'],
        'routes' => [
            'PatientPortal' => $allActions['PatientPortal'] ?? [],
            'PortalAppointment' => $allActions['PortalAppointment'] ?? [],
        ],
    ],
    [
        'key' => 'erapor',
        'label' => 'e-Rapor',
        'description' => 'e-Rapor listesi, oluşturma ve hasta havuzu işlemleri.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Erapor'],
        'routes' => ['Erapor' => $allActions['Erapor'] ?? []],
    ],
    [
        'key' => 'randevu',
        'label' => 'Branş randevu takvimi',
        'description' => 'Branş konsültasyon randevu takvimi ve kayıtları.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Randevu'],
        'routes' => ['Randevu' => $allActions['Randevu'] ?? []],
    ],
    [
        'key' => 'uhds',
        'label' => 'Uhds',
        'description' => 'Uhds randevu takvimi.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Uhds'],
        'routes' => ['Uhds' => $allActions['Uhds'] ?? []],
    ],
    [
        'key' => 'hasta_ilac_rapor',
        'label' => 'İlaç / tanı raporu',
        'description' => 'Hasta kartından ilaç ve tanı raporu modülü.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['HastaIlacRapor'],
        'routes' => ['HastaIlacRapor' => $allActions['HastaIlacRapor'] ?? []],
    ],
    [
        'key' => 'ilac_rehber',
        'label' => 'İlaç rehberi (etken)',
        'description' => 'Rehber snapshot: etken / ilaç arama; veri özeti bölge yöneticisi menüsünde.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['IlacRehber'],
        'routes' => ['IlacRehber' => $allActions['IlacRehber'] ?? []],
    ],
    [
        'key' => 'mesajlasma',
        'label' => 'Mesajlaşma',
        'description' => 'Birebir mesaj, hasta konuşmaları ve sistem duyuruları (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Mesaj'],
        'routes' => ['Mesaj' => $allActions['Mesaj'] ?? []],
    ],
    [
        'key' => 'sms_bildirim',
        'label' => 'SMS bildirimleri',
        'description' => 'Toplu SMS, şablonlar, günlük plan bildirimi ve gönderim geçmişi.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Sms'],
        'routes' => ['Sms' => $allActions['Sms'] ?? []],
    ],
    [
        'key' => 'stok',
        'label' => 'Stok takibi',
        'description' => 'Malzeme, giriş/çıkış/iade, kritik stok ve hasta bazlı tüketim.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Stok'],
        'routes' => ['Stok' => $allActions['Stok'] ?? []],
    ],
    [
        'key' => 'archive',
        'label' => 'Hasta dosya sistemi',
        'description' => 'Arşiv / hasta dosya sistemi (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Archive'],
        'routes' => ['Archive' => $allActions['Archive'] ?? []],
    ],
    [
        'key' => 'ekip',
        'label' => 'Ekip planlama sistemi',
        'description' => 'Günlük ekip planlama ve vardiya yönetimi (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Ekip'],
        'routes' => ['Ekip' => $allActions['Ekip'] ?? []],
    ],
    [
        'key' => 'adrestanim',
        'label' => 'Adres tanımları sistemi',
        'description' => 'Mahalle, sokak ve kapı no adres hiyerarşisi (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Adrestanim'],
        'routes' => ['Adrestanim' => $allActions['Adrestanim'] ?? []],
    ],
    [
        'key' => 'nobet',
        'label' => 'Nöbet planı sistemi',
        'description' => 'Nöbet takvimi, izin/istek/tatil ve otomatik dağıtım (yönetim). Personel İzin/Mazeret ekranı modülden bağımsızdır.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Nobet'],
        'actions' => [
            'index',
            'monthlySummary',
            'yearlyStats',
            'rebuild',
            'saveIzin',
            'saveIstek',
            'saveTatil',
            'deleteIzin',
            'deleteIstek',
            'deleteTatil',
            'addNobet',
            'moveNobet',
            'deleteNobet',
        ],
        'routes' => ['Nobet' => $allActions['Nobet'] ?? []],
    ],
    [
        'key' => 'harita',
        'label' => 'Hasta haritası',
        'description' => 'Hasta haritası — çoklu sağlayıcı (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Harita'],
        'routes' => ['Harita' => $allActions['Harita'] ?? []],
    ],
    [
        'key' => 'adres_koordinat',
        'label' => 'Adres koordinat bulma',
        'description' => 'Adres koordinat eşleme aracı (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['AdresKoordinat'],
        'routes' => ['AdresKoordinat' => $allActions['AdresKoordinat'] ?? []],
    ],
    [
        'key' => 'manuel_koordinat',
        'label' => 'Manuel koordinat düzeltme',
        'description' => 'Haritadan kapı koordinatı manuel seçme (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['ManuelKoordinat'],
        'routes' => ['ManuelKoordinat' => $allActions['ManuelKoordinat'] ?? []],
    ],
    [
        'key' => 'adres_fetch',
        'label' => 'Denizli adres senkronu',
        'description' => 'Denizli adres hiyerarşisi dış kaynak senkronu (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['AdresFetch' => $allActions['AdresFetch'] ?? []],
    ],
    [
        'key' => 'brans',
        'label' => 'Branş tanımları',
        'description' => 'Branş listesi, kota ve CRUD (platform kataloğu — yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Brans' => $allActions['Brans'] ?? []],
    ],
    [
        'key' => 'guvence',
        'label' => 'Güvence tanımları',
        'description' => 'Güvence türü CRUD (platform kataloğu — yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Guvence' => $allActions['Guvence'] ?? []],
    ],
    [
        'key' => 'hastalik',
        'label' => 'Hastalık tanımları',
        'description' => 'ICD / hastalık kataloğu CRUD (platform kataloğu — yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Hastalik' => $allActions['Hastalik'] ?? []],
    ],
    [
        'key' => 'islem',
        'label' => 'İşlem tanımları',
        'description' => 'Ev ziyareti işlem kodları CRUD (platform kataloğu — yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Islem' => $allActions['Islem'] ?? []],
    ],
    [
        'key' => 'unvan',
        'label' => 'Personel ünvanları',
        'description' => 'Personel ünvan kataloğu CRUD (platform kataloğu — yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Unvan' => $allActions['Unvan'] ?? []],
    ],
    [
        'key' => 'istek',
        'label' => 'Personel izin / istek',
        'description' => 'Personel izin ve mazeret istekleri (yönetim listesi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Istek' => $allActions['Istek'] ?? []],
    ],
    [
        'key' => 'arac',
        'label' => 'Araç tanımları',
        'description' => 'Saha araç listesi CRUD (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Arac' => $allActions['Arac'] ?? []],
    ],
    [
        'key' => 'ilac_listesi',
        'label' => 'TİTCK ilaç listesi yükleme',
        'description' => 'E-Reçete ilaç listesi JSON yükleme (yalnızca platform sahibi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['IlacListesi' => $allActions['IlacListesi'] ?? []],
    ],
    [
        'key' => 'db_maintenance',
        'label' => 'Veritabanı bakımı',
        'description' => 'Yedekleme, geri yükleme ve optimizasyon (bölge yöneticisi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['DbMaintenance' => $allActions['DbMaintenance'] ?? []],
    ],
    [
        'key' => 'audit_log',
        'label' => 'İşlem günlüğü (denetim)',
        'description' => 'KVKK / iç denetim — hasta, izlem, dışa aktarma ve oturum kayıtları.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['AuditLog' => $allActions['AuditLog'] ?? []],
    ],
    [
        'key' => 'esys_compliance',
        'label' => 'ESYS uyum hazırlığı',
        'description' => 'ESYS alan eşlemesi, dosya köprüsü ve referans numaraları.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'controllers' => ['EsysCompliance', 'EsysBridge'],
        'routes' => [
            'EsysCompliance' => $allActions['EsysCompliance'] ?? [],
            'EsysBridge' => $allActions['EsysBridge'] ?? [],
        ],
    ],
    [
        'key' => 'usbs_compliance',
        'label' => 'USBS / e-Nabız uyum hazırlığı',
        'description' => 'USBS alan eşlemesi, izlem bildirimi köprüsü ve referans numaraları.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'controllers' => ['UsbsCompliance', 'UsbsBridge'],
        'routes' => [
            'UsbsCompliance' => $allActions['UsbsCompliance'] ?? [],
            'UsbsBridge' => $allActions['UsbsBridge'] ?? [],
        ],
    ],
    [
        'key' => 'federation',
        'label' => 'Federasyon / çok bölgeli SaaS',
        'description' => 'Bölge yönetimi, bölge yöneticisi bölge filtresi ve dosya köprüsü.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'controllers' => ['Federation', 'FederationRegion', 'FederationBridge'],
        'routes' => [
            'Federation' => $allActions['Federation'] ?? [],
            'FederationRegion' => $allActions['FederationRegion'] ?? [],
            'FederationBridge' => $allActions['FederationBridge'] ?? [],
        ],
    ],
    [
        'key' => 'kurum',
        'label' => 'Kurum yönetimi',
        'description' => 'Kurum tanımları, filtre ve adres atamaları (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => [
            'Kurum' => $allActions['Kurum'] ?? [],
            'KurumAdres' => $allActions['KurumAdres'] ?? [],
        ],
    ],
    [
        'key' => 'rest_api',
        'label' => 'REST API (v1)',
        'description' => 'Bearer token ile /api/v1/patients, visits, plans JSON uçları.',
        'group' => 'admin',
        'default' => false,
        'toggleable' => true,
        'routes' => ['ApiToken' => $allActions['ApiToken'] ?? []],
    ],
    [
        'key' => 'cdn_check',
        'label' => 'CDN sürüm denetimi',
        'description' => 'Harici CDN paket sürüm kontrolü (sistem yöneticisi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['CdnCheck' => $allActions['CdnCheck'] ?? []],
    ],
    [
        'key' => 'theme',
        'label' => 'Tema yönetimi',
        'description' => 'Site teması seçimi, renk düzenleyici ve önizleme.',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Theme' => $allActions['Theme'] ?? []],
    ],
    [
        'key' => 'settings',
        'label' => 'Uygulama ayarları',
        'description' => 'Modül, harita, planlama ve operasyonel ayar paneli (bölge yöneticisi).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Settings' => $allActions['Settings'] ?? []],
    ],
    [
        'key' => 'kps_tc_sorgu',
        'label' => 'KPS TC sorgusu',
        'description' => 'Kimlik Paylaşım Sistemi (KPS) ile TC kimlik doğrulama ve nüfus bilgisi sorgusu.',
        'group' => 'admin',
        'default' => false,
        'toggleable' => true,
        'controllers' => ['Kps'],
        'routes' => ['Kps' => $allActions['Kps'] ?? ['lookupAjax', 'testConnection']],
    ],
];

$header = <<<'HDR'
<?php
declare(strict_types=1);

/**
 * ESH — Uygulama modül kayıt defteri
 *
 * Tek doğruluk kaynağı: modüller, controller/action rotaları, isteğe bağlı modül kapısı.
 * Yeniden üretim: php tools/build_app_modules_registry.php
 *
 * İlişkili dosyalar:
 *   config/app-settings.defaults.json — toggleable modül açık/kapalı varsayılanları
 *   public/assets/data/app-settings.json — panelden kaydedilen runtime değerler
 *
 * Alanlar:
 *   routes          — permission katalogu: controller => action listesi (tüm rota)
 *   controllers     — yalnız toggleable modüllerde: kapı kapsamı (tüm controller)
 *   actions         — yalnız toggleable modüllerde: kısmi kapı (belirli action)
 *   toggleable      — false ise ayar panelinde görünmez ve index.php kapısı uygulanmaz
 *
 * @return array<string, array{
 *   key: string,
 *   label: string,
 *   description: string,
 *   group: 'core'|'site'|'admin'|'auth'|'public',
 *   default: bool,
 *   toggleable: bool,
 *   routes: array<string, list<string>>,
 *   controllers?: list<string>,
 *   actions?: list<string>
 * }>
 */
return [

HDR;

$body = '';
foreach ($moduleDefs as $def) {
    $key = (string) $def['key'];
    $body .= "    '" . addslashes($key) . "' => [\n";
    $body .= "        'key' => '" . addslashes($key) . "',\n";
    $body .= "        'label' => '" . addslashes((string) $def['label']) . "',\n";
    $body .= "        'description' => '" . addslashes((string) $def['description']) . "',\n";
    $body .= "        'group' => '" . addslashes((string) $def['group']) . "',\n";
    $body .= "        'default' => " . (!empty($def['default']) ? 'true' : 'false') . ",\n";
    $body .= "        'toggleable' => " . (!empty($def['toggleable']) ? 'true' : 'false') . ",\n";
    /** @var array<string, list<string>> $routes */
    $routes = $def['routes'] ?? [];
    foreach ($routes as $ctrl => $acts) {
        sort($acts);
        $routes[$ctrl] = $acts;
    }
    $body .= "        'routes' => " . php_export_routes($routes) . ",\n";
    if (!empty($def['controllers']) && is_array($def['controllers'])) {
        $body .= "        'controllers' => " . php_export_actions(array_values($def['controllers'])) . ",\n";
    }
    if (!empty($def['actions']) && is_array($def['actions'])) {
        $body .= "        'actions' => " . php_export_actions(array_values($def['actions'])) . ",\n";
    }
    $body .= "    ],\n";
}

$content = $header . $body . "];\n";

if ($checkOnly) {
    $current = is_file($outFile) ? (string) file_get_contents($outFile) : '';
    if ($current === $content) {
        echo "OK: registry güncel.\n";
        exit(0);
    }
    fwrite(STDERR, "Registry güncel değil; php tools/build_app_modules_registry.php çalıştırın.\n");
    exit(1);
}

if (!is_dir(dirname($outFile))) {
    fwrite(STDERR, "config/ dizini yok.\n");
    exit(1);
}

file_put_contents($outFile, $content);
echo "OK: {$outFile}\n";
echo 'Modül sayısı: ' . count($moduleDefs) . "\n";
