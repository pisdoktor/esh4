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

function esh_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // CdnAssetHelper vendor kaynakları + runtime ihtiyaçları:
    // TomTom worker (blob:), EK-3 pdfMake iframe (data:), e-imza köprüsü (127.0.0.1), jQuery UI ikonları (code.jquery.com)
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://api.tomtom.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://code.jquery.com https://api.tomtom.com; "
        . "img-src 'self' data: blob: https://*.tomtom.com https://api.tomtom.com https://code.jquery.com; "
        . "font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
        . "connect-src 'self' https://api.tomtom.com https://*.tomtom.com http://127.0.0.1:15873 https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
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
