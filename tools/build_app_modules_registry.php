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
        'key' => 'patient',
        'label' => 'Hasta yönetimi',
        'description' => 'Hasta listeleri, kayıt, düzenleme, Barthel, pansuman fotoğrafı ve adres AJAX.',
        'group' => 'core',
        'default' => true,
        'toggleable' => false,
        'routes' => [
            'Patient' => $allActions['Patient'] ?? [],
            'Address' => $allActions['Address'] ?? [],
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
        'description' => 'Rehber snapshot: etken / ilaç arama; veri özeti süper yönetici menüsünde.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['IlacRehber'],
        'routes' => ['IlacRehber' => $allActions['IlacRehber'] ?? []],
    ],
    [
        'key' => 'mesajlasma',
        'label' => 'Mesajlaşma',
        'description' => 'Birebir mesaj, hasta konuşmaları ve sistem duyuruları.',
        'group' => 'site',
        'default' => true,
        'toggleable' => true,
        'controllers' => ['Mesaj'],
        'routes' => ['Mesaj' => $allActions['Mesaj'] ?? []],
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
        'description' => 'TomTom tabanlı hasta haritası (yönetim).',
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
        'key' => 'brans',
        'label' => 'Branş tanımları',
        'description' => 'Branş listesi, kota ve CRUD (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Brans' => $allActions['Brans'] ?? []],
    ],
    [
        'key' => 'guvence',
        'label' => 'Güvence tanımları',
        'description' => 'Güvence türü CRUD (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Guvence' => $allActions['Guvence'] ?? []],
    ],
    [
        'key' => 'hastalik',
        'label' => 'Hastalık tanımları',
        'description' => 'ICD / hastalık kataloğu CRUD (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Hastalik' => $allActions['Hastalik'] ?? []],
    ],
    [
        'key' => 'islem',
        'label' => 'İşlem tanımları',
        'description' => 'Ev ziyareti işlem kodları CRUD (yönetim).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['Islem' => $allActions['Islem'] ?? []],
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
        'description' => 'E-Reçete ilaç listesi JSON yükleme (süper yönetici).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['IlacListesi' => $allActions['IlacListesi'] ?? []],
    ],
    [
        'key' => 'db_maintenance',
        'label' => 'Veritabanı bakımı',
        'description' => 'Yedekleme, geri yükleme ve optimizasyon (süper yönetici).',
        'group' => 'admin',
        'default' => true,
        'toggleable' => false,
        'routes' => ['DbMaintenance' => $allActions['DbMaintenance'] ?? []],
    ],
    [
        'key' => 'cdn_check',
        'label' => 'CDN sürüm denetimi',
        'description' => 'Harici CDN paket sürüm kontrolü (süper yönetici).',
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
        'description' => 'Modül, harita, planlama ve operasyonel ayar paneli (süper yönetici).',
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
