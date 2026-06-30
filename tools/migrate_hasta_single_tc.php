<?php
declare(strict_types=1);

/**
 * Tek TC / tek hasta satırı göçü (CLI).
 *
 *   php tools/migrate_hasta_single_tc.php --dry-run
 *   php tools/migrate_hasta_single_tc.php --apply
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$apply = in_array('--apply', $argv, true);
$dryRun = !$apply || in_array('--dry-run', $argv, true);

require_once dirname(__DIR__) . '/config/config.php';

spl_autoload_register(static function ($class) {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Models\HastaNakil;

$db = Database::getInstance();
$mode = $dryRun && !$apply ? 'DRY-RUN' : ($apply ? 'APPLY' : 'DRY-RUN');
echo "=== migrate_hasta_single_tc [{$mode}] ===\n\n";

/** @return list<array<string, mixed>> */
function duplicateTcGroups(Database $db): array
{
    return $db->fetchAllPrepared(
        "SELECT tckimlik, COUNT(*) AS cnt
         FROM #__hastalar
         WHERE TRIM(COALESCE(tckimlik, '')) <> ''
         GROUP BY tckimlik
         HAVING COUNT(*) > 1
         ORDER BY cnt DESC, tckimlik ASC"
    );
}

/**
 * @param list<array<string, mixed>> $rows
 */
function pickCanonicalId(Database $db, array $rows): int
{
    $ids = array_map(static fn ($r) => (int) ($r['id'] ?? 0), $rows);
    $ids = array_values(array_filter($ids, static fn ($id) => $id > 0));
    if ($ids === []) {
        return 0;
    }

    foreach ($rows as $r) {
        if ((string) ($r['pasif'] ?? '') === '0') {
            return (int) $r['id'];
        }
    }

    if (HastaNakil::tableExists()) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $log = $db->fetchOnePrepared(
            "SELECT hedef_hasta_id, kaynak_hasta_id, hedef_kurum_id
             FROM #__hasta_nakil
             WHERE durum = ? AND tip = ?
               AND (hedef_hasta_id IN ({$placeholders}) OR kaynak_hasta_id IN ({$placeholders}))
             ORDER BY id DESC LIMIT 1",
            array_merge(
                [HastaNakil::DURUM_ONAYLANDI, HastaNakil::TIP_KURUM_ICI],
                $ids,
                $ids
            )
        );
        if ($log) {
            $hid = (int) ($log['hedef_hasta_id'] ?? 0);
            if ($hid > 0 && in_array($hid, $ids, true)) {
                return $hid;
            }
            $hid = (int) ($log['kaynak_hasta_id'] ?? 0);
            if ($hid > 0 && in_array($hid, $ids, true)) {
                return $hid;
            }
        }
    }

    foreach ($rows as $r) {
        if ((string) ($r['pasif'] ?? '') === '-3') {
            return (int) $r['id'];
        }
    }

    return max($ids);
}

function syncCanonicalFromLatestLog(Database $db, int $canonicalId, bool $apply): void
{
    if (!HastaNakil::tableExists() || $canonicalId <= 0) {
        return;
    }
    $log = $db->fetchOnePrepared(
        'SELECT hedef_kurum_id, durum, tip FROM #__hasta_nakil
         WHERE (kaynak_hasta_id = ? OR hedef_hasta_id = ?) AND durum = ?
         ORDER BY id DESC LIMIT 1',
        [$canonicalId, $canonicalId, HastaNakil::DURUM_ONAYLANDI]
    );
    if (!$log || (string) ($log['tip'] ?? '') !== HastaNakil::TIP_KURUM_ICI) {
        return;
    }
    $hedefKid = (int) ($log['hedef_kurum_id'] ?? 0);
    if ($hedefKid <= 0) {
        return;
    }
    echo "  sync canonical #{$canonicalId} -> kurum_id={$hedefKid}, pasif=-3\n";
    if ($apply) {
        $db->executePrepared(
            'UPDATE #__hastalar SET kurum_id = ?, pasif = ?, pasifnedeni = NULL, pasiftarihi = NULL WHERE id = ?',
            [$hedefKid, '-3', $canonicalId]
        );
        $db->executePrepared(
            'UPDATE #__hasta_nakil SET kaynak_hasta_id = ?, hedef_hasta_id = ? WHERE kaynak_hasta_id = ? OR hedef_hasta_id = ?',
            [$canonicalId, $canonicalId, $canonicalId, $canonicalId]
        );
    }
}

$groups = duplicateTcGroups($db);
echo 'Duplicate TC groups: ' . count($groups) . "\n\n";

