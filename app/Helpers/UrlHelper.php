<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Uygulama içi URL üretimi (SEF veya legacy query-string).
 */
class UrlHelper
{
    /**
     * @param array<string, scalar|null> $params
     */
    public static function route(string $controller, string $action = 'index', array $params = [], bool $absolute = false): string
    {
        return self::fromRequestParams(
            array_merge(['controller' => $controller, 'action' => $action], $params),
            $absolute
        );
    }

    /**
     * `index.php?controller=...` veya yalnızca sorgu dizesinden URL üretir.
     */
    public static function fromQueryString(string $queryOrUrl, bool $absolute = false): string
    {
        $query = html_entity_decode(trim($queryOrUrl), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_starts_with($query, 'index.php?')) {
            $query = substr($query, strlen('index.php?'));
        } elseif (str_starts_with($query, 'index.php')) {
            return self::route('Dashboard', 'index', [], $absolute);
        }

        $params = [];
        if ($query !== '') {
            parse_str($query, $params);
        }

        return self::fromRequestParams($params, $absolute);
    }

    /**
     * @param array<string, mixed> $params
     */
    /**
     * GET filtre formları — legacy modda action yalnızca index.php; controller/action gizli alanlarda.
     */
    public static function formAction(string $controller, string $action = 'index'): string
    {
        if (defined('ESH_SEF_URLS_ENABLED') && ESH_SEF_URLS_ENABLED) {
            return self::route($controller, $action);
        }

        return rtrim(self::publicWebPath(), '/') . '/index.php';
    }

    public static function fromRequestParams(array $params, bool $absolute = false): string
    {
        $params = self::filterParams($params);

        if (PHP_SAPI !== 'cli') {
            $needsController = !isset($params['controller']) || (string) $params['controller'] === '';
            $needsAction = !isset($params['action']) || (string) $params['action'] === '';
            if ($needsController || $needsAction) {
                $route = RouterHelper::resolveRoute();
                if ($needsController) {
                    $params['controller'] = $route['controller'];
                }
                if ($needsAction) {
                    $params['action'] = $route['action'];
                }
            }
        }

        $controller = isset($params['controller']) ? (string) $params['controller'] : 'Dashboard';
        $action = isset($params['action']) ? (string) $params['action'] : 'index';
        unset($params['controller'], $params['action']);

        if (defined('ESH_SEF_URLS_ENABLED') && ESH_SEF_URLS_ENABLED) {
            $path = $controller . '/' . $action;
            $query = http_build_query($params);
            $sefPath = $path . ($query !== '' ? '?' . $query : '');
            if ($absolute) {
                return rtrim((string) SITEURL, '/') . '/public/' . ltrim($sefPath, '/');
            }

            return rtrim(self::publicWebPath(), '/') . '/' . ltrim($sefPath, '/');
        }

        $queryParams = array_merge(
            ['controller' => $controller, 'action' => $action],
            $params
        );
        $legacyQuery = 'index.php?' . http_build_query($queryParams);
        if ($absolute) {
            return rtrim((string) SITEURL, '/') . '/public/' . $legacyQuery;
        }

        return rtrim(self::publicWebPath(), '/') . '/' . $legacyQuery;
    }

    /**
     * Web kökünden proje dizinine giden yol (public/ bir üstü; örn. `` veya `/esh`).
     * ilac_rehber_migration.php bağlantıları için.
     */
    public static function projectWebPath(): string
    {
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $scriptName = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
            $leaf = basename($scriptName);
            if ($leaf === 'ilac_rehber_migration.php') {
                $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
                if ($base !== '' && $base !== '/' && $base !== '.') {
                    return $base;
                }

                return '';
            }
        }

        $publicPath = rtrim(self::publicWebPath(), '/');
        if ($publicPath !== '' && $publicPath !== '/') {
            if (str_ends_with($publicPath, '/public')) {
                return substr($publicPath, 0, -strlen('/public'));
            }
            $parent = rtrim(str_replace('\\', '/', dirname($publicPath)), '/');
            if ($parent !== '' && $parent !== '/' && $parent !== '.') {
                return $parent;
            }
        }

        if (defined('SITEURL')) {
            $path = parse_url((string) SITEURL, PHP_URL_PATH);
            if (is_string($path) && $path !== '' && $path !== '/') {
                return rtrim(str_replace('\\', '/', $path), '/');
            }
        }

        return '';
    }

    /**
     * Proje kökündeki tek dosyalı betik URL’si (göreli; örn. `/esh/ilac_rehber_migration.php`).
     */
    public static function projectRootScriptUrl(string $basename): string
    {
        $basename = ltrim(str_replace('\\', '/', $basename), '/');
        $base = self::projectWebPath();

        return ($base === '' || $base === '.')
            ? '/' . $basename
            : $base . '/' . $basename;
    }

    /**
     * Web kökünden public/ dizinine giden yol (örn. `/public/` veya `/htdocs/public/`).
     */
    public static function publicWebPath(): string
    {
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $scriptName = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
            $publicMarker = '/public/';
            $pos = strrpos($scriptName, $publicMarker);
            if ($pos !== false) {
                return substr($scriptName, 0, $pos + strlen($publicMarker));
            }
            if (str_ends_with($scriptName, '/public/index.php')) {
                return substr($scriptName, 0, -strlen('index.php'));
            }

            $dir = dirname($scriptName);
            if ($dir === '/' || $dir === '.') {
                return '/';
            }

            return rtrim($dir, '/') . '/';
        }

        return '/public/';
    }

    /**
     * @param array<string, scalar|null> $overrides
     */
    public static function fromMergedGet(array $overrides, bool $absolute = false): string
    {
        $merged = array_merge($_GET, $overrides);

        return self::fromRequestParams($merged, $absolute);
    }

    /**
     * @param array<string, scalar|null> $params
     */
    public static function redirect(string $controller, string $action = 'index', array $params = [], int $code = 302): never
    {
        header('Location: ' . self::route($controller, $action, $params), true, $code);
        exit;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, scalar>
     */
    private static function filterParams(array $params): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (is_bool($value)) {
                $out[$key] = $value ? '1' : '0';
                continue;
            }
            if (is_int($value) || is_float($value) || is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
