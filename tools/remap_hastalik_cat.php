<?php
/**
 * esh_hastaliklar.cat alanını ICD koduna göre Icd10CatMapper ile hastalikcat (1–21) ile hizalar.
 *
 * Kullanım:
 *   php tools/remap_hastalik_cat.php           — ICD eşlemesi uyuşmayan satırları düzelt (varsayılan)
 *   php tools/remap_hastalik_cat.php --all     — tüm satırları ICD'den yeniden eşle
 *   php tools/remap_hastalik_cat.php --verify  — yalnız rapor, güncelleme yok
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

$argv = $argv ?? [];
$verifyOnly = in_array('--verify', $argv, true);
$remapAll = in_array('--all', $argv, true);

$port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';
$dsn = 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$validCatIds = array_map('intval', $pdo->query('SELECT id FROM esh_hastalikcat ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
$validSet = array_flip($validCatIds);

$rows = $pdo->query('SELECT id, icd, cat FROM esh_hastaliklar WHERE icd IS NOT NULL AND icd <> \'\'')->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare('UPDATE esh_hastaliklar SET cat = ? WHERE id = ?');
$updated = 0;
$skipped = 0;
$mismatch = 0;
$samples = [];

if (!$verifyOnly) {
    $pdo->beginTransaction();
}

try {
    foreach ($rows as $row) {
        $icd = strtoupper(trim((string) ($row['icd'] ?? '')));
        if ($icd === '') {
            $skipped++;
            continue;
        }
        $newCat = Icd10CatMapper::toHastalikCat($icd);
        if ($newCat <= 0 || !isset($validSet[$newCat])) {
            $skipped++;
            continue;
        }
        $oldCat = (int) ($row['cat'] ?? 0);
        $needsFix = $remapAll
            ? ($oldCat !== $newCat)
            : ($oldCat !== $newCat || $oldCat <= 0 || !isset($validSet[$oldCat]));

        if (!$needsFix) {
            continue;
        }

        $mismatch++;
        if (count($samples) < 5) {
            $samples[] = ['id' => (int) $row['id'], 'icd' => $icd, 'cat' => $oldCat, 'expected' => $newCat];
        }

        if (!$verifyOnly) {
            $update->execute([$newCat, (int) $row['id']]);
            $updated++;
        }
    }

    if (!$verifyOnly) {
        $pdo->commit();
    }
} catch (Throwable $e) {
    if (!$verifyOnly && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'HATA: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($verifyOnly) {
    echo "Uyuşmazlık: {$mismatch} / " . count($rows) . " satır\n";
} else {
    echo "Güncellenen satır: {$updated}\n";
    if ($skipped > 0) {
        echo "Atlanan satır: {$skipped}\n";
    }
}

if ($mismatch > 0 && $samples !== []) {
    echo "Örnekler:\n";
    foreach ($samples as $s) {
        echo "  id={$s['id']} icd={$s['icd']} cat={$s['cat']} → {$s['expected']}\n";
    }
}

if ($verifyOnly && $mismatch > 0) {
    exit(2);
}
