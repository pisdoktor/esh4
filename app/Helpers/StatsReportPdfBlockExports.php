<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Stats;

/**
 * İstatistik rapor sayfaları — kart blokları (grafik verisi tablo olarak).
 */
final class StatsReportPdfBlockExports {
    /** @var array<string, list<string>> */
    private const BLOCKS_BY_ACTION = [
        'visitStats' => ['grafik', 'durum', 'tablo', 'zaman', 'neden'],
        'plannedVisitStats' => ['grafik', 'durum', 'tablo', 'zaman'],
        'charts' => ['tablo', 'grafik'],
        'clinicalProfile' => ['grafik', 'isaret_grafik', 'tablo'],
        'geoDistribution' => ['grafik', 'mahalle_grafik'],
        'guvenceDist' => ['grafik'],
        'bagimlilikDist' => ['grafik'],
        'ayMovement' => ['grafik', 'hareket_grafik', 'liste'],
        'visitProcedures' => ['grafik', 'tablo'],
        'visitConsultationMonthly' => ['brans', 'istek', 'cift'],
        'waitingPoolProfile' => ['ilce', 'bagimlilik', 'randevu_zaman'],
        'bmiVki' => ['tablo', 'yas_band'],
        'randevuTakvim' => ['kons', 'uhds'],
        'kayitMonths' => ['grafik'],
        'ageSummary' => ['grafik'],
        'pansumanProfile' => ['grafik'],
    ];

    private const TURKCE_AYLAR = [
        1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz',
        7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
    ];

    /**
     * @return list<string>
     */
    public static function blocksForAction(string $action): array {
        return self::BLOCKS_BY_ACTION[trim($action)] ?? [];
    }

