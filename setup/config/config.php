<?php
/**
 * ESH v3.0 — Ana yapılandırma dosyası
 *
 * Bu dosya uygulama genelinde `require_once` ile yüklenir (giriş: public/index.php).
 * Sabitler `define()` ile tanımlanır; değerler aşağıdaki kaynaklardan okunabilir:
 *
 *   1. config/config.local.php  — yerel/sunucu ayarları (git dışı; kurulum sihirbazı oluşturur)
 *   2. Ortam değişkenleri       — ESH_SITEURL, ESH_DB_DEBUG, TOMTOM_KEY, ESH_MYSQL_BIN vb.
 *   3. Yönetim paneli           — app-settings.json (OperationalSettings, debug → DB_DEBUG / display_errors)
 *
 * Şablon: config/config.local.example.php
 *
 * config/ klasöründeki diğer dosyalar:
 *   config.local.php              — yerel ayarlar (git dışı; kurulum üretir)
 *   config.local.example.php      — config.local.php şablonu
 *   version.php                   — sürüm sabitleri (bölüm 12'de yüklenir)
 *   url_helpers.php               — SEF/legacy URL yardımcıları (bölüm 12)
 *   app-modules.registry.php      — modül tanımları (AppSettings)
 *   app-settings.defaults.json    — modül açık/kapalı varsayılanları
 *   operational-settings.defaults.json — harita/planlama vb. varsayılanları
 *   install.lock                  — kurulum tamamlandı işareti (git dışı; JSON)
 *
 * ── İçindekiler ──────────────────────────────────────────────────────────────
 *  1. Yerel yapılandırma yükleme
 *  2. Veritabanı bağlantısı
 *  4. Dosya yolları
 *  5. Site URL ve varlık adresleri
 *  6. Zaman dilimi ve hata raporlama
 *  7. Harici API anahtarları (TomTom)
 *  8. Operasyonel ayarlar (harita, planlama, süreler, işlem id'leri)
 *  9. E-imza giriş ayarları
 * 10. Oturum güvenliği
 * 11. Kurulum kilidi
 * 12. Sürüm ve URL yardımcıları
 */


// =============================================================================
// 1. YEREL YAPILANDIRMA YÜKLEME
// =============================================================================
// Kurulum sihirbazı config.local.php üretir. Anahtarlar esh_config_local() ile okunur.

$GLOBALS['__esh_config_local'] = [];
$__esh_local_cfg = __DIR__ . '/config.local.php';
if (is_readable($__esh_local_cfg)) {
    $__esh_local_loaded = include $__esh_local_cfg;
    if (is_array($__esh_local_loaded)) {
        $GLOBALS['__esh_config_local'] = $__esh_local_loaded;
    }
}
unset($__esh_local_cfg, $__esh_local_loaded);

/**
 * config.local.php içindeki bir anahtarı okur; yoksa varsayılanı döndürür.
 *
 * @param mixed $default
 * @return mixed
 */
function esh_config_local(string $key, $default = null)
{
    $a = $GLOBALS['__esh_config_local'] ?? [];
    return array_key_exists($key, $a) ? $a[$key] : $default;
}


// =============================================================================
// 2. VERİTABANI BAĞLANTISI
// =============================================================================
// config.local.php anahtarları: db_driver, db_host, db_port, db_user, db_pass, db_name, db_prefix
// active_theme: görünüm teması (templates/<slug>/)

define('DB_DRIVER', strtolower((string) esh_config_local('db_driver', 'mysql')));
define('DB_HOST', esh_config_local('db_host', 'localhost'));
define('DB_PORT', (string) esh_config_local('db_port', ''));
define('DB_USER', esh_config_local('db_user', 'root'));
define('DB_PASS', esh_config_local('db_pass', ''));
define('DB_NAME', esh_config_local('db_name', 'esh4'));
define('DB_PREFIX', esh_config_local('db_prefix', 'esh_'));
define('ACTIVE_THEME', esh_config_local('active_theme', 'default'));

                                                                                                    
// =============================================================================
// 4. DOSYA YOLLARI
// =============================================================================
// Proje kökü ve alt dizinler; dosya sistemi işlemleri için kullanılır.

