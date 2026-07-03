<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Modern frontend pilot — Vue ESM (CDN veya derlenmiş bundle), dashboard / plan ekranı.
 */
final class ModernFrontendHelper
{
    /** Prod ESM — pilot bileşenleri render() kullanır, template derleyici / unsafe-eval gerekmez. */
    public const DEFAULT_VUE_CDN = 'https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.esm-browser.prod.js';

    public static function enabled(): bool
    {
        return AppSettings::isModuleEnabled('modern_frontend')
            && OperationalSettings::modernFrontendEnabled();
    }

    public static function dashboardPilotActive(): bool
    {
        return self::enabled()
            && self::passesRolloutGate()
            && OperationalSettings::modernFrontendDashboardPilot()
            && self::assetExists('dashboard-pilot.mjs');
    }

    public static function planningPilotActive(): bool
    {
        return self::enabled()
            && self::passesRolloutGate()
            && OperationalSettings::modernFrontendPlanningPilot()
            && self::assetExists('planning-pilot.mjs');
    }

    public static function shouldLoadForRoute(string $controller, string $action): bool
    {
        $controller = strtolower(trim($controller));
        $action = strtolower(trim($action));
        if ($controller === 'dashboard' && $action === 'index') {
            return self::dashboardPilotActive();
        }
        if ($controller === 'plannedvisit' && $action === 'index') {
            return self::planningPilotActive();
        }

        return false;
    }

    public static function vueEsmUrl(): string
    {
        $url = trim(OperationalSettings::modernFrontendVueCdnUrl());
        if ($url === '') {
            return self::DEFAULT_VUE_CDN;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    public static function useBuiltBundle(string $scope = 'dashboard'): bool
    {
        if (!OperationalSettings::modernFrontendUseBuiltBundle()) {
            return false;
        }
        $scope = strtolower(trim($scope));

        return self::assetExists(self::builtAssetRelative($scope));
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientBootConfig(string $scope): array
    {
        $scope = strtolower(trim($scope));
        $builtAsset = self::builtAssetRelative($scope);

        return [
            'scope' => $scope,
            'vueUrl' => self::vueEsmUrl(),
            'apiUrl' => esh_url('ModernFrontend', 'pilotData', ['scope' => $scope]),
            'useBuiltBundle' => self::useBuiltBundle($scope),
            'builtUrl' => self::assetUrl($builtAsset),
        ];
    }

    private static function passesRolloutGate(): bool
    {
        $pct = OperationalSettings::modernFrontendPilotRolloutPercent();
        if ($pct >= 100) {
            return true;
        }
        if ($pct <= 0) {
            return false;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }
        $bucket = abs(crc32('modern:' . $userId)) % 100;

        return $bucket < $pct;
    }

    public static function assetUrl(string $relative): string
    {
        $rel = ltrim(str_replace('\\', '/', $relative), '/');
        $path = self::assetPath($rel);
        $suffix = '';
        if (is_readable($path)) {
            $mtime = @filemtime($path);
            if ($mtime !== false) {
                $suffix = '?v=' . $mtime;
            }
        }
        // Oturum hangi host/yol ile açıksa aynı köken (127.0.0.1 vs localhost, alt dizin vb.)
        if (PHP_SAPI !== 'cli' && function_exists('esh_public_web_path')) {
            $public = rtrim((string) esh_public_web_path(), '/');
            if ($public !== '') {
                return $public . '/assets/modern/' . $rel . $suffix;
            }
        }

        return rtrim((string) ASSETS_URL, '/') . '/modern/' . $rel . $suffix;
    }

    public static function assetPath(string $relative): string
    {
        $rel = ltrim(str_replace('\\', '/', $relative), '/');

        return ROOT_PATH . '/public/assets/modern/' . $rel;
    }

    public static function assetExists(string $relative): bool
    {
        return is_readable(self::assetPath($relative));
    }

    private static function builtAssetRelative(string $scope): string
    {
        $name = strtolower(trim($scope)) === 'planning'
            ? 'planning-pilot.built.mjs'
            : 'dashboard-pilot.built.mjs';

        return 'dist/' . $name;
    }
}
