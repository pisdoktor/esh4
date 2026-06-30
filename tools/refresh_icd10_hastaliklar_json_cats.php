<?php
/**
 * icd10_hastaliklar.json içindeki cat alanlarını güncel Icd10CatMapper ile yeniler.
 * Kullanım: php tools/refresh_icd10_hastaliklar_json_cats.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI gerekli.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Helpers\Icd10CatMapper;

$jsonPath = $root . '/database/import/icd10_hastaliklar.json';
if (!is_readable($jsonPath)) {
    fwrite(STDERR, "JSON bulunamadı: {$jsonPath}\n");
    exit(1);
}

$decoded = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($decoded)) {
    fwrite(STDERR, "JSON okunamadı.\n");
    exit(1);
}

$updated = 0;
foreach ($decoded as &$row) {
    if (!is_array($row)) {
        continue;
    }
    $icd = strtoupper(trim((string) ($row['icd'] ?? '')));
    if ($icd === '') {
        continue;
    }
    $newCat = Icd10CatMapper::toHastalikCat($icd);
    $oldCat = (int) ($row['cat'] ?? 0);
    if ($oldCat !== $newCat) {
        $row['cat'] = $newCat;
        $updated++;
    }
}
unset($row);

$encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($encoded) || file_put_contents($jsonPath, $encoded) === false) {
    fwrite(STDERR, "JSON yazılamadı.\n");
    exit(1);
}

echo "OK: " . count($decoded) . " satır, {$updated} cat güncellendi → {$jsonPath}\n";
