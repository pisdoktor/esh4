<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Stats;

/**
 * İstatistik hub kartları — rapor bazlı PDF verisi.
 */
final class StatsReportPdfExports {
    private const PDF_LIST_CAP = 300;

    /**
     * @param array<string, mixed> $query $_GET
     * @return array<string, mixed>|null pdfMake payload parçaları
     */
    public static function tryExport(string $action, array $query): ?array {
        $m = new Stats();

        return match ($action) {
            'overview' => self::exportOverview($m),
            'operationsPulse' => self::exportOperationsPulse($m),
            'patientStatus' => self::exportPatientStatus($m),
            'bagimlilikDist' => self::exportLabelAdetPack($m->getBagimlilikDistributionLabeled(), 'Bağımlılık dağılımı'),
            'geoDistribution' => self::exportGeo($m),
            'kayitTenure' => self::exportLabelAdetPack($m->getKayitTenureReport(), 'Kayıt süresi dağılımı'),
            'hastalikCountDist' => self::exportLabelAdetPack($m->getHastalikCountDistribution(), 'Tanı sayısı dağılımı'),
            'guvenceDist' => self::exportGuvence($m),
            'topVisits' => self::exportTopVisits($m),
            'birthdays' => self::exportBirthdays($m),
            'followKpi' => self::exportFollowKpi($m),
            'regionalPerformance' => self::exportRegional($m),
            'yearlyFollow' => self::exportYearlyFollow($m),
            'monthlyFollowFreq' => self::exportMonthlyFollowFreq($m),
            'monthlyPool' => self::exportMonthlyPool($m),
            'kayitMonths' => self::exportKayitMonths($m, $query),
            'ayMovement' => self::exportAyMovement($m, $query),
            'exitReasons' => self::exportExitReasons($m, $query),
            'ageGenderBands' => self::exportAgeGender($m),
            'ageSummary' => self::exportAgeSummary($m),
            'anthroCoverage' => self::exportAnthro($m),
            'demographicCompleteness' => self::exportDemographicCompleteness($m),
            'clinicalProfile' => self::exportClinicalProfile($m),
            'pansumanProfile' => self::exportPansumanProfile($m),
            'barthel' => self::exportBarthel($m),
            'bmiVki' => self::exportBmiVki($m),
            'waitingPoolProfile' => self::exportWaitingPool($m),
            'fieldCoverage' => self::exportFieldCoverage($m),
            'kayitKohortAge' => self::exportKayitKohort($m),
            'guvenceAgeBands' => self::exportGuvenceAgeBands($m),
            'hastalik', 'charts' => self::exportHastalikCategories($m, $action),
            'visitStats' => self::exportVisitStats($m, $query),
            'plannedVisitStats' => self::exportPlannedVisitStats($m, $query),
            'visitProcedures' => self::exportProcedures($m, $query),
            'visitPersonnel' => self::exportPersonnel($m, $query),
            'visitConsultationMonthly' => self::exportConsultationMonthly($m, $query),
            'randevuKayitGap' => self::exportRandevuKayitGap($m, $query),
            'randevuTakvim' => self::exportRandevuTakvim($m, $query),
            'dataHealth' => self::exportDataHealth($m),
            'chronologyIssues' => self::exportChronology($m),
            'workload' => self::exportWorkload($m),
            'birIzlemliler' => self::exportBirIzlemliler($m, $query),
            'specialDevices' => self::exportSpecialDevices($m),
            'supplyReports' => self::exportSupplyReports($m, $query),
            'sondaChanges' => self::exportSondaChanges($m, $query),
            'eraporList' => self::exportEraporList($m, $query),
            'eraporHastaUyum' => self::exportEraporHastaUyum($m),
            default => null,
        };
    }

