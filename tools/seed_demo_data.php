<?php
declare(strict_types=1);

/**
 * Temel operasyonel tablolara anlamlı demo veriler yükler (UUID şeması).
 *
 * Kullanım:
 *   php tools/seed_demo_data.php          # zaten yüklüyse atlar
 *   php tools/seed_demo_data.php --force  # demo verileri silip yeniden yükler
 *
 * Demo kullanıcılar (şifre: Demo123):
 *   demo.hemsire  — hemşire
 *   demo.doktor   — doktor
 *   demo.kurum    — kurum yöneticisi
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/config/config.php';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    $baseDir = $root . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Helpers\IdHelper;

const DEMO_MARKER = '[DEMO]';
const DEMO_PASSWORD = 'Demo123';
const DEMO_KURUM = 1;

/** @var list<string> */
const DEMO_TCS = [
    '11111111110', '22222222220', '33333333330', '44444444440',
    '55555555550', '66666666660', '77777777770', '88888888880',
];

/** @var array<string, string> */
const DEMO_USERS = [
    'demo.hemsire' => 'd0000001-0001-4001-8001-000000000001',
    'demo.doktor'  => 'd0000002-0001-4001-8001-000000000002',
    'demo.kurum'   => 'd0000003-0001-4001-8001-000000000003',
];

/** @var list<string> Eski seed (p-prefix) — --force temizliği */
const LEGACY_DEMO_PATIENT_IDS = [
    'p0000001-0001-4001-8001-000000000001',
    'p0000002-0001-4001-8001-000000000002',
    'p0000003-0001-4001-8001-000000000003',
    'p0000004-0001-4001-8001-000000000004',
    'p0000005-0001-4001-8001-000000000005',
    'p0000006-0001-4001-8001-000000000006',
    'p0000007-0001-4001-8001-000000000007',
    'p0000008-0001-4001-8001-000000000008',
];

/** @var list<string> */
const DEMO_PATIENT_IDS = [
    'a0000001-0001-4001-8001-000000000001',
    'a0000002-0001-4001-8001-000000000002',
    'a0000003-0001-4001-8001-000000000003',
    'a0000004-0001-4001-8001-000000000004',
    'a0000005-0001-4001-8001-000000000005',
    'a0000006-0001-4001-8001-000000000006',
    'a0000007-0001-4001-8001-000000000007',
    'a0000008-0001-4001-8001-000000000008',
];

$force = in_array('--force', $argv ?? [], true);
$p = DB_PREFIX;
$db = Database::getInstance();
$pdo = $db->getPdo();

function demoTableExists(Database $db, string $table): bool
{
    $row = $db->fetchOnePrepared(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
        [DB_NAME, DB_PREFIX . $table]
    );

    return $row !== null;
}

function demoDelete(Database $db, string $sql, array $params = []): void
{
    $db->executePrepared($sql, $params);
}

function demoInClause(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}

function demoFetchAddressChain(Database $db): ?array
{
    $ilce = $db->fetchOnePrepared(
        'SELECT id, adi FROM ' . DB_PREFIX . 'adrestablosu WHERE tip = ? LIMIT 1',
        ['ilce']
    );
    if (!$ilce) {
        return null;
    }
    $mahalle = $db->fetchOnePrepared(
        'SELECT id, adi FROM ' . DB_PREFIX . 'adrestablosu WHERE tip = ? AND ust_id = ? LIMIT 1',
        ['mahalle', $ilce['id']]
    );
    if (!$mahalle) {
        return ['ilce' => $ilce['id'], 'mahalle' => null, 'sokak' => null, 'kapino' => null];
    }
    $sokak = $db->fetchOnePrepared(
        'SELECT id, adi FROM ' . DB_PREFIX . 'adrestablosu WHERE tip = ? AND ust_id = ? LIMIT 1',
        ['sokak', $mahalle['id']]
    );
    $kapino = null;
    if ($sokak) {
        $kapino = $db->fetchOnePrepared(
            'SELECT id, adi, coords FROM ' . DB_PREFIX . 'adrestablosu WHERE tip = ? AND ust_id = ? LIMIT 1',
            ['kapino', $sokak['id']]
        );
    }

    return [
        'ilce' => $ilce['id'],
        'mahalle' => $mahalle['id'] ?? null,
        'sokak' => $sokak['id'] ?? null,
        'kapino' => $kapino['id'] ?? null,
    ];
}

