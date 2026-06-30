<?php
declare(strict_types=1);

/**
 * ESH — URL ve form yardımcıları (global fonksiyonlar)
 *
 * Yükleme: config/config.php → bölüm 12 (require_once).
 * Bağımlılık: app/Helpers/UrlHelper.php (SEF ve legacy query-string mantığı).
 *
 * ESH_SEF_URLS_ENABLED (config.local.php → sef_urls_enabled) true iken rotalar
 * /public/Controller/action biçiminde; false iken index.php?controller=… kullanılır.
 *
 * ── Fonksiyonlar ─────────────────────────────────────────────────────────────
 *  esh_url                  — rota URL üretir
 *  esh_url_from_query        — query string veya göreli yolu çözümler
 *  esh_url_from_merged_get   — mevcut GET + override ile URL
 *  esh_redirect              — Location başlığı ile yönlendirme (never)
 *  esh_public_web_path       — public/ web yolu öneki
 *  esh_form_action           — form action attribute
 *  esh_form_route_hiddens    — SEF kapalıyken controller/action gizli alanları
 */

require_once __DIR__ . '/../app/Helpers/UrlHelper.php';

use App\Helpers\UrlHelper;

// =============================================================================
// ROTA URL ÜRETİMİ
// =============================================================================

/**
 * @param array<string, scalar|null> $params
 */
function esh_url(string $controller, string $action = 'index', array $params = [], bool $absolute = false): string
{
    return UrlHelper::route($controller, $action, $params, $absolute);
}

function esh_url_from_query(string $queryOrUrl, bool $absolute = false): string
{
    return UrlHelper::fromQueryString($queryOrUrl, $absolute);
}

/**
 * @param array<string, scalar|null> $overrides
 */
function esh_url_from_merged_get(array $overrides, bool $absolute = false): string
{
    return UrlHelper::fromMergedGet($overrides, $absolute);
}

/**
 * @param array<string, scalar|null> $params
 */
function esh_redirect(string $controller, string $action = 'index', array $params = [], int $code = 302): never
{
    UrlHelper::redirect($controller, $action, $params, $code);
}

// =============================================================================
// PUBLIC YOL VE FORM
// =============================================================================

function esh_public_web_path(): string
{
    return UrlHelper::publicWebPath();
}

function esh_project_web_path(): string
{
    return UrlHelper::projectWebPath();
}

function esh_project_root_script_url(string $basename): string
{
    return UrlHelper::projectRootScriptUrl($basename);
}

function esh_form_action(string $controller, string $action = 'index'): string
{
    return UrlHelper::formAction($controller, $action);
}

/**
 * SEF kapalıyken filtre/POST formlarına controller ve action gizli alanları ekler.
 */
function esh_form_route_hiddens(string $controller, string $action = 'index'): string
{
    $out = esh_csrf_field();
    if (defined('ESH_SEF_URLS_ENABLED') && ESH_SEF_URLS_ENABLED) {
        return $out;
    }

    $c = htmlspecialchars($controller, ENT_QUOTES, 'UTF-8');
    $a = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');

    return '<input type="hidden" name="controller" value="' . $c . '">' . "\n"
        . '                <input type="hidden" name="action" value="' . $a . '">' . "\n"
        . '                ' . $out;
}

/**
 * Oturumlu upload proxy URL (patients, wounds, profile, temp).
 */
function esh_upload_url(string $type, string $filename): string
{
    $type = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($type)));
    $filename = basename(trim($filename));
    if ($type === '' || $filename === '') {
        return '';
    }

    return esh_url('Upload', 'serve', ['type' => $type, 'file' => $filename]);
}
