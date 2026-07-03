<?php
declare(strict_types=1);

/**
 * dist mirror ve patch birleştirme — paylaşılan fonksiyonlar.
 * Yükleyen: tools/build_dist_mirror.php, tools/build_patch_sql.php
 */

/**
 * @return list<array{file: string, desc: string}>
 */
function distRequiredMigratesForVersion(string $ver): array
{
    $byVersion = [
        '3.1.2' => [
            [
                'file' => 'database/migrate_esh_hasta_ilaclar_create.sql',
                'desc' => 'esh_hasta_ilaclar tablosu (hasta ilaç listesi; HastaIlac / hasta_ilac_rapor modülü)',
            ],
        ],
        '3.2.0' => [
            [
                'file' => 'database/migrate_esh_hasta_ilaclar_create.sql',
                'desc' => 'esh_hasta_ilaclar tablosu (yoksa oluşturur)',
            ],
            [
                'file' => 'database/migrate_esh_hasta_ilaclar_etken_recete.sql',
                'desc' => 'etken_madde + recete_turu sütunları (TİTCK ilaç listesi entegrasyonu)',
            ],
            [
                'file' => 'database/migrate_erapor_patient_perf.sql',
                'desc' => 'e-Rapor TC trim, idx_erapor_basvuru_id, idx_pasif_isim (yavaş liste sorguları)',
            ],
            [
                'file' => 'database/migrate_adres_kapino_coords_perf.sql',
                'desc' => 'esh_adrestablosu has_coords + idx_tip_has_coords (AdresKoordinat sayımları)',
            ],
        ],
        '3.2.1' => [
            [
                'file' => 'database/migrate_adres_kapino_coords_perf.sql',
                'desc' => 'esh_adrestablosu has_coords + idx_tip_has_coords (harita/kapı koordinat sayımları)',
            ],
            [
                'file' => 'database/migrate_erapor_patient_perf.sql',
                'desc' => 'idx_erapor_basvuru_id, idx_pasif_isim (e-Rapor ve hasta liste performansı)',
            ],
            [
                'file' => 'database/migrate_izlemtarihi_dt.sql',
                'desc' => 'esh_izlemler izlemtarihi_dt + idx_izlem_yapildi_tarih_dt (Stats tarih filtreleri)',
            ],
            [
                'file' => 'database/migrate_esh_rehber_ilac_create.sql',
                'desc' => 'esh_rehber_etken, esh_rehber_ilac, esh_rehber_import_log tabloları (CREATE IF NOT EXISTS)',
            ],
            [
                'file' => 'database/migrate_esh_rehber_ilac_seed.sql',
                'desc' => 'ilaç rehberi snapshot verisi (~1800 etken, ~10k ilaç; ON DUPLICATE KEY UPDATE, idempotent)',
            ],
        ],
        '4.0.0' => [
            [
                'file' => 'database/migrate_4.0.0_multi_kurum.sql',
                'desc' => 'esh_kurumlar tablosu + çekirdek tablolara kurum_id (çoklu kurum / tenant altyapısı)',
            ],
        ],
    ];

    return $byVersion[$ver] ?? [];
}

function buildConsolidatedPatchSql(string $ver, string $releaseDate, string $projectRoot): ?string
{
    $required = distRequiredMigratesForVersion($ver);
    if ($required === []) {
        return null;
    }

    $patchFile = 'patch_' . $ver . '.sql';
    $lines = [
        '-- =============================================================================',
        "-- ESH {$ver} — birleşik veritabanı yaması",
        "-- Tarih: {$releaseDate}",
        '-- Üretim: php tools/build_patch_sql.php ' . $ver,
        '--',
        '-- Yedek aldıktan sonra (tek dosya):',
        "--   mysql -u KULLANICI -p VERITABANI < database/{$patchFile}",
        "--   mysql -u KULLANICI -p VERITABANI < {$patchFile}   (dist kökünden)",
        '--',
        '-- İçerik bölümleri (sırayla):',
    ];

    foreach ($required as $m) {
        $lines[] = '--   • ' . basename($m['file']) . ' — ' . $m['desc'];
    }

    $lines[] = '--';
    $lines[] = '-- Idempotent: CREATE IF NOT EXISTS, indeks/sütun yoksa ALTER, seed ON DUPLICATE KEY UPDATE.';
    if ($ver === '3.2.1') {
        $lines[] = '-- Seed bölümü büyük; gerekirse max_allowed_packet artırın.';
        $lines[] = '-- Seed yenileme: php tools/export_rehber_seed_sql.php → php tools/build_patch_sql.php ' . $ver;
    }
    $lines[] = '-- =============================================================================';
    $lines[] = '';
    $lines[] = 'SET NAMES utf8mb4;';
    $lines[] = '';

    foreach ($required as $m) {
        $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $m['file']);
        if (!is_readable($path)) {
            throw new RuntimeException('Migrate okunamadı: ' . $m['file']);
        }

        $body = trim((string) file_get_contents($path));
        $body = preg_replace('/^--[^\n]*\r?\n(?:--[^\n]*\r?\n)*/', '', $body, 1) ?? $body;
        $body = preg_replace('/(?:^|\n)\s*SET NAMES utf8mb4;\s*/i', "\n", $body) ?? $body;
        $body = trim($body);

        $lines[] = '-- -----------------------------------------------------------------------------';
        $lines[] = '-- Bölüm: ' . $m['file'];
        $lines[] = '-- ' . $m['desc'];
        $lines[] = '-- -----------------------------------------------------------------------------';
        $lines[] = '';
        $lines[] = $body;
        $lines[] = '';
    }

    return implode("\n", $lines) . "\n";
}

