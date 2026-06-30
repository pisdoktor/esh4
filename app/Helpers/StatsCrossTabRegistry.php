<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İki kırılımlı istatistik raporları — tanım, hub yerleşimi ve intro metinleri.
 */
final class StatsCrossTabRegistry {
    /** @var array<string, array<string, mixed>> */
    private const REPORTS = [
        'bagimlilikAge' => [
            'title' => 'Bağımlılık × yaş',
            'desc' => 'Aktif hastada bağımlılık düzeyi ile yaş bandı kesişimi.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Nüfus ve durum',
            'intro' => 'Satırlar bağımlılık düzeyi (1 bağımsız, 2 yarı bağımlı, 3 tam bağımlı; boş ayrı), sütunlar güncel yaş bantlarıdır (doğum tarihine göre bugün). Her hücre aktif (pasif=0) hasta sayısını verir; Σ satır toplamıdır. Hücre rengi, satır içindeki dağılım oranını gösterir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'guvenceBagimlilik' => [
            'title' => 'Güvence × bağımlılık',
            'desc' => 'Güvence türü ile bağımlılık düzeyi matrisi.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Güvence ve tanı yükü',
            'intro' => 'Satırlar güvence adı, sütunlar bağımlılık düzeyidir. Hücreler aktif hasta sayısıdır. Hasta yoğunluğuna göre ilk güvence satırları listelenir; kalan kayıtlar «Diğer» satırında toplanabilir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'ilceAge' => [
            'title' => 'İlçe × yaş',
            'desc' => 'En yoğun ilçelerde yaş bandı dağılımı.',
            'icon' => 'fa-table',
            'color' => 'danger',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Coğrafya ve kayıt',
            'intro' => 'Satırlar aktif hasta sayısına göre seçilen ilçeler, sütunlar yaş bantlarıdır. Her hücre o ilçede o bantta kaç aktif hasta olduğunu gösterir. Liste yoğunluğa göre sınırlanır; seyrek ilçeler «Diğer» satırına düşebilir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'kayitYearAge' => [
            'title' => 'Kayıt yılı × yaş',
            'desc' => 'Kayıt yılına göre güncel yaş bandı dağılımı.',
            'icon' => 'fa-table',
            'color' => 'dark',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Coğrafya ve kayıt',
            'intro' => 'Satırlar kayıt yılı (son yıllar), sütunlar bugünkü yaş bantlarıdır — kayıt anındaki yaş değil, güncel bant kullanılır. Hücreler aktif hasta sayısıdır. Kohort×yaş raporundan farkı budur.',
            'footnote' => '',
            'period_type' => '',
        ],
        'tenureVisitCount' => [
            'title' => 'Kayıt süresi × izlem sayısı',
            'desc' => 'Sistemde kalma süresi ile tamamlanmış izlem adedi.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Coğrafya ve kayıt',
            'intro' => 'Satırlar kayıt tarihinden bugüne geçen süre grupları (kayıt yok, 0–6 ay, 6–12 ay, 1–3 yıl, 3+ yıl), sütunlar hasta başına tüm zamanlardaki tamamlanmış izlem adedi gruplarıdır (0, 1–3, 4–10, 10+). Her hücre aktif hasta sayısıdır; yalnızca yapıldı=1 izlemler sayılır. Dönem filtresi yoktur.',
            'footnote' => '',
            'period_type' => '',
        ],
        'bmiAge' => [
            'title' => 'VKİ grubu × yaş',
            'desc' => 'VKİ sınıfı ile yaş bandı (boy/kilo kayıtlı).',
            'icon' => 'fa-table',
            'color' => 'warning',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Ölçüm ve kapsam',
            'intro' => 'Satırlar VKİ sınıfı (zayıf, normal, obez vb.), sütunlar yaş bantlarıdır. Yalnızca boy ve kilo ile VKİ hesaplanabilen aktif hastalar dahildir. Hücreler hasta sayısıdır.',
            'footnote' => '',
            'period_type' => '',
        ],
        'bmiBagimlilik' => [
            'title' => 'VKİ × bağımlılık',
            'desc' => 'VKİ grubu ile bağımlılık düzeyi.',
            'icon' => 'fa-table',
            'color' => 'warning',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Ölçüm ve kapsam',
            'intro' => 'Satırlar VKİ grubu, sütunlar bağımlılık düzeyidir. VKİ hesaplanabilen aktif hastalar sayılır. Hücreler hasta adedini verir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'barthelAge' => [
            'title' => 'Barthel × yaş',
            'desc' => 'Barthel skor grubu ile yaş bandı.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Ölçüm ve kapsam',
            'intro' => 'Satırlar Barthel toplam skor grupları (0–20, 21–61, …), sütunlar yaş bantlarıdır. Barthel alanları dolu olmayan aktif hastalar tabloya girmez. Hücreler hasta sayısıdır.',
            'footnote' => '',
            'period_type' => '',
        ],
        'pansumanVisitGap' => [
            'title' => 'Pansuman × izlem gecikmesi',
            'desc' => 'Pansuman hastasında son izleme göre gecikme grubu.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Ölçüm ve kapsam',
            'intro' => 'Tek satırda pansuman işaretli aktif hastalar gösterilir; sütunlar son tamamlanmış izlemden bugüne geçen süre gruplarıdır (hiç izlem, 0–30 gün, 31–60 gün, 60+ gün). Hücreler hasta sayısıdır.',
            'footnote' => '',
            'period_type' => '',
        ],
        'deviceCountAge' => [
            'title' => 'Cihaz sayısı × yaş',
            'desc' => 'Klinik bayrak adedi ile yaş bandı.',
            'icon' => 'fa-table',
            'color' => 'warning',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Güvence ve tanı yükü',
            'intro' => 'Satırlar NG, sonda, O₂ vb. klinik bayrak sayısı grupları, sütunlar yaş bantlarıdır. Aktif hastalar sayılır; her hücre hasta adedidir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'hastalikCountAge' => [
            'title' => 'Tanı sayısı × yaş',
            'desc' => 'Hasta başına tanı adedi ile yaş bandı.',
            'icon' => 'fa-table',
            'color' => 'danger',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Güvence ve tanı yükü',
            'intro' => 'Satırlar hasta kartındaki tanı listesi uzunluğu grupları (0, 1, 2, 3, 4+), sütunlar yaş bantlarıdır. Aktif hasta sayısı gösterilir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'guvenceVisitGap' => [
            'title' => 'Güvence × izlem gecikmesi',
            'desc' => 'Güvence türüne göre son izlemden bu yana geçen süre.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'Hasta Profili ve Dağılım',
            'hub_section' => 'Güvence ve tanı yükü',
            'intro' => 'Satırlar güvence adı, sütunlar son tamamlanmış izlemden bu güne geçen süre gruplarıdır (hiç izlem, 0–30, 31–90, 91–180, 180+ gün). Aktif hastalar sayılır. Yoğun olmayan güvence satırları «Diğer»de toplanabilir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'monthVisitDone' => [
            'title' => 'Ay × yapıldı',
            'desc' => 'Aylık izlem kayıtlarında yapıldı / yapılmadı.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar takvim ayları (YYYY-MM), sütunlar izlem kaydının yapıldı / yapılmadı durumudur. Her hücre izlem kaydı (satır) adedidir; aktif hastaya bağlı kayıtlar dahildir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'monthVisitZaman' => [
            'title' => 'Ay × zaman dilimi',
            'desc' => 'Tamamlanmış izlemlerde sabah, öğle, akşam ve diğer dağılımı.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar takvim ayları, sütunlar tamamlanmış (yapıldı=1) izlemlerin zaman dilimidir: sabah, öğle, akşam (kod 1/2/3) ve diğer (boş veya geçersiz kod). Hücreler izlem kaydı adedidir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'ilceVisitDone' => [
            'title' => 'İlçe × yapıldı',
            'desc' => 'İlçe bazında dönem içi izlem tamamlama oranı.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar hasta ilçesi (yoğun ilçeler), sütunlar yapıldı / yapılmadı durumudur. Seçilen dönemdeki izlem kayıtları sayılır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'ageMonthVisited' => [
            'title' => 'Ay × yaş (izlenen)',
            'desc' => 'Ay içinde izlenen benzersiz hasta — yaş bandı.',
            'icon' => 'fa-table',
            'color' => 'success',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar takvim ayları, sütunlar yaş bantlarıdır. Her hücre, o ayda en az bir tamamlanmış izlemi olan benzersiz aktif hasta sayısını verir (izlem satırı değil). Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'vehicleMonth' => [
            'title' => 'Araç × ay',
            'desc' => 'Araç plakasına göre aylık izlem adedi.',
            'icon' => 'fa-table',
            'color' => 'dark',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar tamamlanmış izlemde seçilen araç plakaları (yoğun araçlar; kalan «Diğer»), sütunlar takvim aylarıdır. Hücreler izlem kaydı adedidir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'bagimlilikVisitYear' => [
            'title' => 'Bağımlılık × yıllık izlem',
            'desc' => 'Bağımlılık düzeyine göre yıllık tamamlanmış izlem.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'İzlem performansı',
            'intro' => 'Satırlar bağımlılık düzeyi, sütunlar cari takvim yılında hasta başına tamamlanmış izlem adedi gruplarıdır (0, 1–2, 3–5, 6+). Her hücre aktif hasta sayısıdır. Dönem filtresi yoktur; yıl otomatik olarak bugünün yılıdır.',
            'footnote' => '',
            'period_type' => '',
        ],
        'monthPlanStatus' => [
            'title' => 'Ay × plan durumu',
            'desc' => 'Planlı izlemde tamamlandı / bekliyor.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'Planlı izlem çaprazları',
            'intro' => 'Satırlar planlanan tarihe göre takvim ayları, sütunlar plan kaydının tamamlandı / bekliyor durumudur. Hücreler planlı izlem kaydı adedidir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'plan',
        ],
        'monthPlanPriority' => [
            'title' => 'Ay × öncelik',
            'desc' => 'Plan kayıtlarında öncelik kodu dağılımı.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'Planlı izlem çaprazları',
            'intro' => 'Satırlar takvim ayları, sütunlar plan önceliğidir (normal, orta, yüksek). Hücreler planlı izlem kaydı sayısıdır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'plan',
        ],
        'monthPlanZaman' => [
            'title' => 'Ay × plan zamanı',
            'desc' => 'Planlı izlemde sabah/öğle/akşam.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'Planlı izlem çaprazları',
            'intro' => 'Satırlar planlanan tarihe göre aylar, sütunlar planlanan zaman dilimidir (sabah, öğle, akşam, diğer). Hücreler plan kaydı adedidir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'plan',
        ],
        'ilcePlanStatus' => [
            'title' => 'İlçe × plan durumu',
            'desc' => 'İlçeye göre plan tamamlama kırılımı.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'İzlem ve Hizmet Kalitesi',
            'hub_section' => 'Planlı izlem çaprazları',
            'intro' => 'Satırlar hasta ilçesi, sütunlar plan tamamlandı / bekliyor durumudur. Seçilen dönemdeki plan kayıtları sayılır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'plan',
        ],
        'procedureMonth' => [
            'title' => 'İşlem × ay',
            'desc' => 'Dönemde kaydı olan tüm işlemlerin aylık adedi.',
            'icon' => 'fa-table',
            'color' => 'primary',
            'hub_group' => 'Klinik ve Operasyon Raporları',
            'hub_section' => 'İzlem içeriği',
            'intro' => 'Satırlar tamamlanmış izlemde «yapılan» alanında geçen işlem kodları (dönemde en az bir kaydı olan tüm işlemler), sütunlar takvim aylarıdır. Hücreler izlem-işlem atfı adedidir; visitProcedures raporu ile aynı kapsamdır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'personnelMonth' => [
            'title' => 'Personel × ay',
            'desc' => 'Dönemde kaydı olan tüm personelin aylık izlem atfı; ünvana göre gruplu.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'Klinik ve Operasyon Raporları',
            'hub_section' => 'İzlem içeriği',
            'intro' => 'Satırlar tamamlanmış izlemde «izlemiyapan» alanındaki personel (dönemde kaydı olan tümü); ünvan başlıkları altında listelenir. Sütunlar takvim aylarıdır. Her tamamlanmış izlemde personel alanındaki her kullanıcı ayrı atıf sayılır; visitPersonnel ile aynı kapsamdır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
        'branchMonthKons' => [
            'title' => 'Branş × ay (kons.)',
            'desc' => 'Dönemde kaydı olan tüm branşların aylık konsültasyon randevusu.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'Klinik ve Operasyon Raporları',
            'hub_section' => 'Randevu analizi',
            'intro' => 'Satırlar konsültasyon randevusundaki branşlar (dönemde kaydı olan tümü), sütunlar randevu tarihine göre takvim aylarıdır. Hücreler randevu adedidir; randevuTakvim konsültasyon bölümü ile uyumludur. Silinmiş branş kartları «Branş #id» olarak görünebilir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'randevu',
        ],
        'branchZamanKons' => [
            'title' => 'Branş × zaman (kons.)',
            'desc' => 'Tüm branşlarda sabah/öğle/akşam/diğer dağılımı.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'Klinik ve Operasyon Raporları',
            'hub_section' => 'Randevu analizi',
            'intro' => 'Satırlar branş, sütunlar randevu zaman dilimidir (sabah, öğle, akşam, diğer). Hücreler konsültasyon randevu adedidir; branchMonthKons ile aynı randevu havuzu. Zaman kodu 1/2/3 kullanılır; eski 0–2 kayıtlar normalize edilir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'randevu',
        ],
        'monthAttendKons' => [
            'title' => 'Ay × katılım (kons.)',
            'desc' => 'Aylık geldi / gelmedi dağılımı.',
            'icon' => 'fa-table',
            'color' => 'info',
            'hub_group' => 'Klinik ve Operasyon Raporları',
            'hub_section' => 'Randevu analizi',
            'intro' => 'Satırlar randevu tarihine göre takvim ayları, sütunlar hasta_geldi alanındaki katılım durumudur (geldi / gelmedi / belirsiz). Hücreler konsültasyon randevu adedidir. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'randevu',
        ],
        'exitReasonYear' => [
            'title' => 'Çıkış nedeni × yıl',
            'desc' => 'Pasife alınanların neden ve yıl kırılımı.',
            'icon' => 'fa-table',
            'color' => 'danger',
            'hub_group' => 'Pasif ve Çıkış Analizi',
            'hub_section' => 'Pasif çıkış',
            'intro' => 'Satırlar çıkış nedeni (PatientCareHelper tanımları 1–8; belirtilmemiş ayrı), sütunlar pasife alınma yılıdır (pasiftarihi, yoksa kayıt tarihi). Hücreler pasif (pasif=1) hasta sayısıdır. Son yedi yıl kapsanır; seyrek nedenler «Diğer nedenler» satırında toplanabilir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'exitReasonTenure' => [
            'title' => 'Çıkış nedeni × kayıt süresi',
            'desc' => 'Çıkış nedeni ile sistemde kalma süresi.',
            'icon' => 'fa-table',
            'color' => 'danger',
            'hub_group' => 'Pasif ve Çıkış Analizi',
            'hub_section' => 'Pasif çıkış',
            'intro' => 'Satırlar çıkış nedeni, sütunlar kayıt ile pasif tarihi arası süre gruplarıdır (süre bilinmiyor, 0–6 ay, 6–12 ay, 1–3 yıl, 3+ yıl). Hücreler pasif hasta sayısıdır. Satır etiketleri PatientCareHelper çıkış neden listesinden gelir.',
            'footnote' => '',
            'period_type' => '',
        ],
        'exitMonthIlce' => [
            'title' => 'Ay × ilçe (çıkış)',
            'desc' => 'Pasife alınanların ay ve ilçe dağılımı.',
            'icon' => 'fa-table',
            'color' => 'secondary',
            'hub_group' => 'Pasif ve Çıkış Analizi',
            'hub_section' => 'Pasif çıkış',
            'intro' => 'Satırlar hasta ilçesi (yoğun ilçeler; kalan «Diğer ilçeler»), sütunlar pasife geçiş ayıdır (YYYY-MM). Hücreler pasif hasta sayısıdır. Dönem filtresinden son 3, 6, 9, 12 veya 24 ay seçilebilir.',
            'footnote' => '',
            'period_type' => 'visit',
        ],
    ];

    public static function has(string $id): bool {
        return isset(self::REPORTS[$id]);
    }

    /** @return array<string, mixed> */
    public static function definition(string $id): array {
        return self::REPORTS[$id] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array {
        return self::REPORTS;
    }

    public static function actionFor(string $id): string {
        return 'xTab_' . $id;
    }

    public static function idFromAction(string $action): ?string {
        if (!str_starts_with($action, 'xTab_')) {
            return null;
        }
        $id = substr($action, 5);

        return self::has($id) ? $id : null;
    }

    /**
     * Hub gruplarına çapraz tablo kartlarını ekler (mevcut bölümlere veya yeni alt bölüme).
     *
     * @param array<int, array<string, mixed>> $groups
     */
    public static function injectHubCards(array &$groups): void {
        foreach (self::REPORTS as $id => $def) {
            if (!empty($def['skip_hub'])) {
                continue;
            }
            $hubGroup = (string) ($def['hub_group'] ?? '');
            $hubSection = (string) ($def['hub_section'] ?? '');
            if ($hubGroup === '') {
                continue;
            }
            $card = [
                'action' => self::actionFor($id),
                'title' => (string) ($def['title'] ?? $id),
                'desc' => (string) ($def['desc'] ?? ''),
                'icon' => (string) ($def['icon'] ?? 'fa-table'),
                'color' => (string) ($def['color'] ?? 'secondary'),
            ];
            if (array_key_exists('dashboard_quick', $def) && $def['dashboard_quick'] === false) {
                $card['dashboard_quick'] = false;
            }
            $placed = false;
            foreach ($groups as &$group) {
                if ((string) ($group['title'] ?? '') !== $hubGroup) {
                    continue;
                }
                if (!isset($group['sections']) || !is_array($group['sections'])) {
                    $group['sections'] = [['label' => '', 'cards' => $group['cards'] ?? []]];
                    unset($group['cards']);
                }
                foreach ($group['sections'] as &$section) {
                    if (trim((string) ($section['label'] ?? '')) !== $hubSection) {
                        continue;
                    }
                    $section['cards'][] = $card;
                    $placed = true;
                    break 2;
                }
                if (!$placed && $hubSection !== '') {
                    $group['sections'][] = [
                        'label' => $hubSection,
                        'cards' => [$card],
                    ];
                    $placed = true;
                }
                break;
            }
            unset($group, $section);
            if (!$placed) {
                $groups[] = [
                    'title' => $hubGroup,
                    'desc' => 'Çapraz tablo raporları',
                    'icon' => 'fa-table',
                    'accent' => 'secondary',
                    'sections' => [
                        ['label' => $hubSection !== '' ? $hubSection : 'Çapraz tablolar', 'cards' => [$card]],
                    ],
                ];
            }
        }
    }
}
