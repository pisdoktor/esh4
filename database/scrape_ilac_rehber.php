<?php
/**
 * ÃœÃ§Ã¼ncÃ¼ taraf ilaÃ§ rehberi â†’ esh_rehber_* tablolarÄ±na CLI snapshot.
 *
 * KullanÄ±m:
 *   php database/scrape_ilac_rehber.php --site=ilacabak --sleep=50
 *   php database/scrape_ilac_rehber.php --site=ilacabak --discovery=both --id-max=15000
 *   php database/scrape_ilac_rehber.php --truncate --site=ilacabak --limit-etken=10
 *   php database/scrape_ilac_rehber.php --fresh --discovery=seeds
 *   php database/scrape_ilac_rehber.php --no-resume
 *   php database/scrape_ilac_rehber.php --db-name=esh3 --debug
 */

declare(strict_types=1);

set_time_limit(0);
ini_set('memory_limit', '512M');

if (PHP_SAPI === 'cli') {
    @ob_implicit_flush(true);
}

$root = dirname(__DIR__);
require $root . '/config/config.php';
require_once __DIR__ . '/ilac_rehber_parsers/IlacRehberParserInterface.php';
require_once __DIR__ . '/ilac_rehber_parsers/IlacRehberParserIlacabak.php';
require_once __DIR__ . '/ilac_rehber_checkpoint.php';
require_once __DIR__ . '/ilac_rehber_import_log.php';
require_once $root . '/app/Helpers/TrSearchFoldHelper.php';

$opts = getopt('', [
    'truncate',
    'fresh',
    'no-resume',
    'sleep::',
    'limit-etken::',
    'limit-ilac::',
    'site::',
    'debug',
    'insecure-ssl',
    'db-host::',
    'db-user::',
    'db-pass::',
    'db-name::',
    'discovery::',
    'id-max::',
    'id-empty-stop::',
    'progress-every::',
]);

$doTruncate = isset($opts['truncate']);
$doFresh = isset($opts['fresh']);
$doResume = !isset($opts['no-resume']);
// Varsayılan 50ms (50000 µs); --sleep ile geçersiz kılınır. Daha düşük değerler rate limit riskini artırır.
$sleepUs = isset($opts['sleep']) ? max(0, (int) $opts['sleep']) * 1000 : 50000;
$limitEtken = isset($opts['limit-etken']) ? max(0, (int) $opts['limit-etken']) : 0;
$limitIlac = isset($opts['limit-ilac']) ? max(0, (int) $opts['limit-ilac']) : 0;
$site = isset($opts['site']) && trim((string) $opts['site']) !== ''
    ? strtolower(trim((string) $opts['site']))
    : 'ilacabak';
$debug = isset($opts['debug']);
$insecureSsl = isset($opts['insecure-ssl']);
$discovery = isset($opts['discovery']) && trim((string) $opts['discovery']) !== ''
    ? strtolower(trim((string) $opts['discovery']))
    : 'both';
if (!in_array($discovery, ['seeds', 'ids', 'both'], true)) {
    fwrite(STDERR, "GeÃ§ersiz --discovery={$discovery} (seeds|ids|both)\n");
    exit(1);
}
$idMax = isset($opts['id-max']) ? max(1, (int) $opts['id-max']) : 15000;
$idEmptyStop = isset($opts['id-empty-stop']) ? max(1, (int) $opts['id-empty-stop']) : 50;
$progressEvery = isset($opts['progress-every']) ? max(1, (int) $opts['progress-every']) : 0;

$dbHost = isset($opts['db-host']) && trim((string) $opts['db-host']) !== '' ? trim((string) $opts['db-host']) : DB_HOST;
$dbUser = isset($opts['db-user']) && trim((string) $opts['db-user']) !== '' ? trim((string) $opts['db-user']) : DB_USER;
$dbPass = isset($opts['db-pass']) ? (string) $opts['db-pass'] : DB_PASS;
$dbName = isset($opts['db-name']) && trim((string) $opts['db-name']) !== '' ? trim((string) $opts['db-name']) : DB_NAME;

