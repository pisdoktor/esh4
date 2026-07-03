<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeCssColorHelper;
use App\Helpers\ThemeViewHelper;

class ThemeController
{
    /** JSON döndüren eylemler — yetkisiz istekte HTML yönlendirme yerine JSON. */
    private const JSON_ACTIONS = [
        'applySessionPreview',
        'clearSessionPreview',
        'previewSave',
        'saveColors',
        'sessionPreviewState',
        'indexRows',
    ];

    public function __construct()
    {
        $action = (string) ($GLOBALS['actionName'] ?? '');
        $isJsonAction = in_array($action, self::JSON_ACTIONS, true);

        if (!AuthHelper::sessionIsSuperAdmin()) {
            if ($isJsonAction) {
                self::jsonResponse(403, ['ok' => false, 'error' => 'Bu alana erişim yetkiniz bulunmamaktadır!']);
            }
            AuthHelper::requireSuperAdmin();
        }
    }

    public function index()
    {
        $siteThemeSlug = ThemeViewHelper::siteThemeSlug();
        $effectiveThemeSlug = ThemeViewHelper::activeTheme();
        $indexRowsFetchUrl = esh_url('Theme', 'indexRows');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'theme/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Tema listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows()
    {
        $themesMeta = ThemeViewHelper::discoverThemesMeta();
        $siteThemeSlug = ThemeViewHelper::siteThemeSlug();

        ob_start();
        include ROOT_PATH . '/views/admin/theme/partials/theme_list_table_rows.php';
        $html = ob_get_clean();

        self::jsonResponse(200, ['ok' => true, 'html' => $html]);
    }

    public function editor()
    {
        $themesMeta = ThemeViewHelper::discoverThemesMeta();
        $slugs = array_column($themesMeta, 'slug');
        $requested = isset($_GET['theme']) ? ThemeViewHelper::sanitizeThemeSlug((string) $_GET['theme']) : '';
        $editorThemeSlug = ($requested !== '' && in_array($requested, $slugs, true))
            ? $requested
            : ThemeViewHelper::siteThemeSlug();
        if (!ThemeViewHelper::isInstalledThemeSlug($editorThemeSlug) && $slugs !== []) {
            $editorThemeSlug = (string) $slugs[0];
        }

        $colorTokens = ThemeCssColorHelper::parseColorTokens($editorThemeSlug);
        $colorTokenGroups = ThemeCssColorHelper::parseColorTokenGroups($editorThemeSlug);
        $gradientVarTokens = ThemeCssColorHelper::parseGradientVariableTokens($editorThemeSlug);
        $gradientPropEntries = ThemeCssColorHelper::parseGradientPropertyEntries($editorThemeSlug);
        $otherVarTokens = ThemeCssColorHelper::parseOtherVariableTokens($editorThemeSlug);
        $otherPropEntries = ThemeCssColorHelper::parseOtherPropertyEntries($editorThemeSlug);
        $typographyVarTokens = ThemeCssColorHelper::parseTypographyVariableTokens($editorThemeSlug);
        $typographyPropEntries = ThemeCssColorHelper::parseTypographyPropertyEntries($editorThemeSlug);
        $eshUiTokensAll = ThemeCssColorHelper::parseEshUiVariableTokens($editorThemeSlug);
        $eshUiTokenGroups = ThemeCssColorHelper::parseEshUiVariableTokenGroups($editorThemeSlug);
        $eshUiTokens = [];
        $eshUiTypographyTokens = [];
        foreach ($eshUiTokensAll as $eshRow) {
            $n = (string) ($eshRow['name'] ?? '');
            if (ThemeCssColorHelper::isEshUiTypographyTokenName($n)) {
                $eshUiTypographyTokens[] = $eshRow;
            } else {
                $eshUiTokens[] = $eshRow;
            }
        }
        $eshUiBridgePresent = ThemeCssColorHelper::themeHasEshUiBridgeInCss($editorThemeSlug);
        $eshUiBridgeSuggestion = ThemeCssColorHelper::suggestedEshUiBridgeCss($editorThemeSlug);
        $previewBodyClasses = ThemeCssColorHelper::previewBodyClasses($editorThemeSlug);
        $previewStylesheetUrls = ThemeCssColorHelper::previewStylesheetUrls($editorThemeSlug);
        $sessionPreview = ThemeCssColorHelper::getSessionPreview();
        $sessionPreviewActive = is_array($sessionPreview)
            && ($sessionPreview['theme'] ?? '') === $editorThemeSlug;

        $editorEmbedded = ThemeViewHelper::editorEmbeddedRequested();
        $editorStandalone = !$editorEmbedded;

        if ($editorStandalone) {
            ThemeViewHelper::renderEditorStandaloneDocumentOpen($editorThemeSlug);
            include ThemeViewHelper::resolveAreaView('admin', 'theme/editor');
            ThemeViewHelper::renderEditorStandaloneDocumentClose();

            return;
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'theme/editor');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function previewShell()
    {
        $theme = isset($_GET['theme']) ? ThemeViewHelper::sanitizeThemeSlug((string) $_GET['theme']) : '';
        if ($theme === '' || !ThemeViewHelper::isInstalledThemeSlug($theme)) {
            http_response_code(404);
            echo 'Tema bulunamadı.';
            exit;
        }

        $previewThemeSlug = $theme;
        $previewBodyClasses = ThemeCssColorHelper::previewBodyClasses($theme);
        $previewStylesheetUrls = ThemeCssColorHelper::previewStylesheetUrls($theme);

        include ROOT_PATH . '/views/admin/theme/preview_shell.php';
        exit;
    }

    public function sessionPreviewState()
    {
        self::jsonResponse(200, [
            'ok' => true,
            'preview' => ThemeCssColorHelper::getSessionPreview(),
        ]);
    }

    public function applySessionPreview()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            self::jsonResponse(405, ['ok' => false, 'error' => 'Yalnızca POST']);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz JSON']);
        }

        $theme = ThemeViewHelper::sanitizeThemeSlug((string) ($payload['theme'] ?? ''));
        if ($theme === '' || !ThemeViewHelper::isInstalledThemeSlug($theme)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz tema']);
        }

        $validated = self::validateEditorPayload($theme, $payload);
        if (!$validated['ok']) {
            self::jsonResponse(400, ['ok' => false, 'error' => $validated['error']]);
        }

        ThemeCssColorHelper::setSessionPreview(
            $theme,
            $validated['vars'],
            $validated['properties']
        );

        self::jsonResponse(200, [
            'ok' => true,
            'message' => 'Oturum önizlemesi uygulandı. Tüm sayfalarda geçerli; dosyaya yazılmadı.',
            'preview' => ThemeCssColorHelper::getSessionPreview(),
        ]);
    }

