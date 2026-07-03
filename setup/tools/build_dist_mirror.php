<?php
declare(strict_types=1);

/**
 * dist/{sürüm}/ altına dağıtım aynası (mirror) oluşturur — CLI.
 *
 * Örnek:
 *   php tools/build_dist_mirror.php
 *   php tools/build_dist_mirror.php 3.1.0
 *   php tools/build_dist_mirror.php --full
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu betik yalnızca CLI ile çalıştırılmalıdır.\n");
    exit(1);
}

require_once __DIR__ . '/build_dist_mirror.inc.php';

$full = false;
$versionArg = null;
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--full') {
        $full = true;
    } elseif ($a === '--help' || $a === '-h') {
        echo <<<'TXT'
ESH — dist sürüm aynası (mirror)

  php tools/build_dist_mirror.php [SÜRÜM] [--full]

SÜRÜM   Varsayılan: config/version.php → ESH_APP_VERSION
--full  storage/old_tables ve migration_jobs dahil

TXT;
        exit(0);
    } elseif (!str_starts_with($a, '-')) {
        $versionArg = $a;
    }
}

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Proje kökü bulunamadı.\n");
    exit(1);
}

$ver = $versionArg ?? '3.0.0';
$releaseDate = gmdate('Y-m-d');
$vfile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'version.php';
if (is_readable($vfile)) {
    $raw = (string) file_get_contents($vfile);
    if ($versionArg === null && preg_match("/define\s*\(\s*'ESH_APP_VERSION'\s*,\s*'([^']+)'/", $raw, $m)) {
        $ver = $m[1];
    }
    if (preg_match("/define\s*\(\s*'ESH_APP_RELEASE_DATE'\s*,\s*'([^']+)'/", $raw, $m)) {
        $releaseDate = $m[1];
    }
}

$targetDir = $projectRoot . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . $ver;
if (is_dir($targetDir)) {
    removeTree($targetDir);
}
if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Hedef dizin oluşturulamadı: {$targetDir}\n");
    exit(1);
}

$consolidatedPatch = null;
$consolidatedPatchName = 'patch_' . $ver . '.sql';
try {
    $consolidatedPatch = buildConsolidatedPatchSql($ver, $releaseDate, $projectRoot);
    if ($consolidatedPatch !== null) {
        $dbPatchPath = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $consolidatedPatchName;
        file_put_contents($dbPatchPath, $consolidatedPatch);
    }
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$fileList = [];
/** @param callable(string, bool): bool $exclude */
$walk = static function (string $dir, string $baseRel) use (&$walk, &$fileList, $full, $projectRoot, $targetDir): void {
    $exclude = static function (string $rel, bool $isDir) use ($full): bool {
        $norm = str_replace('\\', '/', $rel);
        $segs = explode('/', $norm);

        if (in_array('.git', $segs, true) || in_array('.cursor', $segs, true) || in_array('.vscode', $segs, true)) {
            return true;
        }
        if (($segs[0] ?? '') === 'dist') {
            return true;
        }
        if (in_array('node_modules', $segs, true)) {
            return true;
        }
        if (($segs[0] ?? '') === 'backups') {
            return true;
        }
        if (($segs[0] ?? '') === 'logs') {
            return true;
        }

        if ($norm === 'config/config.local.php' || $norm === 'config/install.lock') {
            return true;
        }

        if (!$full) {
            if (($segs[0] ?? '') === 'storage' && in_array('old_tables', $segs, true)) {
                return true;
            }
            if (($segs[0] ?? '') === 'storage' && in_array('migration_jobs', $segs, true)) {
                return true;
            }
            if (str_starts_with($norm, 'storage/backups/')) {
                $bn = basename($norm);
                if ($bn !== '.gitignore' && $bn !== '.htaccess') {
                    return true;
                }
            }
            if (($segs[0] ?? '') === 'tools' && ($segs[1] ?? '') === 'archive') {
                return true;
            }
            if (($segs[0] ?? '') === 'database' && ($segs[1] ?? '') === 'archive') {
                return true;
            }
        }

        if ($norm === '.DS_Store' || str_ends_with($norm, '/.DS_Store')) {
            return true;
        }
        if (str_ends_with($norm, 'Thumbs.db')) {
            return true;
        }
        if (str_ends_with($norm, '.pre-edit.bak') || str_ends_with($norm, '.bak-regroup')) {
            return true;
        }

        return false;
    };

    $dh = @opendir($dir);
    if ($dh === false) {
        return;
    }
    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;
        $rel = $baseRel === '' ? $entry : $baseRel . '/' . $entry;
        $rel = str_replace('\\', '/', $rel);
        $isDir = is_dir($fullPath);
        if ($exclude($rel, $isDir)) {
            continue;
        }
        if ($isDir) {
            $walk($fullPath, $rel);
        } else {
            $dest = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $destDir = dirname($dest);
            if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
                fwrite(STDERR, "Dizin oluşturulamadı: {$destDir}\n");
                continue;
            }
            if (!@copy($fullPath, $dest)) {
                fwrite(STDERR, "Kopyalanamadı: {$rel}\n");
                continue;
            }
            $fileList[] = $rel;
        }
    }
    closedir($dh);
};

