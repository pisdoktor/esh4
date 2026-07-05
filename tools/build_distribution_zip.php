<?php
declare(strict_types=1);

/**
 * ESH dağıtım paketi (ZIP + tar.gz) üretir — CLI.
 *
 * Örnek:
 *   php tools/build_distribution_zip.php
 *   php tools/build_distribution_zip.php --out=C:\deploylar
 *   php tools/build_distribution_zip.php --full
 *   php tools/build_distribution_zip.php --zip-only
 *
 * --full : storage/old_tables ve storage/migration_jobs içeriğini de paketler
 *          (genelde yerel migasyon dökümleri; dağıtımda önerilmez.)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu betik yalnızca CLI ile çalıştırılmalıdır.\n");
    exit(1);
}

$argOut = null;
$full = false;
$zipOnly = false;
$gzipOnly = false;
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--full') {
        $full = true;
    } elseif ($a === '--zip-only') {
        $zipOnly = true;
    } elseif ($a === '--gzip-only') {
        $gzipOnly = true;
    } elseif (str_starts_with($a, '--out=')) {
        $argOut = substr($a, 6);
    } elseif ($a === '--help' || $a === '-h') {
        echo <<<'TXT'
ESH — dağıtım arşivi oluşturur (ZIP + tar.gz).

  php tools/build_distribution_zip.php [--out=DİZİN] [--full] [--zip-only|--gzip-only]

--out=      Çıktı dizini (varsayılan: <proje>/dist)
--full      storage/old_tables ve migration_jobs dahil (varsayılan: hariç)
--zip-only  Yalnızca .zip
--gzip-only Yalnızca .tar.gz

TXT;
        exit(0);
    }
}

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Proje kökü bulunamadı.\n");
    exit(1);
}

$ver = '4.0.0';
$releaseDate = gmdate('Y-m-d');
$vfile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'version.php';
if (is_readable($vfile)) {
    $raw = (string) file_get_contents($vfile);
    if (preg_match("/define\s*\(\s*'ESH_APP_VERSION'\s*,\s*'([^']+)'/", $raw, $m)) {
        $ver = $m[1];
    }
    if (preg_match("/define\s*\(\s*'ESH_APP_RELEASE_DATE'\s*,\s*'([^']+)'/", $raw, $m)) {
        $releaseDate = $m[1];
    }
}

$outDir = $argOut !== null && $argOut !== ''
    ? rtrim($argOut, "/\\")
    : $projectRoot . DIRECTORY_SEPARATOR . 'dist';
if (!is_dir($outDir) && !@mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Çıktı dizini oluşturulamadı: {$outDir}\n");
    exit(1);
}

$baseName = 'esh-' . $ver . '-install';
$zipPath = $outDir . DIRECTORY_SEPARATOR . $baseName . '.zip';
$tarGzPath = $outDir . DIRECTORY_SEPARATOR . $baseName . '.tar.gz';

$fileList = [];
/** @param callable(string, bool): bool $exclude */
$walk = static function (string $dir, string $baseRel) use (&$walk, &$fileList, $full): void {
    $exclude = static function (string $rel, bool $isDir) use ($full): bool {
        $norm = str_replace('\\', '/', $rel);
        $segs = explode('/', $norm);
        $root = $segs[0] ?? '';

        if (in_array('.git', $segs, true) || in_array('.cursor', $segs, true) || in_array('.vscode', $segs, true)) {
            return true;
        }
        if ($root === 'dist' || $root === 'setup') {
            return true;
        }
        if (in_array('node_modules', $segs, true)) {
            return true;
        }
        if ($root === 'logs') {
            return true;
        }
        if ($root === 'backups') {
            return true;
        }

        if ($norm === 'config/config.local.php' || $norm === 'config/install.lock') {
            return true;
        }
        if ($norm === 'public/install.php__') {
            return true;
        }

        if (!$full) {
            if ($root === 'storage' && in_array('old_tables', $segs, true)) {
                return true;
            }
            if ($root === 'storage' && in_array('migration_jobs', $segs, true)) {
                return true;
            }
            if (str_starts_with($norm, 'storage/backups/')) {
                $bn = basename($norm);
                if ($bn !== '.gitignore' && $bn !== '.htaccess') {
                    return true;
                }
            }
            if ($root === 'tools' && ($segs[1] ?? '') === 'archive') {
                return true;
            }
            if ($root === 'database' && ($segs[1] ?? '') === 'archive') {
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
            $fileList[] = [$fullPath, $rel];
        }
    }
    closedir($dh);
};

$walk($projectRoot, '');

if ($fileList === []) {
    fwrite(STDERR, "Paketlenecek dosya bulunamadı.\n");
    exit(1);
}

$rootInside = 'esh-' . $ver;
$readme = <<<MD
# ESH {$ver} — kurulum paketi

Sürüm: {$ver} · Tarih: {$releaseDate}

## Kurulum

1. Arşivi sunucuda açın; `{$rootInside}/` klasörünün **içeriğini** web köküne taşıyın.
2. Tarayıcıdan `public/install.php` sihirbazını açın.
3. Veritabanı türünü seçin (mysql, sqlsrv, pgsql, sqlite, oci).
4. Kurulum bitince `public/install.php` dosyasını kaldırın veya erişimi kilitleyin.

## Notlar

- `config/config.local.php` ve `config/install.lock` pakette yoktur; sihirbaz oluşturur.
- Şema: `database/schemas/` · Seed sırası: `database/seed/install_seeds.php`
- Ayrıntılar: README.txt ve README.md

Bu dosya `tools/build_distribution_zip.php` tarafından üretilmiştir.
MD;

$buildZip = !$gzipOnly;
$buildGzip = !$zipOnly;

if ($buildZip) {
    if (!class_exists(ZipArchive::class)) {
        fwrite(STDERR, "PHP ZipArchive eklentisi yüklü değil.\n");
        exit(1);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        fwrite(STDERR, "ZIP açılamadı: {$zipPath}\n");
        exit(1);
    }

    foreach ($fileList as [$abs, $rel]) {
        $entry = $rootInside . '/' . $rel;
        if (!$zip->addFile($abs, $entry)) {
            fwrite(STDERR, "ZIP'e eklenemedi: {$rel}\n");
        }
    }
    if (!$zip->addFromString($rootInside . '/README_DIST.txt', $readme)) {
        fwrite(STDERR, "README_DIST.txt ZIP'e eklenemedi.\n");
    }
    if (!$zip->addEmptyDir($rootInside . '/logs')) {
        fwrite(STDERR, "logs/ ZIP'e eklenemedi.\n");
    }
    $zip->close();

    $bytes = @filesize($zipPath) ?: 0;
    $mb = number_format($bytes / 1048576, 2, ',', '.');
    echo "OK ZIP: {$zipPath}\n";
    echo "Boyut: {$mb} MiB, dosya sayısı: " . count($fileList) . " (+ README_DIST.txt, logs/)\n";
}

if ($buildGzip) {
    if (!buildTarGzArchive($fileList, $rootInside, $readme, $tarGzPath, $outDir)) {
        if (PHP_OS_FAMILY === 'Windows') {
            echo "UYARI: tar.gz bu ortamda üretilemedi (GitHub Releases veya Linux/macOS kullanın).\n";
        } else {
            fwrite(STDERR, "tar.gz oluşturulamadı.\n");
            exit(1);
        }
    }
}

echo 'Seçenek --full: ' . ($full ? 'açık (ham storage dahil)' : 'kapalı') . "\n";

/**
 * @param list<array{0: string, 1: string}> $fileList
 */
function buildTarGzArchive(array $fileList, string $rootInside, string $readme, string $tarGzPath, string $outDir): bool
{
    $tarBin = trim((string) shell_exec('command -v tar 2>/dev/null'));
    if ($tarBin === '') {
        return false;
    }

    $staging = $outDir . DIRECTORY_SEPARATOR . '_staging_tar_' . md5($rootInside);
    $rootPath = $staging . DIRECTORY_SEPARATOR . $rootInside;
    if (is_dir($staging)) {
        removeTree($staging);
    }
    if (!@mkdir($rootPath . DIRECTORY_SEPARATOR . 'logs', 0755, true)) {
        return false;
    }

    foreach ($fileList as [$abs, $rel]) {
        $dest = $rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $destDir = dirname($dest);
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
            removeTree($staging);
            return false;
        }
        if (!@copy($abs, $dest)) {
            removeTree($staging);
            return false;
        }
    }

    file_put_contents($rootPath . DIRECTORY_SEPARATOR . 'README_DIST.txt', $readme);

    if (is_file($tarGzPath)) {
        @unlink($tarGzPath);
    }

    $cmd = sprintf(
        '%s -czf %s -C %s %s',
        escapeshellarg($tarBin),
        escapeshellarg($tarGzPath),
        escapeshellarg($staging),
        escapeshellarg($rootInside)
    );
    exec($cmd, $out, $code);
    removeTree($staging);

    if ($code !== 0 || !is_file($tarGzPath)) {
        return false;
    }

    $bytes = @filesize($tarGzPath) ?: 0;
    $mb = number_format($bytes / 1048576, 2, ',', '.');
    echo "OK tar.gz: {$tarGzPath}\n";
    echo "Boyut: {$mb} MiB\n";

    return true;
}

/**
 * @param string $dir
 */
function removeTree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            removeTree($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