function demoPatientNotesJson(string $message, string $dateYmd = ''): string
{
    $message = trim($message);
    if ($message !== '' && !str_starts_with($message, DEMO_MARKER)) {
        $message = DEMO_MARKER . ' ' . $message;
    }

    $date = date('d-m-Y H:i');
    if ($dateYmd !== '') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt instanceof DateTimeImmutable) {
            $date = $dt->format('d-m-Y') . ' 09:00';
        }
    }

    return json_encode([[
        'date' => $date,
        'user' => 'Demo',
        'message' => $message,
    ]], JSON_UNESCAPED_UNICODE);
}

function fixDemoPatientNotesJson(Database $db, string $dateYmd): int
{
    $p = DB_PREFIX;
    $patientIds = array_values(array_unique(array_merge(DEMO_PATIENT_IDS, LEGACY_DEMO_PATIENT_IDS)));
    $patIn = demoInClause($patientIds);
    $tcIn = demoInClause(DEMO_TCS);
    $params = array_merge($patientIds, DEMO_TCS);

    $rows = $db->fetchAllPrepared(
        "SELECT id, notes FROM {$p}hastalar WHERE id IN ($patIn) OR tckimlik IN ($tcIn)",
        $params
    );

    $fixed = 0;
    foreach ($rows as $row) {
        $notes = trim((string) ($row['notes'] ?? ''));
        if ($notes === '') {
            continue;
        }
        $decoded = json_decode($notes, true);
        if (is_array($decoded)) {
            continue;
        }

        $message = $notes;
        if (str_starts_with($message, DEMO_MARKER)) {
            $message = trim(substr($message, strlen(DEMO_MARKER)));
        }

        $db->executePrepared(
            "UPDATE {$p}hastalar SET notes = ? WHERE id = ?",
            [demoPatientNotesJson($message, $dateYmd), $row['id']]
        );
        $fixed++;
    }

    return $fixed;
}