$logDir = $root . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/ilac_rehber_scrape.log';

$pdo = new PDO(
    'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci',
    ]
);

function outLine(string $line): void
{
    echo $line . PHP_EOL;
    if (PHP_SAPI === 'cli') {
        @flush();
    }
}

function logScrape(string $file, string $line): void
{
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($file, "[{$ts}] {$line}\n", FILE_APPEND | LOCK_EX);
}

function httpGetOnce(string $url, bool $insecureSsl = false, int $timeoutSec = 90, int $connectTimeoutSec = 25): string
{
    $userAgent = 'Mozilla/5.0 (compatible; ESH-IlacRehber-Scrape/1.0; +https://localhost)';
    if ($insecureSsl) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeoutSec,
                'header' => "User-Agent: {$userAgent}\r\nAccept: text/html,*/*;q=0.8\r\nAccept-Encoding: gzip\r\n",
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw !== false) {
            return (string) $raw;
        }
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('curl_init basarisiz');
    }
    $sslVerify = !$insecureSsl;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeoutSec,
        CURLOPT_CONNECTTIMEOUT => $connectTimeoutSec,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_HTTPHEADER => ['Accept: text/html,*/*;q=0.8'],
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => $sslVerify,
        CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $code >= 400) {
        throw new RuntimeException('HTTP ' . $code . ' - ' . $url . ($err !== '' ? ' - ' . $err : ''));
    }

    return (string) $raw;
}

function httpGetIsRetryableError(string $message): bool
{
    if ($message === '') {
        return false;
    }
    // 4xx/5xx: sunucu/istemci hatası — aynı URL ile tekrar denemek genelde boşa
    if (preg_match('/HTTP 4\d\d\b/', $message) && !preg_match('/HTTP 429\b/', $message)) {
        return false;
    }
    if (preg_match('/HTTP 5\d\d\b/', $message)) {
        return false;
    }
    if (str_contains($message, 'HTTP 0')) {
        return true;
    }
    if (preg_match('/HTTP 429\b/', $message)) {
        return true;
    }

    return (bool) preg_match('/timed out|timeout|Couldn\'t connect|Connection reset|Empty reply|Recv failure/i', $message);
}

function httpGet(string $url, bool $insecureSsl = false, int $maxAttempts = 3): string
{
    $lastError = '';
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            return httpGetOnce($url, $insecureSsl);
        } catch (RuntimeException $e) {
            $lastError = $e->getMessage();
            if (!httpGetIsRetryableError($lastError) || $attempt >= $maxAttempts) {
                throw $e;
            }
            usleep(750000 * $attempt);
        }
    }

    throw new RuntimeException($lastError !== '' ? $lastError : 'HTTP istegi basarisiz');
}

function trFold(string $s): string
{
    return \App\Helpers\TrSearchFoldHelper::fold($s);
}

function ensureTables(PDO $pdo): void
{
    $sql = @file_get_contents(__DIR__ . '/migrate_esh_rehber_ilac_create.sql');
    if ($sql === false) {
        throw new RuntimeException('migrate_esh_rehber_ilac_create.sql okunamadÄ±');
    }
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt === '' || !preg_match('/^CREATE\s+TABLE/i', $stmt)) {
            continue;
        }
        $pdo->exec($stmt);
    }
}

function upsertEtken(PDO $pdo, string $site, string $key, string $ad): int
{
    static $st = null;
    if ($st === null) {
        $st = $pdo->prepare(
            'INSERT INTO esh_rehber_etken (ad, ad_normalized, source_site, source_key, scraped_at)
             VALUES (:ad, :norm, :site, :key, NOW())
             ON DUPLICATE KEY UPDATE ad = VALUES(ad), ad_normalized = VALUES(ad_normalized), scraped_at = NOW()'
        );
    }
    $norm = trFold($ad);
    $st->execute([
        ':ad' => $ad,
        ':norm' => $norm,
        ':site' => $site,
        ':key' => $key,
    ]);
    $id = (int) $pdo->lastInsertId();
    if ($id > 0) {
        return $id;
    }
    $sel = $pdo->prepare('SELECT id FROM esh_rehber_etken WHERE source_site = :site AND source_key = :key LIMIT 1');
    $sel->execute([':site' => $site, ':key' => $key]);

    return (int) $sel->fetchColumn();
}

