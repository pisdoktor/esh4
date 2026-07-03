<?php
namespace App\Helpers;

/**
 * Kullanıcı profili «iş özeti» metrik tanımları ve URL yardımcıları.
 */
class UserProfileStatsHelper
{
    /**
     * @return array<string, array{
     *   key: string,
     *   label: string,
     *   group: string,
     *   group_label: string,
     *   group_icon: string,
     *   list_type: string,
     *   stat_key: string,
     *   hint: string
     * }>
     */
    public static function metrics(): array
    {
        $defs = [
            ['visits_total', 'Toplam izlem kaydı', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'visit', 'visits_total', 'Size atanan tüm izlem satırları'],
            ['visits_done', 'Yapıldı (izlem)', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'visit', 'visits_done', 'yapildimi = 1'],
            ['visits_missed', 'Yapılmadı (izlem)', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'visit', 'visits_missed', 'yapildimi = 0'],
            ['visits_distinct_patients', 'Farklı hasta (izlem)', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'patient_visit', 'visits_distinct_patients', 'İzlemde adınızın geçtiği benzersiz hastalar'],
            ['visits_with_vehicle', 'Araç tanımlı izlem', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'visit', 'visits_with_vehicle', 'Araç seçilmiş izlemler'],
            ['visits_with_kons', 'Konsültasyon (branş yazılı)', 'izlem', 'İzlem', 'fa-solid fa-stethoscope', 'visit', 'visits_with_kons', 'Branş alanı dolu izlemler'],
            ['plans_total', 'Toplam plan satırı', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'plan', 'plans_total', 'Planlayanlar arasında yer aldığınız kayıtlar'],
            ['plans_open', 'Bekleyen plan', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'plan', 'plans_open', 'durum = 0'],
            ['plans_done', 'Tamamlanan plan', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'plan', 'plans_done', 'durum = 1'],
            ['plans_distinct_patients', 'Farklı hasta (plan)', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'patient_plan', 'plans_distinct_patients', 'Planda adınızın geçtiği benzersiz hastalar'],
            ['plans_open_overdue', 'Gecikmiş açık plan', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'plan', 'plans_open_overdue', 'Bekleyen ve plan tarihi geçmiş'],
            ['plans_due_next_7_days', 'Önümüzdeki 7 gün (açık plan)', 'plan', 'Planlı izlem', 'fa-solid fa-calendar-check', 'plan', 'plans_due_next_7_days', 'Bugünden itibaren bir hafta içinde bekleyen'],
            ['visits_this_month', 'Bu ay — izlem', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'visit', 'visits_this_month', 'İzlem tarihi bu ay'],
            ['plans_this_month', 'Bu ay — plan', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'plan', 'plans_this_month', 'Planlanan tarih bu ay'],
            ['visits_this_year', 'Bu yıl — izlem', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'visit', 'visits_this_year', 'İzlem tarihi bu yıl'],
            ['plans_this_year', 'Bu yıl — plan', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'plan', 'plans_this_year', 'Planlanan tarih bu yıl'],
            ['visits_last_30_days', 'Son 30 gün — izlem', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'visit', 'visits_last_30_days', 'Son 30 günde izlem tarihi'],
            ['visits_last_7_days', 'Son 7 gün — izlem', 'zaman', 'Zaman çizelgesi', 'fa-solid fa-clock', 'visit', 'visits_last_7_days', 'Son 7 günde izlem tarihi'],
            ['nobet_total', 'Onaylı nöbet günü', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'nobet', 'nobet_total', 'durum = 1 onaylı nöbet'],
            ['nobet_this_month', 'Onaylı nöbet (bu ay)', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'nobet', 'nobet_this_month', 'Bu ay onaylı nöbet'],
            ['izin_total', 'İzin kaydı', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'izin', 'izin_total', 'Personel izin takvimi'],
            ['istek_total', 'Mazeret / talep kaydı', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'istek', 'istek_total', 'Personel talep kayıtları'],
            ['ekip_assignments', 'Ekip listesinde olduğunuz gün', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'ekip', 'ekip_assignments', 'Rota ekiplerinde adınızın geçtiği kayıt'],
            ['wound_photos_uploaded', 'Yüklediğiniz yara fotoğrafı', 'personel', 'Personel ve dosya', 'fa-solid fa-id-badge', 'wound_photo', 'wound_photos_uploaded', 'Sizin yüklediğiniz fotoğraflar'],
        ];

        $out = [];
        foreach ($defs as $d) {
            $out[$d[0]] = [
                'key' => $d[0],
                'label' => $d[1],
                'group' => $d[2],
                'group_label' => $d[3],
                'group_icon' => $d[4],
                'list_type' => $d[5],
                'stat_key' => $d[6],
                'hint' => $d[7],
            ];
        }

        return $out;
    }

    public static function metric(string $key): ?array
    {
        $key = trim($key);
        $all = self::metrics();

        return $all[$key] ?? null;
    }

    /**
     * Hub sayfası için gruplu metrik listesi.
     *
     * @return list<array{group: string, label: string, icon: string, items: list<array>}>
     */
    public static function hubGroups(): array
    {
        $order = ['izlem', 'plan', 'zaman', 'personel'];
        $labels = [
            'izlem' => ['İzlem', 'fa-solid fa-stethoscope'],
            'plan' => ['Planlı izlem (takvim)', 'fa-solid fa-calendar-check'],
            'zaman' => ['Zaman çizelgesi', 'fa-solid fa-clock'],
            'personel' => ['Personel ve dosya', 'fa-solid fa-id-badge'],
        ];
        $buckets = [];
        foreach (self::metrics() as $m) {
            $g = $m['group'];
            if (!isset($buckets[$g])) {
                $buckets[$g] = [];
            }
            $buckets[$g][] = $m;
        }

        $groups = [];
        foreach ($order as $g) {
            if (empty($buckets[$g])) {
                continue;
            }
            [$label, $icon] = $labels[$g];
            $groups[] = [
                'group' => $g,
                'label' => $label,
                'icon' => $icon,
                'items' => $buckets[$g],
            ];
        }

        return $groups;
    }

    /**
     * @param int|null $forUserId Başka kullanıcı (yönetici görünümü); oturum kullanıcısı için null
     */
    public static function statsHubUrl(?int $forUserId = null): string
    {
        $q = ['controller' => 'User', 'action' => 'stats'];
        if ($forUserId !== null && $forUserId > 0) {
            $q['user_id'] = $forUserId;
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * @param int|null $forUserId Başka kullanıcı (yönetici görünümü); oturum kullanıcısı için null
     */
    public static function statsDetailUrl(string $metricKey, ?int $forUserId = null): string
    {
        $q = [
            'controller' => 'User',
            'action' => 'statsDetail',
            'metric' => $metricKey,
        ];
        if ($forUserId !== null && $forUserId > 0) {
            $q['user_id'] = $forUserId;
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }
}
