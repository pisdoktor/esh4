<?php
/**
 * ANA GİRİŞ NOKTASI (ROUTER)
 */
// Kurulum: public/install.php varken ve config/install.lock yokken önce sihirbaz
$__eshRoot = dirname(__DIR__);
if (is_file(__DIR__ . '/install.php') && !is_file($__eshRoot . '/config/install.lock')) {
    header('Location: install.php', true, 302);
    exit;
}
unset($__eshRoot);

// 1. Ayarları yükle (session ini_set burada; session_start bundan sonra)
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

esh_send_security_headers();

spl_autoload_register(function ($class) {
    // Proje kök dizini (Namespace'lerin başladığı yer)
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    // Sınıf ismi 'App\' ile başlıyor mu?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Geri kalan kısmı al (Örn: Controllers\PatientController)
    $relative_class = substr($class, $len);

    // Ters slaşları düz slaş yap ve sonuna .php ekle
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Dosya varsa yükle
    if (file_exists($file)) {
        require $file;
    }
});

if (isset($_SESSION['user_id'])) {
    $u = new \App\Models\User();
    if ($u->load((int) $_SESSION['user_id'])) {
        $eshAdminLevel = \App\Helpers\AuthHelper::clampLevel((int) ($u->isadmin ?? 0));
        \App\Helpers\AuthHelper::syncSessionFromLevel($eshAdminLevel);
        \App\Helpers\TenantContext::syncSessionFromUser(
            isset($u->kurum_id) ? (int) $u->kurum_id : null,
            $eshAdminLevel,
            isset($u->bolge_id) ? (int) $u->bolge_id : null
        );
        \App\Services\PermissionService::syncSessionPermissions((int) $u->id, $eshAdminLevel);
    }
}
unset($u, $eshAdminLevel);

\App\Helpers\AppSettings::boot();

// 2. URL'den parametreleri al (query-string veya SEF yol; varsayılan: Dashboard/index)
$__eshRoute = \App\Helpers\RouterHelper::resolveRoute();
$controllerName = $__eshRoute['controller'];
$actionName = $__eshRoute['action'];
unset($__eshRoute);

if (\App\Helpers\MaintenanceHelper::shouldBlock($controllerName, $actionName)) {
    \App\Helpers\MaintenanceHelper::respondBlocked();
}
    
// BURAYI EKLE: Global olarak tanımla ki her yerden erişilsin
$GLOBALS['controllerName'] = $controllerName;
$GLOBALS['actionName'] = $actionName;

// Oturum gerektirmeyen eylemler (giriş sayfası + genel hasta TC sorgusu + portal giriş)
$guestControllers = [
    'Auth' => ['login', 'doLogin', 'eimzaChallenge', 'eimzaLogin'],
    'PublicHastaarama' => ['index', 'sonuc'],
    'Uhds' => ['patientVideo', 'patientVideoConfig'],
    'PatientPortal' => ['login', 'doLogin', 'logout'],
];
$isGuestAllowed = isset($guestControllers[$controllerName])
    && in_array($actionName, $guestControllers[$controllerName], true);

$patientPortalSessionActions = ['index', 'updateSmsConsent'];
$isPatientPortalSessionRoute = $controllerName === 'PatientPortal'
    && in_array($actionName, $patientPortalSessionActions, true);
$isPatientPortalSessionOk = $isPatientPortalSessionRoute
    && \App\Helpers\PatientPortalHelper::hasValidSession();

if ($isPatientPortalSessionRoute && !$isPatientPortalSessionOk) {
    header('Location: ' . esh_url('PatientPortal', 'login', [], true));
    exit;
}

if (!isset($_SESSION['user_id']) && !$isGuestAllowed && !$isPatientPortalSessionOk) {
    if (\App\Helpers\CsrfHelper::isJsonClientRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'auth' => false,
            'error' => 'Oturum süresi doldu veya oturum açılmadı.',
            'mesaj' => 'Oturum süresi doldu. Sayfayı yenileyip tekrar giriş yapın.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ' . esh_url('Auth', 'login'));
    exit;
}

$__eshModuleKey = \App\Helpers\AppSettings::moduleForController($controllerName, $actionName);
if ($__eshModuleKey !== null && !\App\Helpers\AppSettings::isModuleEnabled($__eshModuleKey)) {
    if ($isGuestAllowed) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(
            ['ok' => false, 'error' => 'Bu modül yönetici tarafından kapatılmış.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    $_SESSION['error'] = 'Bu modül yönetici tarafından kapatılmış.';
    header('Location: ' . esh_url('Dashboard', 'index'));
    exit;
}
unset($__eshModuleKey);

if (!$isGuestAllowed && $controllerName !== 'PatientPortal') {
    \App\Services\PermissionService::assertRouteAllowed($controllerName, $actionName);
}

\App\Helpers\CsrfHelper::enforcePost($controllerName, $actionName);

// 4. Sınıfı ve Metodu çalıştır
$controllerClass = "\\App\\Controllers\\" . $controllerName . "Controller";

if (class_exists($controllerClass)) {
    $controllerInstance = new $controllerClass();
    
    if (method_exists($controllerInstance, $actionName)) {
        // Metodu çalıştır
        $controllerInstance->$actionName();
    } elseif (
        $controllerName === 'Stats'
        && str_starts_with($actionName, 'xTab_')
        && \App\Helpers\StatsCrossTabRegistry::idFromAction($actionName) !== null
    ) {
        $controllerInstance->$actionName();
    } else {
        header("HTTP/1.0 404 Not Found");
        die("Hata: <b>{$actionName}</b> metodu <b>{$controllerClass}</b> içinde bulunamadı!");
    }
} else {
    header("HTTP/1.0 404 Not Found");
    die("Hata: <b>{$controllerClass}</b> sınıfı sistemde kayıtlı değil!");
}