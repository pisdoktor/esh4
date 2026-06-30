<?php
declare(strict_types=1);

/**
 * Aktif tema stilinin kaynağı: `templates/<tema>/theme.css`
 * Oturum önizlemesi (admin renk editörü) üst katman CSS olarak eklenir.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Helpers/ThemeViewHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/ThemeCssColorHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$slug = isset($_GET['s']) ? (string) $_GET['s'] : 'default';
$slug = \App\Helpers\ThemeViewHelper::sanitizeThemeSlug($slug);
$path = \App\Helpers\ThemeViewHelper::themeCssPath($slug);

if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '/* Tema stylesheet bulunamadı */';
    exit;
}

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

readfile($path);
echo \App\Helpers\ThemeCssColorHelper::renderSessionPreviewCssBlock($slug);