function purgeDemoData(Database $db): void
{
    $p = DB_PREFIX;
    $tcs = DEMO_TCS;
    $tcIn = demoInClause($tcs);
    $userIds = array_values(DEMO_USERS);
    $userIn = demoInClause($userIds);
    $patientIds = array_values(array_unique(array_merge(DEMO_PATIENT_IDS, LEGACY_DEMO_PATIENT_IDS)));
    $patIn = demoInClause($patientIds);

    $db->transaction(static function () use ($db, $p, $tcs, $tcIn, $userIds, $userIn, $patientIds, $patIn): void {
        if (demoTableExists($db, 'stok_hareket')) {
            demoDelete($db, "DELETE FROM {$p}stok_hareket WHERE kullanici_id IN ($userIn) OR hasta_id IN ($patIn)", array_merge($userIds, $patientIds));
        }
        if (demoTableExists($db, 'sms_alici')) {
            demoDelete($db, "DELETE FROM {$p}sms_alici WHERE hasta_id IN ($patIn)", $patientIds);
        }
        if (demoTableExists($db, 'sms_gonderim')) {
            demoDelete($db, "DELETE FROM {$p}sms_gonderim WHERE olusturan_id IN ($userIn)", $userIds);
        }
        if (demoTableExists($db, 'mesajlar')) {
            demoDelete($db, "DELETE FROM {$p}mesajlar WHERE konusma_id IN (SELECT id FROM {$p}mesaj_konusmalar WHERE olusturan_id IN ($userIn) OR hasta_id IN ($patIn))", array_merge($userIds, $patientIds));
        }
        if (demoTableExists($db, 'mesaj_konusma_uyeler')) {
            demoDelete($db, "DELETE FROM {$p}mesaj_konusma_uyeler WHERE user_id IN ($userIn) OR konusma_id IN (SELECT id FROM {$p}mesaj_konusmalar WHERE olusturan_id IN ($userIn) OR hasta_id IN ($patIn))", array_merge($userIds, $userIds, $patientIds));
        }
        if (demoTableExists($db, 'mesaj_konusmalar')) {
            demoDelete($db, "DELETE FROM {$p}mesaj_konusmalar WHERE olusturan_id IN ($userIn) OR hasta_id IN ($patIn)", array_merge($userIds, $patientIds));
        }
        foreach (['goruntulu_randevu', 'kons_randevu'] as $tbl) {
            if (demoTableExists($db, $tbl)) {
                demoDelete($db, "DELETE FROM {$p}{$tbl} WHERE hastatckimlik IN ($tcIn)", $tcs);
            }
        }
        foreach (['izlemler', 'pizlemler', 'erapor'] as $tbl) {
            if (demoTableExists($db, $tbl)) {
                demoDelete($db, "DELETE FROM {$p}{$tbl} WHERE hastatckimlik IN ($tcIn)", $tcs);
            }
        }
        foreach (['hasta_braden', 'hasta_itaki', 'hasta_barthel', 'hasta_mna', 'hasta_harizmi', 'hasta_yara_fotolar'] as $tbl) {
            if (demoTableExists($db, $tbl)) {
                demoDelete($db, "DELETE FROM {$p}{$tbl} WHERE hasta_id IN ($patIn)", $patientIds);
            }
        }
        if (demoTableExists($db, 'hasta_ilaclar')) {
            demoDelete($db, "DELETE FROM {$p}hasta_ilaclar WHERE hasta_id IN ($patIn)", $patientIds);
        }
        if (demoTableExists($db, 'hastailacrapor')) {
            demoDelete($db, "DELETE FROM {$p}hastailacrapor WHERE hastatckimlik IN ($tcIn)", $tcs);
        }
        if (demoTableExists($db, 'ekipler')) {
            demoDelete($db, "DELETE FROM {$p}ekipler WHERE kurum_id = ? AND (user_ids LIKE ? OR user_ids LIKE ? OR user_ids LIKE ?)", [DEMO_KURUM, '%' . DEMO_USERS['demo.hemsire'] . '%', '%' . DEMO_USERS['demo.doktor'] . '%', '%' . DEMO_USERS['demo.kurum'] . '%']);
        }
        foreach (['personel_nobet', 'personel_izin', 'personel_istek'] as $tbl) {
            if (demoTableExists($db, $tbl)) {
                demoDelete($db, "DELETE FROM {$p}{$tbl} WHERE personel_id IN ($userIn)", $userIds);
            }
        }
        if (demoTableExists($db, 'stok_mevcut')) {
            demoDelete($db, "DELETE sm FROM {$p}stok_mevcut sm INNER JOIN {$p}stok_malzeme m ON m.id = sm.malzeme_id WHERE m.kod LIKE 'DEMO-%'");
        }
        if (demoTableExists($db, 'stok_malzeme')) {
            demoDelete($db, "DELETE FROM {$p}stok_malzeme WHERE kod LIKE 'DEMO-%'");
        }
        if (demoTableExists($db, 'araclar')) {
            demoDelete($db, "DELETE FROM {$p}araclar WHERE plaka LIKE '34 DEMO%'");
        }
        demoDelete($db, "DELETE FROM {$p}hastalar WHERE id IN ($patIn)", $patientIds);
        demoDelete($db, "DELETE FROM {$p}user_roles WHERE user_id IN ($userIn)", $userIds);
        demoDelete($db, "DELETE FROM {$p}users WHERE id IN ($userIn)", $userIds);
    });

    echo "Mevcut demo veriler temizlendi.\n";
}

$existing = $db->fetchOnePrepared(
    'SELECT id FROM ' . DB_PREFIX . 'users WHERE username = ? LIMIT 1',
    ['demo.hemsire']
);
if ($existing && !$force) {
    $lastMonthForFix = (new DateTimeImmutable('today'))->modify('-30 days')->format('Y-m-d');
    $notesFixed = fixDemoPatientNotesJson($db, $lastMonthForFix);
    if ($notesFixed > 0) {
        echo "Demo hasta notları JSON formatına dönüştürüldü: {$notesFixed} kayıt.\n";
    }
    echo "Demo veriler zaten yüklü (demo.hemsire mevcut). Yeniden yüklemek için: php tools/seed_demo_data.php --force\n";
    exit(0);
}
if ($force) {
    purgeDemoData($db);
}

$addr = demoFetchAddressChain($db);
$today = new DateTimeImmutable('today');
$yesterday = $today->modify('-1 day')->format('Y-m-d');
$lastWeek = $today->modify('-7 days')->format('Y-m-d');
$lastMonth = $today->modify('-30 days')->format('Y-m-d');
$tomorrow = $today->modify('+1 day')->format('Y-m-d');
$nextWeek = $today->modify('+7 days')->format('Y-m-d');
$now = date('Y-m-d H:i:s');
$passHash = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);

$hemsireId = DEMO_USERS['demo.hemsire'];
$doktorId = DEMO_USERS['demo.doktor'];
$kurumAdminId = DEMO_USERS['demo.kurum'];