$merged = 0;
$deleted = 0;

foreach ($groups as $g) {
    $tc = (string) ($g['tckimlik'] ?? '');
    $rows = $db->fetchAllPrepared(
        'SELECT id, kurum_id, pasif, pasifnedeni, isim, soyisim FROM #__hastalar WHERE tckimlik = ? ORDER BY id ASC',
        [$tc]
    );
    if (count($rows) < 2) {
        continue;
    }

    $canonicalId = pickCanonicalId($db, $rows);
    if ($canonicalId <= 0) {
        echo "SKIP TC {$tc}: canonical seçilemedi\n";
        continue;
    }

    echo "TC {$tc}: canonical #{$canonicalId}, duplicates: ";
    $dupIds = [];
    foreach ($rows as $r) {
        $rid = (int) ($r['id'] ?? 0);
        if ($rid > 0 && $rid !== $canonicalId) {
            $dupIds[] = $rid;
            echo "#{$rid} ";
        }
    }
    echo "\n";
    $merged++;

    foreach ($dupIds as $dupId) {
        if ($apply) {
            $db->executePrepared('UPDATE #__hasta_ilaclar SET hasta_id = ? WHERE hasta_id = ?', [$canonicalId, $dupId]);
            $db->executePrepared('UPDATE #__hasta_yara_fotolar SET hasta_id = ? WHERE hasta_id = ?', [$canonicalId, $dupId]);
            $hasCanonThread = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__mesaj_konusmalar WHERE hasta_id = ?',
                [$canonicalId]
            ) > 0;
            if ($hasCanonThread) {
                $db->executePrepared('DELETE FROM #__mesaj_konusmalar WHERE hasta_id = ?', [$dupId]);
            } else {
                $db->executePrepared('UPDATE #__mesaj_konusmalar SET hasta_id = ? WHERE hasta_id = ?', [$canonicalId, $dupId]);
            }
            if (HastaNakil::tableExists()) {
                $db->executePrepared(
                    'UPDATE #__hasta_nakil SET kaynak_hasta_id = ? WHERE kaynak_hasta_id = ?',
                    [$canonicalId, $dupId]
                );
                $db->executePrepared(
                    'UPDATE #__hasta_nakil SET hedef_hasta_id = ? WHERE hedef_hasta_id = ?',
                    [$canonicalId, $dupId]
                );
            }
            $db->executePrepared('DELETE FROM #__hastalar WHERE id = ?', [$dupId]);
        }
        $deleted++;
    }

    if (HastaNakil::tableExists() && $apply) {
        $db->executePrepared(
            'UPDATE #__hasta_nakil SET kaynak_hasta_id = ?, hedef_hasta_id = COALESCE(hedef_hasta_id, ?) WHERE kaynak_hasta_id = ?',
            [$canonicalId, $canonicalId, $canonicalId]
        );
    }

    syncCanonicalFromLatestLog($db, $canonicalId, $apply);
}

echo "\nMerged groups: {$merged}, rows to delete: {$deleted}\n";

if (HastaNakil::tableExists() && $apply) {
    $db->executePrepared(
        'UPDATE #__hasta_nakil SET hedef_hasta_id = kaynak_hasta_id
         WHERE durum = ? AND tip = ? AND hedef_hasta_id IS NOT NULL AND hedef_hasta_id <> kaynak_hasta_id',
        [HastaNakil::DURUM_ONAYLANDI, HastaNakil::TIP_KURUM_ICI]
    );
}

if (!$apply) {
    echo "\nDry-run only. Re-run with --apply to execute.\n";
} else {
    $tbl = $db->replacePrefix('#__hastalar');
    $indexes = $db->fetchAllPrepared("SHOW INDEX FROM `{$tbl}` WHERE Key_name LIKE '%tckimlik%'");
    $names = array_unique(array_map(static fn ($r) => (string) ($r['Key_name'] ?? ''), $indexes));
    if (in_array('uk_kurum_tckimlik', $names, true)) {
        echo "Dropping uk_kurum_tckimlik...\n";
        $db->getPdo()->exec("ALTER TABLE `{$tbl}` DROP INDEX `uk_kurum_tckimlik`");
    }
    if (!in_array('uk_tckimlik', $names, true)) {
        echo "Adding uk_tckimlik...\n";
        $db->getPdo()->exec("ALTER TABLE `{$tbl}` ADD UNIQUE KEY `uk_tckimlik` (`tckimlik`)");
    }
}

echo "Done.\n";
