<?php
declare(strict_types=1);

/**
 * ESH 3.0 dağıtım paketi (ZIP) üretir — CLI.
 *
 * Örnek:
 *   cd 3.0.0
 *   php tools/build_distribution_zip.php
 *   php tools/build_distribution_zip.php --out=C:\deploylar
 *   php tools/build_distribution_zip.php --full
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
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--full') {
        $full = true;
    } elseif (str_starts_with($a, '--out=')) {
        $argOut = substr($a, 6);
    } elseif ($a === '--help' || $a === '-h') {
        echo <<<'TXT'
ESH 3.0 — dağıtım ZIP oluşturur.

  php tools/build_distribution_zip.php [--out=DİZİN] [--full]

--out=  Çıktı dizini (varsayılan: <proje>/dist)
--full   storage/old_tables ve migration_jobs dahil (varsayılan: hariç)

TXT;
        exit(0);
    }
}

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Proje kökü bulunamadı.\n");
    exit(1);
}

$ver = '3.0.0';
$vfile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'version.php';
if (is_readable($vfile)) {
    $raw = (string) file_get_contents($vfile);
    if (preg_match("/define\s*\(\s*'ESH_APP_VERSION'\s*,\s*'([^']+)'/", $raw, $m)) {
        $ver = $m[1];
    }
}

$outDir = $argOut !== null && $argOut !== ''
    ? rtrim($argOut, "/\\")
    : $projectRoot . DIRECTORY_SEPARATOR . 'dist';
if (!is_dir($outDir) && !@mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Çıktı dizini oluşturulamadı: {$outDir}\n");
    exit(1);
}

$date = gmdate('Ymd');
$zipName = 'esh-' . $ver . '-dist-' . $date . '.zip';
$zipPath = $outDir . DIRECTORY_SEPARATOR . $zipName;

$fileList = [];
/** @param callable(string, bool): bool $exclude */
$walk = static function (string $dir, string $baseRel) use (&$walk, &$fileList, $full): void {
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

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "PHP ZipArchive eklentisi yüklü değil.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "ZIP açılamadı: {$zipPath}\n");
    exit(1);
}

$rootInside = 'esh-' . $ver;
foreach ($fileList as [$abs, $rel]) {
    $entry = $rootInside . '/' . $rel;
    if (!$zip->addFile($abs, $entry)) {
        fwrite(STDERR, "Eklenemedi: {$rel}\n");
    }
}

$readme = <<<'MD'
# ESH 3.0 paket içeriği

- Kurulum: tarayıcıdan `public/install.php` sihirbazı (`config/config.local.example.php` şablonu).
- `config/config.local.php` ve `config/install.lock` pakette yoktur; hedefte sıfırdan kurulum veya kendi dosyalarınızı kopyalayın.
- Veritabanı şeması: `database/schemas/schema.sql` ve `docs/VERSIONING.md`; adres senkronu: süper yönetici AdresFetch.

Bu dosya `tools/build_distribution_zip.php` tarafından üretilmiştir.
MD;

if (!$zip->addFromString($rootInside . '/README_DIST.txt', $readme)) {
    fwrite(STDERR, "README_DIST.txt eklenemedi.\n");
}

$zip->close();

$bytes = @filesize($zipPath) ?: 0;
$mb = number_format($bytes / 1048576, 2, ',', '.');
echo "OK: {$zipPath}\n";
echo "Boyut: {$mb} MiB, dosya sayısı: " . count($fileList) . " (+ README_DIST.txt)\n";
echo 'Seçenek --full: ' . ($full ? 'açık (ham storage dahil)' : 'kapalı') . "\n";
