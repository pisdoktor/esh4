<?php
declare(strict_types=1);

/**
 * Türkçe karakter bozukluğu taraması ve düzeltmesi.
 */

$root = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);

$scanDirs = [
    $root . '/views',
    $root . '/public/assets/pages/js',
    $root . '/app/Controllers',
    $root . '/app/Helpers',
    $root . '/templates',
];

$skipPathContains = [
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
];

$latin1Map = [
    'Ý' => 'İ',
    'ý' => 'ı',
    'Þ' => 'Ş',
    'þ' => 'ş',
    'Ð' => 'Ğ',
    'ð' => 'ğ',
];

/** U+FFFD (bozuk tek bayt) sonrası bilinen kalıplar */
$replacementPatterns = [
    '/\xEF\xBF\xBDnceki/u' => 'Önceki',
    '/\xEF\xBF\xBDzin/u' => 'İzin',
    '/\xEF\xBF\xBDdari/u' => 'İdari',
    '/\xEF\xBF\xBDste/u' => 'İste',
    '/\xEF\xBF\xBDstatistik/u' => 'İstatistik',
    '/Ayl\xEF\xBF\xBDk/u' => 'Aylık',
    '/Y\xEF\xBF\xBDll\xEF\xBF\xBDk/u' => 'Yıllık',
    '/N\xEF\xBF\xBDbet/u' => 'Nöbet',
    '/\xEF\xBF\xBDzeti/u' => 'Özeti',
    '/Da\xEF\xBF\xBD\xEF\xBF\xBDt\xEF\xBF\xBDm/u' => 'Dağıtım',
    '/olu\xEF\xBF\xBDturulsun/u' => 'oluşturulsun',
    '/ay\xEF\xBF\xBDn/u' => 'ayın',
    '/n\xEF\xBF\xBDbetleri/u' => 'nöbetleri',
    '/Ba\xEF\xBF\xBDlang\xEF\xBF\xBD\xEF\xBF\xBD/u' => 'Başlangıç',
    '/Biti\xEF\xBF\xBD/u' => 'Bitiş',
    '/ad\xEF\xBF\xBD/u' => 'adı',
    '/se\xEF\xBF\xBDiniz/u' => 'seçiniz',
    '/A\xEF\xBF\xBD\xEF\xBF\xBDklama/u' => 'Açıklama',
    '/\xEF\xBF\xBD\xEF\xBF\xBDlem/u' => 'İşlem',
    '/B\xEF\xBF\xBDlge/u' => 'Bölge',
    '/Hasta ad\xEF\xBF\xBD/u' => 'Hasta adı',
    '/Kay\xEF\xBF\xBDtlar\xEF\xBF\xBD/u' => 'Kayıtları',
    '/Kay\xEF\xBF\xBDt/u' => 'Kayıt',
    '/kay\xEF\xBF\xBDtlar\xEF\xBF\xBD/u' => 'kayıtları',
    '/y\xEF\xBF\xBDkleniyor\xEF\xBF\xBD/u' => 'yükleniyor…',
    '/G\xEF\xBF\xBDster/u' => 'Göster',
    '/Ar\xEF\xBF\xBDiv/u' => 'Arşiv',
    '/\xEF\xBF\xBDsim Ba\xEF\xBF\xBD/u' => 'İsim Baş',
    '/T\xEF\xBF\xBDm\xEF\xBF\xBD/u' => 'Tümü',
    '/T\xEF\xBF\xBDM\xEF\xBF\xBDN\xEF\xBF\xBD SE\xEF\xBF\xBD/u' => 'TÜMÜNÜ SEÇ',
    '/B\xEF\xBF\xBDlgeler/u' => 'Bölgeler',
    '/\xEF\xBF\xBDl\xEF\xBF\xBDe Ad\xEF\xBF\xBD/u' => 'İlçe Adı',
    '/il\xEF\xBF\xBDeye/u' => 'ilçeye',
    '/al\xEF\xBF\xBDyoruz/u' => 'alıyoruz',
    '/Se\xEF\xBF\xBDili/u' => 'Seçili',
    '/il\xEF\xBF\xBDenin/u' => 'ilçenin',
    '/kar\xEF\xBF\xBD\xEF\xBF\xBDla\xEF\xBF\xBDt\xEF\xBF\xBDr/u' => 'karşılaştır',
    '/b\xEF\xBF\xBDrak/u' => 'bırak',
    '/\xEF\xBF\xBDnceki/u' => 'Önceki',
];

