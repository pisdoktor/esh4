<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * PWA manifest ve mobil ana ekran meta etiketleri.
 */
final class PwaHelper
{
    private const THEME_COLOR = '#198754';
    private const BACKGROUND_COLOR = '#ffffff';

    public static function manifestUrl(): string
    {
        return rtrim((string) SITEURL, '/') . '/public/manifest.webmanifest';
    }

    public static function iconUrl(int $size): string
    {
        $size = $size === 512 ? 512 : 192;

        return rtrim((string) SITEURL, '/') . '/public/icons/icon-' . $size . '.png';
    }

    /**
     * &lt;head&gt; içine eklenecek PWA link ve meta HTML.
     */
    public static function renderHeadHtml(): string
    {
        $manifest = htmlspecialchars(self::manifestUrl(), ENT_QUOTES, 'UTF-8');
        $theme = htmlspecialchars(self::THEME_COLOR, ENT_QUOTES, 'UTF-8');
        $icon192 = htmlspecialchars(self::iconUrl(192), ENT_QUOTES, 'UTF-8');
        $appName = htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8');

        return implode("\n", [
            '<link rel="manifest" href="' . $manifest . '">',
            '<meta name="theme-color" content="' . $theme . '">',
            '<meta name="mobile-web-app-capable" content="yes">',
            '<meta name="apple-mobile-web-app-capable" content="yes">',
            '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
            '<meta name="apple-mobile-web-app-title" content="' . $appName . '">',
            '<link rel="apple-touch-icon" href="' . $icon192 . '">',
            '<script' . esh_csp_nonce_attr() . '>window.ESH_PWA=window.ESH_PWA||{};window.ESH_PWA.swUrl=' . json_encode(self::serviceWorkerUrl(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>',
        ]) . "\n";
    }

    public static function serviceWorkerUrl(): string
    {
        return rtrim((string) SITEURL, '/') . '/public/sw.js';
    }

    public static function themeColor(): string
    {
        return self::THEME_COLOR;
    }

    public static function backgroundColor(): string
    {
        return self::BACKGROUND_COLOR;
    }
}
