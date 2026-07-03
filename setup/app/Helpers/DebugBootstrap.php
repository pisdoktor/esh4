<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Hata ayıklama bayrakları — config.php erken aşamasında (OperationalSettings boot öncesi).
 *
 * Öncelik: app-settings.json (debug) → config.local.php → ortam değişkeni → operational defaults.
 */
final class DebugBootstrap
{
    /** @var array<string, bool|null>|null */
    private static $resolved = null;

    public static function displayErrors(): bool
    {
        return self::resolve('display_errors');
    }

    public static function dbDebug(): bool
    {
        return self::resolve('db_debug');
    }

    private static function resolve(string $key): bool
    {
        if (self::$resolved === null) {
            self::$resolved = [];
        }
        if (array_key_exists($key, self::$resolved) && self::$resolved[$key] !== null) {
            return (bool) self::$resolved[$key];
        }

        $defaults = self::loadDefaults();
        $fallback = self::toBool($defaults[$key] ?? false);

        $runtime = self::loadRuntimeSection();
        if (array_key_exists($key, $runtime)) {
            $value = self::toBool($runtime[$key]);
            self::$resolved[$key] = $value;

            return $value;
        }

        $local = self::fromConfigLocal($key);
        if ($local !== null) {
            self::$resolved[$key] = $local;

            return $local;
        }

        $env = self::fromEnv($key);
        if ($env !== null) {
            self::$resolved[$key] = $env;

            return $env;
        }

        self::$resolved[$key] = $fallback;

        return $fallback;
    }

    private static function fromConfigLocal(string $key): ?bool
    {
        if (!function_exists('esh_config_local')) {
            return null;
        }
        if ($key === 'db_debug') {
            if (array_key_exists('db_debug', $GLOBALS['__esh_config_local'] ?? [])) {
                return self::toBool(esh_config_local('db_debug'));
            }
            if (array_key_exists('db_sql_debug_enabled', $GLOBALS['__esh_config_local'] ?? [])) {
                return self::toBool(esh_config_local('db_sql_debug_enabled'));
            }

            return null;
        }
        if (array_key_exists('display_errors', $GLOBALS['__esh_config_local'] ?? [])) {
            return self::toBool(esh_config_local('display_errors'));
        }

        return null;
    }

    private static function fromEnv(string $key): ?bool
    {
        $envName = $key === 'db_debug' ? 'ESH_DB_DEBUG' : 'ESH_DISPLAY_ERRORS';
        $raw = getenv($envName);
        if ($raw === false || trim((string) $raw) === '') {
            return null;
        }

        return filter_var(trim((string) $raw), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadDefaults(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        $path = rtrim((string) ROOT_PATH, '/\\') . '/config/operational-settings.defaults.json';
        if (!is_readable($path)) {
            return $cache;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $cache;
        }
        $debug = $decoded['debug'] ?? [];
        $cache = is_array($debug) ? $debug : [];

        return $cache;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadRuntimeSection(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        $path = AppSettingsStore::runtimePath();
        if (!is_readable($path)) {
            return $cache;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $cache;
        }
        $debug = $decoded['debug'] ?? [];
        $cache = is_array($debug) ? $debug : [];

        return $cache;
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }
        $s = strtolower(trim((string) $value));

        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }
}