function countIlacForEtken(PDO $pdo, string $site, string $key): int
{
    static $st = null;
    if ($st === null) {
        $st = $pdo->prepare(
            'SELECT COUNT(*)
             FROM esh_rehber_ilac i
             INNER JOIN esh_rehber_etken e ON e.id = i.etken_id
             WHERE e.source_site = :site AND e.source_key = :key'
        );
    }
    $st->execute([':site' => $site, ':key' => $key]);

    return (int) $st->fetchColumn();
}

function upsertIlac(
    PDO $pdo,
    int $etkenId,
    string $site,
    string $key,
    string $ad,
    string $firma,
    string $recete,
    string $url
): void {
    static $st = null;
    if ($st === null) {
        $st = $pdo->prepare(
            'INSERT INTO esh_rehber_ilac (etken_id, ad, firma, recete_turu, source_site, source_url, source_key, scraped_at)
             VALUES (:eid, :ad, :firma, :rec, :site, :url, :key, NOW())
             ON DUPLICATE KEY UPDATE
               etken_id = VALUES(etken_id),
               ad = VALUES(ad),
               firma = VALUES(firma),
               recete_turu = VALUES(recete_turu),
               source_url = VALUES(source_url),
               scraped_at = NOW()'
        );
    }
    $st->execute([
        ':eid' => $etkenId,
        ':ad' => $ad,
        ':firma' => $firma !== '' ? $firma : null,
        ':rec' => $recete !== '' ? $recete : null,
        ':site' => $site,
        ':url' => $url !== '' ? $url : null,
        ':key' => $key,
    ]);
}

$debugSave = null;
if ($debug) {
    $sampleDir = $root . '/storage/ilac_rehber_scrape_samples';
    if (!is_dir($sampleDir)) {
        @mkdir($sampleDir, 0755, true);
    }
    $debugSave = static function (string $name, string $body) use ($sampleDir): void {
        @file_put_contents($sampleDir . '/' . preg_replace('/[^a-z0-9._-]+/i', '_', $name), $body);
    };
}

$httpWithSleep = static function (string $url) use (&$sleepUs, $insecureSsl): string {
    if ($sleepUs > 0) {
        usleep($sleepUs);
    }

    return httpGet($url, $insecureSsl);
};

$parser = match ($site) {
    'ilacabak' => new IlacRehberParserIlacabak($httpWithSleep, $debug, $debugSave),
    default => null,
};

if ($parser === null) {
    fwrite(STDERR, "Bilinmeyen --site={$site} (desteklenen: ilacabak)\n");
    exit(1);
}

$checkpointConfig = [
    'site' => $site,
    'discovery' => $discovery,
    'id_max' => $idMax,
];

if ($doTruncate || $doFresh) {
    ilacRehberCheckpointClear($root);
    if ($doFresh) {
        outLine('Checkpoint silindi (--fresh).');
        logScrape($logFile, 'Checkpoint cleared (--fresh)');
    }
}

$seedTotal = 0;
$resumeSeedIndex = 0;
$resumeIdCursor = 1;
$resumeEtkenProcessed = 0;
$existingCheckpoint = ilacRehberCheckpointRead($root);
if ($parser instanceof IlacRehberParserIlacabak) {
    $seedTotal = $parser->countSeedQueries();
}

