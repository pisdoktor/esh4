<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Modül bayrakları: config/app-settings.defaults.json + public/assets/data/app-settings.json.
 */
final class AppSettings
{

    /** @var bool */
    private static $booted = false;

    /** @var array<string, array<string, mixed>>|null */
    private static $registry = null;

    /** @var array{version?:int, modules?:array<string,bool>}|null */
    private static $merged = null;

    /** @var array<string, string|null> controller => module key (toggle kapısı — tüm action) */
    private static $controllerMap = [];

    /** @var array<string, string> controller::action => module key (toggle kapısı — kısmi) */
    private static $actionMap = [];

    /** @var array<string, string> controller::action => module key (routes katalogu) */
    private static $routeMap = [];

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::loadRegistry();
        self::buildMaps();
        self::$merged = self::loadMergedSettings();
    }

    public static function isModuleEnabled(string $key): bool
    {
        self::boot();
        $entry = self::$registry[$key] ?? null;
        if (!is_array($entry)) {
            return false;
        }
        if (!self::isToggleableEntry($entry)) {
            return true;
        }

        return self::resolveModuleBool($key);
    }

    /**
     * Permission katalogu: controller + action hangi modüle ait?
     */
    public static function moduleForRoute(string $controller, string $action): ?string
    {
        self::boot();
        $controller = trim($controller);
        $action = trim($action);
        if ($controller === '' || $action === '') {
            return null;
        }

        return self::$routeMap[$controller . '::' . $action] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function registry(): array
    {
        self::boot();

        return self::$registry ?? [];
    }

    /**
     * @return array<string, string> controller::action => module key
     */
    public static function routeCatalog(): array
    {
        self::boot();

        return self::$routeMap;
    }

    /**
     * @return array<string, list<string>> controller => action listesi
     */
    public static function routesForModule(string $key): array
    {
        self::boot();
        $entry = self::$registry[$key] ?? null;
        if (!is_array($entry)) {
            return [];
        }
        $routes = $entry['routes'] ?? [];
        if (!is_array($routes)) {
            return [];
        }
        $out = [];
        foreach ($routes as $controller => $actions) {
            if (!is_string($controller) || $controller === '' || !is_array($actions)) {
                continue;
            }
            $out[$controller] = array_values(array_map('strval', $actions));
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function controllersForModule(string $key): array
    {
        self::boot();
        $entry = self::$registry[$key] ?? null;
        if (!is_array($entry)) {
            return [];
        }
        $controllers = $entry['controllers'] ?? [];

        return is_array($controllers) ? array_values(array_map('strval', $controllers)) : [];
    }

    public static function moduleForController(string $controller, ?string $action = null): ?string
    {
        self::boot();
        $controller = trim($controller);
        if ($controller === '') {
            return null;
        }
        if ($action !== null && $action !== '') {
            $actionKey = $controller . '::' . $action;
            if (isset(self::$actionMap[$actionKey])) {
                return self::$actionMap[$actionKey];
            }
        }
        if (isset(self::$controllerMap[$controller])) {
            $mod = self::$controllerMap[$controller];
            if ($mod !== null) {
                return $mod;
            }
        }

        return null;
    }

    /**
     * @param array<string, bool> $enabledMap
     * @return true|string Hata metni
     */
    public static function saveModules(array $enabledMap)
    {
        self::boot();
        $modules = [];
        foreach (self::$registry as $key => $_entry) {
            if (!is_array($_entry) || !self::isToggleableEntry($_entry)) {
                continue;
            }
            if (!array_key_exists($key, $enabledMap)) {
                continue;
            }
            $modules[$key] = (bool) $enabledMap[$key];
        }
        if ($modules === []) {
            return 'Kaydedilecek geçerli modül seçilmedi.';
        }

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null) {
            $existing = KurumCorporateSettings::getModulesMap($kurumId);
            $merged = array_merge($existing, $modules);
            foreach (array_keys(self::$registry) as $regKey) {
                $entry = self::$registry[$regKey] ?? null;
                if (!is_array($entry) || !self::isToggleableEntry($entry)) {
                    continue;
                }
                if (!array_key_exists($regKey, $merged)) {
                    $merged[$regKey] = self::resolveGlobalModuleBool($regKey);
                }
            }
            $written = KurumCorporateSettings::saveModules($kurumId, $merged);
            if ($written !== true) {
                return $written;
            }

            return true;
        }

        $target = SettingsWriteScope::resolveSaveTarget();
        if (is_string($target)) {
            return $target;
        }
        if (($target['target'] ?? '') === SettingsWriteScope::TARGET_BOLGE) {
            $bolgeId = (int) ($target['bolge_id'] ?? 0);
            $existing = BolgeCorporateSettings::getModulesMap($bolgeId);
            $merged = array_merge($existing, $modules);
            foreach (array_keys(self::$registry) as $regKey) {
                $entry = self::$registry[$regKey] ?? null;
                if (!is_array($entry) || !self::isToggleableEntry($entry)) {
                    continue;
                }
                if (!array_key_exists($regKey, $merged)) {
                    $merged[$regKey] = self::resolveGlobalModuleBool($regKey);
                }
            }
            $written = BolgeCorporateSettings::saveModules($bolgeId, $merged);
            if ($written !== true) {
                return $written;
            }

            return true;
        }
        if (($target['target'] ?? '') !== SettingsWriteScope::TARGET_PLATFORM) {
            return 'Kayıt kapsamı belirlenemedi.';
        }
        if (!SettingsWriteScope::canWritePlatformDefaults()) {
            return 'Platform varsayılanları yalnızca sistem sahibi tarafından değiştirilebilir.';
        }

        $currentFile = self::readRuntimeFile();
        $out = [
            'version' => (int) ($currentFile['version'] ?? 1),
            'modules' => array_merge(
                is_array($currentFile['modules'] ?? null) ? $currentFile['modules'] : [],
                $modules
            ),
        ];
        if (isset($currentFile['islem_ids']) && is_array($currentFile['islem_ids'])) {
            $out['islem_ids'] = $currentFile['islem_ids'];
        }
        foreach (array_keys(self::$registry) as $regKey) {
            $entry = self::$registry[$regKey] ?? null;
            if (!is_array($entry) || !self::isToggleableEntry($entry)) {
                continue;
            }
            if (!array_key_exists($regKey, $out['modules'])) {
                $out['modules'][$regKey] = self::resolveGlobalModuleBool($regKey);
            }
        }

        $written = self::writeRuntimeFile($out);
        if ($written !== true) {
            return $written;
        }

        self::$merged = $out;

        return true;
    }

    /**
     * Ayar paneli için registry + mevcut durum.
     *
     * @return list<array{key:string,label:string,description:string,group:string,enabled:bool,default:bool}>
     */
    public static function allForAdmin(): array
    {
        self::boot();
        $rows = [];
        foreach (self::$registry as $key => $entry) {
            if (!is_array($entry) || !self::isToggleableEntry($entry)) {
                continue;
            }
            $rows[] = [
                'key' => $key,
                'label' => (string) ($entry['label'] ?? $key),
                'description' => (string) ($entry['description'] ?? ''),
                'group' => (string) ($entry['group'] ?? 'site'),
                'enabled' => self::isModuleEnabled($key),
                'default' => (bool) ($entry['default'] ?? true),
            ];
        }

        return $rows;
    }

    public static function groupLabel(string $group): string
    {
        $map = [
            'core' => 'Çekirdek modüller',
            'site' => 'Site modülleri',
            'auth' => 'Kimlik doğrulama',
            'admin' => 'Yönetim modülleri',
            'public' => 'Misafir erişimi',
        ];

        return $map[$group] ?? $group;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readRuntimeFile(): array
    {
        return AppSettingsStore::read();
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    private static function writeRuntimeFile(array $data)
    {
        return AppSettingsStore::write($data);
    }

    private static function loadRegistry(): void
    {
        $file = rtrim((string) ROOT_PATH, '/\\') . '/config/app-modules.registry.php';
        if (!is_file($file)) {
            self::$registry = [];

            return;
        }
        $loaded = include $file;
        self::$registry = is_array($loaded) ? $loaded : [];
    }

    private static function buildMaps(): void
    {
        self::$controllerMap = [];
        self::$actionMap = [];
        self::$routeMap = [];
        foreach (self::$registry as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $routes = $entry['routes'] ?? [];
            if (is_array($routes)) {
                foreach ($routes as $controller => $actions) {
                    $controller = (string) $controller;
                    if ($controller === '' || !is_array($actions)) {
                        continue;
                    }
                    foreach ($actions as $action) {
                        $action = (string) $action;
                        if ($action === '') {
                            continue;
                        }
                        self::$routeMap[$controller . '::' . $action] = $key;
                    }
                }
            }
            if (!self::isToggleableEntry($entry)) {
                continue;
            }
            $controllers = $entry['controllers'] ?? [];
            if (!is_array($controllers)) {
                continue;
            }
            $actions = $entry['actions'] ?? null;
            $hasActionScope = is_array($actions) && $actions !== [];
            foreach ($controllers as $controller) {
                $controller = (string) $controller;
                if ($controller === '') {
                    continue;
                }
                if ($hasActionScope) {
                    self::$controllerMap[$controller] = null;
                    foreach ($actions as $action) {
                        $action = (string) $action;
                        if ($action === '') {
                            continue;
                        }
                        self::$actionMap[$controller . '::' . $action] = $key;
                    }
                } else {
                    self::$controllerMap[$controller] = $key;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function isToggleableEntry(array $entry): bool
    {
        return ($entry['toggleable'] ?? true) === true;
    }

    /**
     * @return array{version?:int, modules?:array<string,bool>}
     */
    private static function loadMergedSettings(): array
    {
        $defaultsFile = rtrim((string) ROOT_PATH, '/\\') . '/config/app-settings.defaults.json';
        $base = ['version' => 1, 'modules' => []];
        if (is_readable($defaultsFile)) {
            $raw = @file_get_contents($defaultsFile);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $base = $decoded;
                }
            }
        }
        if (!isset($base['modules']) || !is_array($base['modules'])) {
            $base['modules'] = [];
        }

        $runtimeFile = AppSettingsStore::read();
        $runtime = [];
        if (isset($runtimeFile['modules']) && is_array($runtimeFile['modules'])) {
            $runtime = $runtimeFile['modules'];
        }
        if (array_key_exists('goruntulu_randevu', $runtime) && !array_key_exists('uhds', $runtime)) {
            $runtime['uhds'] = $runtime['goruntulu_randevu'];
        }
        if (array_key_exists('goruntulu_randevu', $base['modules']) && !array_key_exists('uhds', $base['modules'])) {
            $base['modules']['uhds'] = $base['modules']['goruntulu_randevu'];
        }

        $modules = [];
        foreach (self::$registry as $key => $entry) {
            if (!is_array($entry) || !self::isToggleableEntry($entry)) {
                continue;
            }
            if (array_key_exists($key, $runtime)) {
                $modules[$key] = (bool) $runtime[$key];
            } elseif (array_key_exists($key, $base['modules'])) {
                $modules[$key] = (bool) $base['modules'][$key];
            } elseif ($key === 'eimza_login') {
                $modules[$key] = (bool) esh_config_local(
                    'eimza_login_enabled',
                    (bool) ($entry['default'] ?? true)
                );
            } else {
                $modules[$key] = (bool) ($entry['default'] ?? true);
            }
        }

        return [
            'version' => (int) ($base['version'] ?? 1),
            'modules' => $modules,
        ];
    }

    private static function resolveModuleBool(string $key): bool
    {
        $kurumOverride = self::resolveKurumModuleOverride($key);
        if ($kurumOverride !== null) {
            return $kurumOverride;
        }

        return self::resolveGlobalModuleBool($key);
    }

    private static function resolveGlobalModuleBool(string $key): bool
    {
        $modules = self::$merged['modules'] ?? [];
        if (!is_array($modules) || !array_key_exists($key, $modules)) {
            return false;
        }

        return (bool) $modules[$key];
    }

    private static function resolveKurumModuleOverride(string $key): ?bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !class_exists(KurumCorporateSettings::class, false)) {
            return null;
        }
        $map = KurumCorporateSettings::getModulesMap();
        if (!array_key_exists($key, $map) && $key === 'uhds' && array_key_exists('goruntulu_randevu', $map)) {
            return (bool) $map['goruntulu_randevu'];
        }
        if (!array_key_exists($key, $map)) {
            return self::resolveBolgeModuleOverride($key);
        }

        return (bool) $map[$key];
    }

    private static function resolveBolgeModuleOverride(string $key): ?bool
    {
        if (!class_exists(BolgeCorporateSettings::class, false)) {
            return null;
        }
        $map = BolgeCorporateSettings::getModulesMap();
        if (!array_key_exists($key, $map) && $key === 'uhds' && array_key_exists('goruntulu_randevu', $map)) {
            return (bool) $map['goruntulu_randevu'];
        }
        if (!array_key_exists($key, $map)) {
            return null;
        }

        return (bool) $map[$key];
    }
}
