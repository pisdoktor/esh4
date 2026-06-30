<?php
namespace App\Helpers;

/**
 * Tema editörü — `theme.css` renk jetonları (--mr-*, --au-*, --fluent-*, --bs-*).
 *
 * @see ThemeCssColorHelper::parseColorTokens()
 */
final class ThemeColorTokenCatalog
{
    /** @var array<string, array<string, string>> */
    private const FAMILY_LABELS = [
        'mr' => [
            '--mr-bg0' => 'Sayfa — en koyu arka plan katmanı',
            '--mr-bg1' => 'Sayfa — orta arka plan katmanı (gradyan)',
            '--mr-surface' => 'Kart / panel cam yüzeyi',
            '--mr-surface2' => 'İkincil yüzey (tablo başlık, hover)',
            '--mr-border' => 'Kenarlık ve ayırıcı çizgiler',
            '--mr-text' => 'Ana metin rengi',
            '--mr-muted' => 'Soluk / ikincil metin',
            '--mr-accent' => 'Birincil vurgu (teal)',
            '--mr-accent2' => 'İkincil vurgu (link, başlık)',
            '--mr-warn' => 'Uyarı sarısı',
            '--mr-danger' => 'Hata / tehlike kırmızısı',
        ],
        'au' => [
            '--au-teal' => 'Marka teal — temel',
            '--au-teal-600' => 'Marka teal — koyu',
            '--au-teal-700' => 'Marka teal — en koyu',
            '--au-cyan' => 'Gradyan — cyan tonu',
            '--au-emerald' => 'Gradyan — emerald tonu',
            '--au-sky' => 'Gradyan — gökyüzü mavisi',
            '--au-accent' => 'Birincil vurgu rengi',
            '--au-accent-strong' => 'Güçlü vurgu (aktif link, başlık)',
            '--au-canvas' => 'Sayfa tuval zemin',
            '--au-canvas-top' => 'Sayfa üst gradyan zemin',
            '--au-surface' => 'Kart / panel yüzeyi',
            '--au-surface-2' => 'İkincil yüzey (filtre, tablo başlık)',
            '--au-surface-3' => 'Üçüncü yüzey tonu',
            '--au-text' => 'Ana metin',
            '--au-text-soft' => 'Yumuşak metin',
            '--au-text-muted' => 'Soluk / yardımcı metin',
            '--au-border' => 'Varsayılan kenarlık',
            '--au-border-strong' => 'Belirgin kenarlık',
            '--au-ring' => 'Odak halkası / seçim çerçevesi',
        ],
        'fluent' => [
            '--fluent-accent' => 'Fluent — birincil vurgu (mavi)',
            '--fluent-accent-secondary' => 'Fluent — ikincil vurgu',
            '--fluent-accent-tertiary' => 'Fluent — üçüncül vurgu (koyu mavi)',
            '--fluent-accent-subtle' => 'Fluent — hafif vurgu zemin',
            '--fluent-accent-glow' => 'Fluent — parlama / seçim ışıması',
            '--fluent-bg-page' => 'Sayfa arka planı',
            '--fluent-bg-page-top' => 'Sayfa üst gradyan tonu',
            '--fluent-bg-layer-base' => 'Kart / komut çubuğu taban katmanı',
            '--fluent-bg-layer-alt' => 'Alternatif katman (tablo başlık vb.)',
            '--fluent-stroke-control' => 'Form kontrol kenarlığı',
            '--fluent-stroke-card' => 'Kart kenarlığı',
            '--fluent-text-primary' => 'Birincil metin',
            '--fluent-text-secondary' => 'İkincil metin',
            '--fluent-text-disabled' => 'Pasif / devre dışı metin',
        ],
        'bs' => [
            '--bs-body-bg' => 'Bootstrap — gövde arka planı',
            '--bs-body-color' => 'Bootstrap — gövde metni',
            '--bs-emphasis-color' => 'Bootstrap — vurgulu metin',
            '--bs-secondary-color' => 'Bootstrap — ikincil metin',
            '--bs-tertiary-color' => 'Bootstrap — üçüncül metin',
            '--bs-heading-color' => 'Bootstrap — başlık rengi',
            '--bs-link-color' => 'Bootstrap — bağlantı',
            '--bs-link-hover-color' => 'Bootstrap — bağlantı hover',
            '--bs-border-color' => 'Bootstrap — kenarlık',
            '--bs-navbar-color' => 'Bootstrap navbar — link metni',
            '--bs-navbar-hover-color' => 'Bootstrap navbar — link hover',
            '--bs-navbar-active-color' => 'Bootstrap navbar — aktif link',
            '--bs-navbar-brand-color' => 'Bootstrap navbar — marka metni',
            '--bs-navbar-brand-hover-color' => 'Bootstrap navbar — marka hover',
            '--bs-navbar-disabled-color' => 'Bootstrap navbar — pasif link',
            '--bs-table-color' => 'Bootstrap tablo — hücre metni',
            '--bs-table-bg' => 'Bootstrap tablo — zemin',
            '--bs-table-border-color' => 'Bootstrap tablo — kenarlık',
            '--bs-table-striped-bg' => 'Bootstrap tablo — çizgili satır zemin',
            '--bs-table-striped-color' => 'Bootstrap tablo — çizgili satır metin',
            '--bs-table-hover-bg' => 'Bootstrap tablo — satır hover zemin',
            '--bs-table-hover-color' => 'Bootstrap tablo — satır hover metin',
            '--bs-table-accent-bg' => 'Bootstrap tablo — vurgu satır zemin',
        ],
    ];