if (!$doTruncate && !$doFresh && $doResume) {
    $resolved = ilacRehberCheckpointResolveResume($existingCheckpoint, $checkpointConfig, true);
    $resumeSeedIndex = $resolved['seed_index'];
    $resumeIdCursor = $resolved['id_cursor'];
    foreach ($resolved['warnings'] as $warn) {
        outLine('Checkpoint: ' . $warn);
        logScrape($logFile, 'Checkpoint warn: ' . $warn);
    }
    if ($resolved['used']) {
        $msg = "Resuming seeds from index {$resumeSeedIndex}";
        if ($discovery !== 'seeds' && $resumeIdCursor > 1) {
            $msg .= ", Id cursor {$resumeIdCursor}";
        }
        outLine($msg);
        logScrape($logFile, $msg);
    }
    if ($existingCheckpoint !== null) {
        $resumeEtkenProcessed = (int) ($existingCheckpoint['etken_processed'] ?? 0);
    }
} elseif (isset($opts['no-resume'])) {
    outLine('Checkpoint yok sayıldı (--no-resume).');
}

$checkpointState = ilacRehberCheckpointNewState(array_merge($checkpointConfig, [
    'seed_index' => $resumeSeedIndex,
    'seed_total' => $seedTotal,
    'id_cursor' => $resumeIdCursor,
    'etken_processed' => $resumeEtkenProcessed,
]));

$persistCheckpoint = static function () use ($root, &$checkpointState): void {
    ilacRehberCheckpointWrite($root, $checkpointState);
};
$persistCheckpoint();

if ($parser instanceof IlacRehberParserIlacabak) {
    $parser->setDiscoveryMode($discovery);
    $parser->setIdScanOptions($idMax, $idEmptyStop);
    $parser->setSeedStartIndex($resumeSeedIndex);
    $parser->setIdStartCursor($resumeIdCursor);
    $parser->setOnCheckpointTick(static function (array $tick) use (&$checkpointState, $persistCheckpoint): void {
        if (($tick['phase'] ?? '') === 'seed') {
            $checkpointState['seed_index'] = (int) ($tick['seed_index'] ?? 0);
            $checkpointState['seed_total'] = (int) ($tick['seed_total'] ?? 0);
        } elseif (($tick['phase'] ?? '') === 'id') {
            $checkpointState['id_cursor'] = (int) ($tick['id_cursor'] ?? 1);
        }
        $persistCheckpoint();
    });
    $parser->setProgressLog(static function (string $msg) use ($logFile): void {
        outLine($msg);
        logScrape($logFile, $msg);
    });
}

outLine("Ä°laÃ§ rehberi scrape ({$dbName}) â€” site={$site}, sleep=" . (int) ($sleepUs / 1000) . 'ms'
    . ", discovery={$discovery}, id-max={$idMax}, id-empty-stop={$idEmptyStop}"
    . ($progressEvery > 0 ? ", progress-every={$progressEvery}" : ''));
logScrape($logFile, "START site={$site} db={$dbName} discovery={$discovery} id-max={$idMax}"
    . ($progressEvery > 0 ? " progress-every={$progressEvery}" : ''));

ensureTables($pdo);