$walk($projectRoot, '');

sort($fileList, SORT_STRING);

$dosyalarPath = $targetDir . DIRECTORY_SEPARATOR . 'DOSYALAR.txt';
$dosyalarBody = implode("\n", $fileList) . "\n";
file_put_contents($dosyalarPath, $dosyalarBody);
$fileList[] = 'DOSYALAR.txt';

$patchSql = buildDistPatchDatabaseSql($ver, $releaseDate);
$readmeHighlights = buildDistReadmeHighlights($ver);

$patchName = 'patch_' . $ver . '_database.sql';
file_put_contents($targetDir . DIRECTORY_SEPARATOR . $patchName, $patchSql);
$fileList[] = $patchName;

if ($consolidatedPatch !== null) {
    file_put_contents($targetDir . DIRECTORY_SEPARATOR . $consolidatedPatchName, $consolidatedPatch);
    $fileList[] = $consolidatedPatchName;
}

$readme = <<<TXT
ESH {$ver} — dosya aynası (mirror)
================================
Tarih: {$releaseDate}

Bu klasör, güncel uygulama dosyalarının dağıtım aynasıdır (dist/{$ver}/).
3.0.3 veya önceki 3.0.x kurulumunun üzerine kopyalanır.

Kurulum
-------
1. Veritabanı yedeği alın.
2. Birleşik patch varsa database/patch_{$ver}.sql uygulayın ({$patchName} adım listesi).
3. Bu klasördeki dosyaları sunucu proje köküne aynı yollarla üzerine yazın.
   config/config.local.php, logs/ ve kullanıcı yüklemelerini EZMEYİN.
4. Önbellek: tarayıcıda Ctrl+F5; OPcache varsa temizleyin.

{$readmeHighlights}

Dosya listesi
-------------
Tam liste: DOSYALAR.txt (bu betikle üretildi: tools/build_dist_mirror.php)

SQL
---
• patch_{$ver}.sql — birleşik veritabanı yaması (varsa; tek mysql içe aktarım)
• {$patchName} — sürüm notu ve bölüm özeti
• database/migrate_*.sql — parça parça uygulama (isteğe bağlı)
• database/schemas/schema.sql — tam şema referansı

Sürüm
-----
config/version.php → ESH_APP_VERSION={$ver}

Değişiklik özeti: changelog.php ({$ver} kartı)

TXT;

file_put_contents($targetDir . DIRECTORY_SEPARATOR . 'README.txt', $readme);
$fileList[] = 'README.txt';

sort($fileList, SORT_STRING);

echo "OK: {$targetDir}\n";
echo 'Dosya sayısı: ' . count($fileList) . " (+ README.txt, DOSYALAR.txt, {$patchName}";
if ($consolidatedPatch !== null) {
    echo ", {$consolidatedPatchName}";
}
echo ")\n";
echo 'Seçenek --full: ' . ($full ? 'açık' : 'kapalı') . "\n";