    /** @var array<string, string> */
    private const GROUP_NAMES = [
        'mr' => 'Meridian',
        'au' => 'Aurora',
        'fluent' => 'WinUI / Fluent',
        'bs' => 'Bootstrap (tema override)',
        'other' => 'Diğer',
    ];

    /** @var array<string, string> */
    private const GROUP_ORDER = ['mr', 'au', 'fluent', 'bs', 'other'];

    public static function label(string $tokenName): string
    {
        $name = trim($tokenName);
        if ($name === '') {
            return '';
        }

        $family = self::familyKey($name);
        if ($family !== null && isset(self::FAMILY_LABELS[$family][$name])) {
            return self::FAMILY_LABELS[$family][$name];
        }

        return self::inferLabel($name, $family);
    }

    public static function group(string $tokenName): string
    {
        $family = self::familyKey($tokenName);

        return self::GROUP_NAMES[$family ?? 'other'] ?? self::GROUP_NAMES['other'];
    }

    /** @return list<string> */
    public static function groupOrder(): array
    {
        return array_values(self::GROUP_NAMES);
    }

    private static function familyKey(string $name): ?string
    {
        if (preg_match('/^--([a-z]+)-/', $name, $m)) {
            $key = $m[1];
            if (isset(self::FAMILY_LABELS[$key]) || isset(self::GROUP_NAMES[$key])) {
                return $key;
            }
        }

        return null;
    }

    private static function inferLabel(string $name, ?string $family): string
    {
        $suffix = $name;
        if (preg_match('/^--[^-]+-(.+)$/', $name, $m)) {
            $suffix = $m[1];
        }

        $hints = [
            'bg0' => 'arka plan (koyu katman)',
            'bg1' => 'arka plan (orta katman)',
            'bg-page' => 'sayfa zemin',
            'canvas' => 'tuval zemin',
            'surface' => 'kart / panel yüzeyi',
            'text' => 'ana metin',
            'muted' => 'soluk metin',
            'accent' => 'vurgu rengi',
            'border' => 'kenarlık',
            'danger' => 'tehlike / hata',
            'warn' => 'uyarı',
            'success' => 'başarı',
            'primary' => 'birincil',
            'secondary' => 'ikincil',
        ];

        foreach ($hints as $needle => $desc) {
            if ($suffix === $needle || str_contains($suffix, $needle)) {
                $prefix = $family !== null ? (self::GROUP_NAMES[$family] ?? 'Tema') : 'Tema';

                return $prefix . ' — ' . $desc . ' (' . $suffix . ')';
            }
        }

        $readable = str_replace('-', ' ', $suffix);

        return ($family !== null ? (self::GROUP_NAMES[$family] ?? 'Tema') : 'Tema') . ' — ' . $readable;
    }
}
