<?php
declare(strict_types=1);

/**
 * CdnAssetHelper sabit sürümlerini npm/cdnjs ile karşılaştırır; isteğe bağlı CDN HEAD sondası ve (dikkatli) dosya güncellemesi.
 *
 * Yönetici arayüzü (tarayıcı, salt okunur + isteğe bağlı HEAD): panelde
 * Yönetim → «CDN sürüm kontrolü» veya doğrudan
 *   public/index.php?controller=CdnCheck&action=index
 * HEAD sondası: aynı adres &probe=1
 *
 * Kullanım (proje kökünden):
 *   php tools/check_cdn_versions.php
 *   php tools/check_cdn_versions.php --probe
 *   php tools/check_cdn_versions.php --timeout=15
 *   php tools/check_cdn_versions.php --dry-apply   (önerilen sürümleri yazar ama dosyaya uygulamaz — sadece listeler)
 *   php tools/check_cdn_versions.php --apply       (CdnAssetHelper.php içindeki private const sürümlerini yazar; yedek alın)
 */

$root = dirname(__DIR__);
require $root . '/app/Helpers/CdnAssetHelper.php';

use App\Helpers\CdnAssetHelper;

$argv = $GLOBALS['argv'] ?? [];
$timeout = 12;
$probe = in_array('--probe', $argv, true);
$apply = in_array('--apply', $argv, true);
$dryApply = in_array('--dry-apply', $argv, true);

foreach ($argv as $a) {
    if (is_string($a) && str_starts_with($a, '--timeout=')) {
        $timeout = max(1, (int) substr($a, strlen('--timeout=')));
    }
}

$cmp = CdnAssetHelper::comparePinnedToRegistryLatest($timeout);
echo CdnAssetHelper::formatVersionCompareReport($cmp);

if ($probe) {
    echo "\n";
    $heads = CdnAssetHelper::probeCdnUrls($timeout);
    echo CdnAssetHelper::formatProbeReport($heads);
}

$suggested = CdnAssetHelper::suggestPinnedUpdatesFromRegistry($cmp, true);
if ($suggested !== []) {
    echo "\nÖnerilen güncellemeler (yalnız registry > pinned):\n";
    foreach ($suggested as $k => $v) {
        echo "  {$k} => {$v}\n";
    }
} else {
    echo "\nÖnerilen otomatik güncelleme yok (hepsi pinned ≥ latest veya latest okunamadı).\n";
}

if ($dryApply || $apply) {
    $dry = !$apply;
    echo "\n" . ($dry ? 'Dry-run: CdnAssetHelper.php const değişikliği simülasyonu' : 'UYARI: CdnAssetHelper.php dosyası yazılıyor') . ":\n";
    $changes = CdnAssetHelper::applyPinnedVersionsToSourceFile($suggested, $dry);
    if ($changes === []) {
        echo "  (değişiklik yok)\n";
    } else {
        foreach ($changes as $c => $pair) {
            echo sprintf("  %s: %s -> %s\n", $c, $pair['from'], $pair['to']);
        }
    }
    if ($apply) {
        echo "\nTamamlandı. Projede smoke test (panel, install, migration arayüzü) çalıştırın.\n";
    }
}
