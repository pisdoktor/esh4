<?php
namespace App\Helpers;

/**
 * Harici CDN kütüphane adresleri. Sürüm güncellemesi: yalnızca aşağıdaki private const
 * değerlerini (ve gerekiyorsa CHART_JS_UMD_PATH gibi yol parçasını) değiştirmeniz yeterlidir.
 *
 * Uzak sürüm kontrolü: {@see self::comparePinnedToRegistryLatest()}, {@see self::probeCdnUrls()}, {@see self::formatProbeReport()}
 * Güncelleme önerisi: {@see self::suggestPinnedUpdatesFromRegistry()}
 * Dosyada const güncelleme (dry-run varsayılan): {@see self::applyPinnedVersionsToSourceFile()}
 *
 * Şablonlar: views/partials/header.php, views/partials/footer.php, views/login_template.php (+ views/login.php), changelog.php, public/install.php
 */
final class CdnAssetHelper
{
    private const BOOTSTRAP = '5.3.8';
    private const FONT_AWESOME = '7.0.1';
    private const JQUERY = '4.0.0';
    private const JQUERY_UI = '1.14.2';
    private const TOASTR = '2.1.4';
    /** Tom Select — vanilla enhanced select (yerel vendor). */
    private const TOM_SELECT = '2.6.1';
    /** TomTom Maps SDK for Web v6 — CDN yolu: cdn/6.x/{sürüm}/ */
    private const TOMTOM_MAPS_WEB = '6.25.0';
    /** MapLibre GL — OpenRouteService/OSM karoları */
    private const MAPLIBRE_GL = '4.7.1';
    /** Mapbox GL JS */
    private const MAPBOX_GL_JS = '3.8.0';
    private const BOOTSTRAP_DATEPICKER = '1.10.0';
    private const PDFMAKE = '0.3.11';
    /** pdfmake npm — tarayıcı build (cdnjs 0.3.x güncel değil; jsdelivr). */
    private const PDFMAKE_JS_PATH = '/build/pdfmake.min.js';
    private const PDFMAKE_VFS_PATH = '/build/vfs_fonts.js';
    /** SheetJS (xlsx) — istatistik/liste Excel dışa aktarımı. */
    private const SHEETJS = '0.18.5';
    private const CHART_JS = '4.5.1';
    /** Profil fotoğrafı kırpma — Cropper.js 2.x (jQuery gerektirmez). */
    private const CROPPER = '2.1.1';
    /** chart.js npm paketinde tarayıcı için UMD göreli yol (sürümle birlikte kontrol edin). */
    private const CHART_JS_UMD_PATH = '/dist/chart.umd.min.js';

    private const GOOGLE_FONT_INTER = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap';

    /**
     * Sürüm const adı → kayıt defteri (npm veya cdnjs). TOMTOM_MAPS_WEB manuel takip.
     *
     * @var array<string, array{0: 'npm'|'cdnjs'|'manual', 1: string|null}>
     */
    private const REMOTE_VERSION_LOOKUP = [
        'BOOTSTRAP' => ['npm', 'bootstrap'],
        'FONT_AWESOME' => ['cdnjs', 'font-awesome'],
        'JQUERY' => ['npm', 'jquery'],
        'JQUERY_UI' => ['npm', 'jquery-ui'],
        'TOASTR' => ['cdnjs', 'toastr.js'],
        'TOM_SELECT' => ['npm', 'tom-select'],
        'BOOTSTRAP_DATEPICKER' => ['cdnjs', 'bootstrap-datepicker'],
        'PDFMAKE' => ['npm', 'pdfmake'],
        'CHART_JS' => ['npm', 'chart.js'],
        'CROPPER' => ['npm', 'cropperjs'],
        'TOMTOM_MAPS_WEB' => ['manual', null],
        'MAPLIBRE_GL' => ['npm', 'maplibre-gl'],
        'MAPBOX_GL_JS' => ['npm', 'mapbox-gl'],
    ];

    /** Harita sağlayıcısı → tarayıcı SDK const adı (OpenRouteService = MapLibre). */
    private const MAP_PROVIDER_SDK_CONST = [
        'tomtom' => 'TOMTOM_MAPS_WEB',
        'openrouteservice' => 'MAPLIBRE_GL',
        'mapbox' => 'MAPBOX_GL_JS',
    ];