    public function clearSessionPreview()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            self::jsonResponse(405, ['ok' => false, 'error' => 'Yalnızca POST']);
        }

        ThemeCssColorHelper::clearSessionPreview();

        self::jsonResponse(200, [
            'ok' => true,
            'message' => 'Oturum önizlemesi temizlendi.',
        ]);
    }

    public function previewSave()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            self::jsonResponse(405, ['ok' => false, 'error' => 'Yalnızca POST']);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz JSON']);
        }

        $theme = ThemeViewHelper::sanitizeThemeSlug((string) ($payload['theme'] ?? ''));
        if ($theme === '' || !ThemeViewHelper::isInstalledThemeSlug($theme)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz istek']);
        }

        $maps = self::editorUpdateMapsFromPayload($payload);
        $result = ThemeCssColorHelper::previewThemeEdits(
            $theme,
            $maps['colors'],
            $maps['gradient_vars'],
            $maps['gradient_props'],
            $maps['other_vars'],
            $maps['other_props'],
            $maps['esh_ui_vars'],
            $maps['typography_vars'],
            $maps['typography_props']
        );

        if (!$result['ok']) {
            self::jsonResponse(400, ['ok' => false, 'error' => $result['message']]);
        }

        self::jsonResponse(200, [
            'ok' => true,
            'message' => $result['message'],
            'changed' => $result['changed'],
            'changes' => $result['changes'],
        ]);
    }

    public function saveColors()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            self::jsonResponse(405, ['ok' => false, 'error' => 'Yalnızca POST']);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz JSON']);
        }

        $theme = ThemeViewHelper::sanitizeThemeSlug((string) ($payload['theme'] ?? ''));
        if ($theme === '' || !ThemeViewHelper::isInstalledThemeSlug($theme)) {
            self::jsonResponse(400, ['ok' => false, 'error' => 'Geçersiz istek']);
        }

        $maps = self::editorUpdateMapsFromPayload($payload);
        $result = ThemeCssColorHelper::applyThemeEdits(
            $theme,
            $maps['colors'],
            $maps['gradient_vars'],
            $maps['gradient_props'],
            $maps['other_vars'],
            $maps['other_props'],
            $maps['esh_ui_vars'],
            $maps['typography_vars'],
            $maps['typography_props']
        );
        if (!$result['ok']) {
            self::jsonResponse(400, ['ok' => false, 'error' => $result['message']]);
        }

        self::jsonResponse(200, [
            'ok' => true,
            'message' => $result['message'],
            'backup' => $result['backup'],
            'tokens' => ThemeCssColorHelper::parseColorTokens($theme),
            'gradient_vars' => ThemeCssColorHelper::parseGradientVariableTokens($theme),
            'gradient_props' => ThemeCssColorHelper::parseGradientPropertyEntries($theme),
            'other_vars' => ThemeCssColorHelper::parseOtherVariableTokens($theme),
            'other_props' => ThemeCssColorHelper::parseOtherPropertyEntries($theme),
            'typography_vars' => ThemeCssColorHelper::parseTypographyVariableTokens($theme),
            'typography_props' => ThemeCssColorHelper::parseTypographyPropertyEntries($theme),
            'esh_ui_vars' => ThemeCssColorHelper::parseEshUiVariableTokens($theme),
        ]);
    }

    public function activate()
    {
        $theme = isset($_GET['theme']) ? trim((string) $_GET['theme']) : '';
        $theme = strtolower(preg_replace('/[^a-z0-9_-]/', '', $theme));
        if ($theme === '') {
            $_SESSION['error'] = 'Geçersiz tema.';
            header('Location: ' . esh_url('Theme', 'index'));
            exit;
        }

        $themesMeta = ThemeViewHelper::discoverThemesMeta();
        $slugs = array_column($themesMeta, 'slug');
        if (!in_array($theme, $slugs, true) || !ThemeViewHelper::isInstalledThemeSlug($theme)) {
            $_SESSION['error'] = 'Seçilen tema bulunamadı veya kurulu değil.';
            header('Location: ' . esh_url('Theme', 'index'));
            exit;
        }

        $cfgFile = ROOT_PATH . '/config/config.local.php';
        $cfg = [];
        if (is_file($cfgFile)) {
            $loaded = include $cfgFile;
            if (is_array($loaded)) {
                $cfg = $loaded;
            }
        }
        $cfg['active_theme'] = $theme;

        $content = "<?php\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= 'return ' . var_export($cfg, true) . ";\n";

        if (file_put_contents($cfgFile, $content) === false) {
            $_SESSION['error'] = 'Tema ayarı kaydedilemedi.';
            header('Location: ' . esh_url('Theme', 'index'));
            exit;
        }

        $_SESSION['success'] = 'Aktif tema güncellendi: ' . $theme;
        header('Location: ' . esh_url('Theme', 'index'));
        exit;
    }

    /**
     * @return array{ok:bool,error?:string,vars?:array<string,string>,properties?:array<string,array{property:string,value:string}>}
     */
    private static function validateEditorPayload(string $theme, array $payload): array
    {
        $allowedColorNames = [];
        foreach (ThemeCssColorHelper::parseColorTokens($theme) as $row) {
            $allowedColorNames[$row['name']] = true;
        }

        $allowedGradVarNames = [];
        foreach (ThemeCssColorHelper::parseGradientVariableTokens($theme) as $row) {
            $allowedGradVarNames[$row['name']] = true;
        }

        $allowedGradProps = [];
        foreach (ThemeCssColorHelper::parseGradientPropertyEntries($theme) as $row) {
            $allowedGradProps[$row['id']] = $row;
        }

        $allowedOtherVarNames = [];
        foreach (ThemeCssColorHelper::parseOtherVariableTokens($theme) as $row) {
            $allowedOtherVarNames[$row['name']] = true;
        }

        $allowedOtherProps = [];
        foreach (ThemeCssColorHelper::parseOtherPropertyEntries($theme) as $row) {
            $allowedOtherProps[$row['id']] = $row;
        }

        $allowedTypographyVarNames = [];
        foreach (ThemeCssColorHelper::parseTypographyVariableTokens($theme) as $row) {
            $allowedTypographyVarNames[$row['name']] = true;
        }

        $allowedTypographyProps = [];
        foreach (ThemeCssColorHelper::parseTypographyPropertyEntries($theme) as $row) {
            $allowedTypographyProps[$row['id']] = $row;
        }

        $allowedEshUi = [];
        foreach (array_keys(ThemeCssColorHelper::eshUiTokenCatalog()) as $name) {
            $allowedEshUi[$name] = true;
        }

        $vars = [];
        foreach (self::stringMapFromPayload($payload['colors'] ?? []) as $name => $value) {
            if (!isset($allowedColorNames[$name])) {
                continue;
            }
            if (!ThemeCssColorHelper::isColorCSSValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz renk: ' . $name];
            }
            $vars[$name] = $value;
        }

        foreach (self::stringMapFromPayload($payload['gradient_vars'] ?? []) as $key => $value) {
            $name = str_starts_with($key, 'var:') ? substr($key, 4) : $key;
            if (!isset($allowedGradVarNames[$name])) {
                continue;
            }
            if (!ThemeCssColorHelper::isGradientCSSValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz gradyan değişkeni: ' . $name];
            }
            $vars[$name] = $value;
        }

        foreach (self::stringMapFromPayload($payload['other_vars'] ?? []) as $key => $value) {
            $name = str_starts_with($key, 'var:') ? substr($key, 4) : $key;
            if (!isset($allowedOtherVarNames[$name])) {
                continue;
            }
            if (!ThemeCssColorHelper::isOtherCssVariableValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz jeton: ' . $name];
            }
            $vars[$name] = $value;
        }

        foreach (self::stringMapFromPayload($payload['typography_vars'] ?? []) as $key => $value) {
            $name = str_starts_with($key, 'var:') ? substr($key, 4) : $key;
            if (!isset($allowedTypographyVarNames[$name])) {
                continue;
            }
            if (!ThemeCssColorHelper::isTypographyCssVariableValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz tipografi jetonu: ' . $name];
            }
            $vars[$name] = $value;
        }

        foreach (self::stringMapFromPayload($payload['esh_ui_vars'] ?? []) as $key => $value) {
            $name = str_starts_with($key, 'var:') ? substr($key, 4) : $key;
            if (!isset($allowedEshUi[$name])) {
                continue;
            }
            if (!ThemeCssColorHelper::isEshUiVariableValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz sayfa standardı: ' . $name];
            }
            $vars[$name] = $value;
        }

        $properties = [];
        foreach (self::stringMapFromPayload($payload['gradient_props'] ?? []) as $id => $value) {
            if (!isset($allowedGradProps[$id])) {
                continue;
            }
            if (!ThemeCssColorHelper::isGradientCSSValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz gradyan satırı: ' . $id];
            }
            $properties[$id] = [
                'property' => (string) $allowedGradProps[$id]['property'],
                'value' => $value,
            ];
        }

        foreach (self::stringMapFromPayload($payload['other_props'] ?? []) as $id => $value) {
            if (!isset($allowedOtherProps[$id])) {
                continue;
            }
            if (!ThemeCssColorHelper::isOtherPropertyValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz kural satırı: ' . $id];
            }
            $properties[$id] = [
                'property' => (string) $allowedOtherProps[$id]['property'],
                'value' => $value,
            ];
        }

        foreach (self::stringMapFromPayload($payload['typography_props'] ?? []) as $id => $value) {
            if (!isset($allowedTypographyProps[$id])) {
                continue;
            }
            if (!ThemeCssColorHelper::isTypographyPropertyValue($value)) {
                return ['ok' => false, 'error' => 'Geçersiz tipografi satırı: ' . $id];
            }
            $properties[$id] = [
                'property' => (string) $allowedTypographyProps[$id]['property'],
                'value' => $value,
            ];
        }

        if ($vars === [] && $properties === []) {
            return ['ok' => false, 'error' => 'Önizlenecek değişiklik yok.'];
        }

        return ['ok' => true, 'vars' => $vars, 'properties' => $properties];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *   colors: array<string, string>,
     *   gradient_vars: array<string, string>,
     *   gradient_props: array<string, string>,
     *   other_vars: array<string, string>,
     *   other_props: array<string, string>,
     *   esh_ui_vars: array<string, string>,
     *   typography_vars: array<string, string>,
     *   typography_props: array<string, string>
     * }
     */
    private static function editorUpdateMapsFromPayload(array $payload): array
    {
        return [
            'colors' => self::stringMapFromPayload($payload['colors'] ?? []),
            'gradient_vars' => self::stringMapFromPayload($payload['gradient_vars'] ?? []),
            'gradient_props' => self::stringMapFromPayload($payload['gradient_props'] ?? []),
            'other_vars' => self::stringMapFromPayload($payload['other_vars'] ?? []),
            'other_props' => self::stringMapFromPayload($payload['other_props'] ?? []),
            'typography_vars' => self::stringMapFromPayload($payload['typography_vars'] ?? []),
            'typography_props' => self::stringMapFromPayload($payload['typography_props'] ?? []),
            'esh_ui_vars' => self::stringMapFromPayload($payload['esh_ui_vars'] ?? []),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function stringMapFromPayload(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $k = trim($key);
            $v = trim($value);
            if ($k !== '' && $v !== '') {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function jsonResponse(int $statusCode, array $data): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $json = '{"ok":false,"error":"JSON kodlama hatası"}';
        }
        echo $json;
        exit;
    }
}