    public static function supports(string $action, string $block): bool {
        $action = trim($action);
        $block = trim($block);
        if ($block === '' || $action === '') {
            return false;
        }

        return in_array($block, self::blocksForAction($action), true);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|null
     */
    public static function tryExport(string $action, string $block, array $query): ?array {
        $action = trim($action);
        $block = trim($block);
        if ($action === '' || $block === '' || !self::supports($action, $block)) {
            return null;
        }

        $m = new Stats();

        return match ($action) {
            'visitStats' => self::exportVisitStatsBlock($m, $block, $query, false),
            'plannedVisitStats' => self::exportVisitStatsBlock($m, $block, $query, true),
            'charts' => self::exportChartsBlock($m, $block),
            'clinicalProfile' => self::exportClinicalProfileBlock($m, $block),
            'geoDistribution' => self::exportGeoBlock($m, $block),
            'guvenceDist' => self::exportGuvenceGrafik($m),
            'bagimlilikDist' => self::exportBagimlilikGrafik($m),
            'ayMovement' => self::exportAyMovementBlock($m, $block, $query),
            'visitProcedures' => self::exportVisitProceduresBlock($m, $block, $query),
            'visitConsultationMonthly' => self::exportConsultationBlock($m, $block, $query),
            'waitingPoolProfile' => self::exportWaitingPoolBlock($m, $block),
            'bmiVki' => self::exportBmiVkiBlock($m, $block),
            'randevuTakvim' => self::exportRandevuTakvimBlock($m, $block, $query),
            'kayitMonths' => self::exportKayitMonthsGrafik($m, $query),
            'ageSummary' => self::exportAgeSummaryGrafik($m),
            'pansumanProfile' => self::exportPansumanGrafik($m),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportVisitStatsBlock(Stats $m, string $block, array $query, bool $planned): ?array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $planned
            ? $m->getPlannedVisitStatsReport($from, $to)
            : $m->getVisitStatsReport($from, $to);
        $pageTitle = $planned ? 'Planlı izlem istatistikleri' : 'Yapılan izlem istatistikleri';
        $slug = $planned ? 'plannedVisitStats' : 'visitStats';
        $period = DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to);

        return match ($block) {
            'grafik' => self::payload(
                $pageTitle . ' — Aylık dağılım',
                $planned
                    ? ['Dönem', 'Toplam', 'Tamamlanan', 'Bekleyen']
                    : ['Dönem', 'Toplam', 'Yapıldı', 'Yapılmadı'],
                self::visitMonthRows($report['by_month'] ?? [], $planned),
                $period,
                StatsReportPdfFormatHelper::filename($slug . '_grafik')
            ),
            'durum' => self::exportVisitDurum($report, $pageTitle, $slug, $period, $planned),
            'tablo' => $planned
                ? self::exportPlannedPriority($report, $pageTitle, $slug, $period)
                : self::exportVisitArac($report, $pageTitle, $slug, $period),
            'zaman' => self::exportVisitZaman($report, $pageTitle, $slug, $period, $planned),
            'neden' => $planned ? null : self::exportVisitNeden($report, $pageTitle, $slug, $period),
            default => null,
        };
    }

    /**
     * @param list<object> $byMonth
     * @return list<list<string>>
     */
    private static function visitMonthRows(array $byMonth, bool $planned): array {
        $rows = [];
        foreach ($byMonth as $r) {
            $yil = (int) ($r->yil ?? 0);
            $ay = (int) ($r->ay ?? 0);
            $label = (self::TURKCE_AYLAR[$ay] ?? (string) $ay) . ' ' . $yil;
            if ($planned) {
                $rows[] = [
                    $label,
                    (string) (int) ($r->toplam ?? 0),
                    (string) (int) ($r->tamamlanan ?? 0),
                    (string) (int) ($r->bekleyen ?? 0),
                ];
            } else {
                $rows[] = [
                    $label,
                    (string) (int) ($r->toplam ?? 0),
                    (string) (int) ($r->yapilan ?? 0),
                    (string) (int) ($r->yapilmayan ?? 0),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function exportVisitDurum(array $report, string $pageTitle, string $slug, string $period, bool $planned): array {
        $s = $report['summary'] ?? (object) [];
        if ($planned) {
            $rows = [
                ['Toplam plan', (string) (int) ($s->toplam ?? 0)],
                ['Tamamlanan', (string) (int) ($s->tamamlanan ?? 0)],
                ['Bekleyen', (string) (int) ($s->bekleyen ?? 0)],
                ['Gecikmiş', (string) (int) ($s->gecikmis ?? 0)],
                ['Benzersiz hasta', (string) (int) ($s->benzersiz_hasta ?? 0)],
                ['Tamamlanma %', (string) ($s->tamamlanma_orani ?? '—')],
            ];
        } else {
            $rows = [
                ['Toplam izlem', (string) (int) ($s->toplam ?? 0)],
                ['Yapıldı', (string) (int) ($s->yapilan ?? 0)],
                ['Yapılmadı', (string) (int) ($s->yapilmayan ?? 0)],
                ['Benzersiz hasta', (string) (int) ($s->benzersiz_hasta ?? 0)],
                ['Tamamlanma %', (string) ($s->tamamlanma_orani ?? '—')],
            ];
        }

        return self::payload(
            $pageTitle . ' — Durum özeti',
            ['Gösterge', 'Değer'],
            $rows,
            $period,
            StatsReportPdfFormatHelper::filename($slug . '_durum')
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function exportVisitArac(array $report, string $pageTitle, string $slug, string $period): array {
        $labels = [1 => 'Araçlı izlem', 0 => 'Araçsız izlem'];
        $rows = [];
        foreach ($report['by_arac'] ?? [] as $r) {
            $k = (int) ($r->arac_kod ?? 0);
            $rows[] = [
                $labels[$k] ?? ('Araç ' . $k),
                (string) (int) ($r->toplam ?? 0),
                (string) (int) ($r->yapilan ?? 0),
                (string) (int) ($r->yapilmayan ?? 0),
            ];
        }

        return self::payload(
            $pageTitle . ' — Araç kullanımı',
            ['Kategori', 'Toplam', 'Yapıldı', 'Yapılmadı'],
            $rows,
            $period,
            StatsReportPdfFormatHelper::filename($slug . '_tablo')
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function exportPlannedPriority(array $report, string $pageTitle, string $slug, string $period): array {
        $labels = [1 => 'Normal', 2 => 'Orta (Öncelikli)', 3 => 'Yüksek (Acil)'];
        $rows = [];
        foreach ($report['by_priority'] ?? [] as $r) {
            $k = (int) ($r->oncelik_kod ?? 1);
            $rows[] = [
                $labels[$k] ?? ('Öncelik ' . $k),
                (string) (int) ($r->toplam ?? 0),
                (string) (int) ($r->tamamlanan ?? 0),
                (string) (int) ($r->bekleyen ?? 0),
            ];
        }

        return self::payload(
            $pageTitle . ' — Öncelik durumu',
            ['Öncelik', 'Toplam', 'Tamamlanan', 'Bekleyen'],
            $rows,
            $period,
            StatsReportPdfFormatHelper::filename($slug . '_tablo')
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function exportVisitZaman(array $report, string $pageTitle, string $slug, string $period, bool $planned): array {
        $rows = [];
        foreach ($report['by_zaman'] ?? [] as $z) {
            $zk = (int) ($z->zaman_kod ?? 1);
            if ($planned) {
                $rows[] = [
                    ZamanDilimiHelper::label($zk),
                    (string) (int) ($z->toplam ?? 0),
                    (string) (int) ($z->tamamlanan ?? 0),
                    (string) (int) ($z->bekleyen ?? 0),
                ];
            } else {
                $rows[] = [
                    ZamanDilimiHelper::label($zk),
                    (string) (int) ($z->toplam ?? 0),
                    (string) (int) ($z->yapilan ?? 0),
                    (string) (int) ($z->yapilmayan ?? 0),
                ];
            }
        }
        $headers = $planned
            ? ['Dilim', 'Toplam', 'Tamamlanan', 'Bekleyen']
            : ['Dilim', 'Toplam', 'Yapıldı', 'Yapılmadı'];

        return self::payload(
            $pageTitle . ' — Zaman dilimi',
            $headers,
            $rows,
            $period,
            StatsReportPdfFormatHelper::filename($slug . '_zaman')
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function exportVisitNeden(array $report, string $pageTitle, string $slug, string $period): array {
        $labels = IzlemYapilmamaNedenHelper::labels();
        $rows = [];
        foreach ($report['by_neden'] ?? [] as $r) {
            $k = (string) ($r->neden_kod ?? '');
            $rows[] = [
                $labels[$k] ?? ($k === '' ? 'Belirtilmemiş' : 'Neden ' . $k),
                (string) (int) ($r->adet ?? 0),
            ];
        }

        return self::payload(
            $pageTitle . ' — Yapılmama nedeni',
            ['Neden', 'Adet'],
            $rows,
            $period,
            StatsReportPdfFormatHelper::filename($slug . '_neden')
        );
    }

    private static function exportChartsBlock(Stats $m, string $block): ?array {
        $title = 'Hastalık istatistiği';

        return match ($block) {
            'tablo' => self::payload(
                $title . ' — Tanı kategorileri',
                ['Kategori', 'Tanı kayıtlı', 'Hasta'],
                self::hastalikCategoryRows($m),
                'Aktif hastalar',
                StatsReportPdfFormatHelper::filename('charts_tablo')
            ),
            'grafik' => self::payload(
                $title . ' — En sık tanılar',
                ['Tanı', 'Hasta'],
                self::hastalikTopRows($m, 12),
                'Üst 12 tanı (grafik verisi)',
                StatsReportPdfFormatHelper::filename('charts_grafik')
            ),
            default => null,
        };
    }

    /** @return list<list<string>> */
    private static function hastalikCategoryRows(Stats $m): array {
        $rows = [];
        foreach ($m->getHastalikCategorySummary() ?: [] as $cat) {
            $rows[] = [
                (string) ($cat->cat_name ?? ''),
                (string) (int) ($cat->tani_kayitli_sayisi ?? 0),
                (string) (int) ($cat->hasta_sayisi ?? 0),
            ];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private static function hastalikTopRows(Stats $m, int $limit): array {
        $rows = [];
        foreach (array_slice($m->getHastalikStats() ?: [], 0, $limit) as $h) {
            $rows[] = [
                (string) ($h->etiket ?? '—'),
                (string) (int) ($h->sayi ?? 0),
            ];
        }

        return $rows;
    }

    private static function exportClinicalProfileBlock(Stats $m, string $block): ?array {
        $report = $m->getClinicalProfileReport();
        $title = 'Klinik profil';

        return match ($block) {
            'grafik' => self::payload(
                $title . ' — Cihaz / özel durum',
                ['İşaret', 'Hasta'],
                StatsReportPdfFormatHelper::rowsFromItems($report['flags'] ?? [], ['label', 'adet']),
                'Aktif: ' . (int) ($report['aktif'] ?? 0),
                StatsReportPdfFormatHelper::filename('clinicalProfile_grafik')
            ),
            'isaret_grafik' => self::payload(
                $title . ' — Hasta başına işaret sayısı',
                ['İşaret sayısı', 'Hasta'],
                StatsReportPdfFormatHelper::rowsFromItems($report['multi'] ?? [], ['label', 'adet']),
                'Aktif hastalar',
                StatsReportPdfFormatHelper::filename('clinicalProfile_isaret')
            ),
            'tablo' => StatsReportPdfExports::tryExport('clinicalProfile', []) ?: self::payload(
                $title . ' — İşaret tablosu',
                ['İşaret', 'Hasta'],
                StatsReportPdfFormatHelper::rowsFromItems($report['flags'] ?? [], ['label', 'adet']),
                'Aktif hastalar',
                StatsReportPdfFormatHelper::filename('clinicalProfile_tablo')
            ),
            default => null,
        };
    }

    private static function exportGeoBlock(Stats $m, string $block): ?array {
        $report = $m->getGeoDistributionReport(30);
        $title = 'Coğrafi dağılım';

        return match ($block) {
            'grafik' => self::payload(
                $title . ' — İlçe',
                ['İlçe', 'Aktif hasta'],
                StatsReportPdfFormatHelper::rowsFromItems($report['ilce'] ?? [], ['ilce_adi', 'adet']),
                'Tüm ilçeler',
                StatsReportPdfFormatHelper::filename('geoDistribution_ilce')
            ),
            'mahalle_grafik' => self::payload(
                $title . ' — Yoğun mahalleler',
                ['Mahalle', 'İlçe', 'Aktif hasta'],
                self::mahalleChartRows($report['mahalle'] ?? []),
                'İlk ' . count($report['mahalle'] ?? []) . ' mahalle',
                StatsReportPdfFormatHelper::filename('geoDistribution_mahalle')
            ),
            default => null,
        };
    }

    /** @param list<object> $items @return list<list<string>> */
    private static function mahalleChartRows(array $items): array {
        $rows = [];
        foreach ($items as $r) {
            $rows[] = [
                (string) ($r->mahalle_adi ?? ''),
                (string) ($r->ilce_adi ?? ''),
                (string) (int) ($r->adet ?? 0),
            ];
        }

        return $rows;
    }

    private static function exportGuvenceGrafik(Stats $m): array {
        $rows = [];
        foreach ($m->getGuvenceActiveDistribution() as $r) {
            $rows[] = [
                (string) ($r->guvence_adi ?? 'Belirtilmemiş'),
                (string) (int) ($r->hastasayisi ?? 0),
            ];
        }

        return self::payload(
            'Güvence dağılımı — Grafik verisi',
            ['Güvence', 'Hasta'],
            $rows,
            'Aktif hastalar',
            StatsReportPdfFormatHelper::filename('guvenceDist_grafik')
        );
    }

    private static function exportBagimlilikGrafik(Stats $m): array {
        $pack = $m->getBagimlilikDistributionLabeled();
        $total = (int) ($pack['total'] ?? 0);
        $rows = [];
        foreach ($pack['rows'] ?? [] as $r) {
            $adet = (int) ($r->adet ?? 0);
            $pct = $total > 0 ? (string) round(100.0 * $adet / $total, 1) : '0';
            $rows[] = [(string) ($r->label ?? ''), (string) $adet, $pct];
        }

        return self::payload(
            'Bağımlılık dağılımı — Grafik verisi',
            ['Bağımlılık', 'Hasta', '%'],
            $rows,
            'Toplam aktif: ' . $total,
            StatsReportPdfFormatHelper::filename('bagimlilikDist_grafik')
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportAyMovementBlock(Stats $m, string $block, array $query): ?array {
        $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
        $month = isset($query['month']) ? (int) $query['month'] : (int) date('n');
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $g = $m->getGeneralStats($year, $month);
        $gen = $g->general ?? (object) [];
        $new = $g->new ?? (object) [];
        $ex = $g->exit ?? (object) [];
        $period = sprintf('%04d-%02d', $year, $month);
        $title = 'Ay hareket özeti';

        return match ($block) {
            'grafik' => self::payload(
                $title . ' — Aktif cinsiyet',
                ['Cinsiyet', 'Adet'],
                [
                    ['Erkek (aktif)', (string) (int) ($gen->active_male ?? 0)],
                    ['Kadın (aktif)', (string) (int) ($gen->active_female ?? 0)],
                    ['Aktif toplam', (string) (int) ($gen->active_total ?? 0)],
                ],
                $period,
                StatsReportPdfFormatHelper::filename('ayMovement_grafik')
            ),
            'hareket_grafik' => self::payload(
                $title . ' — Yeni kayıt / çıkış',
                ['Gösterge', 'Erkek', 'Kadın', 'Toplam'],
                [
                    ['Takibe başlayan', (string) (int) ($new->new_male ?? 0), (string) (int) ($new->new_female ?? 0), (string) ((int) ($new->new_male ?? 0) + (int) ($new->new_female ?? 0))],
                    ['Takipten çıkan', (string) (int) ($ex->exit_male ?? 0), (string) (int) ($ex->exit_female ?? 0), (string) ((int) ($ex->exit_male ?? 0) + (int) ($ex->exit_female ?? 0))],
                ],
                $period,
                StatsReportPdfFormatHelper::filename('ayMovement_hareket')
            ),
            'liste' => self::payload(
                $title . ' — Dönem özeti',
                ['Gösterge', 'Değer'],
                StatsReportPdfFormatHelper::keyValueRows([
                    'Ulaşılan hasta' => (string) (int) ($gen->total_reached ?? 0),
                    'Aktif toplam' => (string) (int) ($gen->active_total ?? 0),
                    'Tam bağımlı aktif' => (string) (int) ($gen->fully_dependent ?? 0),
                    'Dönem — yeni kayıt' => (string) ((int) ($new->new_male ?? 0) + (int) ($new->new_female ?? 0)),
                    'Dönem — çıkış' => (string) ((int) ($ex->exit_male ?? 0) + (int) ($ex->exit_female ?? 0)),
                ]),
                $period,
                StatsReportPdfFormatHelper::filename('ayMovement_liste')
            ),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportVisitProceduresBlock(Stats $m, string $block, array $query): ?array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $items = $m->getProcedureCountsFromVisits($from, $to);
        $period = DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to);
        $title = 'İzlem işlemleri';

        return match ($block) {
            'grafik' => self::payload(
                $title . ' — İşlem dağılımı (üst 12)',
                ['İşlem', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems(array_slice($items, 0, 12), ['islemadi', 'adet']),
                $period,
                StatsReportPdfFormatHelper::filename('visitProcedures_grafik')
            ),
            'tablo' => self::payload(
                $title . ' — İşlem tablosu',
                ['İşlem', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems($items, ['islemadi', 'adet']),
                $period,
                StatsReportPdfFormatHelper::filename('visitProcedures_tablo')
            ),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportConsultationBlock(Stats $m, string $block, array $query): ?array {
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -5 months');
        $data = $m->getVisitConsultationMonthlyBreakdown($from, $to);
        $period = DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to);
        $title = 'İzlem konsültasyon aylık';

        return match ($block) {
            'brans' => self::payload(
                $title . ' — En sık branşlar',
                ['Branş', 'Adet'],
                self::assocCountRows($data['brans_top'] ?? []),
                $period,
                StatsReportPdfFormatHelper::filename('visitConsultation_brans')
            ),
            'istek' => self::payload(
                $title . ' — En sık istekler',
                ['İstek', 'Adet'],
                self::assocCountRows($data['istek_top'] ?? []),
                $period,
                StatsReportPdfFormatHelper::filename('visitConsultation_istek')
            ),
            'cift' => self::payload(
                $title . ' — En sık branş–istek çiftleri',
                ['Branş – istek', 'Adet'],
                self::assocCountRows($data['pair_top'] ?? []),
                $period,
                StatsReportPdfFormatHelper::filename('visitConsultation_cift')
            ),
            default => null,
        };
    }

    /**
     * @param array<string, int> $map
     * @return list<list<string>>
     */
    private static function assocCountRows(array $map): array {
        $rows = [];
        foreach ($map as $label => $count) {
            $rows[] = [(string) $label, (string) (int) $count];
        }

        return $rows;
    }

    private static function exportWaitingPoolBlock(Stats $m, string $block): ?array {
        $report = $m->getWaitingPoolProfile();
        $title = 'Bekleyen hasta profili';
        $sub = 'Pasif kodu -3';

        return match ($block) {
            'ilce' => self::payload(
                $title . ' — İlçe',
                ['İlçe', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems($report['by_ilce'] ?? [], ['label', 'adet']),
                $sub,
                StatsReportPdfFormatHelper::filename('waitingPool_ilce')
            ),
            'bagimlilik' => self::payload(
                $title . ' — Bağımlılık',
                ['Bağımlılık', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems($report['by_bagimlilik'] ?? [], ['label', 'adet']),
                $sub,
                StatsReportPdfFormatHelper::filename('waitingPool_bagimlilik')
            ),
            'randevu_zaman' => self::payload(
                $title . ' — Randevu zamanı',
                ['Zaman dilimi', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems($report['by_zaman'] ?? [], ['label', 'adet']),
                $sub,
                StatsReportPdfFormatHelper::filename('waitingPool_zaman')
            ),
            default => null,
        };
    }

    private static function exportBmiVkiBlock(Stats $m, string $block): ?array {
        $r = $m->getBmiVkiReport();
        $title = 'VKİ dağılımı';

        return match ($block) {
            'tablo' => self::exportBmiGenderMatrix($r, $title),
            'yas_band' => self::exportBmiAgeMatrix($r, $title),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $r
     */
    private static function exportBmiGenderMatrix(array $r, string $title): array {
        $catKeys = $r['cat_keys'] ?? [];
        $catMeta = $r['cat_meta'] ?? [];
        $byGender = $r['by_gender'] ?? [];
        $headers = array_merge(['Cinsiyet'], array_map(
            static fn ($k) => (string) (($catMeta[$k]['label'] ?? $k)),
            $catKeys
        ), ['Toplam']);
        $rows = [];
        foreach (['K', 'E', '?'] as $gk) {
            $row = $byGender[$gk] ?? [];
            $line = [BmiHelper::genderLabel($gk)];
            $tot = 0;
            foreach ($catKeys as $ck) {
                $n = (int) ($row[$ck] ?? 0);
                $line[] = (string) $n;
                $tot += $n;
            }
            if ($tot === 0 && $gk === '?') {
                continue;
            }
            $line[] = (string) $tot;
            $rows[] = $line;
        }

        return self::payload(
            $title . ' — Cinsiyete göre VKİ',
            $headers,
            $rows,
            'Boy ve kilo kayıtlı aktif hastalar',
            StatsReportPdfFormatHelper::filename('bmiVki_tablo')
        );
    }

    /**
     * @param array<string, mixed> $r
     */
    private static function exportBmiAgeMatrix(array $r, string $title): array {
        $catKeys = $r['cat_keys'] ?? [];
        $catMeta = $r['cat_meta'] ?? [];
        $ageBandKeys = $r['age_band_keys'] ?? [];
        $byAge = $r['by_age'] ?? [];
        $headers = array_merge(['Yaş bandı'], array_map(
            static fn ($k) => (string) (($catMeta[$k]['label'] ?? $k)),
            $catKeys
        ), ['Toplam']);
        $rows = [];
        foreach ($ageBandKeys as $band) {
            $row = $byAge[$band] ?? [];
            $line = [AgeBandHelper::label($band)];
            $tot = 0;
            foreach ($catKeys as $ck) {
                $n = (int) (is_array($row) ? ($row[$ck] ?? 0) : 0);
                $line[] = (string) $n;
                $tot += $n;
            }
            $line[] = (string) $tot;
            $rows[] = $line;
        }

        return self::payload(
            $title . ' — Yaş bandına göre VKİ',
            $headers,
            $rows,
            'Grafik ve tablo verisi',
            StatsReportPdfFormatHelper::filename('bmiVki_yas_band')
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportRandevuTakvimBlock(Stats $m, string $block, array $query): ?array {
        if ($block !== 'kons' && $block !== 'uhds') {
            return null;
        }
        [$from, $to] = StatsReportPdfFormatHelper::dateRangeFromQuery($query, 'first day of -11 months');
        $report = $m->getRandevuTakvimReport($block, $from, $to);
        $section = $block === 'kons' ? 'Branş randevu takvimi' : 'Uhds';
        $rows = StatsReportPdfFormatHelper::keyValueRows([
            'Toplam randevu' => (string) (int) (($report['summary'] ?? (object) [])->toplam_randevu ?? 0),
            'Benzersiz hasta' => (string) (int) (($report['summary'] ?? (object) [])->benzersiz_hasta ?? 0),
        ]);
        foreach ($report['by_month'] ?? [] as $r) {
            $yil = (int) ($r->yil ?? 0);
            $ay = (int) ($r->ay ?? 0);
            $rows[] = [
                'Ay ' . ((self::TURKCE_AYLAR[$ay] ?? (string) $ay) . ' ' . $yil),
                (string) (int) ($r->adet ?? 0),
            ];
        }
        foreach ($report['by_brans'] ?? [] as $r) {
            $rows[] = [
                'Branş: ' . (string) ($r->brans_adi ?? ''),
                (string) (int) ($r->adet ?? 0),
            ];
        }
        foreach ($report['by_zaman'] ?? [] as $r) {
            $rows[] = [
                'Zaman: ' . \App\Models\KonsRandevu::zamanLabel((int) ($r->zaman ?? 0)),
                (string) (int) ($r->adet ?? 0),
            ];
        }

        return self::payload(
            $section,
            ['Kırılım', 'Adet'],
            $rows,
            DateHelper::toTrOrEmpty($from) . ' – ' . DateHelper::toTrOrEmpty($to),
            StatsReportPdfFormatHelper::filename('randevuTakvim_' . $block)
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function exportKayitMonthsGrafik(Stats $m, array $query): array {
        $limit = isset($query['limit']) ? (int) $query['limit'] : 24;
        if ($limit < 0) {
            $limit = 0;
        }
        $items = $m->getKayitAyiStats() ?: [];
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }
        $rows = [];
        foreach (array_reverse($items) as $r) {
            $yil = (int) ($r->kayityili ?? 0);
            $ay = (int) ($r->kayitay ?? 0);
            $rows[] = [
                (self::TURKCE_AYLAR[$ay] ?? (string) $ay) . ' ' . $yil,
                (string) (int) ($r->erkek_sayisi ?? 0),
                (string) (int) ($r->kadin_sayisi ?? 0),
                (string) (int) ($r->toplam_sayi ?? 0),
            ];
        }

        return self::payload(
            'Kayıt ayları — Aylık grafik verisi',
            ['Dönem', 'Erkek', 'Kadın', 'Toplam'],
            $rows,
            'Aktif hastalar — kayıt tarihi',
            StatsReportPdfFormatHelper::filename('kayitMonths_grafik')
        );
    }

    private static function exportAgeSummaryGrafik(Stats $m): array {
        $report = $m->getAgeSummaryReport();

        return self::payload(
            'Yaş özeti — Bantlar',
            ['Bant', 'Adet', '%'],
            StatsReportPdfFormatHelper::rowsFromItems($report['bands'] ?? $report['rows'] ?? [], ['label', 'adet', 'pct']),
            'Ortalama: ' . ($report['ortalama'] ?? '—'),
            StatsReportPdfFormatHelper::filename('ageSummary_grafik')
        );
    }

    private static function exportPansumanGrafik(Stats $m): array {
        $report = $m->getPansumanProfile();

        return self::payload(
            'Pansuman profili — Zaman dilimi',
            ['Zaman dilimi', 'Adet'],
            StatsReportPdfFormatHelper::rowsFromItems($report['by_zaman'] ?? [], ['label', 'adet']),
            'Pansuman hastaları',
            StatsReportPdfFormatHelper::filename('pansumanProfile_grafik')
        );
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     * @return array<string, mixed>
     */
    private static function payload(
        string $title,
        array $headers,
        array $rows,
        string $filterSummary,
        string $filename
    ): array {
        return StatsReportPdfFormatHelper::payload($title, $headers, $rows, $filterSummary, $filename);
    }
}