define('ROOT_PATH', realpath(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', ROOT_PATH . '/views');
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');

require_once ROOT_PATH . '/app/Helpers/ComposerBootstrapHelper.php';
\App\Helpers\ComposerBootstrapHelper::loadIfPresent();

require_once ROOT_PATH . '/app/Helpers/AppSettingsStore.php';
require_once ROOT_PATH . '/app/Helpers/DebugBootstrap.php';
define('DB_DEBUG', \App\Helpers\DebugBootstrap::dbDebug());

/** Yavaş sorgu eşiği (ms); DB_DEBUG gerekmez — ortam: ESH_DB_SLOW_QUERY_MS, config.local: db_slow_query_ms */
$_eshDbSlowMs = getenv('ESH_DB_SLOW_QUERY_MS');
if (is_string($_eshDbSlowMs) && $_eshDbSlowMs !== '' && is_numeric($_eshDbSlowMs)) {
    define('DB_SLOW_QUERY_MS', max(0, (int) $_eshDbSlowMs));
} else {
    define('DB_SLOW_QUERY_MS', max(0, (int) esh_config_local('db_slow_query_ms', 500)));
}
unset($_eshDbSlowMs);

/** Yönetim paneli SQL yedekleri (web kökünün dışında; storage/.htaccess ile korunur) */
define('BACKUP_STORAGE_PATH', ROOT_PATH . '/storage/backups');

/**
 * mysql / mysqldump çalıştırılabilir dosyalarının dizini.
 * Windows örneği: C:\xampp\mysql\bin
 * Boş bırakılırsa ortak yollar ve PATH denenir.
 * Ortam değişkeni: ESH_MYSQL_BIN
 */
$_eshMysqlBin = getenv('ESH_MYSQL_BIN');
define('MYSQL_BIN_DIR', (is_string($_eshMysqlBin) && $_eshMysqlBin !== '') ? rtrim($_eshMysqlBin, "/\\") : '');
unset($_eshMysqlBin);


// =============================================================================
// 5. SİTE URL VE VARLIK ADRESLERİ
// =============================================================================
// SITEURL = public/ klasörünün bir üst dizini (örn. http://localhost/4.0.0)
//
// Öncelik sırası:
//   1. Ortam değişkeni ESH_SITEURL
//   2. config.local.php → siteurl
//   3. HTTP isteğinden otomatik tespit
//   4. Varsayılan: http://localhost/

$manualSite = getenv('ESH_SITEURL');
$localSite = esh_config_local('siteurl');
$localSiteStr = is_string($localSite) ? trim($localSite) : '';

if (is_string($manualSite) && $manualSite !== '') {
    define('SITEURL', rtrim(rtrim($manualSite, '/'), '\\'));
} elseif ($localSiteStr !== '') {
    define('SITEURL', rtrim(rtrim($localSiteStr, '/'), '\\'));
} elseif (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $https = $https || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $publicDir = dirname(str_replace('\\', '/', $script));
    $basePath = dirname($publicDir);
    if ($basePath === '/' || $basePath === '.' || $basePath === '') {
        $basePath = '';
    } else {
        $basePath = rtrim($basePath, '/');
    }
    define('SITEURL', rtrim($scheme . '://' . $host . $basePath, '/'));
} else {
    define('SITEURL', 'http://localhost/');
}

define('ASSETS_URL', SITEURL . '/public/assets');
define('UPLOADS_URL', SITEURL . '/public/uploads');

/** SEF URL (/public/Patient/scan); false iken UrlHelper legacy query-string üretir */
define('ESH_SEF_URLS_ENABLED', (bool) esh_config_local('sef_urls_enabled', true));


// =============================================================================
// 6. ZAMAN DİLİMİ VE HATA RAPORLAMA
// =============================================================================

define('TIMEZONE', 'Europe/Istanbul');
date_default_timezone_set(TIMEZONE);

// Öncelik: app-settings.json (debug) → config.local → ESH_DISPLAY_ERRORS → varsayılan (kapalı).
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', \App\Helpers\DebugBootstrap::displayErrors() ? '1' : '0');


// =============================================================================
// 7. HARİCİ API ANAHTARLARI
// =============================================================================

/**
 * TomTom Maps API anahtarı — harita, rota ve adres koordinat işlemleri.
 * JSON ayar dosyasına yazılmaz; yalnızca config.local.php veya TOMTOM_KEY ortam değişkeni.
 */
$_eshTomtomEnv = getenv('TOMTOM_KEY');
$_eshTomtomFromEnv = is_string($_eshTomtomEnv) && trim($_eshTomtomEnv) !== '' ? trim($_eshTomtomEnv) : '';
define('TOMTOM_KEY', (string) esh_config_local('tomtom_key', $_eshTomtomFromEnv));
unset($_eshTomtomEnv, $_eshTomtomFromEnv);

$_eshOrsEnv = getenv('OPENROUTESERVICE_KEY');
$_eshOrsFromEnv = is_string($_eshOrsEnv) && trim($_eshOrsEnv) !== '' ? trim($_eshOrsEnv) : '';
define('OPENROUTESERVICE_KEY', (string) esh_config_local('openrouteservice_key', $_eshOrsFromEnv));
unset($_eshOrsEnv, $_eshOrsFromEnv);

$_eshMapboxEnv = getenv('MAPBOX_TOKEN');
$_eshMapboxFromEnv = is_string($_eshMapboxEnv) && trim($_eshMapboxEnv) !== '' ? trim($_eshMapboxEnv) : '';
define('MAPBOX_TOKEN', (string) esh_config_local('mapbox_token', $_eshMapboxFromEnv));
unset($_eshMapboxEnv, $_eshMapboxFromEnv);

$_eshGoogleMapsEnv = getenv('GOOGLE_MAPS_KEY');
$_eshGoogleMapsFromEnv = is_string($_eshGoogleMapsEnv) && trim($_eshGoogleMapsEnv) !== '' ? trim($_eshGoogleMapsEnv) : '';
define('GOOGLE_MAPS_KEY', (string) esh_config_local('google_maps_key', $_eshGoogleMapsFromEnv));
unset($_eshGoogleMapsEnv, $_eshGoogleMapsFromEnv);


// =============================================================================
// 8. OPERASYONEL AYARLAR
// =============================================================================
// Çoğu değer Yönetim → Uygulama ayarları panelinden yönetilir (app-settings.json).
// Burada tanımlanan sabitler uygulama genelinde kullanılır.

require_once ROOT_PATH . '/app/Helpers/VisitIslemHelper.php';
require_once ROOT_PATH . '/app/Helpers/TenantContext.php';
require_once ROOT_PATH . '/app/Helpers/KurumCorporateSettings.php';
require_once ROOT_PATH . '/app/Helpers/OperationalSettings.php';
require_once ROOT_PATH . '/app/Helpers/IslemIdSettings.php';

\App\Helpers\OperationalSettings::boot();

// --- 8a. Harita başlangıç noktası (OperationalSettings → map) ---

define('START_LAT', \App\Helpers\OperationalSettings::string('map', 'start_lat', '37.7744'));
define('START_LNG', \App\Helpers\OperationalSettings::string('map', 'start_lng', '29.0875'));
define('START_NAME', \App\Helpers\OperationalSettings::string('map', 'start_name', 'DDH Evde Bakım'));

// --- 8b. Rota planlama skorları (OperationalSettings → planning) ---
// Dashboard rota optimizasyonunda bonus/ceza puanları.

define('oncelik_yuksek_bonusu', \App\Helpers\OperationalSettings::int('planning', 'oncelik_yuksek_bonusu', 75));
define('mahalle_bonusu', \App\Helpers\OperationalSettings::int('planning', 'mahalle_bonusu', 40));
define('bolge_bonusu', \App\Helpers\OperationalSettings::int('planning', 'bolge_bonusu', 50));
define('ESH_MAHALLE_BOLGE_MAX', \App\Helpers\OperationalSettings::int('planning', 'mahalle_bolge_max', 15));
define('is_yuku_cezasi', \App\Helpers\OperationalSettings::int('planning', 'is_yuku_cezasi', 10));
define('personel_dosya_sayisi', \App\Helpers\OperationalSettings::int('planning', 'personel_dosya_sayisi', 10));
define('izolasyon_oncelik_bonusu', \App\Helpers\OperationalSettings::int('planning', 'izolasyon_oncelik_bonusu', 60));
define('izolasyon_karisim_cezasi', \App\Helpers\OperationalSettings::int('planning', 'izolasyon_karisim_cezasi', 120));
define('yetkinlik_eslesme_bonusu', \App\Helpers\OperationalSettings::int('planning', 'yetkinlik_eslesme_bonusu', 30));
define('varsayilan_arac_kapasitesi', \App\Helpers\OperationalSettings::int('planning', 'varsayilan_arac_kapasitesi', 4));
define('travel_time_weight', \App\Helpers\OperationalSettings::int('planning', 'travel_time_weight', 1));

// --- 8c. İzlem süreleri — dakika (OperationalSettings → durations) ---

define('sure_pansuman', \App\Helpers\OperationalSettings::int('durations', 'sure_pansuman', 15));
define('sure_muayene', \App\Helpers\OperationalSettings::int('durations', 'sure_muayene', 25));
define('sure_izlem', \App\Helpers\OperationalSettings::int('durations', 'sure_izlem', 20));

// --- 8d. Kurumsal uygulama adı (OperationalSettings → corporate) ---

if (!defined('ESH_APP_NAME')) {
    define('ESH_APP_NAME', \App\Helpers\OperationalSettings::string('corporate', 'esh_app_name', 'SONEV'));
}

// --- 8e. İşlem ID eşlemeleri (IslemIdSettings) ---
// Tanımlar: public/assets/data/islem-idleri.json
// Seçilen değerler: Yönetim → Uygulama ayarları → «İşlem id'leri» → app-settings.json
// config.local.php yalnızca panelden henüz kaydedilmemiş anahtarlar için yedek okunur.

define('ESH_NAKIL_ISLEM_ID', \App\Helpers\IslemIdSettings::resolvedInt('nakil_islem_id'));
define('ESH_KONSULTASYON_ISLEM_ID', \App\Helpers\IslemIdSettings::resolvedInt('konsultasyon_islem_id'));
define('ESH_DASHBOARD_PANSUMAN_IZLEM_DEFAULT_ISLEM_ID', \App\Helpers\IslemIdSettings::resolvedInt('dashboard_pansuman_izlem_default_islem_id'));
define('ESH_VISIT_SONDA_TAKILI_ISLEM_IDS', \App\Helpers\IslemIdSettings::resolvedCsv('visit_sonda_takili_islem_ids'));
define('ESH_VISIT_SONDA_CIKARILDI_ISLEM_IDS', \App\Helpers\IslemIdSettings::resolvedCsv('visit_sonda_cikarildi_islem_ids'));
define('ESH_STATS_BIR_IZLEM_ISLEM_ID', \App\Helpers\IslemIdSettings::resolvedInt('stats_bir_izlem_yapilan_islem_id'));
define('ESH_VISIT_AFTER_REGISTER_DEFAULT_ISLEM_ID', \App\Helpers\IslemIdSettings::resolvedInt('visit_after_register_default_yapilan_islem_id'));


// =============================================================================
// 9. E-İMZA GİRİŞ AYARLARI
// =============================================================================
// config.local.php anahtarları: eimza_* (örnekler config.local.example.php içinde)

// --- 9a. Temel e-imza ayarları ---

/** true ise login ekranında e-imza challenge/doğrulama akışı aktif olur */
define('ESH_EIMZA_LOGIN_ENABLED', (bool) esh_config_local('eimza_login_enabled', true));

/** Challenge geçerlilik süresi (saniye) */
define('ESH_EIMZA_CHALLENGE_TTL_SECONDS', (int) esh_config_local('eimza_challenge_ttl_seconds', 180));

/** PEM formatında güvenilen kök/ara CA sertifika zinciri dosyası */
define('ESH_EIMZA_TRUST_STORE_PATH', (string) esh_config_local('eimza_trust_store_path', ROOT_PATH . '/storage/certs/eimza_trust_store.pem'));

// --- 9b. Güvenlik ve hız sınırlama ---

define('ESH_EIMZA_REQUIRE_REVOCATION_CHECK', (bool) esh_config_local('eimza_require_revocation_check', false));
define('ESH_EIMZA_ENFORCE_USER_CERT_PINNING', (bool) esh_config_local('eimza_enforce_user_cert_pinning', true));
define('ESH_EIMZA_RATE_WINDOW_SECONDS', (int) esh_config_local('eimza_rate_window_seconds', 300));
define('ESH_EIMZA_MAX_FAILED_PER_IP', (int) esh_config_local('eimza_max_failed_per_ip', 10));
define('ESH_EIMZA_MAX_FAILED_PER_TC', (int) esh_config_local('eimza_max_failed_per_tc', 6));

// --- 9c. Yerel e-imza köprüsü (masaüstü kart okuyucu) ---

define('ESH_EIMZA_LOCAL_BRIDGE_ENABLED', (bool) esh_config_local('eimza_local_bridge_enabled', true));
define('ESH_EIMZA_LOCAL_BRIDGE_BASE_URL', (string) esh_config_local('eimza_local_bridge_base_url', 'http://127.0.0.1:15873'));


// =============================================================================
// 10. OTURUM GÜVENLİĞİ
// =============================================================================
// Session çalınmasını önlemek için temel cookie ayarları.
// session_start() genelde public/index.php içinde çağrılır.

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $eshHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    if ($eshHttps) {
        ini_set('session.cookie_secure', '1');
    }
}
unset($eshHttps);

define('ESH_LOGIN_RATE_WINDOW_SECONDS', max(60, (int) esh_config_local('login_rate_window_seconds', 300)));
define('ESH_LOGIN_RATE_MAX_ATTEMPTS', max(3, (int) esh_config_local('login_rate_max_attempts', 10)));
define('ESH_PUBLIC_HASTAARAMA_RATE_WINDOW_SECONDS', max(60, (int) esh_config_local('public_hastaarama_rate_window_seconds', 300)));
define('ESH_PUBLIC_HASTAARAMA_RATE_MAX_ATTEMPTS', max(5, (int) esh_config_local('public_hastaarama_rate_max_attempts', 20)));
define('ESH_PATIENT_PORTAL_RATE_WINDOW_SECONDS', max(60, (int) esh_config_local('patient_portal_rate_window_seconds', 300)));
define('ESH_PATIENT_PORTAL_RATE_MAX_ATTEMPTS', max(5, (int) esh_config_local('patient_portal_rate_max_attempts', 15)));
define('ESH_TOMTOM_GEOCODE_RATE_WINDOW_SECONDS', max(60, (int) esh_config_local('tomtom_geocode_rate_window_seconds', 300)));
define('ESH_TOMTOM_GEOCODE_RATE_MAX_ATTEMPTS', max(10, (int) esh_config_local('tomtom_geocode_rate_max_attempts', 60)));

require_once __DIR__ . '/security_helpers.php';


// =============================================================================
// 11. KURULUM KİLİDİ
// =============================================================================
// Dosya: config/install.lock (kurulum sihirbazı oluşturur; .gitignore)
// İçerik örneği: {"installed_at":"…","php":"8.2.x"} — yalnızca varlık kontrol edilir.
// public/index.php: lock yokken install.php varsa sihirbaza yönlendirir.
// Kurulum bittikten sonra public/install.php hâlâ duruyorsa uygulama kilitlenir.
// Geliştirme: ortam değişkeni ESH_ALLOW_INSTALL_PHP=1 ile kontrol atlanır.
// install.php config yüklemez; bu blok onu etkilemez.

if (PHP_SAPI_NAME() !== 'cli' && defined('ROOT_PATH') && ROOT_PATH) {
    $eshInstallScript = ROOT_PATH . '/public/install.php';
    $eshInstallLock = ROOT_PATH . '/config/install.lock';
    if (is_file($eshInstallScript) && is_file($eshInstallLock)) {
        $allowInstallPhp = getenv('ESH_ALLOW_INSTALL_PHP');
        if ($allowInstallPhp !== '1') {
            $sn = basename(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));
            if ($sn !== 'install.php') {
                http_response_code(503);
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Bakım</title></head><body style="font-family:sans-serif;padding:2rem;">';
                echo '<h1>Geçici olarak kullanılamıyor</h1>';
                echo '<p>Kurulum tamamlanmış; güvenlik için sunucudan <code>public/install.php</code> dosyasını kaldırın veya yeniden adlandırın.</p>';
                echo '<p class="small">Yerel geliştirme: ortam değişkeni <code>ESH_ALLOW_INSTALL_PHP=1</code> (Apache SetEnv / PHP-FPM pool) ile kontrol devre dışı bırakılabilir.</p>';
                echo '</body></html>';
                exit;
            }
        }
    }
}


// =============================================================================
// 12. SÜRÜM VE URL YARDIMCILARI
// =============================================================================

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/url_helpers.php';
