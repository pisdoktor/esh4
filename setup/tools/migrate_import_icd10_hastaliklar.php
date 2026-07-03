<?php
/**
 * icd10_hastaliklar.json veya SKRS dosyasından esh_hastaliklar platform kataloğuna UPSERT.
 * Kullanım: php tools/migrate_import_icd10_hastaliklar.php [json|xlsx|csv]
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

use App\Helpers\CatalogStoreHelper;
use App\Helpers\Icd10CatMapper;
use App\Helpers\Icd10SkrsParser;

$importDir = $root . '/database/import';
$jsonPath = $importDir . '/icd10_hastaliklar.json';
$input = isset($argv[1]) ? trim((string) $argv[1]) : '';

$rows = [];
if ($input !== '') {
    if (!str_contains($input, ':') && !str_starts_with($input, '/') && !preg_match('/^[A-Za-z]:\\\\/', $input)) {
        $input = $root . '/' . ltrim(str_replace('\\', '/', $input), '/');
    }
    try {
        $rows = Icd10SkrsParser::withCategories(Icd10SkrsParser::parseFile($input));
    } catch (Throwable $e) {
        fwrite(STDERR, 'HATA: ' . $e->getMessage() . "\n");
        exit(1);
    }
} elseif (is_readable($jsonPath)) {
    $decoded = json_decode((string) file_get_contents($jsonPath), true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "JSON okunamadı: {$jsonPath}\n");
        exit(1);
    }
    $rows = $decoded;
} else {
    fwrite(STDERR, "Veri yok. Önce: php tools/build_icd10_hastaliklar_from_skrs.php\n");
    exit(1);
}

if ($rows === []) {
    fwrite(STDERR, "İçe aktarılacak satır yok.\n");
    exit(1);
}

$port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';
$dsn = 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$kurumId = CatalogStoreHelper::PLATFORM_KURUM_ID;
$batchSize = 500;
$total = count($rows);
$upserted = 0;

$sql = 'INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`, `parent_icd`, `seviye`)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `hastalikadi` = VALUES(`hastalikadi`), `cat` = VALUES(`cat`),
        `parent_icd` = VALUES(`parent_icd`), `seviye` = VALUES(`seviye`)';

$stmt = $pdo->prepare($sql);
$pdo->beginTransaction();
try {
    foreach ($rows as $i => $row) {
        $icd = strtoupper(trim((string) ($row['icd'] ?? '')));
        $name = trim((string) ($row['hastalikadi'] ?? ''));
        if ($icd === '' || $name === '') {
            continue;
        }
        $cat = isset($row['cat']) ? (int) $row['cat'] : Icd10CatMapper::toHastalikCat($icd);
        $parentIcd = isset($row['parent_icd']) && $row['parent_icd'] !== null && $row['parent_icd'] !== ''
            ? (string) $row['parent_icd'] : null;
        $seviye = isset($row['seviye']) && $row['seviye'] !== null ? (int) $row['seviye'] : null;
        $stmt->execute([$kurumId, $cat, $name, $icd, $parentIcd, $seviye]);
        $upserted++;
        if (($i + 1) % $batchSize === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            fwrite(STDERR, ($i + 1) . "/{$total}\n");
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'DB HATA: ' . $e->getMessage() . "\n");
    exit(1);
}

$count = (int) $pdo->query('SELECT COUNT(*) FROM esh_hastaliklar WHERE kurum_id = 0')->fetchColumn();
echo "OK: {$upserted} satır işlendi; platform katalog toplam: {$count}\n";
