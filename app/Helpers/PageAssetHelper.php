<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Sayfa özel CSS — head içinde yüklenir (FOUC önleme).
 * Controller/action eşlemesi + manuel kayıt.
 */
final class PageAssetHelper
{
    /** @var list<string> */
    private static array $registered = [];

    /**
     * @var array<string, array<string, list<string>>>
     */
    private const CONTROLLER_ACTION_CSS = [
        'visit' => [
            'index' => ['visit-index.css'],
            'history' => ['visit-history.css'],
            'patientplans' => ['visit-history.css'],
            'missed' => ['visit-index.css'],
            'create' => ['form-pages.css', 'visit-index.css', 'field-visit-mobile.css'],
            'edit' => ['form-pages.css', 'visit-index.css', 'field-visit-mobile.css'],
        ],
        'plannedvisit' => [
            'index' => ['plannedvisit-index.css'],
            'passivependingplans' => ['plannedvisit-index.css'],
            'patient' => ['visit-history.css'],
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'patient' => [
            'unified' => ['patient-unified.css'],
            'listactive' => ['patient-list.css'],
            'listpassive' => ['patient-list.css'],
            'listwaiting' => ['patient-list.css'],
            'edit' => ['form-pages.css'],
            'ilkkayit' => ['form-pages.css'],
            'bedit' => ['form-pages.css'],
            'braden' => ['form-pages.css'],
            'itaki' => ['form-pages.css'],
            'harizmi' => ['form-pages.css'],
            'mna' => ['form-pages.css'],
            'barthel' => ['form-pages.css'],
            'detail' => ['patient-unified.css'],
        ],
        'mesaj' => [
            'index' => ['mesaj.css'],
            'mailbox' => ['mesaj.css'],
            'sent' => ['mesaj.css'],
            'trash' => ['mesaj.css'],
            'thread' => ['mesaj.css'],
            'compose' => ['mesaj.css'],
        ],
        'randevu' => [
            'index' => ['randevu-index.css'],
        ],
        'uhds' => [
            'index' => ['randevu-index.css'],
            'video' => ['uhds-video.css'],
        ],
        'erapor' => [
            'index' => ['erapor-index.css'],
            'create' => ['form-pages.css'],
        ],
        'hastailacrapor' => [
            'index' => ['hastailacrapor-index.css'],
        ],
        'harita' => [
            'index' => ['harita-index.css'],
        ],
        'manuelkoordinat' => [
            'index' => ['manuelkoordinat-index.css'],
        ],
        'pansuman' => [
            'index' => ['pansuman-index.css'],
        ],
        'nobet' => [
            'index' => ['nobet-index.css'],
        ],
        'settings' => [
            'index' => ['settings-index.css'],
        ],
        'planning' => [
            'index' => ['planning-index.css'],
        ],
        'theme' => [
            'editor' => ['theme-editor.css'],
        ],
        'user' => [
            'index' => ['user-list.css'],
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'hastalik' => [
            'index' => ['hastalik-index.css'],
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'kurum' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'islem' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'istek' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'brans' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'arac' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'guvence' => [
            'create' => ['form-pages.css'],
            'edit' => ['form-pages.css'],
        ],
        'stok' => [
            'malzemecreate' => ['form-pages.css'],
            'malzemeedit' => ['form-pages.css'],
            'giris' => ['form-pages.css'],
            'cikis' => ['form-pages.css'],
            'iade' => ['form-pages.css'],
            'sayim' => ['form-pages.css'],
            'hastaozet' => ['form-pages.css'],
            'siparisoneri' => ['form-pages.css'],
        ],
        'ekip' => [
            'edit' => ['form-pages.css'],
        ],
        'archive' => [
            'index' => ['stats-common.css'],
        ],
        'dashboard' => [
            'showroute' => ['rota-index.css'],
            'index' => ['rota-index.css'],
            'admin' => ['rota-index.css'],
        ],
        'stats' => [
            'charts' => ['stats-hub.css', 'stats-common.css', 'stats-charts.css'],
            'workload' => ['stats-hub.css', 'stats-common.css', 'stats-workload.css'],
        ],
        'publichastaarama' => [
            'index' => ['public-hastaarama.css'],
            'search' => ['public-hastaarama.css'],
        ],
    ];

  /**
     * @var array<string, list<string>>
     */
    private const CONTROLLER_DEFAULT_CSS = [
        'stats' => ['stats-hub.css', 'stats-common.css'],
        'visit' => [],
        'patient' => [],
    ];

    public static function registerPageStylesheet(string $filename): void
    {
        $filename = self::sanitizeFilename($filename);
        if ($filename === '') {
            return;
        }
        if (!in_array($filename, self::$registered, true)) {
            self::$registered[] = $filename;
        }
    }

    /**
     * @param list<string> $filenames
     */
    public static function registerPageStylesheets(array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::registerPageStylesheet((string) $filename);
        }
    }

    public static function registerFromRequest(?string $controllerName, ?string $actionName): void
    {
        $controller = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $controllerName));
        $action = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $actionName));

        if ($controller === 'stats') {
            $statsAction = $action !== '' ? $action : (isset($_GET['action']) ? trim((string) $_GET['action']) : '');
            $statsAction = preg_replace('/[^a-z0-9_-]/', '', strtolower($statsAction));
            if ($statsAction === '' || $statsAction === 'index' || $statsAction === 'hub') {
                self::registerPageStylesheets(['stats-hub.css']);
            } elseif (isset(self::CONTROLLER_ACTION_CSS['stats'][$statsAction])) {
                self::registerPageStylesheets(self::CONTROLLER_ACTION_CSS['stats'][$statsAction]);
            } else {
                self::registerPageStylesheets(self::CONTROLLER_DEFAULT_CSS['stats']);
            }

            return;
        }

        if ($controller !== '' && isset(self::CONTROLLER_DEFAULT_CSS[$controller])) {
            self::registerPageStylesheets(self::CONTROLLER_DEFAULT_CSS[$controller]);
        }

        if ($controller !== '' && $action !== '' && isset(self::CONTROLLER_ACTION_CSS[$controller][$action])) {
            self::registerPageStylesheets(self::CONTROLLER_ACTION_CSS[$controller][$action]);
        }
    }

    public static function renderRegisteredStylesheetsHtml(): string
    {
        if (self::$registered === []) {
            return '';
        }

        $base = defined('ASSETS_URL') ? rtrim((string) ASSETS_URL, '/') : '';
        $out = '';
        foreach (self::$registered as $filename) {
            $href = $base . '/pages/css/' . $filename;
            $abs = defined('ROOT_PATH') ? ROOT_PATH . '/public/assets/pages/css/' . $filename : '';
            if ($abs !== '' && is_file($abs)) {
                $href .= '?v=' . (string) filemtime($abs);
            }
            $out .= '<link rel="stylesheet" href="'
                . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                . '">' . "\n";
        }

        return $out;
    }

    public static function reset(): void
    {
        self::$registered = [];
    }

    private static function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = str_replace('\\', '/', $filename);
        $basename = basename($filename);

        if ($basename === '' || !preg_match('/^[a-z0-9][a-z0-9._-]*\.css$/i', $basename)) {
            return '';
        }

        return $basename;
    }
}
