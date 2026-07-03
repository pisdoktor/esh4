<?php
declare(strict_types=1);

/**
 * Temiz kurulum paketi: proje kökündeki gerekli dosyaları setup/ altına mirror kopyalar.
 *
 *   php tools/build_setup_mirror.php
 *   php tools/build_setup_mirror.php --full
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu betik yalnızca CLI ile çalıştırılmalıdır.\n");
    exit(1);
}

$full = in_array('--full', $argv, true);
if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo <<<'TXT'
ESH — temiz kurulum aynası (setup/)

  php tools/build_setup_mirror.php [--full]

Çıktı: <proje>/setup/  (mevcut setup/ silinip yeniden oluşturulur)

Hariç: .git, .cursor, dist/, setup/, node_modules, logs/*, backups/,
       config.local.php, install.lock, *.pre-edit.bak, geliştirme artıkları.

--full  storage/old_tables ve migration_jobs içeriğini de kopyalar (varsayılan: hayır)

TXT;
    exit(0);
}

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Proje kökü bulunamadı.\n");
    exit(1);
}

$ver = '3.1.0';
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

$targetDir = $projectRoot . DIRECTORY_SEPARATOR . 'setup';
if (is_dir($targetDir)) {
    removeTree($targetDir);
}
if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Hedef dizin oluşturulamadı: {$targetDir}\n");
    exit(1);
}

$fileList = [];

/** @param callable(string, bool): bool $exclude */
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
    if ($root === 'backups') {
        return true;
    }
    if ($root === 'logs') {
        return true;
    }

    if ($norm === 'config/config.local.php' || $norm === 'config/install.lock') {
        return true;
    }

    if ($norm === 'cursor-user-rule-yedek-lint-KOPYA.txt') {
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

$walk = static function (string $dir, string $baseRel) use (&$walk, &$fileList, $exclude, $projectRoot, $targetDir): void {
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

$installSrc = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install.php__';
$installDest = $targetDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install.php';
if (is_file($installSrc) && @copy($installSrc, $installDest)) {
    $fileList[] = 'public/install.php';
}

if (!is_dir($targetDir . DIRECTORY_SEPARATOR . 'logs') && !@mkdir($targetDir . DIRECTORY_SEPARATOR . 'logs', 0755, true)) {
    fwrite(STDERR, "logs/ oluşturulamadı.\n");
}

sort($fileList, SORT_STRING);

$dosyalarBody = implode("\n", $fileList) . "\n";
file_put_contents($targetDir . DIRECTORY_SEPARATOR . 'DOSYALAR.txt', $dosyalarBody);
$fileList[] = 'DOSYALAR.txt';

$readme = <<<TXT
ESH {$ver} — temiz kurulum paketi (setup/)
==========================================
Oluşturma: {$releaseDate}
Kaynak betik: php tools/build_setup_mirror.php

Bu klasör, sıfır sunucu kurulumu için gerekli uygulama dosyalarının aynasıdır.
Geliştirme ortamı artıkları, dist/, .git, yerel config ve log yedekleri dahil edilmez.

Kurulum (özet)
--------------
1. Bu klasörün İÇERİĞİNİ (setup/ değil, altındaki app, public, config, …) web köküne kopyalayın.
2. Tarayıcıdan `public/install.php` sihirbazını açın (şema + seed + config.local.php + install.lock).
3. Veritabanı türü: mysql, sqlsrv, pgsql, sqlite veya oci (sihirbazda seçilir).
   İlgili şema: database/schemas/ altında schema.sql, schema.mssql.sql, schema.pgsql.sql, schema.sqlite.sql, schema.oci.sql
4. Kurulum sonrası güvenlik için `public/install.php` dosyasını sunucudan kaldırın veya kilitleyin.

Önemli
------
• Desteklenen sürücüler: `mysql`, `sqlsrv`, `pgsql`, `sqlite`, `oci` (ilgili PDO eklentisi gerekir).
• `config/config.local.php` ve `config/install.lock` pakette YOK — sihirbaz oluşturur.
• `config/config.local.example.php` şablon olarak dahildir.
• `logs/` boş bırakıldı; uygulama çalışırken dolar.
• `storage/backups/` yalnızca .htaccess / .gitignore ile gelir (--full değilse).

Dosya listesi: DOSYALAR.txt

TXT;

file_put_contents($targetDir . DIRECTORY_SEPARATOR . 'README.txt', $readme);
$fileList[] = 'README.txt';

echo "OK: {$targetDir}\n";
echo 'Dosya sayısı: ' . count($fileList) . " (+ README.txt, DOSYALAR.txt, public/install.php)\n";
echo '--full: ' . ($full ? 'açık' : 'kapalı') . "\n";

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
