<?php

declare(strict_types=1);

/**
 * Güvenlik yardımcıları — config.php bölüm 10 sonrasında yüklenir.
 */

require_once __DIR__ . '/../app/Helpers/CsrfHelper.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../app/Helpers/RateLimitHelper.php';

use App\Helpers\CsrfHelper;

function esh_csrf_token(): string
{
    return CsrfHelper::token();
}

function esh_csrf_field(): string
{
    return CsrfHelper::hiddenField();
}

function esh_csrf_meta(): string
{
    return CsrfHelper::metaTag();
}

/**
 * İstek başına CSP nonce — gelecekte inline script/style taşırken nonce="..." ile kullanın.
 * UYARI: nonce CSP başlığına eklenmeden önce tüm inline script/style nonce almalıdır;
 * aksi halde tarayıcı 'unsafe-inline'ı yok sayar ve sayfa kırılır.
 */
function esh_csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    return $nonce;
}

/** Inline <script> için: nonce="..." */
function esh_csp_nonce_attr(): string
{
    return ' nonce="' . htmlspecialchars(esh_csp_nonce(), ENT_QUOTES, 'UTF-8') . '"';
}

/**
 * Harici/yerel script dosyası — sıkı CSP (strict-dynamic) için nonce zorunlu.
 * $attrs: src öncesi öznitelikler (örn. "defer", "type=\"module\"").
 */
function esh_csp_script_src_tag(string $src, string $attrs = ''): string
{
    $tag = '<script' . esh_csp_nonce_attr();
    $extra = trim($attrs);
    if ($extra !== '') {
        $tag .= ' ' . $extra;
    }

    return $tag . ' src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}

/**
 * Sıkı script CSP: script-src yalnızca nonce (+ strict-dynamic); unsafe-inline yok.
 * Ortam: ESH_CSP_SCRIPT_NONCE_STRICT=1 veya config.local.php → csp_script_nonce_strict
 */
function esh_csp_script_strict_enabled(): bool
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }
    $env = getenv('ESH_CSP_SCRIPT_NONCE_STRICT');
    if ($env !== false && $env !== '') {
        $resolved = in_array(strtolower(trim($env)), ['1', 'true', 'yes', 'on'], true);

        return $resolved;
    }
    if (function_exists('esh_config_local')) {
        $local = esh_config_local('csp_script_nonce_strict', null);
        if ($local !== null && $local !== '') {
            $resolved = filter_var($local, FILTER_VALIDATE_BOOLEAN);

            return $resolved;
        }
    }
    $resolved = false;

    return $resolved;
}

/** @return list<string> Harici script-src hostları */
function esh_csp_script_src_hosts(): array
{
    return [
        'https://api.tomtom.com',
        'https://*.tomtom.com',
        'https://api.mapbox.com',
        'https://maps.googleapis.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://code.jquery.com',
    ];
}

function esh_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    $nonce = esh_csp_nonce();
    $nonceToken = "'nonce-" . $nonce . "'";
    $scriptHosts = implode(' ', esh_csp_script_src_hosts());
    if (esh_csp_script_strict_enabled()) {
        // style-src: nonce eklenmez — style="" öznitelikleri unsafe-inline gerektirir.
        $scriptSrc = "'self' {$nonceToken} 'strict-dynamic' blob: {$scriptHosts}";
    } else {
        // Geçiş: inline script'lere nonce eklenir; başlıkta nonce yok → unsafe-inline geçerli kalır.
        $scriptSrc = "'self' 'unsafe-inline' blob: {$scriptHosts}";
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // CdnAssetHelper vendor kaynakları + runtime ihtiyaçları:
    // TomTom worker (blob:), EK-3 pdfMake iframe (data:), e-imza köprüsü (127.0.0.1), jQuery UI ikonları (code.jquery.com)
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src {$scriptSrc}; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://code.jquery.com https://api.tomtom.com https://api.mapbox.com; "
        . "img-src 'self' data: blob: https://*.tomtom.com https://api.tomtom.com https://*.tiles.mapbox.com https://api.mapbox.com https://maps.gstatic.com https://maps.googleapis.com https://tile.openstreetmap.org https://*.openstreetmap.org https://basemaps.cartocdn.com https://*.cartocdn.com https://code.jquery.com; "
        . "font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com https://api.mapbox.com https://*.tiles.mapbox.com; "
        . "connect-src 'self' blob: https://api.tomtom.com https://*.tomtom.com https://api.openrouteservice.org https://api.mapbox.com https://*.mapbox.com https://events.mapbox.com https://*.tiles.mapbox.com https://tile.openstreetmap.org https://*.openstreetmap.org https://basemaps.cartocdn.com https://*.cartocdn.com https://maps.googleapis.com https://maps.gstatic.com http://127.0.0.1:15873 https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
        . "worker-src 'self' blob:; "
        . "frame-src 'self' data:; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
    );
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
