<?php
/**
 * ESH — Uygulama sürüm bilgisi ve görüntüleme yardımcıları
 *
 * Yükleme: config/config.php → bölüm 12 (require_once).
 * Tek kaynak: ESH_APP_VERSION, ESH_APP_RELEASE_DATE, ESH_APP_NAME, ESH_APP_VERSION_CODENAME.
 * Kurumsal uygulama adı (ESH_APP_NAME) config.php bölüm 8'de OperationalSettings ile
 * önceden tanımlanmışsa burada tekrar define edilmez.
 *
 * Sürüm artırımı: docs/VERSIONING.md ve .cursor kuralları.
 * Değişiklik notları: changelog.php (bu dosyadaki sürümle hizalı tutulur).
 *
 * ── Sabitler ─────────────────────────────────────────────────────────────────
 *  ESH_APP_NAME, ESH_APP_VERSION, ESH_APP_RELEASE_DATE, ESH_APP_VERSION_CODENAME
 *
 * ── Yardımcılar (esh_* ) ─────────────────────────────────────────────────────
 *  esh_app_version, esh_version_short_label, esh_version_badge_html, …
 *  esh_changelog_url, esh_version_footer_copyright_html, esh_version_meta_tag
 */

// =============================================================================
// SÜRÜM SABİTLERİ
// =============================================================================

if (!defined('ESH_APP_NAME')) {
    define('ESH_APP_NAME', 'SONEV');
}
if (!defined('ESH_APP_VERSION')) {
    define('ESH_APP_VERSION', '4.0.0');
}
if (!defined('ESH_APP_RELEASE_DATE')) {
    define('ESH_APP_RELEASE_DATE', '2026-06-17');
}
if (!defined('ESH_APP_VERSION_CODENAME')) {
    define('ESH_APP_VERSION_CODENAME', 'Burutay');
}

// =============================================================================
// HAM DEĞER OKUYUCULAR
// =============================================================================

/**
 * Ham sürüm numarası (örn. 3.1.2).
 */
function esh_app_version(): string
{
    return (string) ESH_APP_VERSION;
}

/**
 * Uygulama kısa adı.
 */
function esh_app_name(): string
{
    return (string) ESH_APP_NAME;
}

/**
 * Son sürüm notu tarihi (YYYY-AA-GG).
 */
function esh_app_release_date(): string
{
    return (string) ESH_APP_RELEASE_DATE;
}

/**
 * İsteğe bağlı kod adı; boşsa boş string.
 */
function esh_app_version_codename(): string
{
    return (string) ESH_APP_VERSION_CODENAME;
}

/**
 * [major, minor, patch] — beklenen 3 parça; eksikse 0 ile tamamlanır.
 *
 * @return array{0: int, 1: int, 2: int}
 */
function esh_app_version_parts(): array
{
    $p = array_map('intval', explode('.', ESH_APP_VERSION . '.0.0'));
    return [(int) ($p[0] ?? 0), (int) ($p[1] ?? 0), (int) ($p[2] ?? 0)];
}

// =============================================================================
// CHANGELOG BAĞLANTILARI
// =============================================================================

/**
 * Değişiklik günlüğü URL’si (public köprü dosyası; asset yollarıyla uyumlu).
 */
function esh_changelog_url(): string
{
    if (!defined('SITEURL')) {
        return '/public/changelog.php';
    }
    return rtrim((string) SITEURL, '/') . '/public/changelog.php';
}

/**
 * Üst htdocs changelog.php — gruplu ayrıntılı arşiv; public/changelog_archive.php köprüsü.
 */
function esh_changelog_archive_url(): string
{
    if (!defined('SITEURL')) {
        return '/public/changelog_archive.php';
    }
    return rtrim((string) SITEURL, '/') . '/public/changelog_archive.php';
}

// =============================================================================
// HTML / ETİKET ÇIKTILARI (footer, başlık, meta)
// =============================================================================

/**
 * Tek satır kısa etiket: "SONEV v3.1.2".
 */
function esh_version_short_label(): string
{
    $v = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');
    $n = htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8');
    return $n . ' v' . $v;
}

/**
 * Kod adı varsa parantez içinde ekler.
 */
function esh_version_label_with_codename(): string
{
    $base = esh_version_short_label();
    $code = trim(esh_app_version_codename());
    if ($code === '') {
        return $base;
    }
    return $base . ' (' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . ')';
}

/**
 * Bootstrap rozet HTML (footer, kart başlığı vb.).
 *
 * @param string $extraClass Örn. "bg-light text-dark border"
 */
function esh_version_badge_html(string $extraClass = 'bg-light text-dark border'): string
{
    $cls = htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8');
    $v = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');
    return '<span class="badge ' . $cls . '" title="' . esh_version_short_label() . '">v' . $v . '</span>';
}

/**
 * Birincil renk rozet.
 */
function esh_version_badge_html_primary(): string
{
    return esh_version_badge_html('bg-primary');
}

/**
 * Küçük, sönük metin (footer tek satır).
 */
