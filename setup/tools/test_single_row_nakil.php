<?php
declare(strict_types=1);

/**
 * Tek satır (global TC unique) nakil modeli — CLI entegrasyon testleri.
 *
 *   php tools/test_single_row_nakil.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

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
use App\Helpers\PatientKurumTransfer;
use App\Helpers\PatientNakilRequest;
use App\Helpers\ValidationHelper;
use App\Models\HastaNakil;
use App\Models\Patient;

$db = Database::getInstance();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = (int) ($db->loadResultPrepared('SELECT id FROM #__users ORDER BY id ASC LIMIT 1') ?: 1);
$_SESSION['isadmin_level'] = 2;
$_SESSION['isadmin'] = 2;

$passed = 0;
$failed = 0;

function test_assert(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  OK  {$label}\n";
        $passed++;
    } else {
        echo "  FAIL {$label}\n";
        $failed++;
    }
}

function makeValidTcFromSeed(int $seed): string
{
    $digits = [];
    $digits[0] = 1 + ($seed % 8);
    for ($i = 1; $i < 9; $i++) {
        $digits[$i] = ($seed + $i * 7) % 10;
    }
    $d10 = (($digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8]) * 7
        - ($digits[1] + $digits[3] + $digits[5] + $digits[7])) % 10;
    if ($d10 < 0) {
        $d10 += 10;
    }
    $digits[9] = $d10;
    $digits[10] = array_sum(array_slice($digits, 0, 10)) % 10;

    return implode('', $digits);
}

function uniqueValidTc(Database $db): string
{
    for ($i = 0; $i < 200; $i++) {
        $tc = makeValidTcFromSeed(random_int(1000, 999999999));
        if (!ValidationHelper::isTc($tc)) {
            continue;
        }
        $exists = $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hastalar WHERE tckimlik = ?',
            [$tc]
        );
        if ((int) $exists === 0) {
            return $tc;
        }
    }

    throw new RuntimeException('Benzersiz geçerli TC üretilemedi.');
}

function countRowsForTc(Database $db, string $tc): int
{
    return (int) $db->loadResultPrepared(
        'SELECT COUNT(*) FROM #__hastalar WHERE tckimlik = ?',
        [$tc]
    );
}

echo "=== test_single_row_nakil ===\n\n";

if (!HastaNakil::tableExists()) {
    fwrite(STDERR, "esh_hasta_nakil tablosu yok — test atlanıyor.\n");
    exit(1);
}

$kurumlar = $db->fetchAllPrepared(
    'SELECT id FROM #__kurumlar WHERE aktif = 1 ORDER BY id ASC LIMIT 2'
);
$tempKurumB = false;
$kurumA = 0;
$kurumB = 0;
if (count($kurumlar) >= 2) {
    $kurumA = (int) ($kurumlar[0]['id'] ?? 0);
    $kurumB = (int) ($kurumlar[1]['id'] ?? 0);
} elseif (count($kurumlar) === 1) {
    $kurumA = (int) ($kurumlar[0]['id'] ?? 0);
    $kod = 'test_nakil_' . bin2hex(random_bytes(4));
    $db->executePrepared(
        'INSERT INTO #__kurumlar (ad, kod, aktif) VALUES (?, ?, 1)',
        ['Test Nakil Kurum B', $kod]
    );
    $kurumB = (int) $db->insertid();
    $tempKurumB = $kurumB > 0;
}
if ($kurumA < 1 || $kurumB < 1 || $kurumA === $kurumB) {
    fwrite(STDERR, "Test için iki farklı kurum gerekli.\n");
    exit(1);
}
$userId = (int) $db->loadResultPrepared('SELECT id FROM #__users ORDER BY id ASC LIMIT 1');
if ($userId < 1) {
    $userId = 1;
}

$tc = uniqueValidTc($db);
$hastaId = 0;
$nakilId = 0;
$geriNakilId = 0;
$izlemId = 0;

try {
    echo "Kurum A={$kurumA}, B={$kurumB}, TC={$tc}\n\n";

    $patient = new Patient();
    $patient->bind([
        'kurum_id' => $kurumA,
        'tckimlik' => $tc,
        'isim' => 'Test',
        'soyisim' => 'Nakil',
        'pasif' => '0',
        'kayittarihi' => date('Y-m-d'),
    ], true);
    test_assert($patient->store(), 'Aktif hasta oluşturuldu');
    $hastaId = (int) ($patient->id ?? 0);
    test_assert($hastaId > 0, 'Hasta id atandı');
    test_assert(countRowsForTc($db, $tc) === 1, 'Tek TC satırı');

    $db->executePrepared(
        'INSERT INTO #__izlemler (hastatckimlik, kurum_id, izlemtarihi, yapildimi) VALUES (?, ?, ?, ?)',
        [$tc, $kurumA, date('Y-m-d'), 1]
    );
    $izlemId = (int) $db->insertid();

    $db->updatePrepared(
        '#__hastalar',
        [
            'pasif' => '1',
            'pasifnedeni' => (string) PatientKurumTransfer::PASIF_NEDENI_NAKIL,
            'pasiftarihi' => date('Y-m-d'),
        ],
        'id = ?',
        [$hastaId]
    );
    $patient->load($hastaId);
    test_assert((string) ($patient->pasif ?? '') === '1', 'Nakil başlatma: hasta pasif işaretlendi');

    $nakilLogId = PatientNakilRequest::createFromPassiveSave($patient, (string) $kurumB, $userId);
    test_assert($nakilLogId !== false, 'Nakil talebi oluşturuldu');
    $nakilId = is_int($nakilLogId) ? $nakilLogId : 0;

    $patient->load($hastaId);
    test_assert((int) ($patient->kurum_id ?? 0) === $kurumA, 'Talep sonrası kurum_id kaynakta kaldı');
    test_assert((string) ($patient->pasif ?? '') === '1', 'Talep sonrası hasta pasif');
    test_assert(PatientNakilRequest::hasPending($hastaId), 'Bekleyen nakil logu var');
    test_assert(countRowsForTc($db, $tc) === 1, 'Talep sonrası hâlâ tek satır');

    $approvedId = PatientNakilRequest::approve($nakilId, $userId);
    test_assert($approvedId === $hastaId, 'Onay aynı hasta id döndürdü');

    $patient->load($hastaId);
    test_assert((int) ($patient->kurum_id ?? 0) === $kurumB, 'Onay sonrası kurum_id hedefe taşındı');
    test_assert((string) ($patient->pasif ?? '') === '-3', 'Onay sonrası hasta bekleyen (pasif=-3)');
    test_assert(countRowsForTc($db, $tc) === 1, 'Onay sonrası klon satırı yok');

    $nakilRow = $db->fetchOnePrepared('SELECT * FROM #__hasta_nakil WHERE id = ?', [$nakilId]);
    test_assert(
        (int) ($nakilRow['hedef_hasta_id'] ?? 0) === $hastaId
        && (int) ($nakilRow['kaynak_hasta_id'] ?? 0) === $hastaId,
        'Onaylı log hedef_hasta_id = kaynak_hasta_id'
    );

    $izlemKid = (int) $db->loadResultPrepared(
        'SELECT kurum_id FROM #__izlemler WHERE id = ?',
        [$izlemId]
    );
    test_assert($izlemKid === $kurumA, 'Eski izlem kaynak kurum_id ile kaldı');

    $db->updatePrepared(
        '#__hastalar',
        [
            'pasif' => '1',
            'pasifnedeni' => (string) PatientKurumTransfer::PASIF_NEDENI_NAKIL,
            'pasiftarihi' => date('Y-m-d'),
        ],
        'id = ?',
        [$hastaId]
    );
    $patient->load($hastaId);
    test_assert((string) ($patient->pasif ?? '') === '1', 'Geri nakil: pasif işaretlendi');

    $geriLogId = PatientNakilRequest::createFromPassiveSave($patient, (string) $kurumA, $userId);
    test_assert($geriLogId !== false, 'Geri nakil talebi oluşturuldu');
    $geriNakilId = is_int($geriLogId) ? $geriLogId : 0;

    $geriApproved = PatientNakilRequest::approve($geriNakilId, $userId);
    test_assert($geriApproved === $hastaId, 'Geri nakil onaylandı');

    $patient->load($hastaId);
    test_assert((int) ($patient->kurum_id ?? 0) === $kurumA, 'Geri nakil sonrası kurum_id kaynağa döndü');
    test_assert((string) ($patient->pasif ?? '') === '-3', 'Geri nakil sonrası bekleyen (pasif=-3)');
    test_assert(countRowsForTc($db, $tc) === 1, 'Geri nakil sonrası tek satır');

    test_assert(
        PatientKurumTransfer::movePatientToKurum($hastaId, $kurumB),
        'Süper admin taşıma: movePatientToKurum'
    );
    PatientNakilRequest::logInstantApprovedTransfer($hastaId, $kurumA, $kurumB, $hastaId, $userId);
    $patient->load($hastaId);
    test_assert((int) ($patient->kurum_id ?? 0) === $kurumB, 'Anlık log sonrası kurum_id güncellendi');
    test_assert(countRowsForTc($db, $tc) === 1, 'Anlık taşıma sonrası tek satır');

    $dup = new Patient();
    $dup->bind([
        'kurum_id' => $kurumA,
        'tckimlik' => $tc,
        'isim' => 'Dup',
        'soyisim' => 'Test',
        'pasif' => '0',
    ], true);
    test_assert(!$dup->store(), 'Aynı TC ile ikinci kayıt engellendi');
} finally {
    if ($geriNakilId > 0) {
        $db->executePrepared('DELETE FROM #__hasta_nakil WHERE id = ?', [$geriNakilId]);
    }
    if ($nakilId > 0) {
        $db->executePrepared('DELETE FROM #__hasta_nakil WHERE kaynak_hasta_id = ? OR hedef_hasta_id = ?', [$hastaId, $hastaId]);
    }
    if ($izlemId > 0) {
        $db->executePrepared('DELETE FROM #__izlemler WHERE id = ?', [$izlemId]);
    }
    if ($hastaId > 0) {
        $db->executePrepared('DELETE FROM #__hastalar WHERE id = ?', [$hastaId]);
    }
    if (!empty($tempKurumB) && $kurumB > 0) {
        $db->executePrepared('DELETE FROM #__kurumlar WHERE id = ?', [$kurumB]);
    }
}

echo "\nSonuç: {$passed} geçti, {$failed} başarısız\n";
exit($failed > 0 ? 1 : 0);
