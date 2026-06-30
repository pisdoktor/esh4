<?php
namespace App\Helpers;

/**
 * İstatistik merkezi (hub) ve yönetim paneli hızlı rapor menüleri — tek kaynak.
 */
class StatsNavHelper {
    /** Yönetim paneli — hızlı istatistik düğmeleri (tüm raporlar aynı görünüm). */
    public const DASHBOARD_QUICK_BTN_CLASS = 'btn-outline-secondary';

    /** @return array<int, array<string, mixed>> */
    public static function hubGroups(): array {
        $groups = [
            [
                'title' => 'Operasyonel Durum',
                'desc' => 'Anlık özet, dönem hareketi, kayıt ve aylık izlem kapsama göstergeleri.',
                'icon' => 'fa-heart-pulse',
                'accent' => 'danger',
                'sections' => [
                    [
                        'label' => 'Anlık ve dönem özeti',
                        'cards' => [
                            self::card('operationsPulse', 'Operasyonel nabız', 'İzlem trendi, ilçe, e-rapor, pansuman, planlı izlem, branş — tek ekranda.', 'fa-heart-pulse', 'danger'),
                            self::card('overview', 'Genel özet', 'Toplam / aktif / cinsiyet, mahalle ve yıllık kayıt tabloları.', 'fa-chart-column', 'primary'),
                            self::card('ayMovement', 'Ay hareketi', 'Ulaşılan hasta, bu ay yeni / çıkan, tam bağımlı; mini grafikler.', 'fa-arrows-rotate', 'info'),
                        ],
                    ],
                    [
                        'label' => 'Kayıt ve izlem kapsamı',
                        'cards' => [
                            self::card('kayitMonths', 'Kayıt ayları (aktif)', 'Aktif hastaların kayıt tarihine göre aylık dağılım; Chart.js grafiği ve detay tablosu.', 'fa-calendar-plus', 'primary'),
                            self::card('monthlyFollowFreq', 'Aylık izlem sıklığı', 'Benzersiz hasta, tamamlanmış izlem, sıklık kartları; son 6 ay tablosu.', 'fa-list-check', 'success'),
                            self::card('monthlyPool', 'Bu ay izlenen — yaş grupları', 'Cari ayda izlenen hastaların yaş bandı dağılımı.', 'fa-users-viewfinder', 'success'),
                            self::card('followKpi', 'İzlem KPI (3 ay)', 'Son 3 ayda izlenen aktif hasta oranı.', 'fa-gauge-high', 'danger'),
                            self::card('regionalPerformance', 'Bölgesel izlem performansı', 'İlçe > mahalle; son 3 ay izlenen / toplam aktif hasta oranı.', 'fa-layer-group', 'success'),
                            self::card('yearlyFollow', 'Yıllık izlem', '12 aylık kapsama ve son 3 yıl izlem adetleri.', 'fa-calendar-days', 'dark'),
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Hasta Profili ve Dağılım',
                'desc' => 'Aktif havuzun demografik ve dosya bazlı kırılımları.',
                'icon' => 'fa-users',
                'accent' => 'primary',
                'sections' => [
                    [
                        'label' => 'Nüfus ve durum',
                        'cards' => [
                            self::card('patientStatus', 'Durum dağılımı', 'Pasif koduna göre hasta sayıları ve oranlar.', 'fa-layer-group', 'secondary'),
                            self::card('ageGenderBands', 'Yaş × cinsiyet', 'Aktif hastaların yaş bandı ve kadın/erkek dağılımı.', 'fa-venus-mars', 'primary'),
                            self::card('ageSummary', 'Yaş özeti', 'Ortalama / medyan yaş, basit gruplar ve yaş bantları.', 'fa-chart-simple', 'primary'),
                            self::card('bagimlilikDist', 'Bağımlılık dağılımı', 'Bağımsız, yarı ve tam bağımlı aktif hastalar.', 'fa-person-walking-with-cane', 'info'),
                            self::card('birthdays', 'Doğum günü', 'Bugün doğum günü olan aktif hastalar listesi.', 'fa-cake-candles', 'warning'),
                            self::card('waitingPoolProfile', 'Bekleyen havuz', 'Pasif -3 hastalarda ilçe, bağımlılık ve randevu zamanı.', 'fa-hourglass', 'primary'),
                            self::card('fieldCoverage', 'Alan doluluk', 'Telefon, profil fotoğrafı, anne/baba adı; nokta placeholder (. / .. / ...) ayrı satır.', 'fa-id-card', 'secondary'),
                        ],
                    ],
                    [
                        'label' => 'Coğrafya ve kayıt',
                        'cards' => [
                            self::card('geoDistribution', 'Coğrafi dağılım', 'İlçe sıralaması ve en yoğun mahalleler (aktif).', 'fa-map-location-dot', 'danger'),
                            self::card('kayitTenure', 'Kayıt süresi', 'Aktif hastada sistemde kalma süresi grupları.', 'fa-hourglass-half', 'success'),
                            self::card('kayitKohortAge', 'Kayıt kohortu × yaş bandı', 'Kayıt yılına göre kayıt anındaki yaş bantları (g01–g86).', 'fa-table-cells', 'dark'),
                        ],
                    ],
                    [
                        'label' => 'Ölçüm ve kapsam',
                        'cards' => [
                            self::card('bmiVki', 'VKİ dağılımı', 'Boy ve kilo kayıtlı aktif hastalarda VKİ; cinsiyet ve yaş bandı.', 'fa-weight-scale', 'warning'),
                            self::card('anthroCoverage', 'Antropometri kapsamı', 'Boy, kilo ve VKİ hesaplanabilirlik oranları.', 'fa-ruler-combined', 'warning'),
                            self::card('demographicCompleteness', 'Veri tamamlama', 'Doğum, TC, telefon, güvence ve adres eksikleri (aktif).', 'fa-clipboard-list', 'info'),
                            self::card('clinicalProfile', 'Klinik profil özeti', 'NG, sonda, O₂ vb. bayraklar ve çoklu cihaz yoğunluğu.', 'fa-kit-medical', 'warning'),
                            self::card('pansumanProfile', 'Pansuman profili', 'Pansuman hastalarında zaman dilimi ve gün bilgisi.', 'fa-bandage', 'success'),
                            self::card('barthel', 'Barthel skoru', 'Fonksiyonel bağımsızlık (Barthel toplam) dağılımı.', 'fa-wheelchair', 'primary'),
                        ],
                    ],
                    [
                        'label' => 'Güvence ve tanı yükü',
                        'cards' => [
                            self::card('guvenceDist', 'Güvence türleri', 'Güvence türüne göre aktif hasta sayıları.', 'fa-shield-halved', 'success'),
                            self::card('guvenceAgeBands', 'Güvence × yaş', 'Güvence türüne göre yaş bandı dağılımı (ısı tablo).', 'fa-table', 'success'),
                            self::card('hastalikCountDist', 'Tanı sayısı', 'Hasta başına tanı adedi; komorbidite yükü özeti.', 'fa-list-ol', 'danger'),
                        ],
                    ],
                    [
                        'label' => 'Tanı ve hastalık',
                        'cards' => [
                            self::card('hastalik', 'Tanı dağılımı (ICD)', 'ICD / tanı kırılımı; tanıya göre hasta listesine geçiş.', 'fa-notes-medical', 'danger'),
                            self::card('charts', 'Tanı özeti (grafik)', 'Tanı kategorileri, en sık tanılar ve pasta grafik (aktif hasta).', 'fa-chart-area', 'success'),
                        ],
                    ],
                ],
            ],
            [
                'title' => 'İzlem ve Hizmet Kalitesi',
                'desc' => 'Süreç kalitesi, veri tutarlılığı ve bakım yoğunluğu.',
                'icon' => 'fa-stethoscope',
                'accent' => 'warning',
                'sections' => [
                    [
                        'label' => 'İzlem performansı',
                        'cards' => [
                            self::card('visitStats', 'Yapılan izlem özeti', 'Yapıldı / yapılmadı, araç, zaman dilimi ve yapılmama nedeni; izlem tarihi aralığına göre özet.', 'fa-stethoscope', 'success'),
                            self::card('plannedVisitStats', 'Planlı izlem özeti', 'Yapılan / yapılmayan, öncelik ve zaman dilimi; planlanan tarih aralığına göre özet.', 'fa-calendar-check', 'primary'),
                            self::card('topVisits', 'Yoğun takip', 'En çok tamamlanmış izlemi olan 10 aktif hasta.', 'fa-ranking-star', 'primary'),
                            self::card('birIzlemliler', 'Bir izlemliler', 'Tek tamamlanmış izlem + yapılan alanında işlem 1; ilçe/mahalle filtresi.', 'fa-list-ol', 'info'),
                            self::card('aylikTekIzlemliler', 'Aylık tek izlemliler', 'Seçilen ay/yılda tam 1 tamamlanmış izlem; ilçe/mahalle filtresi.', 'fa-calendar-day', 'info'),
                            self::card('workload', 'Hizmet süresi', 'İzlem başına ortalama süre ve yoğunluk özeti.', 'fa-chart-line', 'dark'),
                        ],
                    ],
                    [
                        'label' => 'Kalite ve skorlar',
                        'cards' => [
                            self::card('chronologyIssues', 'Kayıt–izlem kronolojisi', 'Kayıt tarihinden önce izlem vb. tutarsızlıklar.', 'fa-clock-rotate-left', 'danger'),
                            self::card('dataHealth', 'Veri sağlığı', 'Adres, TC, doğum, izlenmemiş aktif vb. denetim.', 'fa-clipboard-check', 'warning'),
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Klinik ve Operasyon Raporları',
                'desc' => 'İzlem içerikleri, personel yükü ve konsültasyon kırılımları.',
                'icon' => 'fa-user-doctor',
                'accent' => 'info',
                'sections' => [
                    [
                        'label' => 'İzlem içeriği',
                        'cards' => [
                            self::card('visitProcedures', 'İşlem dağılımı', 'Tarih aralığında yapılan işlem sayıları.', 'fa-list-check', 'primary'),
                            self::card('visitPersonnel', 'Personel dağılımı', 'İzlem yapan personel sayıları.', 'fa-user-nurse', 'info'),
                            self::card('visitConsultationMonthly', 'Konsültasyon (aylık)', 'Branş ve konsültasyon isteklerinin aylık dökümü.', 'fa-notes-medical', 'primary'),
                        ],
                    ],
                    [
                        'label' => 'Randevu analizi',
                        'cards' => [
                            self::card('randevuTakvim', 'Randevu takvimleri', 'Branş ve görüntülü muayene randevuları: aylık trend, branş, zaman dilimi, katılım/yapılım.', 'fa-calendar-check', 'info'),
                            self::card('randevuKayitGap', 'Kayıt – randevu gün farkı', 'Aktif hastalarda kayıt ↔ randevu gün farkı; histogram, medyan, ilçe (son 12 ay).', 'fa-hourglass-half', 'secondary'),
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Özel Programlar',
                'desc' => 'Cihaz ve rapor odaklı özel izlem grupları.',
                'icon' => 'fa-kit-medical',
                'accent' => 'warning',
                'sections' => [
                    [
                        'label' => 'Cihaz ve sarf',
                        'cards' => [
                            self::card('specialDevices', 'Cihaz / özel durum', 'NG, PEG ve benzeri özel durum özetleri.', 'fa-kit-medical', 'warning'),
                            self::card('supplyReports', 'Mama / bez raporları', 'Mama ve bez raporu özetleri.', 'fa-baby', 'warning'),
                            self::card('sondaChanges', 'Sonda takibi', 'Planlanan sonda değişim tarihine göre liste (sondatarihi + 1 ay).', 'fa-syringe', 'warning'),
                        ],
                    ],
                    [
                        'label' => 'e-Rapor',
                        'cards' => [
                            self::card('eraporList', 'e-Rapor hastaları', 'e-Rapor kayıtlı hasta listesi.', 'fa-file-waveform', 'info'),
                            self::card('eraporHastaUyum', 'e-Rapor – hasta uyumu', '#__erapor ile #__hastalar karşılaştırması; kayıt bayrakları, pasif durum, havuz eşleşmesi.', 'fa-code-compare', 'info'),
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Pasif ve Çıkış Analizi',
                'desc' => 'Pasife geçiş sebepleri ve çıkış trendleri.',
                'icon' => 'fa-door-open',
                'accent' => 'secondary',
                'sections' => [
                    [
                        'label' => 'Pasif çıkış',
                        'cards' => [
                            self::card('exitReasons', 'Çıkış nedenleri', 'Pasife alınanların nedeni dağılımı (pasta grafik).', 'fa-chart-pie', 'danger'),
                        ],
                    ],
                ],
            ],
        ];
        StatsCrossTabRegistry::injectHubCards($groups);

        return $groups;
    }

    /**
     * Hub kartı → hızlı panel kısa etiket / ikon (hub ile senkron).
     *
     * @var array<string, array{title: string, icon: string}>
     */
    private const DASHBOARD_QUICK_META = [
        'operationsPulse' => ['title' => 'Nabız', 'icon' => 'fa-heart-pulse'],
        'overview' => ['title' => 'Genel özet', 'icon' => 'fa-border-all'],
        'ayMovement' => ['title' => 'Ay hareketi', 'icon' => 'fa-arrow-trend-up'],
        'kayitMonths' => ['title' => 'Kayıt ayları', 'icon' => 'fa-calendar-plus'],
        'monthlyFollowFreq' => ['title' => 'İzlem sıklığı', 'icon' => 'fa-list-check'],
        'monthlyPool' => ['title' => 'Yaş grupları', 'icon' => 'fa-users-viewfinder'],
        'followKpi' => ['title' => 'KPI 3 ay', 'icon' => 'fa-bullseye'],
        'regionalPerformance' => ['title' => 'Bölgesel', 'icon' => 'fa-layer-group'],
        'yearlyFollow' => ['title' => 'Yıllık', 'icon' => 'fa-calendar-days'],
        'patientStatus' => ['title' => 'Durum', 'icon' => 'fa-user-check'],
        'ageGenderBands' => ['title' => 'Yaş×cinsiyet', 'icon' => 'fa-venus-mars'],
        'ageSummary' => ['title' => 'Yaş özeti', 'icon' => 'fa-chart-simple'],
        'bagimlilikDist' => ['title' => 'Bağımlılık', 'icon' => 'fa-person-walking-with-cane'],
        'geoDistribution' => ['title' => 'Coğrafi', 'icon' => 'fa-map-location-dot'],
        'kayitTenure' => ['title' => 'Kayıt süresi', 'icon' => 'fa-hourglass-half'],
        'kayitKohortAge' => ['title' => 'Kayıt kohortu', 'icon' => 'fa-table-cells'],
        'bmiVki' => ['title' => 'VKİ', 'icon' => 'fa-weight-scale'],
        'anthroCoverage' => ['title' => 'Antropometri', 'icon' => 'fa-ruler-combined'],
        'demographicCompleteness' => ['title' => 'Veri tamamlama', 'icon' => 'fa-clipboard-list'],
        'clinicalProfile' => ['title' => 'Klinik profil', 'icon' => 'fa-kit-medical'],
        'pansumanProfile' => ['title' => 'Pansuman', 'icon' => 'fa-bandage'],
        'hastalikCountDist' => ['title' => 'Tanı sayısı', 'icon' => 'fa-list-ol'],
        'waitingPoolProfile' => ['title' => 'Bekleyen havuz', 'icon' => 'fa-hourglass'],
        'fieldCoverage' => ['title' => 'Alan doluluk', 'icon' => 'fa-id-card'],
        'guvenceAgeBands' => ['title' => 'Güvence×yaş', 'icon' => 'fa-table'],
        'guvenceDist' => ['title' => 'Güvence', 'icon' => 'fa-shield-halved'],
        'birthdays' => ['title' => 'Doğum günü', 'icon' => 'fa-cake-candles'],
        'hastalik' => ['title' => 'Tanı (ICD)', 'icon' => 'fa-notes-medical'],
        'charts' => ['title' => 'Tanı (grafik)', 'icon' => 'fa-chart-column'],
        'visitStats' => ['title' => 'Yapılan izlem özeti', 'icon' => 'fa-stethoscope'],
        'plannedVisitStats' => ['title' => 'Planlı izlem özeti', 'icon' => 'fa-clipboard-list'],
        'topVisits' => ['title' => 'Yoğun takip', 'icon' => 'fa-fire'],
        'birIzlemliler' => ['title' => 'Bir izlemliler', 'icon' => 'fa-list-ol'],
        'aylikTekIzlemliler' => ['title' => 'Aylık tek izlemliler', 'icon' => 'fa-calendar-day'],
        'workload' => ['title' => 'Hizmet süresi', 'icon' => 'fa-chart-line'],
        'chronologyIssues' => ['title' => 'Kronoloji', 'icon' => 'fa-clock-rotate-left'],
        'dataHealth' => ['title' => 'Veri sağlığı', 'icon' => 'fa-clipboard-check'],
        'barthel' => ['title' => 'Barthel', 'icon' => 'fa-wheelchair'],
        'visitProcedures' => ['title' => 'İşlemler', 'icon' => 'fa-stethoscope'],
        'visitPersonnel' => ['title' => 'Personel', 'icon' => 'fa-user-nurse'],
        'visitConsultationMonthly' => ['title' => 'Konsültasyon', 'icon' => 'fa-notes-medical'],
        'randevuTakvim' => ['title' => 'Randevu takvimleri', 'icon' => 'fa-calendar-check'],
        'randevuKayitGap' => ['title' => 'Kayıt–randevu gün', 'icon' => 'fa-hourglass-half'],
        'specialDevices' => ['title' => 'Cihazlar', 'icon' => 'fa-kit-medical'],
        'eraporList' => ['title' => 'e-Rapor', 'icon' => 'fa-file-waveform'],
        'eraporHastaUyum' => ['title' => 'e-Rapor uyumu', 'icon' => 'fa-code-compare'],
        'supplyReports' => ['title' => 'Mama/Bez', 'icon' => 'fa-baby'],
        'sondaChanges' => ['title' => 'Sonda', 'icon' => 'fa-syringe'],
        'exitReasons' => ['title' => 'Çıkış nedenleri', 'icon' => 'fa-chart-pie'],
    ];

    /**
     * Yönetim paneli — hızlı bağlantılar (hub gruplarıyla birebir; eksik rapor kalmaz).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function dashboardQuickGroups(): array {
        $out = [];
        foreach (self::hubGroups() as $hubGroup) {
            $items = [];
            foreach (self::hubGroupCardsFlat($hubGroup) as $card) {
                $items[] = self::quickFromHubCard($card);
            }
            $out[] = [
                'title' => (string) ($hubGroup['title'] ?? ''),
                'icon' => (string) ($hubGroup['icon'] ?? 'fa-chart-simple'),
                'accent' => (string) ($hubGroup['accent'] ?? 'secondary'),
                'items' => $items,
            ];
        }

        return $out;
    }

    /** @param array{action: string, title: string, icon: string, color: string} $card */
    private static function quickFromHubCard(array $card): array {
        $action = (string) ($card['action'] ?? '');
        $meta = self::DASHBOARD_QUICK_META[$action] ?? null;
        if ($meta === null && str_starts_with($action, 'xTab_')) {
            $tabId = StatsCrossTabRegistry::idFromAction($action);
            if ($tabId !== null) {
                $def = StatsCrossTabRegistry::definition($tabId);
                $title = trim((string) ($def['dashboard_title'] ?? $def['title'] ?? $card['title'] ?? $action));
                $icon = (string) ($def['icon'] ?? $card['icon'] ?? 'fa-table');

                return self::quick($action, $title !== '' ? $title : $action, $icon);
            }
        }
        $title = $meta['title'] ?? (string) ($card['title'] ?? $action);
        $icon = $meta['icon'] ?? (string) ($card['icon'] ?? 'fa-chart-simple');

        return self::quick($action, $title, $icon);
    }

    /** Hub grubundaki tüm kartları düz liste (kompakt tema şablonları). */
    public static function hubGroupCardsFlat(array $group): array {
        if (!empty($group['sections'])) {
            $flat = [];
            foreach ($group['sections'] as $section) {
                foreach ($section['cards'] as $card) {
                    if (isset($card['dashboard_quick']) && $card['dashboard_quick'] === false) {
                        continue;
                    }
                    $flat[] = $card;
                }
            }
            return $flat;
        }
        return $group['cards'] ?? [];
    }

    /** @return array{action: string, title: string, desc: string, icon: string, color: string} */
    private static function card(string $action, string $title, string $desc, string $icon, string $color): array {
        return compact('action', 'title', 'desc', 'icon', 'color');
    }

    /** @return array{action: string, title: string, icon: string} */
    private static function quick(string $action, string $title, string $icon): array {
        return compact('action', 'title', 'icon');
    }

    /** @var array<string, array{parent: string, label: string}> */
    private const BREADCRUMB_CHILD = [
        'hastalikPatients' => ['parent' => 'hastalik', 'label' => 'Hasta listesi'],
        'dataHealthPatients' => ['parent' => 'dataHealth', 'label' => 'Hasta listesi'],
        'fieldCoveragePatients' => ['parent' => 'fieldCoverage', 'label' => 'Hasta listesi'],
        'eraporHastaUyumList' => ['parent' => 'eraporHastaUyum', 'label' => 'Liste'],
    ];

    /** Hub’da olmayan veya özel kök sayfalar. */
    private const BREADCRUMB_STANDALONE = [
        'adresPatientFilter' => 'Adrese göre hastalar',
    ];

    /**
     * @return array{group_title: string, section_label: string, card_title: string, accent: string}|null
     */
    public static function hubCardLocation(string $action): ?array {
        if ($action === '') {
            return null;
        }
        foreach (self::hubGroups() as $group) {
            $sections = $group['sections'] ?? [['label' => '', 'cards' => $group['cards'] ?? []]];
            foreach ($sections as $section) {
                foreach ($section['cards'] ?? [] as $card) {
                    if ((string) ($card['action'] ?? '') === $action) {
                        return [
                            'group_title' => (string) ($group['title'] ?? ''),
                            'section_label' => trim((string) ($section['label'] ?? '')),
                            'card_title' => (string) ($card['title'] ?? $action),
                            'accent' => (string) ($group['accent'] ?? 'primary'),
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * İstatistik sayfaları breadcrumb zinciri (hub gruplarıyla uyumlu).
     *
     * @return list<array{label: string, url: ?string, muted?: bool}>
     */
    public static function breadcrumbTrail(string $action, ?string $pageTitle = null): array {
        $action = trim($action);
        $dashUrl = esh_url('Dashboard', 'admin');
        $hubUrl = esh_url('Stats', 'index');

        if ($action === '' || $action === 'index') {
            return [
                ['label' => 'Yönetim paneli', 'url' => $dashUrl],
                ['label' => 'İstatistik merkezi', 'url' => null],
            ];
        }

        $items = [
            ['label' => 'Yönetim paneli', 'url' => $dashUrl],
            ['label' => 'İstatistik merkezi', 'url' => $hubUrl],
        ];

        if (isset(self::BREADCRUMB_CHILD[$action])) {
            $child = self::BREADCRUMB_CHILD[$action];
            $parentLoc = self::hubCardLocation($child['parent']);
            if ($parentLoc !== null) {
                $items[] = ['label' => $parentLoc['group_title'], 'url' => null, 'muted' => true];
                if ($parentLoc['section_label'] !== '') {
                    $items[] = ['label' => $parentLoc['section_label'], 'url' => null, 'muted' => true];
                }
                $items[] = [
                    'label' => $parentLoc['card_title'],
                    'url' => esh_url('Stats', $child['parent']),
                ];
            }
            $items[] = ['label' => $child['label'], 'url' => null];

            return $items;
        }

        if (isset(self::BREADCRUMB_STANDALONE[$action])) {
            $items[] = ['label' => self::BREADCRUMB_STANDALONE[$action], 'url' => null];

            return $items;
        }

        $loc = self::hubCardLocation($action);
        if ($loc !== null) {
            $items[] = ['label' => $loc['group_title'], 'url' => null, 'muted' => true];
            if ($loc['section_label'] !== '') {
                $items[] = ['label' => $loc['section_label'], 'url' => null, 'muted' => true];
            }
            $active = trim((string) ($pageTitle ?? ''));
            if ($active === '' || $active === $loc['card_title']) {
                $active = $loc['card_title'];
            }
            $items[] = ['label' => $active, 'url' => null];

            return $items;
        }

        $items[] = ['label' => trim((string) ($pageTitle ?? $action)), 'url' => null];

        return $items;
    }

    public static function statsPageUrl(string $action): string {
        return esh_url('Stats', trim($action));
    }

    /**
     * Hub kart meta (çapraz tablo dahil).
     *
     * @return array<string, mixed>|null action, title, desc, icon, color, group_title?
     */
    public static function hubCardByAction(string $action): ?array {
        $action = trim($action);
        if ($action === '') {
            return null;
        }
        foreach (self::hubGroups() as $group) {
            $sections = $group['sections'] ?? [['label' => null, 'cards' => $group['cards'] ?? []]];
            foreach ($sections as $section) {
                foreach ($section['cards'] ?? [] as $card) {
                    if ((string) ($card['action'] ?? '') === $action) {
                        return array_merge($card, [
                            'group_title' => (string) ($group['title'] ?? ''),
                        ]);
                    }
                }
            }
        }

        return null;
    }
}
