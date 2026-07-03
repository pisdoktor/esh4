<?php
namespace App\Helpers;

class ThemeViewHelper
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $manifestBySlug = null;

    private static function rootPath(string $suffix = ''): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . $suffix;
    }

    public static function sanitizeThemeSlug(string $raw): string
    {
        $slug = strtolower(trim($raw));
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        return $slug !== '' ? $slug : 'default';
    }

    /**
     * Meridian üstü ikinci katman temalar (`meridian` → slug): Catppuccin ailesi, Dracula, Nord.
     *
     * @return list<string>
     */
    private static function meridianOverlayThemeSlugs(): array
    {
        return [
            'catppuccin-latte',
            'catppuccin-mocha',
            'github-dark',
            'github-light',
            'nord',
        ];
    }

    /**
     * Kaldırılmış tema slug → kurulu karşılık (profil / site varsayılanı / oturum).
     *
     * @return array<string, string>
     */
    public static function retiredThemeSlugReplacements(): array
    {
        return [
            'winui-light' => 'winui',
            'catppuccin-frappe' => 'catppuccin-mocha',
            'catppuccin-macchiato' => 'catppuccin-mocha',
            'dracula' => 'nord',
        ];
    }

    /** Kaldırılmış slug’ları eşler; kurulu değilse `default`. */
    public static function resolveInstalledThemeSlug(string $slug): string
    {
        $s = self::sanitizeThemeSlug($slug);
        if ($s === '') {
            return self::isInstalledThemeSlug('default') ? 'default' : $s;
        }
        $replacements = self::retiredThemeSlugReplacements();
        for ($i = 0; $i < 8 && isset($replacements[$s]); $i++) {
            $s = self::sanitizeThemeSlug($replacements[$s]);
        }
        if (self::isInstalledThemeSlug($s)) {
            return $s;
        }

        return self::isInstalledThemeSlug('default') ? 'default' : $s;
    }

    private static function isMeridianOverlayTheme(string $slug): bool
    {
        return in_array($slug, self::meridianOverlayThemeSlugs(), true);
    }

    /** Site geneli varsayılan tema (`config.local` → `active_theme` / `ACTIVE_THEME`). */
    public static function siteThemeSlug(): string
    {
        $raw = defined('ACTIVE_THEME') ? (string) ACTIVE_THEME : 'default';

        return self::resolveInstalledThemeSlug($raw);
    }

    /** `templates/<slug>/theme.css` var mı (geçerli kurulum). */
    public static function isInstalledThemeSlug(string $slug): bool
    {
        $s = self::sanitizeThemeSlug($slug);

        return $s !== '' && is_file(self::themeCssPath($s));
    }

    /**
     * POST/DB değerini doğrular; geçersiz veya boş → null (site varsayılanı).
     */
    public static function normalizeUserUiThemeInput($raw): ?string
    {
        $t = is_string($raw) ? trim($raw) : '';
        if ($t === '' || $t === '__site__') {
            return null;
        }
        $s = self::sanitizeThemeSlug($t);
        if ($s === 'default' && $t !== 'default') {
            return null;
        }
        $s = self::resolveInstalledThemeSlug($s);

        return self::isInstalledThemeSlug($s) ? $s : null;
    }

    /**
     * Oturumdaki kullanıcı için DB `ui_theme` alanını çözümler.
     * Geçersiz slug veya boş: session anahtarı silinir → site varsayılanı.
     */
    public static function syncSessionUserThemeFromDb(?string $dbUiTheme): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return;
        }
        $t = $dbUiTheme === null ? '' : trim($dbUiTheme);
        if ($t === '') {
            unset($_SESSION['user_theme_slug']);

            return;
        }
        $s = self::resolveInstalledThemeSlug($t);
        if (self::isInstalledThemeSlug($s)) {
            $_SESSION['user_theme_slug'] = $s;
        } else {
            unset($_SESSION['user_theme_slug']);
        }
    }

    /**
     * Profil kaydı sonrası — yalnızca kendi hesabı için session güncellenir.
     */
    public static function syncSessionUserThemeAfterProfileSave(int $userId, ?string $savedUiTheme): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return;
        }
        if ((int) $_SESSION['user_id'] !== $userId) {
            return;
        }
        if ($savedUiTheme === null || trim($savedUiTheme) === '') {
            unset($_SESSION['user_theme_slug']);

            return;
        }
        $s = self::resolveInstalledThemeSlug($savedUiTheme);
        if (self::isInstalledThemeSlug($s)) {
            $_SESSION['user_theme_slug'] = $s;
        } else {
            unset($_SESSION['user_theme_slug']);
        }
    }

    /**
     * Geçerli tema: oturumda kayıtlı kullanıcı ve geçerli `user_theme_slug` varsa o; aksi halde site varsayılanı.
     * Giriş ekranı için `siteThemeSlug()` kullanılır (henüz kimlik yok / site görünümü).
     */
    public static function activeTheme(): string
    {
        $site = self::siteThemeSlug();
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return $site;
        }
        $sess = isset($_SESSION['user_theme_slug']) ? trim((string) $_SESSION['user_theme_slug']) : '';
        if ($sess !== '') {
            $s = self::resolveInstalledThemeSlug($sess);
            if (self::isInstalledThemeSlug($s)) {
                return $s;
            }
        }

        return $site;
    }

    /** `_theme.json` görünen adı + slug — profil ve liste etiketi. */
    public static function labelForThemeSlug(string $slug): string
    {
        $s = self::sanitizeThemeSlug($slug);
        foreach (self::discoverThemesMeta() as $row) {
            if (($row['slug'] ?? '') === $s) {
                $name = trim((string) ($row['name'] ?? ''));

                return $name !== '' ? $name . ' (' . $s . ')' : $s;
            }
        }

        return $s;
    }

    /** Liste / özet: boş tercih = site varsayılanına düşer (okunaklı). */
    public static function labelForUserUiThemePreference(?string $dbUiTheme): string
    {
        $t = $dbUiTheme === null ? '' : trim((string) $dbUiTheme);
        if ($t === '') {
            return 'Site varsayılanı · ' . self::siteThemeSlug();
        }
        $s = self::resolveInstalledThemeSlug($t);
        if (!self::isInstalledThemeSlug($s)) {
            return self::sanitizeThemeSlug($t) . ' (geçersiz tema)';
        }

        return self::labelForThemeSlug($s);
    }

    /** @return list<array{slug:string,name:string,surum:string,olusturulma_tarihi:string,guncelleme_tarihi:string,olusturan:string}> */
    public static function discoverThemesMeta(): array
    {
        $bySlug = [];

        $tryAdd = static function (array &$map, array $meta): void {
            $slug = $meta['slug'] ?? '';
            if ($slug !== '' && !isset($map[$slug])) {
                $map[$slug] = $meta;
            }
        };

        $templatesBase = self::rootPath('/templates');
        if (is_dir($templatesBase)) {
            foreach (scandir($templatesBase) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $base = $templatesBase . '/' . $entry;
                if (!is_dir($base)) {
                    continue;
                }
                $meta = self::themeMetaFromManifest($entry, $base . '/_theme.json');
                if ($meta !== null) {
                    $tryAdd($bySlug, $meta);
                }
            }
        }

        $adminBase = self::rootPath('/views/admin');
        $siteBase = self::rootPath('/views/site');
        if (is_dir($adminBase)) {
            foreach (scandir($adminBase) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $adm = $adminBase . '/' . $entry;
                $sit = $siteBase . '/' . $entry;
                if (!is_dir($adm) || !is_dir($sit)) {
                    continue;
                }
                if (!is_file($adm . '/_theme.json') || !is_file($sit . '/_theme.json')) {
                    continue;
                }
                $meta = self::themeMetaFromManifest($entry, $adm . '/_theme.json');
                if ($meta !== null) {
                    $tryAdd($bySlug, $meta);
                }
            }
        }

        ksort($bySlug);

        return array_values($bySlug);
    }

    /**
     * @return array{slug:string,name:string,surum:string,olusturulma_tarihi:string,guncelleme_tarihi:string,olusturan:string}|null
     */
    private static function themeMetaFromManifest(string $directoryName, string $manifestPath): ?array
    {
        if (!is_file($manifestPath)) {
            return null;
        }
        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        $slug = isset($decoded['slug']) ? strtolower(trim((string) $decoded['slug'])) : '';
        if ($slug === '') {
            $slug = $directoryName;
        }
        $slug = strtolower(preg_replace('/[^a-z0-9_-]/', '', $slug));
        if ($slug === '') {
            return null;
        }

        $name = trim((string) ($decoded['name'] ?? ''));
        if ($name === '') {
            $name = $directoryName;
        }

        $surum = trim((string) ($decoded['surum'] ?? ''));
        if ($surum === '') {
            $surum = trim((string) ($decoded['version'] ?? ''));
        }

        $cssStack = [];
        if (isset($decoded['css_stack']) && is_array($decoded['css_stack'])) {
            foreach ($decoded['css_stack'] as $item) {
                $itemSlug = self::sanitizeThemeSlug((string) $item);
                if ($itemSlug !== '' && self::isInstalledThemeSlug($itemSlug)) {
                    $cssStack[] = $itemSlug;
                }
            }
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'surum' => $surum,
            'olusturulma_tarihi' => trim((string) ($decoded['olusturulma_tarihi'] ?? '')),
            'guncelleme_tarihi' => trim((string) ($decoded['guncelleme_tarihi'] ?? '')),
            'olusturan' => trim((string) ($decoded['olusturan'] ?? '')),
            'extends' => trim((string) ($decoded['extends'] ?? '')),
            'css_stack' => $cssStack,
            'body_class' => trim((string) ($decoded['body_class'] ?? '')),
            'main_class' => trim((string) ($decoded['main_class'] ?? '')),
            'main_wrapper' => trim((string) ($decoded['main_wrapper'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function themeMetaForSlug(string $slug): array
    {
        $s = self::sanitizeThemeSlug($slug);
        if (self::$manifestBySlug === null) {
            self::$manifestBySlug = [];
            foreach (self::discoverThemesMeta() as $row) {
                self::$manifestBySlug[(string) ($row['slug'] ?? '')] = $row;
            }
        }

        return self::$manifestBySlug[$s] ?? ['slug' => $s];
    }

    public static function themeUsesDefaultChrome(?string $slug = null): bool
    {
        $s = self::sanitizeThemeSlug((string) ($slug ?? self::activeTheme()));

        return $s === 'default';
    }

    /**
     * HTML body class attribute değeri (escape edilmiş).
     */
    public static function themeBodyClassAttribute(?string $slug = null): string
    {
        $s = self::sanitizeThemeSlug((string) ($slug ?? self::activeTheme()));
        $meta = self::themeMetaForSlug($s);
        $bodyClass = trim((string) ($meta['body_class'] ?? ''));
        if ($bodyClass === '') {
            $bodyClass = self::legacyThemeBodyClass($s);
        }

        return htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8');
    }

    /**
     * HTML main class attribute değeri (escape edilmiş).
     */
    public static function themeMainClassAttribute(?string $slug = null): string
    {
        $s = self::sanitizeThemeSlug((string) ($slug ?? self::activeTheme()));
        $meta = self::themeMetaForSlug($s);
        $mainClass = trim((string) ($meta['main_class'] ?? ''));
        if ($mainClass === '') {
            $mainClass = self::legacyThemeMainClass($s);
        }

        return htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8');
    }

    public static function themeMainWrapperKind(?string $slug = null): string
    {
        $s = self::sanitizeThemeSlug((string) ($slug ?? self::activeTheme()));
        $meta = self::themeMetaForSlug($s);
        $wrapper = trim((string) ($meta['main_wrapper'] ?? ''));
        if ($wrapper !== '') {
            return $wrapper;
        }
        if (str_starts_with($s, 'catppuccin-') || $s === 'nord' || str_starts_with($s, 'github-')) {
            return 'cpp';
        }

        return '';
    }

    private static function legacyThemeBodyClass(string $slug): string
    {
        $base = 'app-shell d-flex flex-column min-vh-100';
        if ($slug === 'aurora') {
            return $base . ' aurora-theme';
        }
        if ($slug === 'winui' || $slug === 'winui-dark') {
            return $base . ' fluent-winui';
        }
        if ($slug === 'meridian-soft') {
            return $base . ' theme-meridian theme-meridian-soft';
        }
        if ($slug === 'evcare') {
            return $base . ' theme-meridian theme-evcare';
        }
        if ($slug === 'meridian') {
            return $base . ' theme-meridian';
        }
        if ($slug === 'nord') {
            return $base . ' theme-meridian theme-nord cpp-shell';
        }
        if (str_starts_with($slug, 'github-')) {
            return $base . ' theme-meridian theme-' . $slug . ' cpp-shell';
        }
        if (str_starts_with($slug, 'catppuccin-')) {
            return $base . ' theme-meridian theme-' . $slug . ' cpp-shell';
        }
        if ($slug === 'old') {
            return $base . ' theme-old';
        }
        if ($slug === 'default') {
            return $base . ' theme-default';
        }

        return $base;
    }

    private static function legacyThemeMainClass(string $slug): string
    {
        if ($slug === 'aurora') {
            return 'esh-app-main aurora-main flex-grow-1 d-flex flex-column py-4';
        }
        if ($slug === 'winui' || $slug === 'winui-dark') {
            return 'esh-app-main fluent-app-main flex-grow-1 d-flex flex-column';
        }
        if ($slug === 'meridian' || $slug === 'meridian-soft') {
            return 'esh-app-main flex-grow-1 d-flex flex-column mr-main py-4';
        }
        if ($slug === 'evcare') {
            return 'esh-app-main flex-grow-1 d-flex flex-column mr-main ev-main';
        }
        if (str_starts_with($slug, 'catppuccin-') || $slug === 'nord' || str_starts_with($slug, 'github-')) {
            return 'esh-app-main flex-grow-1 d-flex flex-column mr-main cpp-main';
        }

        return 'esh-app-main flex-grow-1 d-flex flex-column py-4';
    }

    private static function assetsBaseUrl(): string
    {
        return defined('SITEURL') ? rtrim((string) SITEURL, '/') : '';
    }

    private static function stylesheetLink(string $href): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    /** Tema editörü — `?embed=1` ile admin kabuğu içinde (geri uyum). */
    public static function editorEmbeddedRequested(): bool
    {
        if (!isset($_GET['embed'])) {
            return false;
        }

        return in_array(strtolower((string) $_GET['embed']), ['1', 'true', 'yes'], true);
    }

    /** Tema editörü URL (`Patient/scan` veya legacy query-string). */
    public static function editorPageUrl(?string $themeSlug = null, bool $embedded = false): string
    {
        $params = [];
        if ($themeSlug !== null && $themeSlug !== '') {
            $params['theme'] = self::sanitizeThemeSlug($themeSlug);
        }
        if ($embedded) {
            $params['embed'] = '1';
        }

        return \App\Helpers\UrlHelper::route('Theme', 'editor', $params);
    }

    public static function themeDisplayName(string $slug): string
    {
        $meta = self::themeMetaForSlug(self::sanitizeThemeSlug($slug));
        $name = trim((string) ($meta['name'] ?? ''));

        return $name !== '' ? $name : self::sanitizeThemeSlug($slug);
    }

    /** Standalone editör — minimal HTML başlangıcı (site theme.css yüklenmez). */
    public static function renderEditorStandaloneDocumentOpen(string $editorThemeSlug): void
    {
        $title = 'Tema editörü — ' . self::themeDisplayName($editorThemeSlug);
        echo '<!DOCTYPE html>' . "\n";
        echo '<html lang="tr">' . "\n";
        echo '<head>' . "\n";
        echo '<meta charset="UTF-8">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        echo CdnAssetHelper::themeEditorStandaloneStylesHtml();
        echo '</head>' . "\n";
        echo '<body class="esh-theme-editor-standalone">' . "\n";
    }

    /** Standalone editör — scriptler ve belge kapanışı. */
    public static function renderEditorStandaloneDocumentClose(): void
    {
        echo CdnAssetHelper::themeEditorStandaloneScriptsHtml();
        echo '</body>' . "\n";
        echo '</html>' . "\n";
    }

    /**
     * Ana panel head — app-shell + (default tema) default-chrome + eklenti + tema yığını.
     */
    public static function renderHeadStylesheetsHtml(?string $themeSlug = null): void
    {
        $slug = self::sanitizeThemeSlug((string) ($themeSlug ?? self::activeTheme()));
        $base = self::assetsBaseUrl();
        if ($base === '') {
            return;
        }

        echo self::stylesheetLink($base . '/public/assets/app-shell.css');
        echo self::stylesheetLink($base . '/public/assets/esh-page-language.css');
        if (self::themeUsesDefaultChrome($slug)) {
            echo self::stylesheetLink($base . '/public/assets/default-chrome.css');
        }
        foreach (self::themeDatepickerStylesheetSlugStack($slug) as $dpSlug) {
            $dpUrl = self::themeDatepickerStylesheetUrl($dpSlug);
            if ($dpUrl !== null) {
                echo self::stylesheetLink($dpUrl);
            }
        }
        echo self::stylesheetLink($base . '/public/assets/global-tomselect.css');
        echo self::stylesheetLink($base . '/public/assets/global-tooltip.css');
        foreach (self::themeStylesheetSlugStack($slug) as $thSlug) {
            $thUrl = self::themeStylesheetUrl($thSlug);
            if ($thUrl !== null) {
                echo self::stylesheetLink($thUrl);
            }
        }
    }

    /**
     * Giriş sayfası — app-shell + isteğe bağlı default-chrome + tema yığını.
     */
    public static function renderLoginStylesheetsHtml(string $themeSlug): void
    {
        $slug = self::sanitizeThemeSlug($themeSlug);
        $base = self::assetsBaseUrl();
        if ($base === '') {
            return;
        }

        echo self::stylesheetLink($base . '/public/assets/app-shell.css');
        echo self::stylesheetLink($base . '/public/assets/esh-page-language.css');
        if (self::themeUsesDefaultChrome($slug)) {
            echo self::stylesheetLink($base . '/public/assets/default-chrome.css');
        }
        foreach (self::themeStylesheetSlugStack($slug) as $thSlug) {
            $thUrl = self::themeStylesheetUrl($thSlug);
            if ($thUrl !== null) {
                echo self::stylesheetLink($thUrl);
            }
        }
    }

    /** Önizleme iframe — app-shell + default-chrome (gerekirse) + tema yığını. */
    public static function previewStylesheetUrlsForTheme(string $themeSlug): array
    {
        $slug = self::sanitizeThemeSlug($themeSlug);
        $base = self::assetsBaseUrl();
        if ($base === '') {
            return [];
        }
        $urls = [
            $base . '/public/assets/app-shell.css',
            $base . '/public/assets/esh-page-language.css',
        ];
        if (self::themeUsesDefaultChrome($slug)) {
            $urls[] = $base . '/public/assets/default-chrome.css';
        }
        $urls[] = $base . '/public/assets/global-tomselect.css';
        foreach (self::themeStylesheetSlugStack($slug) as $thSlug) {
            $thUrl = self::themeStylesheetUrl($thSlug);
            if ($thUrl !== null) {
                $urls[] = $thUrl;
            }
        }

        return $urls;
    }

    public static function renderHeadScriptsHtml(): void
    {
        $base = self::assetsBaseUrl();
        if ($base === '') {
            return;
        }

        echo '<script>window.ESH_SEF_ENABLED=' . (defined('ESH_SEF_URLS_ENABLED') && ESH_SEF_URLS_ENABLED ? 'true' : 'false')
            . ';window.ESH_PUBLIC_WEB=' . json_encode(\App\Helpers\UrlHelper::publicWebPath(), JSON_UNESCAPED_SLASHES) . ';</script>' . "\n";
        echo '<script src="' . htmlspecialchars($base . '/public/assets/global.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        echo '<script src="' . htmlspecialchars($base . '/public/assets/esh-fetch-json.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        echo '<script defer src="' . htmlspecialchars($base . '/public/assets/esh-phone-mask.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        echo '<script defer src="' . htmlspecialchars($base . '/public/assets/form-input-uppercase.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        echo '<script defer src="' . htmlspecialchars($base . '/public/assets/form-submit-guard.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }

    /** `templates/<slug>/theme.css` mutlak yolu */
    public static function themeCssPath(string $slug): string
    {
        return self::rootPath('/templates/' . self::sanitizeThemeSlug($slug) . '/theme.css');
    }

    /** `public/assets/datepicker-<slug>.css` mutlak yolu (bootstrap-datepicker görünüm katmanı). */
    public static function datepickerCssPath(string $slug): string
    {
        return self::rootPath('/public/assets/datepicker-' . self::sanitizeThemeSlug($slug) . '.css');
    }

    /**
     * Tema datepicker CSS katmanları — `themeStylesheetSlugStack` ile aynı sıra (winui → varyant).
     *
     * @return list<string>
     */
    public static function themeDatepickerStylesheetSlugStack(?string $slug = null): array
    {
        return self::themeStylesheetSlugStack($slug);
    }

    /** Statik dosya; yoksa null (vendor bootstrap-datepicker + tema katmanı yüklenmez). */
    public static function themeDatepickerStylesheetUrl(string $slug): ?string
    {
        if (!defined('SITEURL')) {
            return null;
        }
        $s = self::sanitizeThemeSlug($slug);
        if (!is_file(self::datepickerCssPath($s))) {
            return null;
        }
        return rtrim((string) SITEURL, '/') . '/public/assets/datepicker-' . rawurlencode($s) . '.css';
    }

    /**
     * Tema CSS katmanları (winui-dark önce WinUI tabanını yükler).
     *
     * @return list<string>
     */
    public static function themeStylesheetSlugStack(?string $slug = null): array
    {
        $s = self::sanitizeThemeSlug((string) ($slug ?? self::activeTheme()));
        $meta = self::themeMetaForSlug($s);
        $stack = $meta['css_stack'] ?? [];
        if (is_array($stack) && $stack !== []) {
            return array_values($stack);
        }

        return self::legacyThemeStylesheetSlugStack($s);
    }

    /**
     * PHP yedek yığın — _theme.json css_stack yoksa.
     *
     * @return list<string>
     */
    private static function legacyThemeStylesheetSlugStack(string $slug): array
    {
        if ($slug === 'winui-dark') {
            return ['winui', $slug];
        }
        if ($slug === 'meridian-soft') {
            return ['meridian', 'meridian-soft'];
        }
        if ($slug === 'evcare') {
            return ['meridian', 'evcare'];
        }
        if (self::isMeridianOverlayTheme($slug)) {
            return ['meridian', $slug];
        }

        return [$slug];
    }

    /** Tarayıcıda `public/theme-sheet.php` üzerinden tema CSS URL’si; dosya yoksa null. */
    public static function themeStylesheetUrl(string $slug): ?string
    {
        if (!defined('SITEURL')) {
            return null;
        }
        $s = self::sanitizeThemeSlug($slug);
        if (!is_file(self::themeCssPath($s))) {
            return null;
        }
        return rtrim((string) SITEURL, '/') . '/public/theme-sheet.php?s=' . rawurlencode($s);
    }

    public static function resolvePartial(string $name): string
    {
        $theme = self::activeTheme();
        $name = trim($name);

        $candidates = [
            self::rootPath('/templates/' . $theme . '/partials/' . $name . '.php'),
        ];
        if ($theme === 'meridian-soft') {
            $candidates[] = self::rootPath('/templates/meridian/partials/' . $name . '.php');
        }
        if ($theme === 'evcare') {
            $candidates[] = self::rootPath('/templates/meridian/partials/' . $name . '.php');
        }
        if (self::isMeridianOverlayTheme($theme)) {
            $candidates[] = self::rootPath('/templates/meridian/partials/' . $name . '.php');
        }
        if ($theme === 'winui-dark') {
            $candidates[] = self::rootPath('/templates/winui/partials/' . $name . '.php');
        }
        $candidates = array_merge($candidates, [
            self::rootPath('/templates/default/partials/' . $name . '.php'),
            self::rootPath('/views/partials/' . $theme . '/' . $name . '.php'),
            self::rootPath('/views/partials/default/' . $name . '.php'),
            self::rootPath('/views/partials/' . $name . '.php'),
        ]);
        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return self::rootPath('/views/partials/' . $name . '.php');
    }

    public static function resolveAreaView(string $area, string $relativePath): string
    {
        $theme = self::activeTheme();
        $area = trim($area);
        $rel = trim($relativePath, '/\\');
        $candidates = [
            self::rootPath('/templates/' . $theme . '/' . $area . '/' . $rel . '.php'),
        ];
        if ($theme === 'meridian-soft') {
            $candidates[] = self::rootPath('/templates/meridian/' . $area . '/' . $rel . '.php');
        }
        if ($theme === 'evcare') {
            $candidates[] = self::rootPath('/templates/meridian/' . $area . '/' . $rel . '.php');
        }
        if (self::isMeridianOverlayTheme($theme)) {
            $candidates[] = self::rootPath('/templates/meridian/' . $area . '/' . $rel . '.php');
        }
        if ($theme === 'winui-dark') {
            $candidates[] = self::rootPath('/templates/winui/' . $area . '/' . $rel . '.php');
        }
        $candidates = array_merge($candidates, [
            self::rootPath('/templates/default/' . $area . '/' . $rel . '.php'),
            self::rootPath('/views/' . $area . '/' . $theme . '/' . $rel . '.php'),
            self::rootPath('/views/' . $area . '/default/' . $rel . '.php'),
            self::rootPath('/views/' . $area . '/' . $rel . '.php'),
        ]);
        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return self::rootPath('/views/' . $area . '/' . $rel . '.php');
    }

    /** Giriş: her zaman site varsayılan teması (`siteThemeSlug`). Kişisel tema yalnızca oturum sonrası. */
    public static function resolveLoginView(): string
    {
        $theme = self::siteThemeSlug();
        $candidates = [
            self::rootPath('/templates/' . $theme . '/login.php'),
        ];
        if ($theme === 'meridian-soft') {
            $candidates[] = self::rootPath('/templates/meridian/login.php');
        }
        if ($theme === 'evcare') {
            $candidates[] = self::rootPath('/templates/meridian/login.php');
        }
        if (self::isMeridianOverlayTheme($theme)) {
            $candidates[] = self::rootPath('/templates/meridian/login.php');
        }
        $candidates = array_merge($candidates, [
            self::rootPath('/templates/default/login.php'),
            self::rootPath('/views/overrides/login/' . $theme . '.php'),
            self::rootPath('/views/overrides/login/default.php'),
            self::rootPath('/views/login_template.php'),
        ]);
        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return self::rootPath('/views/login_template.php');
    }
}