function esh_version_muted_span_html(): string
{
    $v = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');
    return '<span class="text-muted">v' . $v . '</span>';
}

/**
 * © yıl + uygulama adı + sürüm (HTML; telif satırı).
 */
function esh_version_footer_copyright_html(): string
{
    $year = (int) date('Y');
    $name = htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8');
    $v = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');
    return '&copy; ' . $year . ' ' . $name . ' <span class="text-muted">v' . $v . '</span>';
}

/**
 * Changelog’a giden küçük bağlantı.
 */
function esh_version_changelog_link_html(string $linkClass = 'text-muted text-decoration-none'): string
{
    $href = htmlspecialchars(esh_changelog_url(), ENT_QUOTES, 'UTF-8');
    $cls = htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8');
    return '<a href="' . $href . '" class="' . $cls . '"><i class="fa-solid fa-clock-rotate-left me-1"></i>Değişiklik günlüğü</a>';
}

/**
 * Footer için: telif + changelog (aralarında ayraç).
 */
function esh_version_footer_extras_html(): string
{
    return '<span class="mx-2 text-muted">·</span>' . esh_version_changelog_link_html();
}

/**
 * Footer telif satırı (sürüm rozeti ayrı gösterildiği için sürümsüz).
 */
function esh_footer_copyright_compact_html(): string
{
    $year = (int) date('Y');
    $name = htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8');

    return '&copy; ' . $year . ' ' . $name;
}

/**
 * Footer sağ kümesi — telif + changelog + teknik rozet grubu.
 *
 * @param string $badgeClass Rozet stili (badge sınıfından sonrası)
 * @param string $loadTimeBadgeClass Sayfa yükleme süresi rozeti
 */
function esh_footer_right_cluster_html(
    string $badgeClass = 'bg-light text-dark border',
    string $loadTimeBadgeClass = 'bg-success text-white border'
): string {
    return '<div class="esh-footer-right-cluster small d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3">'
        . '<span class="esh-footer-right-cluster__legal text-muted">'
        . esh_footer_copyright_compact_html()
        . esh_version_footer_extras_html()
        . '</span>'
        . esh_footer_badge_group_html($badgeClass, $loadTimeBadgeClass)
        . '</div>';
}

/**
 * Footer teknik rozetleri — hover ile açılan grup (PHP, CDN, yükleme süresi; site sürümü tetikleyicide).
 *
 * @param string $badgeClass Rozet stili (badge sınıfından sonrası; örn. bg-light text-dark border)
 * @param string $loadTimeBadgeClass Sayfa yükleme süresi rozeti
 */
function esh_footer_badge_group_html(
    string $badgeClass = 'bg-light text-dark border',
    string $loadTimeBadgeClass = 'bg-success text-white border'
): string {
    $bc = htmlspecialchars(trim($badgeClass), ENT_QUOTES, 'UTF-8');
    $ltc = htmlspecialchars(trim($loadTimeBadgeClass), ENT_QUOTES, 'UTF-8');
    $phpShort = htmlspecialchars(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION, ENT_QUOTES, 'UTF-8');
    $phpFull = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');
    $ver = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');

    $items = [];
    $items[] = '<span class="badge ' . $bc . '" title="PHP ' . $phpFull . '">PHP ' . $phpShort . '</span>';
    $showCdnVendorBadges = class_exists(\App\Helpers\AuthHelper::class)
        && \App\Helpers\AuthHelper::sessionIsPlatformOwner();
    if ($showCdnVendorBadges) {
        $items[] = \App\Helpers\CdnAssetHelper::footerCdnVendorBadgesHtml('badge ' . trim($badgeClass));
    }
    $items[] = '<span id="pageLoadTime" class="badge ' . $ltc . ' d-none" aria-live="polite">Yükleniyor...</span>';

    return '<div class="esh-footer-badge-group">'
        . '<span class="esh-footer-badge-group__trigger badge ' . $bc . '" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Sistem bilgileri (v' . $ver . ')">'
        . '<i class="fa-solid fa-microchip me-1" aria-hidden="true"></i>'
        . '<span class="esh-footer-badge-group__trigger-label">v' . $ver . '</span>'
        . '</span>'
        . '<div class="esh-footer-badge-group__panel" role="dialog" aria-label="Sistem ve kütüphane bilgileri">'
        . '<div class="esh-footer-badge-group__title text-muted">Sistem bilgileri</div>'
        . '<div class="esh-footer-badge-group__items">' . implode("\n", $items) . '</div>'
        . '</div>'
        . '</div>';
}

/**
 * <head> içi meta etiketi (sürüm damgası).
 */
function esh_version_meta_tag(): string
{
    $v = htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8');
    return '<meta name="application-version" content="' . $v . '">' . "\n";
}

/**
 * JSON API veya hata raporları için tek satır.
 */
function esh_version_build_string(): string
{
    return esh_app_name() . '/' . esh_app_version() . ' (' . esh_app_release_date() . ')';
}