    private static function exportOverview(Stats $m): array {
        $summary = $m->getGeneralSummary() ?: (object) ['toplam' => 0, 'aktif' => 0, 'pasif' => 0, 'erkek' => 0, 'kadin' => 0];
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Toplam kayıt' => (string) (int) ($summary->toplam ?? 0),
            'Aktif' => (string) (int) ($summary->aktif ?? 0),
            'Pasif' => (string) (int) ($summary->pasif ?? 0),
            'Erkek' => (string) (int) ($summary->erkek ?? 0),
            'Kadın' => (string) (int) ($summary->kadin ?? 0),
        ]);
        foreach ($m->getMahalleStats() ?: [] as $r) {
            $rows[] = [
                trim((string) ($r->ilce ?? '') . ' / ' . (string) ($r->mahalle ?? '')),
                (string) (int) ($r->erkek ?? 0),
                (string) (int) ($r->kadin ?? 0),
                (string) (int) ($r->toplam ?? 0),
            ];
        }
        foreach ($m->getKayitYiliStats() ?: [] as $r) {
            $rows[] = ['Kayıt yılı ' . (string) ($r->yil ?? ''), (string) (int) ($r->adet ?? 0), '', ''];
        }

        return StatsReportPdfFormatHelper::payload(
            'Genel özet',
            ['Gösterge / mahalle / yıl', 'Değer 1', 'Değer 2', 'Değer 3'],
            $rows,
            'Özet kartlar, mahalle ve yıllık kayıt dağılımı',
            StatsReportPdfFormatHelper::filename('overview'),
            ['*', 55, 55, 55]
        );
    }

    private static function exportOperationsPulse(Stats $m): array {
        $trend = $m->getVisitTrendStats();
        $erapor = $m->getEraporPoolStats();
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Son 7 gün izlem' => (string) (int) ($trend->gun7 ?? 0),
            'Son 30 gün izlem' => (string) (int) ($trend->gun30 ?? 0),
            'Bu ay izlem' => (string) (int) ($trend->bu_ay ?? 0),
            'Bu ay bekleyen izlem' => (string) (int) $m->getVisitPendingThisMonth(),
            'Pansuman (aktif)' => (string) (int) $m->getPansumanActiveCount(),
            'Açık planlı izlem' => (string) (int) $m->getPlannedOpenCount(),
            'Bekleyen hasta (-3)' => (string) (int) $m->getWaitingPatientCount(),
            'Bu yıl tamamlanan izlem' => (string) (int) $m->getCompletedVisitsThisYear(),
            'e-Rapor havuzu' => (string) (int) ($erapor->toplam ?? $erapor->adet ?? 0),
        ]);
        foreach ($m->getBagimlilikActiveBreakdown() as $r) {
            $rows[] = ['Bağımlılık ' . Stats::bagimlilikLabel((string) ($r->kod ?? '')), (string) (int) ($r->adet ?? 0)];
        }
        foreach ($m->getIlceActiveRanking(25) as $r) {
            $rows[] = ['İlçe: ' . (string) ($r->ilce_adi ?? ''), (string) (int) ($r->adet ?? 0)];
        }
        foreach ($m->getEraporBransDistribution() as $r) {
            $rows[] = ['Branş: ' . (string) ($r->brans ?? $r->label ?? ''), (string) (int) ($r->adet ?? 0)];
        }

        return StatsReportPdfFormatHelper::payload(
            'Operasyonel nabız',
            ['Gösterge', 'Adet'],
            $rows,
            'Anlık operasyon göstergeleri',
            StatsReportPdfFormatHelper::filename('operationsPulse')
        );
    }

    /** @param array<string, mixed> $pack */
    private static function exportLabelAdetPack(array $pack, string $title): array {
        $t = StatsReportPdfFormatHelper::labelAdetPack($pack);

        return StatsReportPdfFormatHelper::payload(
            $title,
            $t['headers'],
            $t['rows'],
            $title,
            StatsReportPdfFormatHelper::filename(preg_replace('/\s+/', '', $title) ?: 'rapor')
        );
    }

    private static function exportPatientStatus(Stats $m): array {
        $rows = [];
        foreach ($m->getPatientStatusCounts() as $r) {
            $rows[] = [(string) ($r->pasif ?? ''), (string) (int) ($r->adet ?? 0)];
        }

        return StatsReportPdfFormatHelper::payload(
            'Hasta durum dağılımı',
            ['Pasif kodu', 'Adet'],
            $rows,
            'Pasif koduna göre hasta sayıları',
            StatsReportPdfFormatHelper::filename('patientStatus')
        );
    }

    private static function exportGeo(Stats $m): array {
        $report = $m->getGeoDistributionReport(30);
        $rows = [['İlçe sıralaması', 'Adet']];
        foreach ($report['ilce'] ?? [] as $r) {
            $rows[] = [(string) ($r->ilce_adi ?? ''), (string) (int) ($r->adet ?? 0)];
        }
        $rows[] = ['', ''];
        $rows[] = ['Mahalle (yoğun)', 'Adet'];
        foreach ($report['mahalle'] ?? [] as $r) {
            $rows[] = [
                (string) ($r->mahalle_adi ?? '') . ' / ' . (string) ($r->ilce_adi ?? ''),
                (string) (int) ($r->adet ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Coğrafi dağılım',
            ['Bölüm', 'Adet'],
            $rows,
            'Aktif hasta: ' . (int) ($report['aktif_toplam'] ?? 0),
            StatsReportPdfFormatHelper::filename('geoDistribution')
        );
    }

    private static function exportGuvence(Stats $m): array {
        $rows = [];
        foreach ($m->getGuvenceActiveDistribution() as $r) {
            $rows[] = [
                (string) ($r->guvence_adi ?? 'Belirtilmemiş'),
                (string) (int) ($r->hastasayisi ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Güvence türleri',
            ['Güvence', 'Adet'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('guvenceDist')
        );
    }

    private static function exportTopVisits(Stats $m): array {
        $rows = [];
        foreach ($m->getTopVisitedPatients(10) as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                (string) (int) ($r->izlem_sayisi ?? $r->adet ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'En yoğun takip',
            ['Hasta', 'TC', 'İzlem adedi'],
            $rows,
            'En çok izlenen aktif hastalar',
            StatsReportPdfFormatHelper::filename('topVisits')
        );
    }

    private static function exportBirthdays(Stats $m): array {
        $rows = [];
        foreach (array_slice($m->getTodaysBirthdays() ?: [], 0, self::PDF_LIST_CAP) as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                DateHelper::toTrOrEmpty($r->dogumtarihi ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Bugün doğum günü',
            ['Hasta', 'TC', 'Doğum tarihi'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('birthdays')
        );
    }

    private static function exportFollowKpi(Stats $m): array {
        $k = $m->getFollowEfficiencyKpi();

        return StatsReportPdfFormatHelper::payload(
            'İzlem KPI',
            ['Gösterge', 'Değer'],
            StatsReportPdfFormatHelper::keyValueRows((array) $k),
            'Son 3 ay izlenen aktif hasta oranı',
            StatsReportPdfFormatHelper::filename('followKpi')
        );
    }

    private static function exportRegional(Stats $m): array {
        $rows = [];
        foreach ($m->getRegionalNeighborhoodVisitPerformance(3) as $r) {
            $rows[] = [
                (string) ($r->ilce_adi ?? ''),
                (string) ($r->mahalle_adi ?? ''),
                (string) (int) ($r->toplam_aktif ?? $r->aktif ?? 0),
                (string) (int) ($r->izlenen ?? 0),
                isset($r->oran) ? (string) $r->oran : '',
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Bölgesel performans',
            ['İlçe', 'Mahalle', 'Aktif', 'İzlenen (3 ay)', 'Oran %'],
            $rows,
            'Son 3 ay',
            StatsReportPdfFormatHelper::filename('regionalPerformance')
        );
    }

    private static function exportYearlyFollow(Stats $m): array {
        $y = $m->getYearlyFollowCoverage();

        return StatsReportPdfFormatHelper::payload(
            'Yıllık izlem',
            ['Gösterge', 'Değer'],
            StatsReportPdfFormatHelper::keyValueRows((array) $y),
            'Yıllık kapsama',
            StatsReportPdfFormatHelper::filename('yearlyFollow')
        );
    }

    private static function exportMonthlyFollowFreq(Stats $m): array {
        $monthly = $m->getMonthlyFollowUpStats();
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Benzersiz hasta (bu ay)' => (string) (int) ($monthly->toplamhasta ?? 0),
            'Tamamlanan izlem (bu ay)' => (string) (int) ($monthly->toplamizlem ?? 0),
        ]);
        foreach ($m->getMonthlyFollowUpStatsLastMonths(6) as $r) {
            $rows[] = [
                (string) ($r->donem ?? $r->ay ?? ''),
                (string) (int) ($r->toplamhasta ?? 0),
                (string) (int) ($r->toplamizlem ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Aylık izlem sıklığı',
            ['Dönem', 'Hasta', 'İzlem'],
            $rows,
            'Cari ay + son 6 ay',
            StatsReportPdfFormatHelper::filename('monthlyFollowFreq')
        );
    }

    private static function exportMonthlyPool(Stats $m): array {
        $age = $m->getMonthlyFollowUpAgeGroups();
        $rows = StatsReportPdfFormatHelper::keyValueRows((array) $age);

        return StatsReportPdfFormatHelper::payload(
            'Bu ay izlenen — yaş',
            ['Gösterge', 'Adet'],
            $rows,
            'Cari ay izlenen hastaların yaş grupları',
            StatsReportPdfFormatHelper::filename('monthlyPool')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportKayitMonths(Stats $m, array $query): array {
        $limit = isset($query['limit']) ? (int) $query['limit'] : 0;
        $items = $m->getKayitAyiStats() ?: [];
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }
        $turkce = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
        $rows = [];
        foreach ($items as $r) {
            $yil = (int) ($r->kayityili ?? 0);
            $ay = (int) ($r->kayitay ?? 0);
            $rows[] = [
                (string) $yil,
                $turkce[$ay] ?? (string) $ay,
                (string) (int) ($r->erkek_sayisi ?? 0),
                (string) (int) ($r->kadin_sayisi ?? 0),
                (string) (int) ($r->toplam_sayi ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Kayıt ayları',
            ['Yıl', 'Ay', 'Erkek', 'Kadın', 'Toplam'],
            $rows,
            'Aktif hastalar — kayıt tarihi',
            StatsReportPdfFormatHelper::filename('kayitMonths')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportAyMovement(Stats $m, array $query): array {
        $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
        $month = isset($query['month']) ? (int) $query['month'] : (int) date('n');
        $g = $m->getGeneralStats($year, $month);

        return StatsReportPdfFormatHelper::payload(
            'Ay hareketi',
            ['Gösterge', 'Değer'],
            StatsReportPdfFormatHelper::keyValueRows((array) $g),
            $year . '-' . sprintf('%02d', $month),
            StatsReportPdfFormatHelper::filename('ayMovement')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportExitReasons(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of this month');
        $items = [];
        foreach ($m->getExitReasons($from, $to) ?: [] as $r) {
            $items[] = ['label' => (string) ($r->pasifnedeni ?? ''), 'adet' => (int) ($r->sayi ?? 0)];
        }
        $rows = StatsReportPdfFormatHelper::rowsFromItems($items, ['label', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Takipten çıkarma nedenleri',
            ['Neden', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('exitReasons')
        );
    }

    private static function exportAgeGender(Stats $m): array {
        $raw = $m->getAgeGroups() ?: [];
        $rows = [];
        foreach ($raw as $r) {
            $cinsiyet = ($r->cinsiyet ?? '') === '1' || ($r->cinsiyet ?? '') === 'K' ? 'Kadın' : 'Erkek';
            $line = [$cinsiyet];
            foreach ((array) $r as $k => $v) {
                if ($k === 'cinsiyet') {
                    continue;
                }
                $line[] = (string) (int) $v;
            }
            $rows[] = $line;
        }
        $headers = array_merge(['Cinsiyet'], array_keys(array_diff_key((array) ($raw[0] ?? (object) []), ['cinsiyet' => 1])));

        return StatsReportPdfFormatHelper::payload(
            'Yaş × cinsiyet',
            $headers !== [] ? $headers : ['Cinsiyet', 'Bantlar'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('ageGenderBands')
        );
    }

    private static function exportAgeSummary(Stats $m): array {
        $report = $m->getAgeSummaryReport();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['bands'] ?? $report['rows'] ?? [], ['label', 'adet', 'pct']);

        return StatsReportPdfFormatHelper::payload(
            'Yaş özeti',
            ['Bant', 'Adet', '%'],
            $rows,
            'Ortalama: ' . ($report['ortalama'] ?? '—'),
            StatsReportPdfFormatHelper::filename('ageSummary')
        );
    }

    private static function exportAnthro(Stats $m): array {
        $r = $m->getAnthropometryCoverageReport();

        return StatsReportPdfFormatHelper::payload(
            'Antropometri kapsamı',
            ['Gösterge', 'Değer'],
            StatsReportPdfFormatHelper::keyValueRows([
                'Aktif hasta' => (string) (int) ($r['aktif'] ?? 0),
                'Boy kayıtlı' => (string) (int) ($r['has_boy'] ?? 0),
                'Kilo kayıtlı' => (string) (int) ($r['has_kilo'] ?? 0),
                'Boy+kilo' => (string) (int) ($r['has_both'] ?? 0),
                'VKİ hesaplanabilir' => (string) (int) ($r['computable_bmi'] ?? 0),
            ]),
            'Boy / kilo / VKİ',
            StatsReportPdfFormatHelper::filename('anthroCoverage')
        );
    }

    private static function exportDemographicCompleteness(Stats $m): array {
        $report = $m->getDemographicCompletenessReport();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['rows'] ?? [], ['label', 'adet', 'pct']);

        return StatsReportPdfFormatHelper::payload(
            'Demografik tamamlama',
            ['Alan', 'Eksik', '%'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('demographicCompleteness')
        );
    }

    private static function exportClinicalProfile(Stats $m): array {
        $report = $m->getClinicalProfileReport();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['flags'] ?? $report['rows'] ?? [], ['label', 'adet', 'pct']);

        return StatsReportPdfFormatHelper::payload(
            'Klinik profil',
            ['Özellik', 'Adet', '%'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('clinicalProfile')
        );
    }

    private static function exportPansumanProfile(Stats $m): array {
        $report = $m->getPansumanProfile();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['by_zaman'] ?? [], ['label', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Pansuman profili',
            ['Zaman dilimi', 'Adet'],
            $rows,
            'Pansuman hastaları',
            StatsReportPdfFormatHelper::filename('pansumanProfile')
        );
    }

    private static function exportBarthel(Stats $m): array {
        $b = $m->getBarthelDistribution();
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Toplam hasta' => (string) (int) ($b->toplam_hasta ?? 0),
            'Ortalama skor' => (string) ($b->ortalama_skor ?? 0),
            '0–20' => (string) (int) ($b->g_0_20 ?? 0),
            '21–61' => (string) (int) ($b->g_21_61 ?? 0),
            '62–90' => (string) (int) ($b->g_62_90 ?? 0),
            '91–99' => (string) (int) ($b->g_91_99 ?? 0),
            '100' => (string) (int) ($b->g_100 ?? 0),
        ]);

        return StatsReportPdfFormatHelper::payload(
            'Barthel skoru',
            ['Grup', 'Adet'],
            $rows,
            'Fonksiyonel bağımsızlık',
            StatsReportPdfFormatHelper::filename('barthel')
        );
    }

    private static function exportBmiVki(Stats $m): array {
        $r = $m->getBmiVkiReport();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($r['bands'] ?? [], ['label', 'adet', 'pct']);

        return StatsReportPdfFormatHelper::payload(
            'VKİ dağılımı',
            ['VKİ grubu', 'Adet', '%'],
            $rows,
            'Boy ve kilo kayıtlı aktif hastalar',
            StatsReportPdfFormatHelper::filename('bmiVki')
        );
    }

    private static function exportWaitingPool(Stats $m): array {
        $report = $m->getWaitingPoolProfile();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['ilce'] ?? [], ['label', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Bekleyen havuz',
            ['Kırılım', 'Adet'],
            $rows,
            'Pasif -3 hastalar',
            StatsReportPdfFormatHelper::filename('waitingPoolProfile')
        );
    }

    private static function exportFieldCoverage(Stats $m): array {
        $report = $m->getDemographicFieldCoverageReport();
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['rows'] ?? [], ['label', 'adet', 'pct']);

        return StatsReportPdfFormatHelper::payload(
            'Alan doluluk',
            ['Alan', 'Dolu', '%'],
            $rows,
            'Telefon, fotoğraf, anne/baba adı',
            StatsReportPdfFormatHelper::filename('fieldCoverage')
        );
    }

    private static function exportKayitKohort(Stats $m): array {
        $report = $m->getKayitKohortAgeReport(10);
        $labels = $report['band_labels'] ?? [];
        $rows = [];
        foreach ($report['years'] ?? [] as $year) {
            foreach ($report['band_keys'] ?? [] as $band) {
                $cnt = (int) ($report['matrix'][$year][$band] ?? 0);
                if ($cnt > 0) {
                    $rows[] = [
                        (string) $year,
                        (string) ($labels[$band] ?? $band),
                        (string) $cnt,
                    ];
                }
            }
        }

        return StatsReportPdfFormatHelper::payload(
            'Kayıt kohortu × yaş',
            ['Kayıt yılı', 'Yaş bandı', 'Adet'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('kayitKohortAge')
        );
    }

    private static function exportGuvenceAgeBands(Stats $m): array {
        $report = $m->getGuvenceAgeBandsReport();
        $labels = $report['band_labels'] ?? [];
        $rows = [];
        foreach ($report['guvences'] ?? [] as $guvence) {
            foreach ($report['band_keys'] ?? [] as $band) {
                $cnt = (int) ($report['matrix'][$guvence][$band] ?? 0);
                if ($cnt > 0) {
                    $rows[] = [
                        (string) $guvence,
                        (string) ($labels[$band] ?? $band),
                        (string) $cnt,
                    ];
                }
            }
        }

        return StatsReportPdfFormatHelper::payload(
            'Güvence × yaş',
            ['Güvence', 'Yaş bandı', 'Adet'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('guvenceAgeBands')
        );
    }

    private static function exportHastalikCategories(Stats $m, string $action): array {
        if ($action === 'charts') {
            $pack = $m->getHastalikDiagnosisDistribution();
            $totalAktif = (int) ($pack['total_aktif'] ?? 0);
            $rows = [];
            foreach (array_slice($m->getHastalikStats() ?: [], 0, 15) as $h) {
                $sayi = (int) ($h->sayi ?? 0);
                $oran = $totalAktif > 0 ? (string) round(100.0 * $sayi / $totalAktif, 2) : '0';
                $rows[] = [
                    (string) ($h->icd ?? ''),
                    (string) ($h->hastalikadi ?? $h->etiket ?? ''),
                    (string) $sayi,
                    $oran,
                ];
            }

            return StatsReportPdfFormatHelper::payload(
                'Hastalık istatistiği',
                ['ICD', 'Tanı', 'Hasta', 'Oran %'],
                $rows,
                'En sık 15 tanı — aktif hasta',
                StatsReportPdfFormatHelper::filename($action)
            );
        }

        $pack = $m->getHastalikDiagnosisDistribution();
        $rows = [];
        foreach ($pack['categories'] ?? [] as $cat) {
            $rows[] = [(string) ($cat['name'] ?? $cat['label'] ?? ''), (string) (int) ($cat['adet'] ?? 0)];
        }

        return StatsReportPdfFormatHelper::payload(
            'Hastalık dağılımı',
            ['Kategori / tanı', 'Adet'],
            $rows,
            'Aktif: ' . (int) ($pack['total_aktif'] ?? 0),
            StatsReportPdfFormatHelper::filename($action)
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportVisitStats(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $m->getVisitStatsReport($from, $to);

        return self::flattenVisitStyleReport($report, 'Yapılan izlem istatistikleri', 'visitStats', $from, $to);
    }

    /** @param array<string, mixed> $query */
    private static function exportPlannedVisitStats(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $m->getPlannedVisitStatsReport($from, $to);
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Toplam plan' => (string) (int) ($report['summary']->toplam ?? 0),
            'Tamamlanan' => (string) (int) ($report['summary']->tamamlanan ?? 0),
            'Bekleyen' => (string) (int) ($report['summary']->bekleyen ?? 0),
        ]);
        foreach ($report['by_month'] ?? [] as $r) {
            $rows[] = ['Ay ' . ($r->donem ?? ''), (string) (int) ($r->adet ?? 0)];
        }

        return StatsReportPdfFormatHelper::payload(
            'Planlı izlem istatistikleri',
            ['Kırılım', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('plannedVisitStats')
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function flattenVisitStyleReport(array $report, string $title, string $slug, string $from, string $to): array {
        $s = $report['summary'] ?? (object) [];
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Toplam izlem' => (string) (int) ($s->toplam ?? 0),
            'Yapılan' => (string) (int) ($s->yapilan ?? 0),
            'Yapılmayan' => (string) (int) ($s->yapilmayan ?? 0),
            'Benzersiz hasta' => (string) (int) ($s->benzersiz_hasta ?? 0),
            'Tamamlanma %' => (string) ($s->tamamlanma_orani ?? '—'),
        ]);
        foreach (['by_month' => 'Ay', 'by_zaman' => 'Zaman', 'by_arac' => 'Araç', 'by_neden' => 'Neden'] as $key => $label) {
            foreach ($report[$key] ?? [] as $r) {
                $rows[] = [
                    $label . ': ' . (string) ($r->label ?? $r->zaman_kod ?? $r->ay ?? ''),
                    (string) (int) ($r->toplam ?? 0),
                    (string) (int) ($r->yapilan ?? 0),
                ];
            }
        }

        return StatsReportPdfFormatHelper::payload(
            $title,
            ['Kırılım', 'Toplam', 'Yapılan'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename($slug)
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportProcedures(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $rows = StatsReportPdfFormatHelper::rowsFromItems($m->getProcedureCountsFromVisits($from, $to), ['islemadi', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Yapılan işlemler',
            ['İşlem', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('visitProcedures')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportPersonnel(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $rows = StatsReportPdfFormatHelper::rowsFromItems($m->getPersonnelCountsFromVisits($from, $to), ['name', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'İzlemi yapan personel',
            ['Personel', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('visitPersonnel')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportConsultationMonthly(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $rows = [];
        foreach ($m->getVisitConsultationMonthlyBreakdown($from, $to) as $r) {
            $rows[] = [
                (string) ($r->donem ?? ''),
                (string) ($r->brans ?? ''),
                (string) (int) ($r->adet ?? 0),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Konsültasyon (aylık)',
            ['Ay', 'Branş', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('visitConsultationMonthly')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportRandevuKayitGap(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $m->getHastaKayitRandevuGapReport($from, $to);
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['histogram'] ?? [], ['label', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Kayıt – randevu gün farkı',
            ['Gün farkı', 'Adet', '%'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('randevuKayitGap')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportRandevuTakvim(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $m->getRandevuTakvimReport('brans', $from, $to);
        $rows = StatsReportPdfFormatHelper::rowsFromItems($report['by_brans'] ?? [], ['brans_adi', 'adet']);

        return StatsReportPdfFormatHelper::payload(
            'Randevu takvimi',
            ['Kırılım', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('randevuTakvim')
        );
    }

    private static function exportDataHealth(Stats $m): array {
        $snap = $m->getDataHealthSnapshot();
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Adres toplam sorun' => (string) (int) ($snap->adres_toplam ?? 0),
            'Hasta dosya toplam' => (string) (int) ($snap->hasta_dosya_toplam ?? 0),
            'Kritik toplam' => (string) (int) ($snap->toplam_kritik ?? 0),
            'İlçe yok' => (string) (int) ($snap->ilce_yok ?? 0),
            'Mahalle yok' => (string) (int) ($snap->mahalle_yok ?? 0),
            'Hatalı TC' => (string) (int) ($snap->hatali_tc ?? 0),
            'Hiç izlenmemiş' => (string) (int) ($snap->hic_izlenmemis ?? 0),
        ]);

        return StatsReportPdfFormatHelper::payload(
            'Veri sağlığı',
            ['Gösterge', 'Adet'],
            $rows,
            'Aktif hasta dosyaları',
            StatsReportPdfFormatHelper::filename('dataHealth')
        );
    }

    private static function exportChronology(Stats $m): array {
        $rows = [];
        foreach (array_slice($m->getChronologyRegistrationVsFirstVisit() ?: [], 0, self::PDF_LIST_CAP) as $r) {
            $rows[] = [
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                DateHelper::toTrOrEmpty($r->kayittarihi ?? ''),
                DateHelper::toTrOrEmpty($r->ilkizlem ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Kronoloji sorunları',
            ['TC', 'Hasta', 'Kayıt', 'İlk izlem'],
            $rows,
            'Kayıt / ilk izlem uyumsuzluğu',
            StatsReportPdfFormatHelper::filename('chronologyIssues')
        );
    }

    private static function exportWorkload(Stats $m): array {
        $counts = $m->getWorkloadContinuityGroupCounts();
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'KRİTİK' => (string) $counts['KRITIK'],
            'KRONİK' => (string) $counts['KRONIK'],
            'STANDART' => (string) $counts['STANDART'],
        ]);
        foreach (array_slice($m->getWorkloadContinuityRows(), 0, self::PDF_LIST_CAP) as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                (string) ($r->hizmet_durumu ?? ''),
                (string) (int) ($r->hizmet_suresi_gun ?? 0),
                DateHelper::toTrOrEmpty($r->sonizlemtarihi ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Hizmet yükü',
            ['Hasta / grup', 'Durum / gün', 'Gün', 'Son izlem'],
            $rows,
            'Aktif hastalar — süreklilik',
            StatsReportPdfFormatHelper::filename('workload')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportBirIzlemliler(Stats $m, array $query): array {
        $ilce = isset($query['ilce']) ? trim((string) $query['ilce']) : '';
        $mahalle = isset($query['mahalle']) ? trim((string) $query['mahalle']) : '';
        $ilceF = ($ilce !== '' && $ilce !== '0') ? $ilce : null;
        $mahalleF = ($mahalle !== '' && $mahalle !== '0') ? $mahalle : null;
        $tcs = $m->getBirIzlemMatchingTckimliks($ilceF, $mahalleF);
        $slice = array_slice($tcs, 0, self::PDF_LIST_CAP);
        $rows = [];
        foreach ($m->getBirIzlemPatientRows($slice, 'ORDER BY h.isim ASC') as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                (string) ($r->mahalle_adi ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Bir izlemliler',
            ['Hasta', 'TC', 'Mahalle'],
            $rows,
            'Tek tamamlanmış izlem (max ' . self::PDF_LIST_CAP . ')',
            StatsReportPdfFormatHelper::filename('birIzlemliler')
        );
    }

    private static function exportSpecialDevices(Stats $m): array {
        $s = $m->getSpecialEquipmentSummary();
        $rows = StatsReportPdfFormatHelper::keyValueRows((array) $s);

        return StatsReportPdfFormatHelper::payload(
            'Özel cihazlar',
            ['Cihaz / gösterge', 'Adet'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('specialDevices')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportSupplyReports(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $type = ($query['type'] ?? 'bez') === 'mama' ? 'mama' : 'bez';
        $rows = $type === 'mama'
            ? $m->getMamaRaporRows($from, $to, self::PDF_LIST_CAP, 0)
            : $m->getBezRaporRows($from, $to, self::PDF_LIST_CAP, 0);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                DateHelper::toTrOrEmpty($r->bitis ?? $r->mamaraporbitis ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Bez / mama raporu',
            ['Hasta', 'TC', 'Bitiş'],
            $out,
            $type . ' — ' . DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('supplyReports')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportSondaChanges(Stats $m, array $query): array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $rows = [];
        foreach ($m->getSondaChangeRows($from, $to, self::PDF_LIST_CAP, 0) as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                DateHelper::toTrOrEmpty($r->tarih ?? $r->degisimtarihi ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'Sonda değişimleri',
            ['Hasta', 'TC', 'Tarih'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('sondaChanges')
        );
    }

    /** @param array<string, mixed> $query */
    private static function exportEraporList(Stats $m, array $query): array {
        $ilce = isset($query['ilce']) ? trim((string) $query['ilce']) : '';
        $mahalle = isset($query['mahalle']) ? trim((string) $query['mahalle']) : '';
        $ilceF = ($ilce !== '' && $ilce !== '0') ? $ilce : null;
        $mahalleF = ($mahalle !== '' && $mahalle !== '0') ? $mahalle : null;
        $rows = [];
        foreach ($m->getEraporPatientRows($ilceF, $mahalleF, self::PDF_LIST_CAP, 0) as $r) {
            $rows[] = [
                trim((string) ($r->isim ?? '') . ' ' . (string) ($r->soyisim ?? '')),
                ValidationHelper::formatTc((string) ($r->tckimlik ?? '')),
                (string) ($r->mahalle_adi ?? '') . ' / ' . (string) ($r->ilce_adi ?? ''),
            ];
        }

        return StatsReportPdfFormatHelper::payload(
            'e-Rapor işaretli hastalar',
            ['Hasta', 'TC', 'Konum'],
            $rows,
            'Max ' . self::PDF_LIST_CAP . ' kayıt',
            StatsReportPdfFormatHelper::filename('eraporList')
        );
    }

    private static function exportEraporHastaUyum(Stats $m): array {
        $snap = $m->getEraporHastaUyumSnapshot();
        $rows = StatsReportPdfFormatHelper::keyValueRows((array) $snap);

        return StatsReportPdfFormatHelper::payload(
            'e-Rapor ↔ hasta uyumu',
            ['Gösterge', 'Değer'],
            $rows,
            'Havuz / hasta eşleşme özeti',
            StatsReportPdfFormatHelper::filename('eraporHastaUyum')
        );
    }
}