function buildDistPatchDatabaseSql(string $ver, string $releaseDate): string
{
    $required = distRequiredMigratesForVersion($ver);
    $patchFile = 'patch_' . $ver . '.sql';
    $lines = [
        '-- =============================================================================',
        "-- ESH {$ver} — veritabanı yaması (bilgi)",
        "-- Tarih: {$releaseDate}",
        '-- =============================================================================',
        '--',
    ];

    if ($required !== []) {
        $lines[] = '-- Bu sürümde ZORUNLU — tek dosya (yedek aldıktan sonra):';
        $lines[] = '--';
        $lines[] = '--   database/' . $patchFile;
        $lines[] = '--       → aşağıdaki migrate dosyalarının birleşimi (ALTER + tablo + seed)';
        $lines[] = '--';
        foreach ($required as $m) {
            $lines[] = '--   Kaynak: ' . $m['file'];
            $lines[] = '--       → ' . $m['desc'];
        }
        $lines[] = '--';
        $lines[] = '-- MySQL örnek:';
        $lines[] = '--   mysql -u KULLANICI -p VERITABANI < database/' . $patchFile;
        $lines[] = '-- (phpMyAdmin: İçe aktar / SQL sekmesinde aynı dosyayı çalıştırın.)';
        if ($ver === '3.2.1') {
            $lines[] = '-- Büyük seed içerir; gerekirse max_allowed_packet artırın.';
            $lines[] = '-- Yeniden üretim: php tools/export_rehber_seed_sql.php && php tools/build_patch_sql.php ' . $ver;
        }
        $lines[] = '--';
        $lines[] = '-- Ayrı migrate dosyaları database/ altında durur (parça parça uygulamak için).';
        $lines[] = '--';
        $bilgi = "ESH {$ver} — database/{$patchFile} dosyasını uygulayın.";
    } else {
        $lines[] = '-- Bu sürüm kartında birleşik patch yok; database/archive/ yamalarına bakın.';
        $lines[] = '--';
        $bilgi = "ESH {$ver} — patch SQL bilgi notu; bu sürümde birleşik patch tanımlı değil.";
    }

    $lines[] = '-- Tam şema referansı: database/schemas/schema.sql';
    $lines[] = '-- Sıfır kurulum: schema.sql + süper yönetici Denizli adres senkronu (AdresFetch)';
    $lines[] = '';
    $lines[] = "SELECT '{$bilgi}' AS bilgi;";
    $lines[] = '';

    return implode("\n", $lines);
}

function buildDistReadmeHighlights(string $ver): string
{
    if ($ver === '3.1.2') {
        return <<<'TXT'
Önemli — 3.1.2 özet
-------------------
• Veritabanı: database/migrate_esh_hasta_ilaclar_create.sql (esh_hasta_ilaclar)
• Hasta ilaç / tanı raporu sekmeleri, TİTCK ilaç autocomplete, ilaç listesi yönetimi
• İstatistik ve hasta/izlem listelerinde Excel (.xlsx) dışa aktarım
TXT;
    }

    if ($ver === '3.2.0') {
        return <<<'TXT'
Önemli — 3.2.0 özet
-------------------
• Veritabanı (sırayla): migrate_esh_hasta_ilaclar_create.sql, migrate_esh_hasta_ilaclar_etken_recete.sql
• TİTCK ilaç listesi (yönetici .xlsx), etken madde / reçete türü autocomplete
• Hasta ilaç & tanı raporu modülü; hasta kartı klinik özet
• İstatistik ve liste sayfalarında Excel (.xlsx) dışa aktarım
TXT;
    }

    if ($ver === '3.2.1') {
        return <<<'TXT'
Önemli — 3.2.1 özet
-------------------
• Veritabanı: database/patch_3.2.1.sql (tek dosya — indeksler + rehber tabloları + seed)
• Performans: has_coords, idx_pasif_isim, idx_erapor_basvuru_id, izlemtarihi_dt
• 3.2.0 üzerine dosya yaması
TXT;
    }

    if ($ver === '3.2.2') {
        return <<<'TXT'
Önemli — 3.2.2 özet
-------------------
• Veritabanı yaması yok — yalnızca dosya güncellemesi (3.2.1 üzerine)
• CSRF: CsrfHelper, csrf-guard.js, tüm POST formları ve AJAX
• HTTP güvenlik başlıkları (CSP, HSTS, X-Frame-Options)
• Giriş ve genel hasta TC sorgusu rate limit; GET ile silme engeli
TXT;
    }

    if ($ver === '4.0.0') {
        return <<<'TXT'
Önemli — 4.0.0 özet
-------------------
• Veritabanı: database/patch_4.0.0.sql (esh_kurumlar + kurum_id sütunları)
• Çoklu kurum: TenantContext, kurum bazlı hasta/kullanıcı/izlem izolasyonu
• Süper yönetici: KurumController CRUD, kurum filtresi, kurumsal ayarlar DB
• Kamu TC sorgusu: kurum_kod parametresi zorunlu (kurum bazlı arama)
TXT;
    }

    return <<<'TXT'
Önemli — 3.1.x özet
-------------------
• İstatistik: yapılan/planlı izlem raporları, adres hasta filtresi
• Kullanıcı profili: iş özeti + User&action=stats / statsDetail (yönetici: user_id)
• Pasif hasta bekleyen planlı izlemler (PlannedVisit&action=passivePendingPlans)
• Meridian / tema ve izlem formu düzenleri
TXT;
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
