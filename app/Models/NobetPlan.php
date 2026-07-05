<?php
namespace App\Models;

use App\Helpers\IdHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;

class NobetPlan extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__personel_nobet', 'id');
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $sql = [];
        $sql[] = "CREATE TABLE IF NOT EXISTS #__personel_izin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            personel_id CHAR(36) NOT NULL,
            baslangic_tarihi DATE NOT NULL,
            bitis_tarihi DATE NOT NULL,
            sebep VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_izin_personel (personel_id),
            KEY idx_izin_tarih (baslangic_tarihi, bitis_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS #__personel_istek (
            id INT AUTO_INCREMENT PRIMARY KEY,
            personel_id CHAR(36) NOT NULL,
            baslangic_tarihi DATE NOT NULL,
            bitis_tarihi DATE NOT NULL,
            aciklama VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_istek_personel (personel_id),
            KEY idx_istek_tarih (baslangic_tarihi, bitis_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS #__resmi_tatiller (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aciklama VARCHAR(255) NOT NULL,
            baslangic_tarihi DATE NOT NULL,
            bitis_tarihi DATE NOT NULL,
            tatil_tipi VARCHAR(50) NOT NULL DEFAULT 'resmi_tatil',
            KEY idx_tatil_tarih (baslangic_tarihi, bitis_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS #__personel_nobet (
            id INT AUTO_INCREMENT PRIMARY KEY,
            personel_id CHAR(36) NOT NULL,
            nobet_tarihi DATE NOT NULL,
            nobet_tipi VARCHAR(50) NOT NULL DEFAULT 'normal',
            durum TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY tekil_nobet (personel_id, nobet_tarihi),
            KEY idx_nobet_tarih (nobet_tarihi),
            KEY idx_nobet_personel (personel_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";

        foreach ($sql as $q) {
            $this->db->execLogged($q);
        }
    }

    public function getNobetCalendar(int $ay, int $yil): array
    {
        $rows = $this->db->fetchObjectListPrepared(
            "SELECT n.*, u.name, u.unvan
             FROM #__personel_nobet n
             LEFT JOIN #__users u ON u.id = n.personel_id
             WHERE MONTH(n.nobet_tarihi) = " . (int) $ay . "
               AND YEAR(n.nobet_tarihi) = " . (int) $yil . "
               AND n.durum = 1" . TenantSqlHelper::andEquals('n') . "
             ORDER BY n.nobet_tarihi ASC, u.unvan ASC, u.name ASC"
        );

        $out = [];
        foreach ($rows as $r) {
            $k = (string) $r->nobet_tarihi;
            if (!isset($out[$k])) {
                $out[$k] = [];
            }
            $out[$k][] = $r;
        }
        return $out;
    }

    public function getPersonnelForNobet(): array
    {
        [$inSql, $inParams] = $this->db->whereInClause(OperationalSettings::nobetAllowedUnvanlar());

        return $this->db->fetchObjectListPrepared(
            "SELECT id, name, unvan
             FROM #__users
             WHERE activated = 1 AND unvan IN ($inSql)"
             . TenantSqlHelper::andBare('kurum_id') . "
             ORDER BY unvan, name",
            $inParams
        );
    }

    public function getIzinList(bool $activeOnly = true): array
    {
        $where = $activeOnly ? ['i.bitis_tarihi >= CURDATE()'] : [];
        [$unvanSql, $unvanParams] = $this->nobetUnvanInClause('u');
        $where[] = $unvanSql;
        TenantSqlHelper::mergeParts($where, 'i', 'kurum_id');
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        return $this->db->fetchObjectListPrepared(
            "SELECT i.*, u.name AS personel_ad, u.unvan
             FROM #__personel_izin i
             LEFT JOIN #__users u ON u.id = i.personel_id
             $whereSql
             ORDER BY i.baslangic_tarihi DESC
             LIMIT 100",
            $unvanParams
        );
    }

    public function getIstekList(): array
    {
        $where = [];
        [$unvanSql, $unvanParams] = $this->nobetUnvanInClause('u');
        $where[] = $unvanSql;
        TenantSqlHelper::mergeParts($where, 'i', 'kurum_id');
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        return $this->db->fetchObjectListPrepared(
            "SELECT i.*, u.name AS personel_ad, u.unvan
             FROM #__personel_istek i
             LEFT JOIN #__users u ON u.id = i.personel_id
             $whereSql
             ORDER BY i.baslangic_tarihi DESC
             LIMIT 100",
            $unvanParams
        );
    }

    public function getTatilList(): array
    {
        return $this->db->fetchObjectListPrepared(
            "SELECT *
             FROM #__resmi_tatiller
             ORDER BY baslangic_tarihi DESC
             LIMIT 100"
        );
    }

    public function saveIzin(int|string $personelId, string $bas, string $bit, string $sebep): bool
    {
        $pid = IdHelper::normalizeRequestId($personelId);
        if ($pid === null) {
            return false;
        }
        $kid = TenantContext::assignKurumIdForStore();
        return $this->db->executePrepared(
            'INSERT INTO #__personel_izin (personel_id, baslangic_tarihi, bitis_tarihi, sebep, kurum_id)
             VALUES (?, ?, ?, ?, ?)',
            [$pid, $bas, $bit, $sebep, (int) $kid]
        );
    }

    public function saveIstek(int|string $personelId, string $bas, string $bit, string $aciklama): bool
    {
        $pid = IdHelper::normalizeRequestId($personelId);
        if ($pid === null) {
            return false;
        }
        $kid = TenantContext::assignKurumIdForStore();
        return $this->db->executePrepared(
            'INSERT INTO #__personel_istek (personel_id, baslangic_tarihi, bitis_tarihi, aciklama, kurum_id)
             VALUES (?, ?, ?, ?, ?)',
            [$pid, $bas, $bit, $aciklama, (int) $kid]
        );
    }

    public function saveTatil(string $aciklama, string $bas, string $bit, string $tip): bool
    {
        return $this->db->executePrepared(
            'INSERT INTO #__resmi_tatiller (aciklama, baslangic_tarihi, bitis_tarihi, tatil_tipi)
             VALUES (?, ?, ?, ?)',
            [$aciklama, $bas, $bit, $tip]
        );
    }

    public function deleteIzin(int $id): bool
    {
        return $this->db->executePrepared(
            'DELETE FROM #__personel_izin WHERE id = ?' . TenantSqlHelper::andBare(),
            [(int) $id]
        );
    }

    public function deleteIstek(int $id): bool
    {
        return $this->db->executePrepared(
            'DELETE FROM #__personel_istek WHERE id = ?' . TenantSqlHelper::andBare(),
            [(int) $id]
        );
    }

    public function deleteTatil(int $id): bool
    {
        return $this->db->executePrepared('DELETE FROM #__resmi_tatiller WHERE id = ?', [(int) $id]);
    }

    public function getPersonelOwnIzin(int|string $uid): array
    {
        $pid = IdHelper::normalizeRequestId($uid);
        if ($pid === null) {
            return [];
        }
        return $this->db->fetchObjectListPrepared(
            "SELECT * FROM #__personel_izin
             WHERE personel_id = ?" . TenantSqlHelper::andBare() . "
             ORDER BY baslangic_tarihi DESC",
            [$pid]
        );
    }

    public function getPersonelOwnIstek(int|string $uid): array
    {
        $pid = IdHelper::normalizeRequestId($uid);
        if ($pid === null) {
            return [];
        }
        return $this->db->fetchObjectListPrepared(
            "SELECT * FROM #__personel_istek
             WHERE personel_id = ?" . TenantSqlHelper::andBare() . "
             ORDER BY baslangic_tarihi DESC",
            [$pid]
        );
    }

    public function getTatilGunleri(int $ay, int $yil): array
    {
        $rows = $this->db->fetchObjectListPrepared(
            "SELECT baslangic_tarihi, bitis_tarihi
             FROM #__resmi_tatiller
             WHERE (MONTH(baslangic_tarihi) = " . (int) $ay . " AND YEAR(baslangic_tarihi) = " . (int) $yil . ")
                OR (MONTH(bitis_tarihi) = " . (int) $ay . " AND YEAR(bitis_tarihi) = " . (int) $yil . ")"
        );

        $tatilGunleri = [];
        foreach ($rows as $r) {
            $begin = new \DateTime((string) $r->baslangic_tarihi);
            $end = new \DateTime((string) $r->bitis_tarihi);
            $end->modify('+1 day');
            $period = new \DatePeriod($begin, new \DateInterval('P1D'), $end);
            foreach ($period as $d) {
                if ((int) $d->format('n') === $ay && (int) $d->format('Y') === $yil) {
                    $tatilGunleri[$d->format('Y-m-d')] = true;
                }
            }
        }
        return array_keys($tatilGunleri);
    }

    public function rebuildMonthNobet(int $ay, int $yil): int
    {
        $this->db->executePrepared(
            'DELETE FROM #__personel_nobet WHERE MONTH(nobet_tarihi) = ? AND YEAR(nobet_tarihi) = ?'
            . TenantSqlHelper::andBare(),
            [(int) $ay, (int) $yil]
        );
        $tatiller = $this->getTatilGunleri($ay, $yil);
        $gunSayisi = cal_days_in_month(CAL_GREGORIAN, $ay, $yil);
        $inserted = 0;

        for ($gun = 1; $gun <= $gunSayisi; $gun++) {
            $tarih = sprintf('%04d-%02d-%02d', $yil, $ay, $gun);
            $gunNo = (int) date('N', strtotime($tarih));
            $tip = (in_array($tarih, $tatiller, true) || $gunNo >= 6) ? 'haftasonu' : 'normal';

            foreach (OperationalSettings::nobetAllowedUnvanlar() as $unvan) {
                $adet = OperationalSettings::nobetRebuildSlotsForUnvan($unvan);
                $secilen = $this->selectNobetci($tarih, $unvan, $adet, $ay);
                foreach ($secilen as $person) {
                    if ($this->insertNobet((string) $person->id, $tarih, $tip)) {
                        $inserted++;
                    }
                }
            }
        }
        return $inserted;
    }

    public function getPersonnelNobetCountByMonth(int $ay, int $yil): array
    {
        $rows = $this->db->fetchObjectListPrepared(
            "SELECT personel_id, COUNT(*) AS adet
             FROM #__personel_nobet
             WHERE MONTH(nobet_tarihi) = " . (int) $ay . "
               AND YEAR(nobet_tarihi) = " . (int) $yil . "
               AND durum = 1" . TenantSqlHelper::andBare() . "
             GROUP BY personel_id"
        );

        $out = [];
        foreach ($rows as $r) {
            $pid = IdHelper::normalizeRequestId($r->personel_id ?? null);
            if ($pid !== null) {
                $out[$pid] = (int) $r->adet;
            }
        }
        return $out;
    }

    public function addNobet(int|string $personelId, string $tarih): array
    {
        $pid = IdHelper::normalizeRequestId($personelId);
        if ($pid === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            return ['status' => 'error', 'msg' => 'Geçersiz veri.'];
        }
        $oncekiGun = date('Y-m-d', strtotime($tarih . ' -1 day'));
        $sonrakiGun = date('Y-m-d', strtotime($tarih . ' +1 day'));
        $yakin = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__personel_nobet
             WHERE personel_id = ?
               AND nobet_tarihi IN (?, ?)
               AND durum = 1" . TenantSqlHelper::andBare(),
            [$pid, $oncekiGun, $sonrakiGun]
        );
        if ($yakin > 0) {
            return ['status' => 'error', 'msg' => 'Nöbet ertesi çakışması var.'];
        }

        $istek = $this->db->loadResultPrepared(
            "SELECT aciklama FROM #__personel_istek
             WHERE personel_id = ?
               AND ? BETWEEN baslangic_tarihi AND bitis_tarihi"
             . TenantSqlHelper::andBare() . "
             LIMIT 1",
            [$pid, $tarih]
        );
        if (!empty($istek)) {
            return ['status' => 'error', 'msg' => 'Bu tarihte muafiyet isteği var: ' . (string) $istek];
        }

        $gunNo = (int) date('N', strtotime($tarih));
        $tip = ($gunNo >= 6) ? 'haftasonu' : 'normal';
        $tatilVarmi = (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__resmi_tatiller
             WHERE ? BETWEEN baslangic_tarihi AND bitis_tarihi',
            [$tarih]
        );
        if ($tatilVarmi > 0) {
            $tip = 'resmi_tatil';
        }

        $ok = $this->db->executePrepared(
            'INSERT IGNORE INTO #__personel_nobet (personel_id, nobet_tarihi, nobet_tipi, durum, kurum_id)
             VALUES (?, ?, ?, 1, ?)',
            [$pid, $tarih, $tip, (int) TenantContext::assignKurumIdForStore()]
        );
        if (!$ok || (int) $this->db->affectedRows() === 0) {
            return ['status' => 'error', 'msg' => 'Bu personel bu tarihte zaten nöbetçi olabilir.'];
        }

        return ['status' => 'success', 'new_id' => (int) $this->db->insertid(), 'tip' => $tip];
    }

    public function moveNobet(int $nobetId, string $yeniTarih): array
    {
        $nobet = $this->db->fetchObjectPrepared(
            'SELECT id, personel_id FROM #__personel_nobet WHERE id = ?' . TenantSqlHelper::andBare(),
            [(int) $nobetId]
        );
        if (!$nobet || IdHelper::isEmptyEntityId($nobet->personel_id ?? null)) {
            return ['status' => 'error', 'msg' => 'Nöbet kaydı bulunamadı.'];
        }
        $pid = IdHelper::normalizeRequestId($nobet->personel_id);
        if ($pid === null) {
            return ['status' => 'error', 'msg' => 'Nöbet kaydı bulunamadı.'];
        }
        $oncekiGun = date('Y-m-d', strtotime($yeniTarih . ' -1 day'));
        $sonrakiGun = date('Y-m-d', strtotime($yeniTarih . ' +1 day'));
        $yakin = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__personel_nobet
             WHERE personel_id = ?
               AND nobet_tarihi IN (?, ?)
               AND id != ?
               AND durum = 1" . TenantSqlHelper::andBare(),
            [$pid, $oncekiGun, $sonrakiGun, (int) $nobetId]
        );
        if ($yakin > 0) {
            return ['status' => 'error', 'msg' => 'Nöbet ertesi çakışması var.'];
        }
        $istek = $this->db->loadResultPrepared(
            "SELECT aciklama FROM #__personel_istek
             WHERE personel_id = ?
               AND ? BETWEEN baslangic_tarihi AND bitis_tarihi"
             . TenantSqlHelper::andBare() . "
             LIMIT 1",
            [$pid, $yeniTarih]
        );
        if (!empty($istek)) {
            return ['status' => 'error', 'msg' => 'Bu tarihte muafiyet isteği var: ' . (string) $istek];
        }
        $ok = $this->db->executePrepared(
            'UPDATE #__personel_nobet
             SET nobet_tarihi = ?, durum = 1
             WHERE id = ?' . TenantSqlHelper::andBare(),
            [$yeniTarih, (int) $nobetId]
        );
        return ['status' => $ok ? 'success' : 'error', 'msg' => $ok ? '' : 'Güncelleme başarısız.'];
    }

    public function deleteNobet(int $nobetId): bool
    {
        return $this->db->executePrepared(
            'DELETE FROM #__personel_nobet WHERE id = ?' . TenantSqlHelper::andBare(),
            [(int) $nobetId]
        );
    }

    /**
     * Eski modüldeki aylık "mesai dengesi" özet hesabı.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMonthlyPersonnelSummary(int $ay, int $yil): array
    {
        $nobetler = $this->db->fetchObjectListPrepared(
            "SELECT n.*, u.name, u.unvan
             FROM #__personel_nobet n
             LEFT JOIN #__users u ON u.id = n.personel_id
             WHERE MONTH(n.nobet_tarihi) = " . (int) $ay . "
               AND YEAR(n.nobet_tarihi) = " . (int) $yil . "
               AND n.durum = 1" . TenantSqlHelper::andEquals('n') . "
             ORDER BY n.nobet_tarihi ASC"
        );
        if (!$nobetler) {
            return [];
        }

        $izinler = $this->db->fetchObjectListPrepared(
            'SELECT personel_id, baslangic_tarihi, bitis_tarihi FROM #__personel_izin WHERE 1=1' . TenantSqlHelper::andBare()
        );
        $tatilGunleri = $this->getTatilGunleri($ay, $yil);
        $gunSayisi = cal_days_in_month(CAL_GREGORIAN, $ay, $yil);

        $toplamAyZorunlu = 0;
        for ($d = 1; $d <= $gunSayisi; $d++) {
            $dt = sprintf('%04d-%02d-%02d', $yil, $ay, $d);
            $gunNo = (int) date('N', strtotime($dt));
            if ($gunNo < 6 && !in_array($dt, $tatilGunleri, true)) {
                $toplamAyZorunlu += 8;
            }
        }

        $ozet = [];
        foreach ($nobetler as $n) {
            $pid = IdHelper::normalizeRequestId($n->personel_id ?? null);
            if ($pid === null) {
                continue;
            }
            if (!isset($ozet[$pid])) {
                $kisiZorunlu = $toplamAyZorunlu;
                $toplamCalisilan = 0;
                $mazeretGunleri = [];

                $pNobetleri = [];
                foreach ($nobetler as $x) {
                    if (IdHelper::idsMatch($x->personel_id ?? null, $pid)) {
                        $pNobetleri[(string) $x->nobet_tarihi] = true;
                    }
                }

                for ($d = 1; $d <= $gunSayisi; $d++) {
                    $dt = sprintf('%04d-%02d-%02d', $yil, $ay, $d);
                    $ts = strtotime($dt);
                    $haftaSonu = ((int) date('N', $ts) >= 6);
                    $resmiTatil = in_array($dt, $tatilGunleri, true);
                    $izinli = false;
                    foreach ($izinler as $iz) {
                        if (!IdHelper::idsMatch($iz->personel_id ?? null, $pid)) {
                            continue;
                        }
                        $bas = strtotime((string) $iz->baslangic_tarihi);
                        $bit = strtotime((string) $iz->bitis_tarihi);
                        if ($ts >= $bas && $ts <= $bit) {
                            $izinli = true;
                            $mazeretGunleri[] = date('d.m', $ts);
                            if (!$haftaSonu && !$resmiTatil) {
                                $kisiZorunlu -= 8;
                            }
                            break;
                        }
                    }

                    if (!$izinli) {
                        if (isset($pNobetleri[$dt])) {
                            $toplamCalisilan += 12;
                        } elseif (!$haftaSonu && !$resmiTatil) {
                            $toplamCalisilan += 8;
                        }
                    }
                }

                $ozet[$pid] = [
                    'ad' => (string) ($n->name ?? ''),
                    'unvan' => (string) ($n->unvan ?? ''),
                    'toplam_calisma' => $toplamCalisilan,
                    'zorunlu_mesai' => $kisiZorunlu,
                    'haftaici' => 0,
                    'haftasonu' => 0,
                    'toplam' => 0,
                    'mazeretler' => $mazeretGunleri,
                ];
            }

            $nTs = strtotime((string) $n->nobet_tarihi);
            if ((int) date('N', $nTs) >= 6 || in_array((string) $n->nobet_tarihi, $tatilGunleri, true)) {
                $ozet[$pid]['haftasonu']++;
            } else {
                $ozet[$pid]['haftaici']++;
            }
            $ozet[$pid]['toplam']++;
        }

        uasort($ozet, static function (array $a, array $b): int {
            if (($a['unvan'] ?? '') !== ($b['unvan'] ?? '')) {
                return strcmp((string) ($a['unvan'] ?? ''), (string) ($b['unvan'] ?? ''));
            }
            $netA = (int) ($a['toplam_calisma'] ?? 0) - (int) ($a['zorunlu_mesai'] ?? 0);
            $netB = (int) ($b['toplam_calisma'] ?? 0) - (int) ($b['zorunlu_mesai'] ?? 0);
            return $netB <=> $netA;
        });

        return $ozet;
    }

    /**
     * Eski modüldeki yıllık nöbet istatistiği.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getYearlyStats(int $yil): array
    {
        [$inSql, $inParams] = $this->db->whereInClause(OperationalSettings::nobetAllowedUnvanlar());
        $personeller = $this->db->fetchObjectListPrepared(
            "SELECT id, name, unvan
             FROM #__users
             WHERE activated = 1 AND unvan IN ($inSql)"
             . TenantSqlHelper::andBare('kurum_id') . "
             ORDER BY unvan, name",
            $inParams
        );
        $nobetler = $this->db->fetchObjectListPrepared(
            'SELECT personel_id, nobet_tarihi, nobet_tipi
             FROM #__personel_nobet
             WHERE YEAR(nobet_tarihi) = ? AND durum = 1' . TenantSqlHelper::andBare(),
            [(int) $yil]
        );
        $tatilGunleriYil = [];
        for ($m = 1; $m <= 12; $m++) {
            foreach ($this->getTatilGunleri($m, $yil) as $dt) {
                $tatilGunleriYil[$dt] = true;
            }
        }

        $istatistik = [];
        foreach ($personeller as $p) {
            $pid = IdHelper::normalizeRequestId($p->id ?? null);
            if ($pid === null) {
                continue;
            }
            $istatistik[$pid] = [
                'ad' => (string) ($p->name ?? ''),
                'unvan' => (string) ($p->unvan ?? ''),
                'aylar' => array_fill(1, 12, 0),
                'haftaici' => 0,
                'haftasonu' => 0,
                'toplam_nobet' => 0,
                'bayram_nobet' => 0,
            ];
        }
        foreach ($nobetler as $n) {
            $pid = IdHelper::normalizeRequestId($n->personel_id ?? null);
            if ($pid === null || !isset($istatistik[$pid])) {
                continue;
            }
            $ts = strtotime((string) $n->nobet_tarihi);
            $ay = (int) date('n', $ts);
            $gunNo = (int) date('N', $ts);
            $istatistik[$pid]['aylar'][$ay]++;
            $istatistik[$pid]['toplam_nobet']++;
            $isResmiTatil = isset($tatilGunleriYil[(string) $n->nobet_tarihi]);
            if ($gunNo >= 6 || $isResmiTatil) {
                $istatistik[$pid]['haftasonu']++;
                if ((string) ($n->nobet_tipi ?? '') === 'bayram' || $isResmiTatil) {
                    $istatistik[$pid]['bayram_nobet']++;
                }
            } else {
                $istatistik[$pid]['haftaici']++;
            }
        }

        uasort($istatistik, static function (array $a, array $b): int {
            if (($a['unvan'] ?? '') !== ($b['unvan'] ?? '')) {
                return strcmp((string) ($a['unvan'] ?? ''), (string) ($b['unvan'] ?? ''));
            }
            return strcasecmp((string) ($a['ad'] ?? ''), (string) ($b['ad'] ?? ''));
        });

        return $istatistik;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function nobetUnvanInClause(string $userAlias = 'u'): array
    {
        [$inSql, $inParams] = $this->db->whereInClause(OperationalSettings::nobetAllowedUnvanlar());

        return ["{$userAlias}.unvan IN ($inSql)", $inParams];
    }

    private function selectNobetci(string $tarih, string $unvan, int $adet, int $ay): array
    {
        $dun = date('Y-m-d', strtotime($tarih . ' -1 day'));
        $izinKurum = TenantSqlHelper::andBare();
        $istekKurum = TenantSqlHelper::andBare();
        $nobetKurum = TenantSqlHelper::andBare();
        $nobetAyKurum = TenantSqlHelper::andBare();

        return $this->db->fetchObjectListPrepared(
            "SELECT u.id, u.name,
                (SELECT COUNT(*) FROM #__personel_nobet n WHERE n.personel_id = u.id AND MONTH(n.nobet_tarihi) = " . (int) $ay . " AND n.durum = 1{$nobetAyKurum}) AS mevcut_nobet
             FROM #__users u
             WHERE u.activated = 1
               AND u.unvan = ?
               AND u.id NOT IN (SELECT personel_id FROM #__personel_izin WHERE ? BETWEEN baslangic_tarihi AND bitis_tarihi{$izinKurum})
               AND u.id NOT IN (SELECT personel_id FROM #__personel_istek WHERE ? BETWEEN baslangic_tarihi AND bitis_tarihi{$istekKurum})
               AND u.id NOT IN (SELECT personel_id FROM #__personel_nobet WHERE nobet_tarihi = ? AND durum = 1{$nobetKurum})"
             . TenantSqlHelper::andEquals('u') . "
             ORDER BY mevcut_nobet ASC, RAND()
             LIMIT " . (int) $adet,
            [$unvan, $tarih, $tarih, $dun]
        );
    }

    private function insertNobet(int|string $pid, string $tarih, string $tip): bool
    {
        $personelId = IdHelper::normalizeRequestId($pid);
        if ($personelId === null) {
            return false;
        }
        $kid = TenantContext::assignKurumIdForStore();

        return $this->db->executePrepared(
            'INSERT IGNORE INTO #__personel_nobet (personel_id, nobet_tarihi, nobet_tipi, durum, kurum_id)
             VALUES (?, ?, ?, 1, ?)',
            [$personelId, $tarih, $tip, (int) $kid]
        );
    }
}