$patients = [
    ['id' => DEMO_PATIENT_IDS[0], 'tc' => DEMO_TCS[0], 'isim' => 'Ayşe', 'soyisim' => 'Yılmaz', 'cinsiyet' => '2', 'dogum' => '1948-03-12', 'pasif' => '0', 'guvence' => 3, 'icd' => 'E11', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'notes' => 'Tip 2 DM — günlük izlem.'],
    ['id' => DEMO_PATIENT_IDS[1], 'tc' => DEMO_TCS[1], 'isim' => 'Mehmet', 'soyisim' => 'Kaya', 'cinsiyet' => '1', 'dogum' => '1955-07-22', 'pasif' => '0', 'guvence' => 1, 'icd' => 'I10,L89', 'basiyarasi' => 1, 'o2' => 0, 'pansuman' => 1, 'pgunleri' => '1,3,5', 'pzaman' => '0', 'notes' => 'Hipertansiyon + dekubitus — pansuman günleri Paz-Çar-Cum sabah.'],
    ['id' => DEMO_PATIENT_IDS[2], 'tc' => DEMO_TCS[2], 'isim' => 'Fatma', 'soyisim' => 'Demir', 'cinsiyet' => '2', 'dogum' => '1960-11-05', 'pasif' => '-3', 'guvence' => 2, 'icd' => 'J44', 'basiyarasi' => 0, 'o2' => 1, 'pansuman' => 0, 'notes' => 'KOAH + O2 — evde bakım başvurusu bekliyor.'],
    ['id' => DEMO_PATIENT_IDS[3], 'tc' => DEMO_TCS[3], 'isim' => 'Ali', 'soyisim' => 'Çelik', 'cinsiyet' => '1', 'dogum' => '1940-01-18', 'pasif' => '1', 'guvence' => 4, 'icd' => 'N18', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'pasiftarihi' => $lastMonth, 'notes' => 'KBH — hastane yatışı nedeniyle pasif.'],
    ['id' => DEMO_PATIENT_IDS[4], 'tc' => DEMO_TCS[4], 'isim' => 'Zeynep', 'soyisim' => 'Arslan', 'cinsiyet' => '2', 'dogum' => '1952-09-30', 'pasif' => '0', 'guvence' => 3, 'icd' => 'G20', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'notes' => 'Parkinson — düşme riski yüksek.'],
    ['id' => DEMO_PATIENT_IDS[5], 'tc' => DEMO_TCS[5], 'isim' => 'Hasan', 'soyisim' => 'Öztürk', 'cinsiyet' => '1', 'dogum' => '1938-06-14', 'pasif' => '0', 'guvence' => 5, 'icd' => 'F03', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'notes' => 'Demans — bakım veren eşi ile yaşıyor.'],
    ['id' => DEMO_PATIENT_IDS[6], 'tc' => DEMO_TCS[6], 'isim' => 'Emine', 'soyisim' => 'Şahin', 'cinsiyet' => '2', 'dogum' => '1945-12-08', 'pasif' => '0', 'guvence' => 1, 'icd' => 'I63', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'notes' => 'İnme sonrası rehabilitasyon.'],
    ['id' => DEMO_PATIENT_IDS[7], 'tc' => DEMO_TCS[7], 'isim' => 'Mustafa', 'soyisim' => 'Koç', 'cinsiyet' => '1', 'dogum' => '1958-04-25', 'pasif' => '0', 'guvence' => 2, 'icd' => 'E11,I10', 'basiyarasi' => 0, 'o2' => 0, 'pansuman' => 0, 'mama' => 1, 'bez' => 1, 'notes' => 'DM + HT — mama ve bez desteği.'],
];

$counts = [
    'users' => 0,
    'patients' => 0,
    'visits' => 0,
    'plans' => 0,
    'erapor' => 0,
    'assessments' => 0,
    'arac' => 0,
    'ekip' => 0,
    'nobet' => 0,
    'sms' => 0,
    'stok' => 0,
    'mesaj' => 0,
    'randevu' => 0,
];