    private static function h(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    private static function linkStylesheet(string $href, string $extraAttrs = ''): string
    {
        return '<link rel="stylesheet" href="' . self::h($href) . '"' . $extraAttrs . '>' . "\n";
    }

    private static function scriptSrc(string $src, string $attrs = ''): string
    {
        if (function_exists('esh_csp_script_src_tag')) {
            return esh_csp_script_src_tag($src, $attrs);
        }

        $extra = trim($attrs);
        $attr = $extra !== '' ? ' ' . $extra : '';

        return '<script' . $attr . ' src="' . self::h($src) . '"></script>' . "\n";
    }

    /** SITEURL + public/assets/... (Tom Select vb. yerel vendor). */
    private static function localPublicAssetHref(string $relativeUnderAssets): string
    {
        $rel = 'public/assets/' . ltrim($relativeUnderAssets, '/');
        $base = defined('SITEURL') ? rtrim((string) SITEURL, '/') : '';

        return ($base !== '' ? $base . '/' : '/') . $rel;
    }

    private static function tomtomCdnBase(): string
    {
        return 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/' . self::TOMTOM_MAPS_WEB . '/';
    }

    public static function bootstrapCssHref(): string
    {
        return 'https://cdn.jsdelivr.net/npm/bootstrap@' . self::BOOTSTRAP . '/dist/css/bootstrap.min.css';
    }

    /** Collapse, modal vb. için (minimal kabuk sayfalarında `minimalAuthLayoutStylesHtml()` sonrası). */
    public static function bootstrapBundleJsHref(): string
    {
        return 'https://cdn.jsdelivr.net/npm/bootstrap@' . self::BOOTSTRAP . '/dist/js/bootstrap.bundle.min.js';
    }

    public static function fontAwesomeCssHref(): string
    {
        return 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . self::FONT_AWESOME . '/css/all.min.css';
    }

    public static function jqueryJsHref(): string
    {
        return 'https://code.jquery.com/jquery-' . self::JQUERY . '.min.js';
    }

    public static function tomSelectCssHref(): string
    {
        return self::localPublicAssetHref('global-tomselect.css');
    }

    public static function tomSelectJsHref(): string
    {
        return self::localPublicAssetHref('vendor/tom-select.complete.min.js');
    }

    public static function jquery4LegacyShimJsHref(): string
    {
        return self::localPublicAssetHref('vendor/jquery4-legacy-shim.js');
    }

    /** Cropper.js 2.x npm paketinde ayrı CSS yok (web component); sayfa düzeni yerel CSS. */
    public static function cropperCssHref(): string
    {
        return self::localPublicAssetHref('esh-cropper-page.css');
    }

    public static function cropperJsHref(): string
    {
        return 'https://cdn.jsdelivr.net/npm/cropperjs@' . self::CROPPER . '/dist/cropper.min.js';
    }

    public static function pdfmakeJsHref(): string
    {
        return 'https://cdn.jsdelivr.net/npm/pdfmake@' . self::PDFMAKE . self::PDFMAKE_JS_PATH;
    }

    public static function pdfmakeVfsJsHref(): string
    {
        return 'https://cdn.jsdelivr.net/npm/pdfmake@' . self::PDFMAKE . self::PDFMAKE_VFS_PATH;
    }

    /**
     * Giriş / changelog gibi sade kabuk sayfalar: Inter + Bootstrap + Font Awesome.
     */
    public static function minimalAuthLayoutStylesHtml(): string
    {
        $out = self::linkStylesheet(self::GOOGLE_FONT_INTER);
        $out .= self::linkStylesheet(self::bootstrapCssHref());
        $out .= self::linkStylesheet(self::fontAwesomeCssHref());
        return $out;
    }

    /**
     * public/install.php — Bootstrap + jQuery + Toastr (TomTom/Tom Select vb. yok).
     */
    public static function installWizardHeadAssetsHtml(): string
    {
        $out = self::minimalAuthLayoutStylesHtml();
        $out .= self::linkStylesheet('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.css');
        $out .= self::scriptSrc(self::jqueryJsHref());
        $out .= self::scriptSrc(self::jquery4LegacyShimJsHref());
        $out .= self::scriptSrc(self::bootstrapBundleJsHref());
        $out .= self::scriptSrc('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.js');
        return $out;
    }

    /**
     * Ana panel head: harici stiller (yerel public/assets linkleri hariç).
     */
    public static function vendorCdnStylesHtml(): string
    {
        $out = self::linkStylesheet(self::GOOGLE_FONT_INTER);
        $out .= self::linkStylesheet(self::bootstrapCssHref());
        $out .= self::linkStylesheet(self::fontAwesomeCssHref());
        $out .= self::linkStylesheet('https://code.jquery.com/ui/' . self::JQUERY_UI . '/themes/base/jquery-ui.css');
        $out .= self::linkStylesheet('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.css');
        $out .= self::linkStylesheet(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/css/bootstrap-datepicker.min.css'
        );
        return $out;
    }

    /**
     * Harita sayfaları için sağlayıcı SDK stilleri.
     */
    public static function mapRoutingPageStylesHtml(?string $mapSdk = null): string
    {
        $sdk = self::resolveMapSdk($mapSdk);
        $out = '';
        if ($sdk === 'tomtom') {
            $out .= self::linkStylesheet(self::tomtomCdnBase() . 'maps/maps.css', ' type="text/css"');
        } elseif ($sdk === 'maplibre_osm') {
            $out .= self::linkStylesheet('https://cdn.jsdelivr.net/npm/maplibre-gl@' . self::MAPLIBRE_GL . '/dist/maplibre-gl.css');
        } elseif ($sdk === 'mapbox') {
            $out .= self::linkStylesheet('https://api.mapbox.com/mapbox-gl-js/v' . self::MAPBOX_GL_JS . '/mapbox-gl.css');
        }
        return $out;
    }

    /**
     * Harita sayfaları: SDK + EshMapRuntime adaptörleri.
     */
    public static function mapRoutingPageScriptsHtml(?string $mapSdk = null): string
    {
        $sdk = self::resolveMapSdk($mapSdk);
        $out = '';
        if ($sdk === 'tomtom') {
            $base = self::tomtomCdnBase();
            $out .= self::scriptSrc($base . 'maps/maps-web.min.js');
            $out .= self::scriptSrc($base . 'services/services-web.min.js');
            $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/esh-map-tomtom.js'));
        } elseif ($sdk === 'maplibre_osm') {
            $out .= self::scriptSrc('https://cdn.jsdelivr.net/npm/maplibre-gl@' . self::MAPLIBRE_GL . '/dist/maplibre-gl.js');
            $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/esh-map-maplibre-osm.js'));
        } elseif ($sdk === 'mapbox') {
            $out .= self::scriptSrc('https://api.mapbox.com/mapbox-gl-js/v' . self::MAPBOX_GL_JS . '/mapbox-gl.js');
            $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/esh-map-mapbox.js'));
        } elseif ($sdk === 'google') {
            $key = defined('GOOGLE_MAPS_KEY') ? trim((string) GOOGLE_MAPS_KEY) : '';
            if ($key !== '') {
                $out .= self::scriptSrc(
                    'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($key) . '&libraries=geometry'
                );
            }
            $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/esh-map-google.js'));
        }
        $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/esh-map-runtime.js'));
        return $out;
    }

    private static function resolveMapSdk(?string $mapSdk): string
    {
        if (is_string($mapSdk) && $mapSdk !== '') {
            return $mapSdk;
        }
        if (class_exists(\App\Helpers\OperationalSettings::class)) {
            $provider = \App\Helpers\OperationalSettings::mapProvider();
            return match ($provider) {
                'openrouteservice' => 'maplibre_osm',
                'mapbox' => 'mapbox',
                'google' => 'google',
                default => 'tomtom',
            };
        }

        return 'tomtom';
    }

    /**
     * Ana panel head: harici scriptler (global.js öncesi sıra korunur).
     */
    public static function vendorCdnScriptsHtml(): string
    {
        $out = self::scriptSrc(self::jqueryJsHref());
        $out .= self::scriptSrc(self::jquery4LegacyShimJsHref());
        $out .= self::scriptSrc('https://code.jquery.com/ui/' . self::JQUERY_UI . '/jquery-ui.min.js');
        $out .= self::scriptSrc(self::bootstrapBundleJsHref());
        $out .= self::scriptSrc('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.js');
        $out .= self::scriptSrc(self::tomSelectJsHref());
        $out .= self::scriptSrc(self::pdfmakeJsHref());
        $out .= self::scriptSrc(self::pdfmakeVfsJsHref());
        $out .= self::scriptSrc(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/js/bootstrap-datepicker.min.js'
        );
        $out .= self::scriptSrc(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/locales/bootstrap-datepicker.tr.min.js'
        );
        $out .= self::scriptSrc(
            'https://cdn.jsdelivr.net/npm/chart.js@' . self::CHART_JS . self::CHART_JS_UMD_PATH
        );
        return $out;
    }

    /**
     * Tema editörü standalone pencere — Bootstrap + Font Awesome + editör CSS (site theme.css yok).
     */
    public static function themeEditorStandaloneStylesHtml(): string
    {
        $out = self::linkStylesheet(self::bootstrapCssHref());
        $out .= self::linkStylesheet(self::fontAwesomeCssHref());
        $out .= self::linkStylesheet(self::localPublicAssetHref('pages/css/theme-editor.css'));

        return $out;
    }

    /**
     * Tema editörü standalone pencere — Bootstrap bundle + theme-editor.js (vendor/global.js yok).
     */
    public static function themeEditorStandaloneScriptsHtml(): string
    {
        $out = self::scriptSrc(self::bootstrapBundleJsHref());
        $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/theme-editor.js'));

        return $out;
    }

    /**
     * Tema renk editörü iframe — bootstrap-datepicker taban CSS (theme-sheet datepicker katmanlarından önce).
     */
    public static function previewShellExtraStylesHtml(): string
    {
        return self::linkStylesheet(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/css/bootstrap-datepicker.min.css'
        );
    }

    /**
     * Tema renk editörü iframe — jQuery, Bootstrap, datepicker, Tom Select, global.js (TomTom/pdfmake yok).
     */
    public static function previewShellScriptsHtml(): string
    {
        $out = self::scriptSrc(self::jqueryJsHref());
        $out .= self::scriptSrc(self::jquery4LegacyShimJsHref());
        $out .= self::scriptSrc(self::bootstrapBundleJsHref());
        $out .= self::scriptSrc(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/js/bootstrap-datepicker.min.js'
        );
        $out .= self::scriptSrc(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/locales/bootstrap-datepicker.tr.min.js'
        );
        $out .= self::scriptSrc(self::tomSelectJsHref());
        $out .= self::scriptSrc(self::localPublicAssetHref('global.js'));
        $out .= self::scriptSrc(self::localPublicAssetHref('pages/js/theme-preview-shell.js'));

        return $out;
    }

    /** EK-3 gömülü önizleme (modal iframe) — yalnızca pdfMake. */
    public static function pdfMakeScriptsHtml(): string
    {
        $out = self::scriptSrc(self::pdfmakeJsHref());
        $out .= self::scriptSrc(self::pdfmakeVfsJsHref());
        return $out;
    }

    /** İstatistik kartları / liste Excel — SheetJS (xlsx.full.min.js). */
    public static function sheetJsScriptsHtml(): string
    {
        return self::scriptSrc(
            'https://cdnjs.cloudflare.com/ajax/libs/xlsx/' . self::SHEETJS . '/xlsx.full.min.js'
        );
    }

    /**
     * Ana kabuk footer: vendor CDN’leri kısa etiket + sürüm rozetleri (şablon stiline göre $badgeClass).
     *
     * Sıra: Bootstrap, Font Awesome, jQuery, jQuery UI, Toastr, Tom Select, bootstrap-datepicker, pdfmake, Chart.js, Cropper.js, TomTom Maps SDK.
     * Google Fonts (Inter) sürümsüz CDN; burada listelenmez.
     */
    public static function footerCdnVendorBadgesHtml(string $badgeClass): string
    {
        $bc = htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8');
        $rows = [
            ['Bootstrap', self::BOOTSTRAP],
            ['Font Awesome', self::FONT_AWESOME],
            ['jQuery', self::JQUERY],
            ['jQuery UI', self::JQUERY_UI],
            ['Toastr', self::TOASTR],
            ['Tom Select', self::TOM_SELECT],
            ['Datepicker', self::BOOTSTRAP_DATEPICKER],
            ['pdfmake', self::PDFMAKE],
            ['Chart.js', self::CHART_JS],
            ['Cropper.js', self::CROPPER],
            ['TomTom SDK', self::TOMTOM_MAPS_WEB],
        ];
        $chunks = [];
        foreach ($rows as [$label, $ver]) {
            $lt = htmlspecialchars($label . ' ' . $ver, ENT_QUOTES, 'UTF-8');
            $lb = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $v = htmlspecialchars((string) $ver, ENT_QUOTES, 'UTF-8');
            $chunks[] = '<span class="' . $bc . '" title="' . $lt . '">' . $lb . ' ' . $v . '</span>';
        }

        return implode("\n                ", $chunks);
    }

    /**
     * Sabitlenmiş sürümler (REMOTE_VERSION_LOOKUP anahtarları + CHART_JS_UMD_PATH sabit yolu).
     *
     * @return array<string, string>
     */
    public static function pinnedVersions(): array
    {
        $ref = new \ReflectionClass(self::class);
        $out = [];
        foreach (array_keys(self::REMOTE_VERSION_LOOKUP) as $constName) {
            $rc = $ref->getReflectionConstant($constName);
            if ($rc !== false) {
                $out[$constName] = (string) $rc->getValue();
            }
        }
        $out['CHART_JS_UMD_PATH'] = self::CHART_JS_UMD_PATH;

        return $out;
    }

    /**
     * Harita SDK sürüm const adları (CDN sürüm kontrolü — tüm sağlayıcılar; aktif seçime bağlı değil).
     *
     * @return list<string>
     */
    public static function mapSdkVersionConstNames(): array
    {
        return ['TOMTOM_MAPS_WEB', 'MAPLIBRE_GL', 'MAPBOX_GL_JS'];
    }

    /**
     * @return array<string, string> sağlayıcı kodu → SDK const
     */
    public static function mapProviderSdkConstMap(): array
    {
        return self::MAP_PROVIDER_SDK_CONST;
    }

    /**
     * Karşılaştırma satırlarını genel kütüphaneler / harita SDK olarak ayırır.
     *
     * @param list<array<string, mixed>> $rows {@see self::comparePinnedToRegistryLatest()}
     * @return array{map: list<array<string, mixed>>, general: list<array<string, mixed>>}
     */
    public static function partitionCompareRowsByMapSdk(array $rows): array
    {
        $mapConsts = array_flip(self::mapSdkVersionConstNames());
        $map = [];
        $general = [];
        foreach ($rows as $row) {
            $c = (string) ($row['const'] ?? '');
            if (isset($mapConsts[$c])) {
                $map[] = $row;
            } else {
                $general[] = $row;
            }
        }
        $order = array_flip(self::mapSdkVersionConstNames());
        usort($map, static function (array $a, array $b) use ($order): int {
            return ($order[$a['const'] ?? ''] ?? 99) <=> ($order[$b['const'] ?? ''] ?? 99);
        });

        return ['map' => $map, 'general' => $general];
    }

    /**
     * Tüm harita sağlayıcıları için HEAD sondası URL’leri (PS CDN kontrolü).
     *
     * @return list<array{key: string, url: string, provider: string}>
     */
    public static function mapSdkProbeUrls(): array
    {
        $tomtomBase = self::tomtomCdnBase();

        return [
            ['key' => 'tomtom_maps_css', 'url' => $tomtomBase . 'maps/maps.css', 'provider' => 'tomtom'],
            ['key' => 'tomtom_maps_web_js', 'url' => $tomtomBase . 'maps/maps-web.min.js', 'provider' => 'tomtom'],
            ['key' => 'tomtom_services_web_js', 'url' => $tomtomBase . 'services/services-web.min.js', 'provider' => 'tomtom'],
            [
                'key' => 'maplibre_gl_css',
                'url' => 'https://cdn.jsdelivr.net/npm/maplibre-gl@' . self::MAPLIBRE_GL . '/dist/maplibre-gl.css',
                'provider' => 'openrouteservice',
            ],
            [
                'key' => 'maplibre_gl_js',
                'url' => 'https://cdn.jsdelivr.net/npm/maplibre-gl@' . self::MAPLIBRE_GL . '/dist/maplibre-gl.js',
                'provider' => 'openrouteservice',
            ],
            [
                'key' => 'mapbox_gl_css',
                'url' => 'https://api.mapbox.com/mapbox-gl-js/v' . self::MAPBOX_GL_JS . '/mapbox-gl.css',
                'provider' => 'mapbox',
            ],
            [
                'key' => 'mapbox_gl_js',
                'url' => 'https://api.mapbox.com/mapbox-gl-js/v' . self::MAPBOX_GL_JS . '/mapbox-gl.js',
                'provider' => 'mapbox',
            ],
            [
                'key' => 'google_maps_api_js',
                'url' => 'https://maps.googleapis.com/maps/api/js',
                'provider' => 'google',
            ],
        ];
    }

    /**
     * Üretilen CDN URL’leri (HEAD/GET sondası için).
     *
     * @return list<array{key: string, url: string}>
     */
    public static function pinnedVendorUrls(): array
    {
        $urls = [];
        $add = static function (string $key, string $url) use (&$urls): void {
            $urls[] = ['key' => $key, 'url' => $url];
        };
        $add('google_font_inter', self::GOOGLE_FONT_INTER);
        $add('bootstrap_css', self::bootstrapCssHref());
        $add('fontawesome_css', self::fontAwesomeCssHref());
        $add('jquery_ui_css', 'https://code.jquery.com/ui/' . self::JQUERY_UI . '/themes/base/jquery-ui.css');
        $add('toastr_css', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.css');
        $add('tom_select_css', self::tomSelectCssHref());
        $add(
            'bootstrap_datepicker_css',
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/css/bootstrap-datepicker.min.css'
        );
        foreach (self::mapSdkProbeUrls() as $probeRow) {
            $add($probeRow['key'], $probeRow['url']);
        }
        $add('jquery_js', self::jqueryJsHref());
        $add('jquery_ui_js', 'https://code.jquery.com/ui/' . self::JQUERY_UI . '/jquery-ui.min.js');
        $add('bootstrap_bundle_js', 'https://cdn.jsdelivr.net/npm/bootstrap@' . self::BOOTSTRAP . '/dist/js/bootstrap.bundle.min.js');
        $add('toastr_js', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/' . self::TOASTR . '/toastr.min.js');
        $add('tom_select_js', self::tomSelectJsHref());
        $add('pdfmake_js', self::pdfmakeJsHref());
        $add('pdfmake_vfs_js', self::pdfmakeVfsJsHref());
        $add(
            'bootstrap_datepicker_js',
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/js/bootstrap-datepicker.min.js'
        );
        $add(
            'bootstrap_datepicker_tr_js',
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/' . self::BOOTSTRAP_DATEPICKER . '/locales/bootstrap-datepicker.tr.min.js'
        );
        $add('chart_js', 'https://cdn.jsdelivr.net/npm/chart.js@' . self::CHART_JS . self::CHART_JS_UMD_PATH);
        $add('cropper_css', self::cropperCssHref());
        $add('cropper_js', self::cropperJsHref());

        return $urls;
    }

    /**
     * registry.npmjs.org üzerinden dist-tags.latest.
     */
    public static function fetchNpmLatestVersion(string $package, int $timeoutSeconds = 10): ?string
    {
        $package = trim($package);
        if ($package === '' || !preg_match('/^[a-zA-Z0-9@._-]+$/', $package)) {
            return null;
        }
        $url = 'https://registry.npmjs.org/' . rawurlencode($package);
        $json = self::httpGetBody($url, $timeoutSeconds);
        if ($json === null || $json === '') {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        $latest = $data['dist-tags']['latest'] ?? null;

        return is_string($latest) && $latest !== '' ? $latest : null;
    }

    /**
     * api.cdnjs.com/libraries/{slug} üst seviye "version" (son yayın).
     */
    public static function fetchCdnjsLatestVersion(string $librarySlug, int $timeoutSeconds = 10): ?string
    {
        $librarySlug = trim($librarySlug);
        if ($librarySlug === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $librarySlug)) {
            return null;
        }
        $url = 'https://api.cdnjs.com/libraries/' . rawurlencode($librarySlug);
        $json = self::httpGetBody($url, $timeoutSeconds);
        if ($json === null || $json === '') {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        $v = $data['version'] ?? null;

        return is_string($v) && $v !== '' ? $v : null;
    }

    /**
     * Sabitlenmiş sürümler ile npm/cdnjs üzerindeki son sürümleri karşılaştırır.
     *
     * @return list<array{
     *   const: string,
     *   pinned: string,
     *   latest: string|null,
     *   registry: string,
     *   id: string|null,
     *   newer: bool|null,
     *   error: string|null
     * }>
     */
    public static function comparePinnedToRegistryLatest(int $timeoutSeconds = 10): array
    {
        $rows = [];
        foreach (self::REMOTE_VERSION_LOOKUP as $constName => [$kind, $id]) {
            $pinned = self::readPrivateConstString($constName);
            if ($pinned === null) {
                $rows[] = [
                    'const' => $constName,
                    'pinned' => '',
                    'latest' => null,
                    'registry' => $kind,
                    'id' => $id,
                    'newer' => null,
                    'error' => 'const okunamadı',
                ];
                continue;
            }
            if ($kind === 'manual') {
                $rows[] = [
                    'const' => $constName,
                    'pinned' => $pinned,
                    'latest' => null,
                    'registry' => 'manual',
                    'id' => null,
                    'newer' => null,
                    'error' => null,
                ];
                continue;
            }
            $latest = null;
            $err = null;
            try {
                if ($kind === 'npm' && is_string($id)) {
                    $latest = self::fetchNpmLatestVersion($id, $timeoutSeconds);
                    if ($latest === null) {
                        $err = 'npm yanıtı okunamadı veya latest yok';
                    }
                } elseif ($kind === 'cdnjs' && is_string($id)) {
                    $latest = self::fetchCdnjsLatestVersion($id, $timeoutSeconds);
                    if ($latest === null) {
                        $err = 'cdnjs yanıtı okunamadı veya version yok';
                    }
                } else {
                    $err = 'geçersiz lookup';
                }
            } catch (\Throwable $e) {
                $err = $e->getMessage();
            }
            $newer = null;
            if ($latest !== null && $latest !== '') {
                $newer = version_compare($latest, $pinned, '>');
            }
            $rows[] = [
                'const' => $constName,
                'pinned' => $pinned,
                'latest' => $latest,
                'registry' => $kind,
                'id' => $id,
                'newer' => $newer,
                'error' => $err,
            ];
        }

        return $rows;
    }

    /**
     * Üretilen CDN URL’lerine HEAD isteği (HTTP kodu).
     *
     * @return list<array{key: string, url: string, code: int|null, ok: bool, error: string|null}>
     */
    public static function probeCdnUrls(int $timeoutSeconds = 8): array
    {
        $out = [];
        foreach (self::pinnedVendorUrls() as $row) {
            $code = null;
            $err = null;
            try {
                $code = self::httpHeadStatus($row['url'], $timeoutSeconds);
            } catch (\Throwable $e) {
                $err = $e->getMessage();
            }
            $ok = $code !== null && $code >= 200 && $code < 400;
            $out[] = [
                'key' => $row['key'],
                'url' => $row['url'],
                'code' => $code,
                'ok' => $ok,
                'error' => $err,
            ];
        }

        return $out;
    }

    /**
     * CdnAssetHelper.php içindeki private const sürümlerini günceller.
     *
     * @param array<string, string> $constToVersion const adı (örn. BOOTSTRAP) => semver dizesi
     * @return array<string, array{from: string, to: string}> uygulanan değişiklikler
     */
    public static function applyPinnedVersionsToSourceFile(array $constToVersion, bool $dryRun = true): array
    {
        $ref = new \ReflectionClass(self::class);
        $file = $ref->getFileName();
        if ($file === false || !is_readable($file)) {
            throw new \RuntimeException('CdnAssetHelper kaynak dosyası okunamadı.');
        }
        $src = (string) file_get_contents($file);
        $applied = [];
        foreach ($constToVersion as $constName => $newVersion) {
            if (!is_string($constName) || !preg_match('/^[A-Z][A-Z0-9_]*$/', $constName)) {
                continue;
            }
            if (!array_key_exists($constName, self::REMOTE_VERSION_LOOKUP)) {
                continue;
            }
            if (!is_string($newVersion) || !preg_match('/^[0-9A-Za-z._+-]+$/', $newVersion)) {
                continue;
            }
            $pattern = '/(private\\s+const\\s+' . preg_quote($constName, '/') . "\\s*=\\s*')([^'\\\\]*)(';)/";
            if (!preg_match($pattern, $src, $m)) {
                continue;
            }
            $from = (string) $m[2];
            if ($from === $newVersion) {
                continue;
            }
            $applied[$constName] = ['from' => $from, 'to' => $newVersion];
            if (!$dryRun) {
                $src = (string) preg_replace($pattern, '${1}' . $newVersion . '${3}', $src, 1);
            }
        }
        if (!$dryRun && $applied !== []) {
            if (file_put_contents($file, $src) === false) {
                throw new \RuntimeException('CdnAssetHelper dosyası yazılamadı: ' . $file);
            }
        }

        return $applied;
    }

    public static function formatVersionCompareConstLabel(string $const): string
    {
        return ucwords(strtolower(str_replace('_', ' ', trim($const))));
    }

    /**
     * Web tablosu için satır durumu (rozet, satır vurgusu, ipucu).
     *
     * @param array<string, mixed> $row {@see self::comparePinnedToRegistryLatest()}
     * @return array{label: string, badge: string, rowClass: string, title: string}
     */
    public static function versionCompareRowMeta(array $row): array
    {
        $err = isset($row['error']) && is_string($row['error']) && $row['error'] !== '' ? $row['error'] : null;
        $registry = (string) ($row['registry'] ?? '');
        $newer = $row['newer'] ?? null;

        if ($err !== null) {
            return [
                'label' => 'Hata',
                'badge' => 'danger',
                'rowClass' => 'table-danger',
                'title' => $err,
            ];
        }
        if ($registry === 'manual') {
            return [
                'label' => 'Manuel',
                'badge' => 'secondary',
                'rowClass' => '',
                'title' => 'npm/cdnjs kayıt defteri karşılaştırması yok',
            ];
        }
        if ($newer === true) {
            return [
                'label' => 'Yeni sürüm',
                'badge' => 'warning',
                'rowClass' => 'table-warning',
                'title' => 'Kayıt defterinde daha yeni sürüm var',
            ];
        }
        if ($newer === false) {
            return [
                'label' => 'Güncel',
                'badge' => 'success',
                'rowClass' => '',
                'title' => 'Sabitlenmiş sürüm kayıt defteri ile uyumlu',
            ];
        }

        return [
            'label' => 'Belirsiz',
            'badge' => 'warning',
            'rowClass' => '',
            'title' => 'Son sürüm okunamadı veya karşılaştırılamadı',
        ];
    }

    /**
     * Karşılaştırma çıktısını düz metin rapor (CLI / log).
     *
     * @param list<array<string, mixed>> $rows {@see self::comparePinnedToRegistryLatest()}
     */
    public static function formatVersionCompareReport(array $rows): string
    {
        $lines = ['CdnAssetHelper — sabit vs kayıt defteri (npm / cdnjs)', str_repeat('-', 72)];
        foreach ($rows as $r) {
            $c = (string) ($r['const'] ?? '');
            $p = (string) ($r['pinned'] ?? '');
            $l = $r['latest'] !== null && $r['latest'] !== '' ? (string) $r['latest'] : '(yok)';
            $reg = (string) ($r['registry'] ?? '');
            $id = $r['id'] !== null ? (string) $r['id'] : '-';
            $newer = $r['newer'];
            $flag = $newer === true ? ' [yeni]' : ($newer === false ? '' : ' [?]');
            $err = isset($r['error']) && $r['error'] !== null && $r['error'] !== '' ? ' ERR:' . $r['error'] : '';
            $lines[] = sprintf('%-22s %-12s -> son sürüm %-12s (%s/%s)%s%s', $c, $p, $l, $reg, $id, $flag, $err);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Kayıt defteri karşılaştırmasından yalnızca daha yeni sürümler için const => sürüm haritası (apply için).
     *
     * @param list<array<string, mixed>> $rows {@see self::comparePinnedToRegistryLatest()}
     * @return array<string, string>
     */
    public static function suggestPinnedUpdatesFromRegistry(array $rows, bool $onlyNewer = true): array
    {
        $map = [];
        foreach ($rows as $r) {
            if (($r['registry'] ?? '') === 'manual') {
                continue;
            }
            if ($onlyNewer && ($r['newer'] ?? null) !== true) {
                continue;
            }
            $c = (string) ($r['const'] ?? '');
            $latest = $r['latest'] ?? null;
            if ($c === '' || !is_string($latest) || $latest === '') {
                continue;
            }
            $map[$c] = $latest;
        }

        return $map;
    }

    /**
     * @param list<array{key: string, url: string, code: int|null, ok: bool, error: string|null}> $rows {@see self::probeCdnUrls()}
     */
    public static function formatProbeReport(array $rows): string
    {
        $lines = ['CdnAssetHelper — CDN URL HEAD sondası', str_repeat('-', 88)];
        foreach ($rows as $r) {
            $code = $r['code'] !== null ? (string) $r['code'] : '-';
            $ok = $r['ok'] ? 'OK' : 'FAIL';
            $err = $r['error'] !== null && $r['error'] !== '' ? ' ' . $r['error'] : '';
            $lines[] = sprintf('%-8s %-4s %s %s%s', $ok, $code, $r['key'], $r['url'], $err);
        }

        return implode("\n", $lines) . "\n";
    }

    private static function readPrivateConstString(string $name): ?string
    {
        $ref = new \ReflectionClass(self::class);
        $rc = $ref->getReflectionConstant($name);
        if ($rc === false) {
            return null;
        }
        $v = $rc->getValue();

        return is_string($v) ? $v : null;
    }

    private static function httpGetBody(string $url, int $timeoutSeconds): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => max(1, min(60, $timeoutSeconds)),
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: ESH-CdnAssetHelper/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }

        return $raw;
    }

    private static function httpHeadStatus(string $url, int $timeoutSeconds): ?int
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => max(1, min(60, $timeoutSeconds)),
                'ignore_errors' => true,
                'header' => "User-Agent: ESH-CdnAssetHelper/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $headers = @get_headers($url, 1, $ctx);
        if ($headers === false) {
            return null;
        }
        $first = is_array($headers) && isset($headers[0]) ? (string) $headers[0] : '';
        if (preg_match('/\bHTTP\/\d\.\d\s+(\d{3})/', $first, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
