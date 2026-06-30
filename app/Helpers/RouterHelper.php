<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * SEF URL ve query-string yönlendirme çözümlemesi.
 */
class RouterHelper
{
    /**
     * @return array{controller: string, action: string}
     */
    public static function resolveRoute(): array
    {
        $controllerReq = isset($_GET['controller']) ? (string) $_GET['controller'] : '';
        $actionReq = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($controllerReq !== '') {
            return [
                'controller' => self::normalizeControllerName($controllerReq),
                'action' => $actionReq !== '' ? $actionReq : 'index',
            ];
        }

        $segments = self::pathSegmentsFromRequest();
        if ($segments === []) {
            return ['controller' => 'Dashboard', 'action' => 'index'];
        }

        if (count($segments) === 1) {
            return [
                'controller' => self::normalizeControllerName($segments[0]),
                'action' => 'index',
            ];
        }

        return [
            'controller' => self::normalizeControllerName($segments[0]),
            'action' => $segments[1],
        ];
    }

    /**
     * REQUEST_URI yolundan public/ altı segmentleri (controller/action).
     *
     * @return list<string>
     */
    public static function pathSegmentsFromRequest(): array
    {
        $uriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (!is_string($uriPath) || $uriPath === '') {
            return [];
        }

        $uriPath = trim(str_replace('\\', '/', $uriPath), '/');
        if ($uriPath === '' || $uriPath === 'index.php') {
            return [];
        }

        $parts = explode('/', $uriPath);
        $publicIdx = array_search('public', $parts, true);
        if ($publicIdx !== false) {
            $parts = array_slice($parts, $publicIdx + 1);
        } else {
            $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseDir = dirname($scriptName);
            if ($baseDir !== '/' && $baseDir !== '.') {
                $baseDir = trim($baseDir, '/');
                if ($baseDir !== '' && str_starts_with($uriPath, $baseDir)) {
                    $parts = explode('/', trim(substr($uriPath, strlen($baseDir)), '/'));
                }
            }
        }

        $segments = [];
        foreach ($parts as $segment) {
            if ($segment === '' || $segment === 'index.php') {
                continue;
            }
            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * Tek kelimelik controller için ucfirst; bileşik PascalCase adları korunur (PlannedVisit vb.).
     */
    public static function normalizeControllerName(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'Dashboard';
        }

        if (preg_match('/[A-Z]/', substr($raw, 1)) !== 0) {
            return $raw;
        }

        return ucfirst($raw);
    }
}