$db->transaction(function () use (
    $db, $passHash, $now, $addr, $hemsireId, $doktorId, $kurumAdminId,
    $patients, $today, $yesterday, $lastWeek, $lastMonth, $tomorrow, $nextWeek, &$counts
): void {
    $p = DB_PREFIX;

    $userRows = [
        [$hemsireId, 'demo.hemsire', 'Demo Hemşire', null, 'hemsire', 0, 2],
        [$doktorId, 'demo.doktor', 'Demo Doktor', null, 'doktor', 0, 7],
        [$kurumAdminId, 'demo.kurum', 'Demo Kurum Yöneticisi', null, null, 1, 1],
    ];
    foreach ($userRows as [$uid, $username, $name, $tc, $unvan, $isadmin, $roleId]) {
        $db->executePrepared(
            "INSERT INTO {$p}users (id, username, password, name, tckimlikno, email, registerDate, activated, isadmin, kurum_id, unvan)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)",
            [$uid, $username, $passHash, $name, $tc, $username . '@demo.local', $now, $isadmin, DEMO_KURUM, $unvan]
        );
        $db->executePrepared(
            "INSERT INTO {$p}user_roles (user_id, role_id) VALUES (?, ?)",
            [$uid, $roleId]
        );
        $counts['users']++;
    }

    $aracId = null;
    if (demoTableExists($db, 'araclar')) {
        $db->executePrepared(
            "INSERT INTO {$p}araclar (kurum_id, plaka, arac_bilgisi, kapasite) VALUES (?, ?, ?, ?)",
            [DEMO_KURUM, '34 DEMO 001', 'Ford Transit — evde bakım aracı', 6]
        );
        $aracId = (int) $db->getPdo()->lastInsertId();
        $counts['arac'] = 1;
    }

    foreach ($patients as $i => $pt) {
        $db->executePrepared(
            "INSERT INTO {$p}hastalar (
                id, kurum_id, tckimlik, isim, soyisim, dogumtarihi, cinsiyet, kayittarihi,
                ceptel1, bakimveren_ad, bakimveren_tel, bakimveren_yakinlik, guvence, kilo, boy,
                ilce, mahalle, sokak, kapino, pasif, pasiftarihi, basiyarasi, o2bagimli, pansuman,
                pgunleri, pzaman, mama, bez, hastaliklar, randevutarihi, zaman, notes
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?
            )",
            [
                $pt['id'], DEMO_KURUM, $pt['tc'], $pt['isim'], $pt['soyisim'], $pt['dogum'], $pt['cinsiyet'], $lastMonth,
                '5321000' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $pt['isim'] === 'Hasan' ? 'Ayşe Öztürk' : ($pt['cinsiyet'] === '2' ? 'Oğlu Mehmet' : 'Eşi Fatma'),
                '5322000' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'Yakın',
                $pt['guvence'],
                62 + ($i * 3),
                158 + ($i % 4),
                $addr['ilce'] ?? null,
                $addr['mahalle'] ?? null,
                $addr['sokak'] ?? null,
                $addr['kapino'] ?? null,
                $pt['pasif'],
                $pt['pasiftarihi'] ?? null,
                $pt['basiyarasi'],
                $pt['o2'],
                $pt['pansuman'],
                $pt['pgunleri'] ?? null,
                $pt['pzaman'] ?? null,
                $pt['mama'] ?? 0,
                $pt['bez'] ?? 0,
                $pt['icd'],
                $pt['pasif'] === '0' ? $tomorrow : null,
                $i % 3,
                demoPatientNotesJson($pt['notes'], $lastMonth),
            ]
        );
        $counts['patients']++;

        if (demoTableExists($db, 'hastailacrapor') && $pt['pasif'] !== '1') {
            foreach (explode(',', $pt['icd']) as $icd) {
                $db->executePrepared(
                    "INSERT INTO {$p}hastailacrapor (kurum_id, hastatckimlik, hastalikicd, rapor, bitistarihi, brans, raporyeri)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [DEMO_KURUM, $pt['tc'], $icd, $icd === 'E11' ? 1 : 0, $icd === 'E11' ? $nextWeek : null, '4', 0]
                );
            }
        }
        if (demoTableExists($db, 'hasta_ilaclar') && $pt['pasif'] === '0' && str_contains($pt['icd'], 'E11')) {
            $db->executePrepared(
                "INSERT INTO {$p}hasta_ilaclar (id, kurum_id, hasta_id, ilac_adi, etken_madde, recete_turu, hastalikicd, sira)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [IdHelper::generateUuidV4(), DEMO_KURUM, $pt['id'], 'Metformin 850 mg', 'Metformin', 'Yeşil', 'E11', 1]
            );
        }
    }

    $activePatients = array_values(array_filter($patients, static fn(array $pt): bool => $pt['pasif'] === '0'));
    $visitSpecs = [
        [$activePatients[0]['tc'], $lastWeek, '1', 1, '0', 'Rutin evde muayene — glisemi kontrolü.'],
        [$activePatients[0]['tc'], $yesterday, '1,6', 1, '0', 'İzlem + IM enjeksiyon uygulandı.'],
        [$activePatients[1]['tc'], $lastWeek, '8', 1, '0', 'Pansuman değişimi yapıldı.'],
        [$activePatients[1]['tc'], $yesterday, '8', 0, '0', 'Hasta evde yok — tekrar planlandı.', 'Hasta hastanede'],
        [$activePatients[2]['tc'], $yesterday, '1', 1, '1', 'Parkinson ilaç uyumu değerlendirildi.'],
        [$activePatients[3]['tc'], $lastWeek, '1', 1, '2', 'Demans — genel durum stabil.'],
        [$activePatients[4]['tc'], $yesterday, '1,7', 1, '0', 'IV tedavi uygulaması.'],
    ];
    foreach ($visitSpecs as $spec) {
        [$tc, $tarih, $yapilan, $yapildi, $zaman, $aciklama] = $spec;
        $neden = $spec[6] ?? null;
        $db->executePrepared(
            "INSERT INTO {$p}izlemler (id, kurum_id, hastatckimlik, izlemtarihi, izlemtarihi_dt, yapilan, yapildimi, neden, izlemiyapan, zaman, aciklama, arac)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                IdHelper::generateUuidV4(), DEMO_KURUM, $tc, $tarih, $tarih, $yapilan, $yapildi, $neden,
                $hemsireId . ($yapildi && str_contains($yapilan, '1') ? ',' . $doktorId : ''),
                $zaman, $aciklama, $aracId,
            ]
        );
        $counts['visits']++;
    }

    $planSpecs = [
        [$activePatients[0]['tc'], $today->format('Y-m-d'), '1', 0, 0, 'Sabah rutin izlem'],
        [$activePatients[1]['tc'], $today->format('Y-m-d'), '8', 0, 1, 'Pansuman'],
        [DEMO_TCS[2], $tomorrow, '1', 0, 0, 'İlk değerlendirme (bekleyen hasta)'],
        [$activePatients[2]['tc'], $tomorrow, '1', 0, 1, 'Öğleden sonra kontrol'],
        [$activePatients[4]['tc'], $nextWeek, '1,7', 0, 0, 'IV tedavi planı'],
        [$activePatients[0]['tc'], $lastWeek, '1', 1, 0, 'Tamamlanan plan'],
    ];
    foreach ($planSpecs as [$tc, $tarih, $yapilacak, $durum, $zaman, $not]) {
        $db->executePrepared(
            "INSERT INTO {$p}pizlemler (id, kurum_id, hastatckimlik, planlanantarih, yapilacak, zaman, planiyapan, plantarihi, oncelik, aciklama, notlar, durum)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, $tc, $tarih, $yapilacak, $zaman, $hemsireId, $now, 1, $not, DEMO_MARKER, $durum]
        );
        $counts['plans']++;
    }

    if (demoTableExists($db, 'erapor')) {
        $db->executePrepared(
            "INSERT INTO {$p}erapor (id, kurum_id, hastatckimlik, isim, soyisim, ceptel1, basvurutarihi, brans, kayitlimi, yenilendimi, neden)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, DEMO_TCS[2], 'Fatma', 'Demir', '5321000003', $today->format('Y-m-d'), 4, 0, 0, 'Evde bakım başvuru e-raporu']
        );
        $db->executePrepared(
            "INSERT INTO {$p}erapor (id, kurum_id, hastatckimlik, isim, soyisim, ceptel1, basvurutarihi, brans, kayitlimi, yenilendimi, neden)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, DEMO_TCS[7], 'Mustafa', 'Koç', '5321000008', $lastWeek, 4, 1, 1, 'Mama/bez raporu yenileme']
        );
        $counts['erapor'] = 2;
    }

    $bradenHasta = DEMO_PATIENT_IDS[1];
    if (demoTableExists($db, 'hasta_braden')) {
        $db->executePrepared(
            "INSERT INTO {$p}hasta_braden (id, kurum_id, hasta_id, degerlendirme_tarihi, duyusal, nem, aktivite, hareket, beslenme, surtunme, toplam_skor, risk_duzeyi, notlar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, $bradenHasta, $yesterday, 3, 2, 2, 2, 3, 2, 14, 'Orta risk', 'Sakrum bölgesi kızarıklık mevcut.']
        );
        $counts['assessments']++;
    }
    if (demoTableExists($db, 'hasta_barthel')) {
        $db->executePrepared(
            "INSERT INTO {$p}hasta_barthel (id, kurum_id, hasta_id, degerlendirme_tarihi, barbeslenme, barbanyo, barbakim, bargiyinme, barbarsak, barmesane, bartuvalet, bartransfer, barmobilite, barmerdiven, toplam_skor, bagimlilik_duzeyi, notlar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, DEMO_PATIENT_IDS[6], $lastWeek, 5, 0, 0, 5, 5, 5, 5, 3, 5, 0, 33, 'Orta bağımlı', 'İnme sonrası ADL değerlendirmesi.']
        );
        $counts['assessments']++;
    }
    if (demoTableExists($db, 'hasta_itaki')) {
        $db->executePrepared(
            "INSERT INTO {$p}hasta_itaki (id, kurum_id, hasta_id, degerlendirme_tarihi, degerlendirme_gerekcesi, secimler_json, toplam_skor, risk_duzeyi, notlar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, DEMO_PATIENT_IDS[4], $yesterday, 1, '{"1":1,"2":0,"3":1}', 8, 'Düşük', 'Parkinson — düşme riski takibi.']
        );
        $counts['assessments']++;
    }

    if (demoTableExists($db, 'ekipler') && $aracId) {
        $db->executePrepared(
            "INSERT INTO {$p}ekipler (kurum_id, tarih, vardiya, ekip_no, user_ids, arac_id, baslangic_saati, kayit_tarihi)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [DEMO_KURUM, $today->format('Y-m-d'), 'Sabah', 1, $hemsireId . ',' . $doktorId, $aracId, '08:30', $now]
        );
        $counts['ekip'] = 1;
    }

    if (demoTableExists($db, 'personel_nobet')) {
        foreach ([$hemsireId, $doktorId] as $pid) {
            $db->executePrepared(
                "INSERT INTO {$p}personel_nobet (kurum_id, personel_id, nobet_tarihi, nobet_tipi, durum) VALUES (?, ?, ?, ?, 1)",
                [DEMO_KURUM, $pid, $today->format('Y-m-d'), 'normal']
            );
            $counts['nobet']++;
        }
    }
    if (demoTableExists($db, 'personel_izin')) {
        $db->executePrepared(
            "INSERT INTO {$p}personel_izin (kurum_id, personel_id, baslangic_tarihi, bitis_tarihi, sebep) VALUES (?, ?, ?, ?, ?)",
            [DEMO_KURUM, $hemsireId, $nextWeek, $nextWeek, 'Yıllık izin (demo)']
        );
    }

    if (demoTableExists($db, 'stok_malzeme')) {
        $malzemeler = [
            ['DEMO-PANS', 'Steril Gazlı Bez 10x10', 'pansuman', 120],
            ['DEMO-BEZ', 'Hasta Bezi Large', 'bez', 80],
            ['DEMO-MAMA', 'Enteral Mama 200ml', 'mama', 48],
        ];
        foreach ($malzemeler as [$kod, $ad, $kat, $miktar]) {
            $db->executePrepared(
                "INSERT INTO {$p}stok_malzeme (kurum_id, kod, ad, kategori, birim, min_stok, aktif) VALUES (?, ?, ?, ?, 'adet', 10, 1)",
                [DEMO_KURUM, $kod, $ad, $kat]
            );
            $mid = (int) $db->getPdo()->lastInsertId();
            if (demoTableExists($db, 'stok_mevcut')) {
                $db->executePrepared(
                    "INSERT INTO {$p}stok_mevcut (kurum_id, malzeme_id, miktar) VALUES (?, ?, ?)",
                    [DEMO_KURUM, $mid, $miktar]
                );
            }
            if (demoTableExists($db, 'stok_hareket')) {
                $db->executePrepared(
                    "INSERT INTO {$p}stok_hareket (id, kurum_id, malzeme_id, hareket_tipi, miktar, hareket_tarihi, hasta_id, kullanici_id, aciklama)
                     VALUES (?, ?, ?, 'cikis', ?, ?, ?, ?, ?)",
                    [IdHelper::generateUuidV4(), DEMO_KURUM, $mid, min(5, $miktar), $yesterday, DEMO_PATIENT_IDS[1], $hemsireId, DEMO_MARKER . ' Pansuman çıkışı']
                );
            }
        }
        $counts['stok'] = count($malzemeler);
    }

    if (demoTableExists($db, 'sms_gonderim') && demoTableExists($db, 'sms_alici')) {
        $gonderimId = IdHelper::generateUuidV4();
        $db->executePrepared(
            "INSERT INTO {$p}sms_gonderim (id, kurum_id, olusturan_id, segment_tipi, govde_ozet, mesaj_turu, durum, toplam, basarili, basarisiz)
             VALUES (?, ?, ?, 'tek_hasta', ?, 'bilgilendirme', 'tamamlandi', 1, 1, 0)",
            [$gonderimId, DEMO_KURUM, $kurumAdminId, 'Yarınki ziyaret hatırlatması']
        );
        $db->executePrepared(
            "INSERT INTO {$p}sms_alici (id, gonderim_id, hasta_id, rol, telefon_norm, govde, durum, gonderim_at)
             VALUES (?, ?, ?, 'hasta', ?, ?, 'gonderildi', ?)",
            [IdHelper::generateUuidV4(), $gonderimId, DEMO_PATIENT_IDS[0], '5321000001', 'Sayın Ayşe Yılmaz, yarın sabah evde bakım ziyaretiniz planlanmıştır.', $now]
        );
        $counts['sms'] = 1;
    }

    if (demoTableExists($db, 'mesaj_konusmalar') && demoTableExists($db, 'mesaj_konusma_uyeler') && demoTableExists($db, 'mesajlar')) {
        $konusmaId = IdHelper::generateUuidV4();
        $db->executePrepared(
            "INSERT INTO {$p}mesaj_konusmalar (id, tip, kurum_id, hasta_id, baslik, olusturan_id, son_mesaj_at)
             VALUES (?, 'patient', ?, ?, ?, ?, ?)",
            [$konusmaId, DEMO_KURUM, DEMO_PATIENT_IDS[0], 'Ayşe Yılmaz — hemşire iletişim', $hemsireId, $now]
        );
        foreach ([$hemsireId, $doktorId] as $uid) {
            $db->executePrepared(
                "INSERT INTO {$p}mesaj_konusma_uyeler (id, konusma_id, user_id) VALUES (?, ?, ?)",
                [IdHelper::generateUuidV4(), $konusmaId, $uid]
            );
        }
        $mesajId = IdHelper::generateUuidV4();
        $db->executePrepared(
            "INSERT INTO {$p}mesajlar (id, konusma_id, gonderen_id, gonderen_tip, govde) VALUES (?, ?, ?, 'user', ?)",
            [$mesajId, $konusmaId, $hemsireId, 'Glisemi değerleri stabil, bir sonraki ziyarette HbA1c takibi yapılacak.']
        );
        $counts['mesaj'] = 1;
    }

    if (demoTableExists($db, 'kons_randevu')) {
        $db->executePrepared(
            "INSERT INTO {$p}kons_randevu (id, kurum_id, randevu_tarihi, zaman, kons_istekler, brans_id, hastatckimlik, notlar, hasta_geldi, olusturan_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, $tomorrow, 1, '3', 4, DEMO_TCS[7], 'İlaç raporu konsültasyonu', null, $doktorId]
        );
        $counts['randevu']++;
    }
    if (demoTableExists($db, 'goruntulu_randevu')) {
        $db->executePrepared(
            "INSERT INTO {$p}goruntulu_randevu (id, kurum_id, randevu_tarihi, zaman, kons_istekler, brans_id, hastatckimlik, notlar, video_room_id, olusturan_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [IdHelper::generateUuidV4(), DEMO_KURUM, $nextWeek, 0, '1', 4, DEMO_TCS[5], 'Tele-sağlık görüşmesi', 'esh-demo-' . substr(DEMO_PATIENT_IDS[5], 0, 8), $doktorId]
        );
        $counts['randevu']++;
    }
});

echo "Demo veriler yüklendi.\n\n";
echo "Kullanıcılar (şifre: " . DEMO_PASSWORD . "):\n";
echo "  demo.hemsire — hemşire\n";
echo "  demo.doktor  — doktor\n";
echo "  demo.kurum   — kurum yöneticisi\n\n";
echo "Özet:\n";
foreach ($counts as $key => $n) {
    if ($n > 0) {
        echo "  {$key}: {$n}\n";
    }
}
echo "\n8 demo hasta (TC: " . implode(', ', array_slice(DEMO_TCS, 0, 3)) . ", …)\n";
echo "Pasif durumlar: 6 aktif, 1 bekleyen (Fatma Demir), 1 pasif (Ali Çelik)\n";