function shouldSkip(string $path, array $skipPathContains): bool
{
    foreach ($skipPathContains as $needle) {
        if (str_contains($path, $needle)) {
            return true;
        }
    }
    return false;
}

function collectFiles(array $dirs, array $skipPathContains): array
{
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (shouldSkip($path, $skipPathContains)) {
                continue;
            }
            if (!preg_match('/\.(php|js)$/i', $path)) {
                continue;
            }
            $files[] = $path;
        }
    }
    sort($files);
    return $files;
}

function detectIssues(string $content): array
{
    $issues = [];
    if (!mb_check_encoding($content, 'UTF-8')) {
        $issues[] = 'invalid_utf8';
    }
    if (str_contains($content, "\xEF\xBF\xBD")) {
        $issues[] = 'replacement_char';
    }
    if (preg_match('/[ÝýÞþÐð]/u', $content)) {
        $issues[] = 'latin1_mojibake';
    }
    return $issues;
}

function fixContent(string $content, array $latin1Map, array $replacementPatterns): string
{
    if (!mb_check_encoding($content, 'UTF-8')) {
        $converted = @iconv('Windows-1254', 'UTF-8//IGNORE', $content);
        if ($converted !== false && $converted !== '') {
            $content = $converted;
        } else {
            $converted = @iconv('ISO-8859-9', 'UTF-8//IGNORE', $content);
            if ($converted !== false && $converted !== '') {
                $content = $converted;
            }
        }
    }

    $content = strtr($content, $latin1Map);

    foreach ($replacementPatterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content) ?? $content;
    }

    return $content;
}

$files = collectFiles($scanDirs, $skipPathContains);
$changed = [];
$issueFiles = [];

foreach ($files as $path) {
    $original = file_get_contents($path);
    if ($original === false) {
        continue;
    }

    $issues = detectIssues($original);
    if ($issues === []) {
        continue;
    }

    $issueFiles[$path] = $issues;
    $fixed = fixContent($original, $latin1Map, $replacementPatterns);

    if ($fixed === $original) {
        continue;
    }

    if (!mb_check_encoding($fixed, 'UTF-8')) {
        fwrite(STDERR, "SKIP (still invalid UTF-8): $path\n");
        continue;
    }

    if (str_contains($fixed, "\xEF\xBF\xBD") && detectIssues($fixed) !== []) {
        // Kısmi düzeltme; yine de kaydet ama raporla
        $issueFiles[$path][] = 'partial_fix';
    }

    $changed[] = $path;
    if (!$dryRun) {
        file_put_contents($path, $fixed);
    }
}

echo ($dryRun ? '[DRY RUN] ' : '') . 'Scanned: ' . count($files) . PHP_EOL;
echo 'With issues: ' . count($issueFiles) . PHP_EOL;
echo 'Fixed: ' . count($changed) . PHP_EOL;

foreach ($changed as $path) {
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    echo '  ' . $rel . PHP_EOL;
}

$stillBad = [];
foreach ($issueFiles as $path => $issues) {
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }
    $remaining = detectIssues($content);
    if ($remaining !== []) {
        $stillBad[$path] = $remaining;
    }
}

if ($stillBad !== []) {
    echo PHP_EOL . 'Still problematic:' . PHP_EOL;
    foreach ($stillBad as $path => $issues) {
        $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        echo '  ' . $rel . ' [' . implode(', ', $issues) . ']' . PHP_EOL;
    }
}
