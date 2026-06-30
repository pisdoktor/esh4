<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Stats;
use App\Helpers\TenantSqlHelper;

/**
 * İki kırılımlı (çapraz tablo) istatistik üretici.
 */
final class StatsCrossTabBuilder {
    private static function k(string $alias = 'h'): string {
        return TenantSqlHelper::andEquals($alias);
    }

    /** @return list<int> */
    public static function periodMonthChoices(): array {
        return [3, 6, 9, 12, 24];
    }

    public static function normalizePeriodMonths(int $months): int {
        $allowed = self::periodMonthChoices();
        if (in_array($months, $allowed, true)) {
            return $months;
        }
        $best = 12;
        $bestDiff = PHP_INT_MAX;
        foreach ($allowed as $choice) {
            $diff = abs($months - $choice);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $choice;
            }
        }

        return $best;
    }

    /** @param array{months?: int} $opts */
    public static function build(Stats $stats, string $id, array $opts = []): array {
        $months = self::normalizePeriodMonths((int) ($opts['months'] ?? 12));
        $meta = StatsCrossTabRegistry::definition($id);
        $pack = match ($id) {
            'bagimlilikAge' => self::bagimlilikAge($stats),
            'guvenceBagimlilik' => self::guvenceBagimlilik($stats),
            'ilceAge' => self::ilceAge($stats),
            'bmiAge' => self::bmiAge($stats),
            'bmiBagimlilik' => self::bmiBagimlilik($stats),
            'deviceCountAge' => self::deviceCountAge($stats),
            'hastalikCountAge' => self::hastalikCountAge($stats),
            'kayitYearAge' => self::kayitYearAge($stats),
            'monthVisitDone' => self::monthVisitDone($stats, $months),
            'monthVisitZaman' => self::monthVisitZaman($stats, $months),
            'ilceVisitDone' => self::ilceVisitDone($stats, $months),
            'procedureMonth' => self::procedureMonth($stats, $months),
            'personnelMonth' => self::personnelMonth($stats, $months),
            'vehicleMonth' => self::vehicleMonth($stats, $months),
            'ageMonthVisited' => self::ageMonthVisited($stats, $months),
            'monthPlanStatus' => self::monthPlanStatus($stats, $months),
            'monthPlanPriority' => self::monthPlanPriority($stats, $months),
            'monthPlanZaman' => self::monthPlanZaman($stats, $months),
            'ilcePlanStatus' => self::ilcePlanStatus($stats, $months),
            'guvenceVisitGap' => self::guvenceVisitGap($stats),
            'bagimlilikVisitYear' => self::bagimlilikVisitYear($stats),
            'barthelAge' => self::barthelAge($stats),
            'tenureVisitCount' => self::tenureVisitCount($stats),
            'pansumanVisitGap' => self::pansumanVisitGap($stats),
            'branchMonthKons' => self::branchMonthKons($stats, $months),
            'branchZamanKons' => self::branchZamanKons($stats, $months),
            'monthAttendKons' => self::monthAttendKons($stats, $months),
            'exitReasonYear' => self::exitReasonYear($stats),
            'exitReasonTenure' => self::exitReasonTenure($stats),
            'exitMonthIlce' => self::exitMonthIlce($stats, $months),
            default => StatsCrossTabMatrix::create([], []),
        };
        $pack['id'] = $id;
        $pack['title'] = (string) ($meta['title'] ?? $id);
        $pack['period_label'] = $pack['period_label'] ?? self::periodLabel($months, (string) ($meta['period_type'] ?? ''));
        $pack['cell_unit'] = (string) ($pack['cell_unit'] ?? 'adet');

        return $pack;
    }

    private static function periodLabel(int $months, string $type): ?string {
        if ($type === '') {
            return null;
        }

        return $type === 'visit' || $type === 'plan' || $type === 'randevu'
            ? ('Son ' . $months . ' ay')
            : null;
    }

    /** @return list<string> */
    private static function monthKeys(int $months): array {
        $keys = [];
        $dt = new \DateTimeImmutable('first day of this month');
        for ($i = $months - 1; $i >= 0; $i--) {
            $keys[] = $dt->modify('-' . $i . ' months')->format('Y-m');
        }

        return $keys;
    }

    private static function ageBandKey(?string $dogum): string {
        $band = AgeBandHelper::bandFromBirthDate($dogum);

        return $band ?? '_unknown';
    }

    private static function ageBandLabels(): array {
        $labels = AgeBandHelper::labels();
        $labels['_unknown'] = 'Yaş bilinmiyor';

        return $labels;
    }

    private static function ageBandColKeys(): array {
        return array_merge(AgeBandHelper::keys(), ['_unknown']);
    }

    private static function bagimlilikRowKey(?string $raw): string {
        $k = trim((string) $raw);
        if ($k === '' || $k === '—') {
            return '_bos';
        }

        return $k;
    }

    /** @return array<string, string> */
    private static function bagimlilikRowLabels(): array {
        return [
            '1' => 'Bağımsız',
            '2' => 'Yarı bağımlı',
            '3' => 'Tam bağımlı',
            '_bos' => 'Belirtilmemiş',
        ];
    }

    private static function bagimlilikAge(Stats $stats): array {
        $cols = self::ageBandColKeys();
        $pack = StatsCrossTabMatrix::create(['1', '2', '3', '_bos'], $cols, self::bagimlilikRowLabels(), self::ageBandLabels());
        $rows = $stats->db->fetchObjectListPrepared("SELECT bagimlilik, dogumtarihi FROM #__hastalar h WHERE h.pasif = '0'" . self::k('h')
        ) ?: [];
        foreach ($rows as $r) {
            StatsCrossTabMatrix::add($pack, self::bagimlilikRowKey($r->bagimlilik ?? null), self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function guvenceBagimlilik(Stats $stats): array {
        $colKeys = ['1', '2', '3', '_bos'];
        $sql = "SELECT IFNULL(g.guvenceadi, 'Belirtilmemiş') AS guvence,
                IFNULL(NULLIF(TRIM(h.bagimlilik), ''), '_bos') AS bag_kod,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN #__guvence g ON g.id = h.guvence
            WHERE h.pasif = '0'" . self::k('h') . "
            GROUP BY guvence, bag_kod";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->guvence] = ($rowCounts[(string) $r->guvence] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 15);
        $rowLabels = array_combine($rowKeys, $rowKeys) ?: [];
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, self::bagimlilikRowLabels());
        foreach ($list as $r) {
            $g = (string) $r->guvence;
            if (!in_array($g, $rowKeys, true)) {
                $g = '_diger';
                if (!in_array($g, $rowKeys, true)) {
                    continue;
                }
            }
            $bk = self::bagimlilikRowKey($r->bag_kod === '_bos' ? '' : $r->bag_kod);
            StatsCrossTabMatrix::add($pack, $g, $bk, (int) $r->adet);
        }

        return $pack;
    }

    private static function ilceAge(Stats $stats): array {
        $cols = self::ageBandColKeys();
        $sql = "SELECT il.adi AS ilce, h.dogumtarihi
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE h.pasif = '0' AND h.ilce IS NOT NULL AND TRIM(CAST(h.ilce AS CHAR)) NOT IN ('', '0')" . self::k('h');
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $il = trim((string) ($r->ilce ?? '')) ?: 'Belirtilmemiş';
            $rowCounts[$il] = ($rowCounts[$il] ?? 0) + 1;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 15);
        $pack = StatsCrossTabMatrix::create($rowKeys, $cols, array_combine($rowKeys, $rowKeys) ?: [], self::ageBandLabels());
        foreach ($list as $r) {
            $il = trim((string) ($r->ilce ?? '')) ?: 'Belirtilmemiş';
            if (!in_array($il, $rowKeys, true)) {
                $il = '_diger';
            }
            StatsCrossTabMatrix::add($pack, $il, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function bmiAge(Stats $stats): array {
        $cols = self::ageBandColKeys();
        $catMeta = BmiHelper::categories();
        $rowKeys = array_keys($catMeta);
        $rowLabels = [];
        foreach ($catMeta as $k => $m) {
            $rowLabels[$k] = (string) ($m['short'] ?? $m['label'] ?? $k);
        }
        $pack = StatsCrossTabMatrix::create($rowKeys, $cols, $rowLabels, self::ageBandLabels());
        $rows = $stats->db->fetchObjectListPrepared("SELECT dogumtarihi, boy, kilo FROM #__hastalar h WHERE h.pasif = '0'" . self::k('h')
        ) ?: [];
        foreach ($rows as $r) {
            $cm = BmiHelper::normalizeBoyCm($r->boy);
            $kg = BmiHelper::normalizeKiloKg($r->kilo);
            if ($cm === null || $kg === null) {
                continue;
            }
            $cat = BmiHelper::classifyBmi(BmiHelper::calculateBmi($kg, $cm));
            if (!isset($pack['matrix'][$cat])) {
                continue;
            }
            StatsCrossTabMatrix::add($pack, $cat, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function bmiBagimlilik(Stats $stats): array {
        $colKeys = ['1', '2', '3', '_bos'];
        $catMeta = BmiHelper::categories();
        $rowKeys = array_keys($catMeta);
        $rowLabels = [];
        foreach ($catMeta as $k => $m) {
            $rowLabels[$k] = (string) ($m['label'] ?? $k);
        }
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, self::bagimlilikRowLabels());
        $rows = $stats->db->fetchObjectListPrepared("SELECT bagimlilik, boy, kilo FROM #__hastalar h WHERE h.pasif = '0'" . self::k('h')
        ) ?: [];
        foreach ($rows as $r) {
            $cm = BmiHelper::normalizeBoyCm($r->boy);
            $kg = BmiHelper::normalizeKiloKg($r->kilo);
            if ($cm === null || $kg === null) {
                continue;
            }
            $cat = BmiHelper::classifyBmi(BmiHelper::calculateBmi($kg, $cm));
            StatsCrossTabMatrix::add($pack, $cat, self::bagimlilikRowKey($r->bagimlilik ?? null));
        }

        return $pack;
    }

    private static function deviceCountAge(Stats $stats): array {
        $cols = self::ageBandColKeys();
        $rowKeys = ['c0', 'c1', 'c2', 'c3p'];
        $rowLabels = ['c0' => 'Cihaz yok', 'c1' => '1 işaret', 'c2' => '2 işaret', 'c3p' => '3+ işaret'];
        $pack = StatsCrossTabMatrix::create($rowKeys, $cols, $rowLabels, self::ageBandLabels());
        $flagExpr = PatientClinicalFlagsHelper::sqlFlagSumExpression();
        $rows = $stats->db->fetchObjectListPrepared("SELECT dogumtarihi, {$flagExpr} AS c FROM #__hastalar h WHERE h.pasif = '0'" . self::k('h')
        ) ?: [];
        foreach ($rows as $r) {
            $c = (int) ($r->c ?? 0);
            $rk = $c >= 3 ? 'c3p' : ('c' . $c);
            StatsCrossTabMatrix::add($pack, $rk, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function hastalikCountAge(Stats $stats): array {
        $cols = self::ageBandColKeys();
        $rowKeys = ['0', '1', '2', '3', '4p'];
        $rowLabels = ['0' => 'Tanı yok', '1' => '1 tanı', '2' => '2 tanı', '3' => '3 tanı', '4p' => '4+ tanı'];
        $pack = StatsCrossTabMatrix::create($rowKeys, $cols, $rowLabels, self::ageBandLabels());
        $rows = $stats->db->fetchObjectListPrepared("SELECT hastaliklar, dogumtarihi FROM #__hastalar h WHERE h.pasif = '0'" . self::k('h')
        ) ?: [];
        foreach ($rows as $r) {
            $raw = trim((string) ($r->hastaliklar ?? ''));
            $ids = $raw === '' ? [] : array_filter(array_map('trim', explode(',', $raw)));
            $n = count($ids);
            $rk = $n >= 4 ? '4p' : (string) min(3, $n);
            StatsCrossTabMatrix::add($pack, $rk, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function kayitYearAge(Stats $stats): array {
        $kayit = "NULLIF(NULLIF(h.kayittarihi, ''), '0000-00-00')";
        $minY = (int) date('Y') - 9;
        $sql = "SELECT YEAR({$kayit}) AS ky, h.dogumtarihi
            FROM #__hastalar h
            WHERE h.pasif = '0' AND {$kayit} IS NOT NULL AND YEAR({$kayit}) >= {$minY}" . self::k('h');
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowKeys = [];
        for ($y = $minY; $y <= (int) date('Y'); $y++) {
            $rowKeys[] = (string) $y;
        }
        $cols = self::ageBandColKeys();
        $pack = StatsCrossTabMatrix::create($rowKeys, $cols, array_combine($rowKeys, $rowKeys) ?: [], self::ageBandLabels());
        foreach ($list as $r) {
            $y = (string) (int) ($r->ky ?? 0);
            if (!isset($pack['matrix'][$y])) {
                continue;
            }
            StatsCrossTabMatrix::add($pack, $y, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function monthVisitDone(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
        $rep = $stats->getVisitStatsReport($from, $to);
        $colKeys = ['yapildi', 'yapilmadi'];
        $colLabels = ['yapildi' => 'Yapıldı', 'yapilmadi' => 'Yapılmadı'];
        $rowKeys = self::monthKeys($months);
        $rowLabels = [];
        foreach ($rowKeys as $mk) {
            $rowLabels[$mk] = $mk;
        }
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, $colLabels);
        foreach ($rep['by_month'] ?? [] as $r) {
            $mk = sprintf('%04d-%02d', (int) $r->yil, (int) $r->ay);
            if (!isset($pack['matrix'][$mk])) {
                continue;
            }
            StatsCrossTabMatrix::add($pack, $mk, 'yapildi', (int) ($r->yapilan ?? 0));
            StatsCrossTabMatrix::add($pack, $mk, 'yapilmadi', (int) ($r->yapilmayan ?? 0));
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'izlem kaydı';

        return $pack;
    }

    private static function monthVisitZaman(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = "COALESCE(STR_TO_DATE(TRIM(i.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM(i.izlemtarihi),'%d.%m.%Y'))";
        $zamanExpr = ZamanDilimiHelper::sqlSlotKeyCaseExpr('i.zaman');
        $sql = "SELECT DATE_FORMAT({$izd}, '%Y-%m') AS mk, {$zamanExpr} AS slot, COUNT(*) AS adet
            FROM #__izlemler i
            INNER JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik AND h.pasif = '0' AND i.kurum_id = h.kurum_id
            WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?
              AND COALESCE(i.yapildimi, 0) = 1" . self::k('i') . "
            GROUP BY mk, slot";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $colKeys = ZamanDilimiHelper::slotKeysWithOther();
        $colLabels = ZamanDilimiHelper::slotKeyLabels();
        $rowKeys = self::monthKeys($months);
        $rowLabels = array_combine($rowKeys, $rowKeys) ?: [];
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, $colLabels);
        foreach ($list as $r) {
            $mk = (string) ($r->mk ?? '');
            $slot = (string) ($r->slot ?? 'diger');
            if (!in_array($mk, $rowKeys, true) || !isset($colLabels[$slot])) {
                continue;
            }
            StatsCrossTabMatrix::add($pack, $mk, $slot, (int) ($r->adet ?? 0));
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'izlem kaydı';

        return $pack;
    }

    private static function ilceVisitDone(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = "COALESCE(STR_TO_DATE(TRIM(i.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM(i.izlemtarihi),'%d.%m.%Y'))";
        $sql = "SELECT IFNULL(il.adi, 'Belirtilmemiş') AS ilce,
                CASE WHEN COALESCE(i.yapildimi,0)=1 THEN 'yapildi' ELSE 'yapilmadi' END AS durum,
                COUNT(*) AS adet
            FROM #__izlemler i
            INNER JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik AND h.pasif = '0' AND i.kurum_id = h.kurum_id
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?" . self::k('i') . "
            GROUP BY ilce, durum";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->ilce] = ($rowCounts[(string) $r->ilce] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 12);
        $pack = StatsCrossTabMatrix::create(
            $rowKeys,
            ['yapildi', 'yapilmadi'],
            array_combine($rowKeys, $rowKeys) ?: [],
            ['yapildi' => 'Yapıldı', 'yapilmadi' => 'Yapılmadı']
        );
        foreach ($list as $r) {
            $il = (string) $r->ilce;
            if (!in_array($il, $rowKeys, true)) {
                $il = '_diger';
            }
            StatsCrossTabMatrix::add($pack, $il, (string) $r->durum, (int) $r->adet);
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'izlem kaydı';

        return $pack;
    }

    private static function procedureMonth(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = $stats->sqlIzlemTarihiAsDate('i');
        $colKeys = self::monthKeys($months);
        $rows = $stats->db->fetchObjectListPrepared("SELECT i.yapilan, DATE_FORMAT({$izd}, '%Y-%m') AS mk
             FROM #__izlemler i
             WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?
               AND COALESCE(i.yapildimi, 0) = 1
               AND TRIM(COALESCE(i.yapilan, '')) != ''" . self::k('i'),
            [$from, $to]
        ) ?: [];
        /** @var array<string, array<string, int>> $cellCounts */
        $cellCounts = [];
        $rowTotals = [];
        foreach ($rows as $r) {
            $mk = (string) ($r->mk ?? '');
            if (!in_array($mk, $colKeys, true)) {
                continue;
            }
            foreach (VisitIslemHelper::yapilanCsvToIntIds($r->yapilan ?? null) as $id) {
                $key = (string) $id;
                $cellCounts[$key][$mk] = ($cellCounts[$key][$mk] ?? 0) + 1;
                $rowTotals[$key] = ($rowTotals[$key] ?? 0) + 1;
            }
        }
        if ($rowTotals === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], array_combine($colKeys, $colKeys) ?: []);
            $empty['period_type'] = 'visit';

            return $empty;
        }
        $nameMap = [];
        foreach (CatalogScopeSqlHelper::loadIslemIdNameMap() as $id => $label) {
            $nameMap[(string) $id] = $label;
        }
        arsort($rowTotals);
        $rowKeys = array_keys($rowTotals);
        $rowLabels = [];
        foreach ($rowKeys as $rk) {
            $label = $nameMap[$rk] ?? '';
            $rowLabels[$rk] = $label !== '' ? $label : ('İşlem #' . $rk);
        }
        $pack = StatsCrossTabMatrix::create(
            $rowKeys,
            $colKeys,
            $rowLabels,
            array_combine($colKeys, $colKeys) ?: []
        );
        foreach ($cellCounts as $key => $byMonth) {
            foreach ($byMonth as $mk => $adet) {
                StatsCrossTabMatrix::add($pack, $key, $mk, $adet);
            }
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'işlem adedi';

        return $pack;
    }

    private static function personnelMonth(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = $stats->sqlIzlemTarihiAsDate('i');
        $colKeys = self::monthKeys($months);
        $rows = $stats->db->fetchObjectListPrepared("SELECT i.izlemiyapan, DATE_FORMAT({$izd}, '%Y-%m') AS mk
             FROM #__izlemler i
             WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?
               AND COALESCE(i.yapildimi, 0) = 1
               AND TRIM(COALESCE(i.izlemiyapan, '')) != ''" . self::k('i'),
            [$from, $to]
        ) ?: [];
        /** @var array<string, array<string, int>> $cellCounts */
        $cellCounts = [];
        $rowTotals = [];
        foreach ($rows as $r) {
            $mk = (string) ($r->mk ?? '');
            if (!in_array($mk, $colKeys, true)) {
                continue;
            }
            foreach (self::personnelCsvToIntIds($r->izlemiyapan ?? null) as $uid) {
                $key = (string) $uid;
                $cellCounts[$key][$mk] = ($cellCounts[$key][$mk] ?? 0) + 1;
                $rowTotals[$key] = ($rowTotals[$key] ?? 0) + 1;
            }
        }
        if ($rowTotals === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], array_combine($colKeys, $colKeys) ?: []);
            $empty['period_type'] = 'visit';

            return $empty;
        }
        $userRows = $stats->db->fetchObjectListPrepared('SELECT id, name, unvan FROM #__users' . TenantSqlHelper::sqlWhereKurum()) ?: [];
        $userMap = [];
        foreach ($userRows as $u) {
            $userMap[(string) $u->id] = [
                'name' => trim((string) $u->name),
                'unvan' => trim((string) ($u->unvan ?? '')),
            ];
        }
        $personnel = [];
        foreach ($rowTotals as $uid => $total) {
            $meta = $userMap[$uid] ?? ['name' => '', 'unvan' => ''];
            $name = $meta['name'] !== '' ? $meta['name'] : ('Personel #' . $uid);
            $unvan = $meta['unvan'] !== '' ? $meta['unvan'] : '_bos';
            $personnel[] = [
                'id' => $uid,
                'name' => $name,
                'unvan' => $unvan,
                'total' => (int) $total,
            ];
        }
        usort($personnel, static function (array $a, array $b): int {
            $uo = self::personnelUnvanSortIndex($a['unvan']) <=> self::personnelUnvanSortIndex($b['unvan']);
            if ($uo !== 0) {
                return $uo;
            }
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }

            return strcasecmp($a['name'], $b['name']);
        });
        $rowKeys = [];
        $rowLabels = [];
        $rowUnvan = [];
        foreach ($personnel as $p) {
            $uid = (string) $p['id'];
            $rowKeys[] = $uid;
            $rowLabels[$uid] = (string) $p['name'];
            $rowUnvan[$uid] = (string) $p['unvan'];
        }
        $pack = StatsCrossTabMatrix::create(
            $rowKeys,
            $colKeys,
            $rowLabels,
            array_combine($colKeys, $colKeys) ?: []
        );
        foreach ($cellCounts as $uid => $byMonth) {
            foreach ($byMonth as $mk => $adet) {
                StatsCrossTabMatrix::add($pack, $uid, $mk, $adet);
            }
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'izlem atfı';
        $pack['group_rows_by_unvan'] = true;
        $pack['row_unvan'] = $rowUnvan;

        return $pack;
    }

    /** @return list<int> */
    private static function personnelCsvToIntIds(mixed $csv): array {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\s*,\s*/', str_replace(' ', '', $csv), -1, PREG_SPLIT_NO_EMPTY) as $p) {
            $id = (int) $p;
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return $out;
    }

    private static function personnelUnvanSortIndex(string $unvanCode): int {
        static $order = null;
        if ($order === null) {
            $choices = \App\Models\User::unvanChoices();
            unset($choices['']);
            $order = array_values(array_keys($choices));
            $order[] = '_bos';
        }
        $idx = array_search($unvanCode, $order, true);

        return $idx === false ? count($order) : (int) $idx;
    }

    public static function personnelUnvanGroupLabel(string $unvanCode): string {
        if ($unvanCode === '' || $unvanCode === '_bos') {
            return 'Belirtilmemiş';
        }
        $label = \App\Models\User::unvanLabel($unvanCode);

        return $label === '—' ? 'Belirtilmemiş' : $label;
    }

    private static function vehicleMonth(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = "COALESCE(STR_TO_DATE(TRIM(i.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM(i.izlemtarihi),'%d.%m.%Y'))";
        $sql = "SELECT IFNULL(a.plaka, CONCAT('Araç #', i.arac)) AS arac_label,
                DATE_FORMAT({$izd}, '%Y-%m') AS mk, COUNT(*) AS adet
            FROM #__izlemler i
            LEFT JOIN #__araclar a ON a.id = i.arac
            WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?
              AND COALESCE(i.arac,0) > 0" . self::k('i') . "
            GROUP BY i.arac, a.plaka, mk";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->arac_label] = ($rowCounts[(string) $r->arac_label] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 8);
        $pack = StatsCrossTabMatrix::create($rowKeys, self::monthKeys($months), array_combine($rowKeys, $rowKeys) ?: [], array_combine(self::monthKeys($months), self::monthKeys($months)) ?: []);
        foreach ($list as $r) {
            $lb = (string) $r->arac_label;
            if (!in_array($lb, $rowKeys, true)) {
                $lb = '_diger';
            }
            StatsCrossTabMatrix::add($pack, $lb, (string) $r->mk, (int) $r->adet);
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'izlem kaydı';

        return $pack;
    }

    private static function ageMonthVisited(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $izd = "COALESCE(STR_TO_DATE(TRIM(i.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM(i.izlemtarihi),'%d.%m.%Y'))";
        $sql = "SELECT DATE_FORMAT({$izd}, '%Y-%m') AS mk, h.dogumtarihi, COUNT(DISTINCT h.tckimlik) AS adet
            FROM #__izlemler i
            INNER JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik AND h.pasif = '0' AND i.kurum_id = h.kurum_id
            WHERE {$izd} IS NOT NULL AND {$izd} >= ? AND {$izd} <= ?
              AND COALESCE(i.yapildimi,0)=1" . self::k('i') . "
            GROUP BY mk, h.dogumtarihi";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $colKeys = self::ageBandColKeys();
        $rowKeys = self::monthKeys($months);
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, array_combine($rowKeys, $rowKeys) ?: [], self::ageBandLabels());
        foreach ($list as $r) {
            StatsCrossTabMatrix::add($pack, (string) $r->mk, self::ageBandKey($r->dogumtarihi ?? null), (int) $r->adet);
        }
        $pack['period_type'] = 'visit';
        $pack['cell_unit'] = 'benzersiz hasta';

        return $pack;
    }

    private static function monthPlanStatus(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $sql = "SELECT DATE_FORMAT(p.planlanantarih, '%Y-%m') AS mk,
                CASE WHEN COALESCE(p.durum,0)=1 THEN 'tamam' ELSE 'bekliyor' END AS st,
                COUNT(*) AS adet
            FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0' AND p.kurum_id = h.kurum_id
            WHERE p.planlanantarih >= ? AND p.planlanantarih <= ?" . self::k('p') . "
            GROUP BY mk, st";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $pack = StatsCrossTabMatrix::fromSqlRows(
            $list,
            'mk',
            'st',
            'adet',
            null,
            static fn ($ck) => $ck === 'tamam' ? 'Tamamlandı' : 'Bekliyor'
        );
        $pack['col_labels'] = ['tamam' => 'Tamamlandı', 'bekliyor' => 'Bekliyor'];
        $pack['period_type'] = 'plan';
        $pack['cell_unit'] = 'plan kaydı';

        return $pack;
    }

    private static function monthPlanPriority(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $sql = "SELECT DATE_FORMAT(p.planlanantarih, '%Y-%m') AS mk,
                CONCAT('p', COALESCE(p.oncelik,1)) AS pr,
                COUNT(*) AS adet
            FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0' AND p.kurum_id = h.kurum_id
            WHERE p.planlanantarih >= ? AND p.planlanantarih <= ?" . self::k('p') . "
            GROUP BY mk, pr";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $pack = StatsCrossTabMatrix::fromSqlRows($list, 'mk', 'pr', 'adet', null, static fn ($ck) => 'Öncelik ' . substr($ck, 1));
        $pack['period_type'] = 'plan';
        $pack['cell_unit'] = 'plan kaydı';

        return $pack;
    }

    private static function monthPlanZaman(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $slotExpr = ZamanDilimiHelper::sqlSlotKeyCaseExpr('p.zaman');
        $sql = "SELECT DATE_FORMAT(p.planlanantarih, '%Y-%m') AS mk,
                {$slotExpr} AS slot,
                COUNT(*) AS adet
            FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0' AND p.kurum_id = h.kurum_id
            WHERE p.planlanantarih >= ? AND p.planlanantarih <= ?" . self::k('p') . "
            GROUP BY mk, slot";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $colKeys = ZamanDilimiHelper::slotKeysWithOther();
        $colLabels = ZamanDilimiHelper::slotKeyLabels();
        $rowKeys = self::monthKeys($months);
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, array_combine($rowKeys, $rowKeys) ?: [], $colLabels);
        foreach ($list as $r) {
            $mk = (string) ($r->mk ?? '');
            $slot = (string) ($r->slot ?? 'diger');
            if (!in_array($mk, $rowKeys, true) || !isset($colLabels[$slot])) {
                continue;
            }
            StatsCrossTabMatrix::add($pack, $mk, $slot, (int) ($r->adet ?? 0));
        }
        $pack['period_type'] = 'plan';
        $pack['cell_unit'] = 'plan kaydı';

        return $pack;
    }

    private static function ilcePlanStatus(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $sql = "SELECT IFNULL(il.adi,'Belirtilmemiş') AS ilce,
                CASE WHEN COALESCE(p.durum,0)=1 THEN 'tamam' ELSE 'bekliyor' END AS st,
                COUNT(*) AS adet
            FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0' AND p.kurum_id = h.kurum_id
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE p.planlanantarih >= ? AND p.planlanantarih <= ?" . self::k('p') . "
            GROUP BY ilce, st";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->ilce] = ($rowCounts[(string) $r->ilce] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 12);
        $pack = StatsCrossTabMatrix::create($rowKeys, ['tamam', 'bekliyor'], array_combine($rowKeys, $rowKeys) ?: [], ['tamam' => 'Tamamlandı', 'bekliyor' => 'Bekliyor']);
        foreach ($list as $r) {
            $il = (string) $r->ilce;
            if (!in_array($il, $rowKeys, true)) {
                $il = '_diger';
            }
            StatsCrossTabMatrix::add($pack, $il, (string) $r->st, (int) $r->adet);
        }
        $pack['period_type'] = 'plan';
        $pack['cell_unit'] = 'plan kaydı';

        return $pack;
    }

    private static function guvenceVisitGap(Stats $stats): array {
        $izd = $stats->sqlIzlemTarihiAsDate('i');
        $sql = "SELECT IFNULL(g.guvenceadi,'Belirtilmemiş') AS guvence,
                CASE
                    WHEN last_iz.max_d IS NULL THEN 'hic'
                    WHEN DATEDIFF(CURDATE(), last_iz.max_d) <= 30 THEN 'd30'
                    WHEN DATEDIFF(CURDATE(), last_iz.max_d) <= 90 THEN 'd90'
                    WHEN DATEDIFF(CURDATE(), last_iz.max_d) <= 180 THEN 'd180'
                    ELSE 'd180p'
                END AS gap,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN #__guvence g ON g.id = h.guvence
            LEFT JOIN (
                SELECT i.hastatckimlik, i.kurum_id, MAX({$izd}) AS max_d
                FROM #__izlemler i WHERE yapildimi=1 AND {$izd} IS NOT NULL" . self::k('i') . "
                GROUP BY i.hastatckimlik, i.kurum_id
            ) last_iz ON last_iz.hastatckimlik = h.tckimlik AND last_iz.kurum_id = h.kurum_id
            WHERE h.pasif = '0'" . self::k('h') . "
            GROUP BY guvence, gap";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $colLabels = [
            'hic' => 'Hiç izlem yok',
            'd30' => '0–30 gün',
            'd90' => '31–90 gün',
            'd180' => '91–180 gün',
            'd180p' => '180+ gün',
        ];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->guvence] = ($rowCounts[(string) $r->guvence] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 12);
        $pack = StatsCrossTabMatrix::create($rowKeys, array_keys($colLabels), array_combine($rowKeys, $rowKeys) ?: [], $colLabels);
        foreach ($list as $r) {
            $g = (string) $r->guvence;
            if (!in_array($g, $rowKeys, true)) {
                $g = '_diger';
            }
            StatsCrossTabMatrix::add($pack, $g, (string) $r->gap, (int) $r->adet);
        }
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    private static function bagimlilikVisitYear(Stats $stats): array {
        $y = (int) date('Y');
        $izd = $stats->sqlIzlemTarihiAsDate('i');
        $sql = "SELECT IFNULL(NULLIF(TRIM(h.bagimlilik),''),'—') AS bag,
                CASE
                    WHEN cnt.c IS NULL OR cnt.c = 0 THEN 'v0'
                    WHEN cnt.c <= 2 THEN 'v12'
                    WHEN cnt.c <= 5 THEN 'v35'
                    ELSE 'v6p'
                END AS vb,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN (
                SELECT i.hastatckimlik, i.kurum_id, COUNT(*) AS c FROM #__izlemler i
                WHERE yapildimi=1 AND {$izd} IS NOT NULL AND YEAR({$izd})={$y}" . self::k('i') . "
                GROUP BY i.hastatckimlik, i.kurum_id
            ) cnt ON cnt.hastatckimlik = h.tckimlik AND cnt.kurum_id = h.kurum_id
            WHERE h.pasif='0'" . self::k('h') . "
            GROUP BY bag, vb";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $colLabels = ['v0' => '0 izlem', 'v12' => '1–2', 'v35' => '3–5', 'v6p' => '6+'];
        $pack = StatsCrossTabMatrix::fromSqlRows(
            $list,
            'bag',
            'vb',
            'adet',
            static fn ($rk) => Stats::bagimlilikLabel($rk === '—' ? '' : $rk),
            static fn ($ck) => $colLabels[$ck] ?? $ck
        );
        $pack['col_labels'] = $colLabels;
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    private static function barthelAge(Stats $stats): array {
        $sumExpr = '(IFNULL(h.barbeslenme,0)+IFNULL(h.barbanyo,0)+IFNULL(h.barbakim,0)+IFNULL(h.bargiyinme,0)+IFNULL(h.barbarsak,0)+'
            . 'IFNULL(h.barmesane,0)+IFNULL(h.bartuvalet,0)+IFNULL(h.bartransfer,0)+IFNULL(h.barmobilite,0)+IFNULL(h.barmerdiven,0))';
        $sql = "SELECT
                CASE
                    WHEN {$sumExpr} <= 20 THEN 'b20'
                    WHEN {$sumExpr} <= 61 THEN 'b61'
                    WHEN {$sumExpr} <= 90 THEN 'b90'
                    WHEN {$sumExpr} < 100 THEN 'b99'
                    ELSE 'b100'
                END AS bg,
                h.dogumtarihi
            FROM #__hastalar h WHERE h.pasif='0'" . self::k('h');
        $rows = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowLabels = [
            'b20' => '0–20',
            'b61' => '21–61',
            'b90' => '62–90',
            'b99' => '91–99',
            'b100' => '100',
        ];
        $cols = self::ageBandColKeys();
        $pack = StatsCrossTabMatrix::create(array_keys($rowLabels), $cols, $rowLabels, self::ageBandLabels());
        foreach ($rows as $r) {
            StatsCrossTabMatrix::add($pack, (string) $r->bg, self::ageBandKey($r->dogumtarihi ?? null));
        }

        return $pack;
    }

    private static function tenureVisitCount(Stats $stats): array {
        $kayit = "NULLIF(NULLIF(h.kayittarihi, ''), '0000-00-00')";
        $sql = "SELECT
                CASE
                    WHEN {$kayit} IS NULL THEN 't0'
                    WHEN DATEDIFF(CURDATE(), {$kayit}) < 183 THEN 't6'
                    WHEN DATEDIFF(CURDATE(), {$kayit}) < 365 THEN 't12'
                    WHEN DATEDIFF(CURDATE(), {$kayit}) < 1095 THEN 't36'
                    ELSE 't36p'
                END AS tg,
                CASE
                    WHEN cnt.c IS NULL OR cnt.c = 0 THEN 'v0'
                    WHEN cnt.c <= 3 THEN 'v13'
                    WHEN cnt.c <= 10 THEN 'v410'
                    ELSE 'v10p'
                END AS vb,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN (
                SELECT i.hastatckimlik, i.kurum_id, COUNT(*) AS c FROM #__izlemler i WHERE yapildimi=1" . self::k('i') . " GROUP BY i.hastatckimlik, i.kurum_id
            ) cnt ON cnt.hastatckimlik = h.tckimlik AND cnt.kurum_id = h.kurum_id
            WHERE h.pasif='0'" . self::k('h') . "
            GROUP BY tg, vb";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowKeys = ['t0', 't6', 't12', 't36', 't36p'];
        $rowLabels = ['t0' => 'Kayıt tarihi yok', 't6' => '0–6 ay', 't12' => '6–12 ay', 't36' => '1–3 yıl', 't36p' => '3+ yıl'];
        $colKeys = ['v0', 'v13', 'v410', 'v10p'];
        $colLabels = ['v0' => '0 izlem', 'v13' => '1–3', 'v410' => '4–10', 'v10p' => '10+'];
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, $colLabels);
        foreach ($list as $r) {
            $tg = (string) ($r->tg ?? '');
            $vb = (string) ($r->vb ?? '');
            if (isset($pack['matrix'][$tg][$vb])) {
                StatsCrossTabMatrix::add($pack, $tg, $vb, (int) ($r->adet ?? 0));
            }
        }
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    private static function pansumanVisitGap(Stats $stats): array {
        $izd = $stats->sqlIzlemTarihiAsDate('i');
        $sql = "SELECT
                CASE
                    WHEN last_iz.max_d IS NULL THEN 'hic'
                    WHEN DATEDIFF(CURDATE(), last_iz.max_d) <= 30 THEN 'd30'
                    WHEN DATEDIFF(CURDATE(), last_iz.max_d) <= 60 THEN 'd60'
                    ELSE 'd60p'
                END AS gap,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN (
                SELECT i.hastatckimlik, i.kurum_id, MAX({$izd}) AS max_d
                FROM #__izlemler i WHERE yapildimi=1 AND {$izd} IS NOT NULL" . self::k('i') . "
                GROUP BY i.hastatckimlik, i.kurum_id
            ) last_iz ON last_iz.hastatckimlik = h.tckimlik AND last_iz.kurum_id = h.kurum_id
            WHERE h.pasif='0' AND h.pansuman=1" . self::k('h') . "
            GROUP BY gap";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowKeys = ['pansuman'];
        $colLabels = ['hic' => 'Hiç izlem', 'd30' => '0–30 gün', 'd60' => '31–60 gün', 'd60p' => '60+ gün'];
        $pack = StatsCrossTabMatrix::create($rowKeys, array_keys($colLabels), ['pansuman' => 'Pansuman hastaları'], $colLabels);
        foreach ($list as $r) {
            StatsCrossTabMatrix::add($pack, 'pansuman', (string) $r->gap, (int) $r->adet);
        }
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    private static function sqlKonsBransCatalogJoin(): string
    {
        return 'LEFT JOIN #__branslar b ON b.id = r.brans_id AND '
            . CatalogScopeSqlHelper::sqlPlatformCatalogOnEquals('b')
            . CatalogScopeSqlHelper::sqlBransAssignedJoin('b', 'kb');
    }

    private static function branchMonthKons(Stats $stats, int $months): array {
        $colKeys = self::monthKeys($months);
        $bounds = self::konsRandevuPeriodBounds($stats, $months);
        $bransLabel = self::konsBransLabelExpr();
        $bransJoin = self::sqlKonsBransCatalogJoin();
        $sql = "SELECT t.brans_id, MAX(t.brans_adi) AS brans_adi, t.mk, COUNT(*) AS adet
            FROM (
                SELECT r.brans_id, {$bransLabel} AS brans_adi,
                    DATE_FORMAT(r.randevu_tarihi, '%Y-%m') AS mk
                FROM #__kons_randevu r
                {$bransJoin}
                WHERE r.randevu_tarihi >= ? AND r.randevu_tarihi <= ?" . self::k('r') . "
            ) t
            GROUP BY t.brans_id, t.mk";
        $list = $stats->db->fetchObjectListPrepared($sql, [$bounds['from'], $bounds['to']]) ?: [];
        /** @var array<string, array<string, int>> $cellCounts */
        $cellCounts = [];
        $rowTotals = [];
        $rowLabels = [];
        foreach ($list as $r) {
            $mk = (string) ($r->mk ?? '');
            if (!in_array($mk, $colKeys, true)) {
                continue;
            }
            $rowKey = 'b' . (int) ($r->brans_id ?? 0);
            $rowLabels[$rowKey] = trim((string) ($r->brans_adi ?? '')) !== ''
                ? (string) $r->brans_adi
                : 'Belirtilmemiş';
            $adet = (int) ($r->adet ?? 0);
            $cellCounts[$rowKey][$mk] = ($cellCounts[$rowKey][$mk] ?? 0) + $adet;
            $rowTotals[$rowKey] = ($rowTotals[$rowKey] ?? 0) + $adet;
        }
        if ($rowTotals === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], array_combine($colKeys, $colKeys) ?: []);
            $empty['period_type'] = 'randevu';

            return $empty;
        }
        arsort($rowTotals);
        $rowKeys = array_keys($rowTotals);
        $pack = StatsCrossTabMatrix::create(
            $rowKeys,
            $colKeys,
            $rowLabels,
            array_combine($colKeys, $colKeys) ?: []
        );
        foreach ($cellCounts as $rowKey => $byMonth) {
            foreach ($byMonth as $mk => $adet) {
                StatsCrossTabMatrix::add($pack, $rowKey, $mk, $adet);
            }
        }
        $pack['period_type'] = 'randevu';
        $pack['cell_unit'] = 'randevu';

        return $pack;
    }

    private static function branchZamanKons(Stats $stats, int $months): array {
        $bounds = self::konsRandevuPeriodBounds($stats, $months);
        $slotExpr = ZamanDilimiHelper::sqlSlotKeyCaseExpr('r.zaman');
        $bransLabel = self::konsBransLabelExpr();
        $bransJoin = self::sqlKonsBransCatalogJoin();
        $sql = "SELECT t.brans_id, MAX(t.brans_adi) AS brans_adi, t.slot, COUNT(*) AS adet
            FROM (
                SELECT r.brans_id, {$bransLabel} AS brans_adi, {$slotExpr} AS slot
                FROM #__kons_randevu r
                {$bransJoin}
                WHERE r.randevu_tarihi >= ? AND r.randevu_tarihi <= ?" . self::k('r') . "
            ) t
            GROUP BY t.brans_id, t.slot";
        $list = $stats->db->fetchObjectListPrepared($sql, [$bounds['from'], $bounds['to']]) ?: [];
        $rowTotals = [];
        $rowLabels = [];
        /** @var array<string, array<string, int>> $cellCounts */
        $cellCounts = [];
        $colKeys = ZamanDilimiHelper::slotKeysWithOther();
        $colLabels = ZamanDilimiHelper::slotKeyLabels();
        foreach ($list as $r) {
            $rowKey = 'b' . (int) ($r->brans_id ?? 0);
            $rowLabels[$rowKey] = trim((string) ($r->brans_adi ?? '')) !== ''
                ? (string) $r->brans_adi
                : 'Belirtilmemiş';
            $slot = (string) ($r->slot ?? 'diger');
            if (!isset($colLabels[$slot])) {
                $slot = 'diger';
            }
            $adet = (int) ($r->adet ?? 0);
            $cellCounts[$rowKey][$slot] = ($cellCounts[$rowKey][$slot] ?? 0) + $adet;
            $rowTotals[$rowKey] = ($rowTotals[$rowKey] ?? 0) + $adet;
        }
        if ($rowTotals === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], $colLabels);
            $empty['period_type'] = 'randevu';

            return $empty;
        }
        arsort($rowTotals);
        $rowKeys = array_keys($rowTotals);
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, $colLabels);
        foreach ($cellCounts as $rowKey => $bySlot) {
            foreach ($bySlot as $slot => $adet) {
                StatsCrossTabMatrix::add($pack, $rowKey, $slot, $adet);
            }
        }
        $pack['period_type'] = 'randevu';
        $pack['cell_unit'] = 'randevu';

        return $pack;
    }

    /**
     * @return array{from: string, to: string}
     */
    private static function konsRandevuPeriodBounds(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    private static function konsBransLabelExpr(string $r = 'r', string $b = 'b'): string {
        return "COALESCE(NULLIF(TRIM({$b}.bransadi), ''), CONCAT('Branş #', {$r}.brans_id), 'Belirtilmemiş')";
    }

    private static function monthAttendKons(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $to = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');
                        $sql = "SELECT DATE_FORMAT(r.randevu_tarihi,'%Y-%m') AS mk,
                CASE
                    WHEN r.hasta_geldi = 1 THEN 'geldi'
                    WHEN r.hasta_geldi = 0 THEN 'gelmedi'
                    ELSE 'belirsiz'
                END AS st,
                COUNT(*) AS adet
            FROM #__kons_randevu r
            WHERE r.randevu_tarihi >= ? AND r.randevu_tarihi <= ?" . self::k('r') . "
            GROUP BY mk, st";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from, $to]) ?: [];
        $colLabels = ['geldi' => 'Geldi', 'gelmedi' => 'Gelmedi', 'belirsiz' => 'Belirtilmedi'];
        $pack = StatsCrossTabMatrix::fromSqlRows($list, 'mk', 'st', 'adet', null, static fn ($ck) => $colLabels[$ck] ?? $ck);
        $pack['col_labels'] = $colLabels;
        $pack['period_type'] = 'randevu';
        $pack['cell_unit'] = 'randevu';

        return $pack;
    }

    private static function exitReasonYear(Stats $stats): array {
        $pt = $stats->sqlPasifTarihiExpr('h');
        $kayit = $stats->sqlKayitTarihiExpr('h');
        $minYear = (int) date('Y') - 6;
        $yilExpr = "COALESCE(YEAR({$pt}), YEAR({$kayit}))";
        $nedenExpr = "IFNULL(NULLIF(TRIM(h.pasifnedeni), ''), 'belirtilmemiş')";
        $sql = "SELECT {$nedenExpr} AS neden_kod, {$yilExpr} AS yil, COUNT(*) AS adet
            FROM #__hastalar h
            WHERE CAST(h.pasif AS SIGNED) = 1
              AND {$yilExpr} IS NOT NULL
              AND {$yilExpr} >= {$minYear}" . self::k('h') . "
            GROUP BY neden_kod, yil";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowTotals = [];
        $colKeys = [];
        for ($y = $minYear; $y <= (int) date('Y'); $y++) {
            $colKeys[] = (string) $y;
        }
        $pairs = [];
        foreach ($list as $r) {
            $code = self::exitReasonCodeFromRow($r->neden_kod ?? null);
            $yil = (string) (int) ($r->yil ?? 0);
            if ($yil === '0' || !in_array($yil, $colKeys, true)) {
                continue;
            }
            $adet = (int) ($r->adet ?? 0);
            $pairs[] = [$code, $yil, $adet];
            $rowTotals[$code] = ($rowTotals[$code] ?? 0) + $adet;
        }
        if ($pairs === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], array_combine($colKeys, $colKeys) ?: []);

            return $empty;
        }
        $rowKeys = self::exitReasonTopRowKeys($rowTotals, 10);
        $rowLabels = [];
        foreach ($rowKeys as $rk) {
            $rowLabels[$rk] = self::exitReasonRowLabel($rk);
        }
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, array_combine($colKeys, $colKeys) ?: []);
        foreach ($pairs as [$code, $yil, $adet]) {
            $rk = in_array($code, $rowKeys, true) ? $code : '_diger';
            StatsCrossTabMatrix::add($pack, $rk, $yil, $adet);
        }
        $pack = self::finalizeExitReasonPack($pack);
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    private static function exitReasonTenure(Stats $stats): array {
        $kayit = $stats->sqlKayitTarihiExpr('h');
        $pt = $stats->sqlPasifTarihiExpr('h');
        $nedenExpr = "IFNULL(NULLIF(TRIM(h.pasifnedeni), ''), 'belirtilmemiş')";
        $sql = "SELECT {$nedenExpr} AS neden_kod,
                CASE
                    WHEN {$kayit} IS NULL OR {$pt} IS NULL THEN 't0'
                    WHEN DATEDIFF({$pt}, {$kayit}) < 183 THEN 't6'
                    WHEN DATEDIFF({$pt}, {$kayit}) < 365 THEN 't12'
                    WHEN DATEDIFF({$pt}, {$kayit}) < 1095 THEN 't36'
                    ELSE 't36p'
                END AS tg,
                COUNT(*) AS adet
            FROM #__hastalar h
            WHERE CAST(h.pasif AS SIGNED) = 1" . self::k('h') . "
            GROUP BY neden_kod, tg";
        $list = $stats->db->fetchObjectListPrepared($sql) ?: [];
        $rowTotals = [];
        $pairs = [];
        foreach ($list as $r) {
            $code = self::exitReasonCodeFromRow($r->neden_kod ?? null);
            $tg = (string) ($r->tg ?? 't0');
            $adet = (int) ($r->adet ?? 0);
            $pairs[] = [$code, $tg, $adet];
            $rowTotals[$code] = ($rowTotals[$code] ?? 0) + $adet;
        }
        $colKeys = ['t0', 't6', 't12', 't36', 't36p'];
        $colLabels = ['t0' => 'Süre bilinmiyor', 't6' => '0–6 ay', 't12' => '6–12 ay', 't36' => '1–3 yıl', 't36p' => '3+ yıl'];
        if ($pairs === []) {
            $empty = StatsCrossTabMatrix::create([], $colKeys, [], $colLabels);

            return $empty;
        }
        $rowKeys = self::exitReasonTopRowKeys($rowTotals, 10);
        $rowLabels = [];
        foreach ($rowKeys as $rk) {
            $rowLabels[$rk] = self::exitReasonRowLabel($rk);
        }
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, $colLabels);
        foreach ($pairs as [$code, $tg, $adet]) {
            $rk = in_array($code, $rowKeys, true) ? $code : '_diger';
            if (isset($pack['matrix'][$rk][$tg])) {
                StatsCrossTabMatrix::add($pack, $rk, $tg, $adet);
            }
        }
        $pack = self::finalizeExitReasonPack($pack);
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }

    /** @param array<string, mixed> $pack */
    private static function finalizeExitReasonPack(array $pack): array {
        $pack['row_keys'] = StatsCrossTabMatrix::sortRowsByTotal($pack, $pack['row_keys'] ?? []);
        $rowLabels = [];
        foreach ($pack['row_keys'] as $rk) {
            $rowLabels[(string) $rk] = self::exitReasonRowLabel($rk);
        }
        $pack['row_labels'] = $rowLabels;

        return $pack;
    }

    private static function exitReasonCodeFromRow(mixed $raw): string {
        $s = trim((string) $raw);
        if ($s === '' || $s === '0') {
            return 'belirtilmemiş';
        }
        if (ctype_digit($s)) {
            $i = (int) $s;
            if ($i >= 1 && $i <= 8) {
                return (string) $i;
            }
        }

        return $s;
    }

    /**
     * Çıkış nedeni satır anahtarları — PHP sayısal string anahtarları int’e çevirir; hepsini string tut.
     *
     * @param array<string|int, int> $rowTotals
     * @return list<string>
     */
    private static function exitReasonTopRowKeys(array $rowTotals, int $limit = 10): array {
        $rowKeys = array_map(
            static fn ($k): string => (string) $k,
            StatsCrossTabMatrix::topKeys($rowTotals, $limit)
        );
        if (count($rowTotals) > count($rowKeys) && !in_array('_diger', $rowKeys, true)) {
            $rowKeys[] = '_diger';
        }

        return $rowKeys;
    }

    private static function exitReasonRowLabel(string|int $rowKey): string {
        $rowKey = (string) $rowKey;
        if ($rowKey === '_diger') {
            return 'Diğer nedenler';
        }
        if ($rowKey === 'belirtilmemiş') {
            return 'Belirtilmemiş';
        }
        if (ctype_digit($rowKey)) {
            $i = (int) $rowKey;
            if ($i >= 1 && $i <= 8) {
                return PatientCareHelper::pasifDosyaNedeniLabelByCode($i);
            }
        }

        return 'Tanımsız (' . $rowKey . ')';
    }

    private static function exitMonthIlce(Stats $stats, int $months): array {
        $from = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $pt = $stats->sqlPasifTarihiExpr('h');
        $kayit = $stats->sqlKayitTarihiExpr('h');
        $refDate = "COALESCE({$pt}, {$kayit})";
                $sql = "SELECT DATE_FORMAT({$refDate}, '%Y-%m') AS mk,
                IFNULL(il.adi,'Belirtilmemiş') AS ilce,
                COUNT(*) AS adet
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE CAST(h.pasif AS SIGNED) = 1 AND {$refDate} IS NOT NULL AND {$refDate} >= ?" . self::k('h') . "
            GROUP BY mk, ilce";
        $list = $stats->db->fetchObjectListPrepared($sql, [$from]) ?: [];
        $rowCounts = [];
        foreach ($list as $r) {
            $rowCounts[(string) $r->ilce] = ($rowCounts[(string) $r->ilce] ?? 0) + (int) $r->adet;
        }
        $rowKeys = StatsCrossTabMatrix::topKeys($rowCounts, 10);
        if (count($rowCounts) > count($rowKeys)) {
            $rowKeys[] = '_diger';
        }
        $rowLabels = array_combine($rowKeys, $rowKeys) ?: [];
        if (isset($rowLabels['_diger'])) {
            $rowLabels['_diger'] = 'Diğer ilçeler';
        }
        $colKeys = self::monthKeys($months);
        $pack = StatsCrossTabMatrix::create($rowKeys, $colKeys, $rowLabels, array_combine($colKeys, $colKeys) ?: []);
        foreach ($list as $r) {
            $il = (string) $r->ilce;
            if (!in_array($il, $rowKeys, true)) {
                $il = '_diger';
            }
            $mk = (string) $r->mk;
            StatsCrossTabMatrix::add($pack, $il, $mk, (int) $r->adet);
        }
        $pack['period_type'] = 'exit';
        $pack['cell_unit'] = 'hasta';

        return $pack;
    }
}