if ($doTruncate) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['esh_rehber_ilac', 'esh_rehber_etken'] as $tbl) {
        $pdo->exec('TRUNCATE TABLE `' . $tbl . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    ilacRehberImportClearAll($root);
    ilacRehberCheckpointClear($root);
    $resumeSeedIndex = 0;
    $resumeIdCursor = 1;
    $checkpointState['seed_index'] = 0;
    $checkpointState['id_cursor'] = 1;
    $persistCheckpoint();
    outLine('esh_rehber_* TRUNCATE edildi (checkpoint silindi).');
}

$importJobId = ilacRehberImportCreateJob($root, $site, [
    'truncate' => $doTruncate,
    'sleep_ms' => (int) ($sleepUs / 1000),
    'limit_etken' => $limitEtken,
    'limit_ilac' => $limitIlac,
    'discovery' => $discovery,
    'id_max' => $idMax,
    'id_empty_stop' => $idEmptyStop,
    'progress_every' => $progressEvery,
    'resume' => $doResume,
    'fresh' => $doFresh,
]);

$errors = [];
$nEtken = 0;
$nIlac = 0;
$processedEtkenKeys = [];
$incrementalUpsert = true;

$logDbCounts = static function () use ($pdo, $logFile): void {
    $etken = (int) $pdo->query('SELECT COUNT(*) FROM esh_rehber_etken')->fetchColumn();
    $ilac = (int) $pdo->query('SELECT COUNT(*) FROM esh_rehber_ilac')->fetchColumn();
    $without = (int) $pdo->query(
        'SELECT COUNT(*) FROM esh_rehber_etken e
         WHERE NOT EXISTS (SELECT 1 FROM esh_rehber_ilac i WHERE i.etken_id = e.id LIMIT 1)'
    )->fetchColumn();
    $msg = "DB counts: etken={$etken} ilac={$ilac} without_ilac={$without}";
    outLine($msg);
    logScrape($logFile, $msg);
};

$processEtkenRow = static function (array $etkenRow) use (
    &$errors,
    &$nEtken,
    &$nIlac,
    &$processedEtkenKeys,
    &$checkpointState,
    $persistCheckpoint,
    $limitEtken,
    $limitIlac,
    $pdo,
    $site,
    $parser,
    $logFile,
    $progressEvery,
    $logDbCounts
): bool {
    $eKey = (string) $etkenRow['key'];
    $eAd = (string) $etkenRow['ad'];
    if ($eKey === '') {
        return false;
    }
    if (isset($processedEtkenKeys[$eKey])) {
        return true;
    }
    if ($limitEtken > 0 && $nEtken >= $limitEtken) {
        return true;
    }

    $cachedHtml = isset($etkenRow['html']) && is_string($etkenRow['html']) && $etkenRow['html'] !== ''
        ? $etkenRow['html']
        : null;

    try {
        $existingIlac = countIlacForEtken($pdo, $site, $eKey);

        $pdo->beginTransaction();
        $etkenId = upsertEtken($pdo, $site, $eKey, $eAd);

        if ($cachedHtml !== null) {
            $detail = $parser->fetchIlaclarForEtken($eKey, $cachedHtml);
        } else {
            $detail = $parser->fetchIlaclarForEtken($eKey);
        }
        if (($detail['ad'] ?? '') !== '') {
            upsertEtken($pdo, $site, $eKey, (string) $detail['ad']);
        }

        $ilaclar = $detail['ilaclar'] ?? [];
        if ($limitIlac > 0) {
            $ilaclar = array_slice($ilaclar, 0, $limitIlac);
        }
        foreach ($ilaclar as $il) {
            upsertIlac(
                $pdo,
                $etkenId,
                $site,
                (string) $il['source_key'],
                (string) $il['ad'],
                (string) ($il['firma'] ?? ''),
                (string) ($il['recete_turu'] ?? ''),
                (string) ($il['source_url'] ?? '')
            );
            $nIlac++;
        }
        $pdo->commit();

        $processedEtkenKeys[$eKey] = true;
        $nEtken++;
        $checkpointState['etken_processed'] = $nEtken;
        $persistCheckpoint();
        $msg = "Etken upsert: {$eAd} — ilaç +" . count($ilaclar)
            . ($existingIlac === 0 && count($ilaclar) === 0 ? ' (sayfa boş)' : '');
        outLine($msg);
        logScrape($logFile, $msg);

        if ($progressEvery > 0 && $nEtken % $progressEvery === 0) {
            $logDbCounts();
        }

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = "Etken atlandı [{$eKey}] {$eAd}: " . $e->getMessage();
        $errors[] = $err;
        fwrite(STDERR, $err . PHP_EOL);
        logScrape($logFile, $err);

        return false;
    }
};

if ($parser instanceof IlacRehberParserIlacabak && $incrementalUpsert) {
    $parser->setOnEtkenDiscovered(static function (array $row) use ($processEtkenRow, $limitEtken, &$nEtken): bool {
        if ($limitEtken > 0 && $nEtken >= $limitEtken) {
            return true;
        }

        return $processEtkenRow($row);
    });
}

if ($limitEtken > 0 && $parser instanceof IlacRehberParserIlacabak) {
    $parser->setMaxSeedQueries(6);
}

outLine("Etken listesi keşfediliyor (discovery={$discovery})…");
$etkenList = [];
try {
    $etkenList = $parser->fetchEtkenList();
} catch (Throwable $e) {
    $msg = 'Etken keşfi kesildi (kısmi veri korunur): ' . $e->getMessage();
    outLine($msg);
    logScrape($logFile, $msg);
    $errors[] = $msg;
}

if ($limitEtken > 0) {
    $etkenList = array_slice($etkenList, 0, $limitEtken);
    outLine("(--limit-etken={$limitEtken})");
}

$totalEtken = count($etkenList);
outLine("Etken aday: {$totalEtken}, işlenen: {$nEtken}, ilaç upsert: {$nIlac}");

$backfillZeroIlac = static function () use ($pdo, $site, $processEtkenRow, &$processedEtkenKeys, $limitEtken, &$nEtken): void {
    $st = $pdo->prepare(
        'SELECT e.source_key, e.ad
         FROM esh_rehber_etken e
         WHERE e.source_site = :site
           AND NOT EXISTS (SELECT 1 FROM esh_rehber_ilac i WHERE i.etken_id = e.id)
         ORDER BY e.id'
    );
    $st->execute([':site' => $site]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        return;
    }
    outLine('0 ilaçlı etken backfill: ' . count($rows) . ' aday');
    foreach ($rows as $row) {
        if ($limitEtken > 0 && $nEtken >= $limitEtken) {
            break;
        }
        $key = (string) $row['source_key'];
        if ($key === '' || isset($processedEtkenKeys[$key])) {
            continue;
        }
        $processEtkenRow(['key' => $key, 'ad' => (string) $row['ad']]);
    }
};
$backfillZeroIlac();

if (!$incrementalUpsert) {
    $idx = 0;
    foreach ($etkenList as $etkenRow) {
        $idx++;
        $eKey = (string) $etkenRow['key'];
        $eAd = (string) $etkenRow['ad'];
        if (isset($processedEtkenKeys[$eKey])) {
            continue;
        }

        try {
            $pdo->beginTransaction();
            $etkenId = upsertEtken($pdo, $site, $eKey, $eAd);
            $nEtken++;

            $detail = $parser->fetchIlaclarForEtken($eKey);
            if (($detail['ad'] ?? '') !== '') {
                upsertEtken($pdo, $site, $eKey, (string) $detail['ad']);
            }

            $ilaclar = $detail['ilaclar'] ?? [];
            if ($limitIlac > 0) {
                $ilaclar = array_slice($ilaclar, 0, $limitIlac);
            }
            foreach ($ilaclar as $il) {
                upsertIlac(
                    $pdo,
                    $etkenId,
                    $site,
                    (string) $il['source_key'],
                    (string) $il['ad'],
                    (string) ($il['firma'] ?? ''),
                    (string) ($il['recete_turu'] ?? ''),
                    (string) ($il['source_url'] ?? '')
                );
                $nIlac++;
            }
            $pdo->commit();
            outLine("Etken {$idx}/{$totalEtken}: {$eAd} — ilaç +" . count($ilaclar));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = "Etken atlandı [{$eKey}] {$eAd}: " . $e->getMessage();
            $errors[] = $err;
            fwrite(STDERR, $err . PHP_EOL);
            logScrape($logFile, $err);
        }
    }
}

$errorSummary = $errors === [] ? null : implode("\n", array_slice($errors, 0, 50));
ilacRehberImportFinishJob($root, $importJobId, $nEtken, $nIlac, $errorSummary);

ilacRehberCheckpointClear($root);
outLine("Tamam. Etken iÅŸlenen: {$nEtken}, ilaÃ§ upsert: {$nIlac}, hata: " . count($errors));
logScrape($logFile, "DONE etken={$nEtken} ilac={$nIlac} errors=" . count($errors) . ' checkpoint=cleared');
