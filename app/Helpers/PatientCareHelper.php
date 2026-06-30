<?php
namespace App\Helpers;

/**
 * Hasta bakım alanları (mama, pansuman günleri vb.) — kod/etiket eşlemesi.
 */
final class PatientCareHelper {

    /** Haftanın günü kodları (#__hastalar.pgunleri — FIND_IN_SET ile uyumlu). @return array<int, string> */
    public static function pansumanGunOptions(): array {
        return [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];
    }

    /** @return int[] 1–7 sıralı benzersiz */
    public static function parsePgunleriToInts($stored): array {
        if ($stored === null || $stored === '') {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', (string) $stored, -1, PREG_SPLIT_NO_EMPTY);
        $nums = [];
        foreach ($parts as $p) {
            if (is_numeric($p)) {
                $n = (int) $p;
                if ($n >= 1 && $n <= 7) {
                    $nums[] = $n;
                }
            }
        }
        $nums = array_values(array_unique($nums));
        sort($nums);
        return $nums;
    }

    /** POST pgunleri[] → "1,2,5" */
    public static function normalizePgunleriCsvFromArray($arr): string {
        if (!is_array($arr) || $arr === []) {
            return '';
        }
        $nums = [];
        foreach ($arr as $p) {
            if (is_numeric($p)) {
                $n = (int) $p;
                if ($n >= 1 && $n <= 7) {
                    $nums[] = $n;
                }
            }
        }
        $nums = array_values(array_unique($nums));
        sort($nums);
        return implode(',', $nums);
    }

    public static function formatPgunleriForDisplay($stored): string {
        $nums = self::parsePgunleriToInts($stored);
        if ($nums === []) {
            return '—';
        }
        $labels = self::pansumanGunOptions();
        $parts = [];
        foreach ($nums as $n) {
            if (isset($labels[$n])) {
                $parts[] = $labels[$n];
            }
        }
        return $parts !== [] ? implode(', ', $parts) : '—';
    }

    public static function pzamanLabel($raw): string {
        if ($raw === null || $raw === '') {
            return '—';
        }

        return \App\Helpers\ZamanDilimiHelper::label((int) $raw);
    }

    /**
     * Dosya pasif (pasif = 1) nedeni — `#__hastalar.pasifnedeni` "1" … "8".
     *
     * @return array<int, array{label: string, icon: string, color: string}>
     */
    public static function pasifDosyaNedeniDefinitions(): array {
        return [
            1 => ['label' => 'İyileşme', 'icon' => 'fa-person-walking-arrow-right', 'color' => 'text-success'],
            2 => ['label' => 'Vefat', 'icon' => 'fa-cross', 'color' => 'text-secondary'],
            3 => ['label' => 'İkamet Değişikliği', 'icon' => 'fa-truck-ramp-box', 'color' => 'text-primary'],
            4 => ['label' => 'Tedaviyi Reddetme', 'icon' => 'fa-user-slash', 'color' => 'text-danger'],
            5 => ['label' => 'Tedaviye Yanıt Alamama', 'icon' => 'fa-heart-crack', 'color' => 'text-warning'],
            6 => ['label' => 'Sonlandırmanın Talep Edilmesi', 'icon' => 'fa-comment-slash', 'color' => 'text-info'],
            7 => ['label' => 'Tedaviye Personel Gerekmemesi', 'icon' => 'fa-user-check', 'color' => 'text-success'],
            8 => ['label' => 'ESH Takibine Uygun Olmaması', 'icon' => 'fa-house-circle-xmark', 'color' => 'text-danger'],
            9 => ['label' => 'Başka Kuruma Nakil', 'icon' => 'fa-building-circle-arrow-right', 'color' => 'text-primary'],
        ];
    }

    /**
     * Hasta düzenleme — pasif nedeni listesi (9 yalnızca yönetici).
     *
     * @return array<int, array{label: string, icon: string, color: string}>
     */
    public static function pasifDosyaNedeniDefinitionsForEdit(bool $isAdmin): array
    {
        $defs = self::pasifDosyaNedeniDefinitions();
        if ($isAdmin) {
            return $defs;
        }
        unset($defs[PatientKurumTransfer::PASIF_NEDENI_NAKIL]);

        return $defs;
    }

    /** @return array<string, array{label: string, icon: string, color: string}> */
    public static function pasifDosyaNedeniDefinitionsStringKeys(): array {
        $out = [];
        foreach (self::pasifDosyaNedeniDefinitions() as $k => $v) {
            $out[(string) $k] = $v;
        }
        return $out;
    }

    /** Birleşik liste / filtre için kod => etiket */
    public static function pasifDosyaNedeniFilterLabels(): array {
        $out = [];
        foreach (self::pasifDosyaNedeniDefinitions() as $k => $v) {
            $out[(string) $k] = $v['label'];
        }
        return $out;
    }

    public static function pasifDosyaNedeniLabelByCode($code): string {
        $i = is_numeric($code) ? (int) $code : 0;
        $def = self::pasifDosyaNedeniDefinitions();
        return $def[$i]['label'] ?? 'Tanımsız';
    }

    public static function normalizePasifNedeniForEdit($raw): int {
        if ($raw === null || $raw === '') {
            return 1;
        }
        $i = (int) $raw;
        return ($i >= 1 && $i <= 9) ? $i : 1;
    }

    /** Bekleyen (nakil) hastada pasifnedeni korunur; diğer durumlarda temizlenir. */
    public static function pasifNedeniForNonPassiveStore(int $pasifVal, object $currentPatient): string
    {
        if ($pasifVal === -3 && PatientKurumTransfer::isWaitingFromNakil($currentPatient)) {
            return (string) PatientKurumTransfer::PASIF_NEDENI_NAKIL;
        }

        return '';
    }

    public static function normalizePasifNedeniForStore($raw): string {
        return (string) self::normalizePasifNedeniForEdit($raw);
    }

    /** Normal kullanıcı: bekleyen, aktif veya pasif dosyada durum değiştirebilir (→ aktif/pasif). */
    public static function canNormalUserChangePasifOnEdit(int $currentPasif): bool {
        return in_array($currentPasif, [-3, 0, 1], true);
    }

    /** Hasta düzenle — kayıt durumu (pasif) seçenekleri. @return array<int, array{label: string, outline: string}> */
    public static function dosyaDurumuPasifOptions(bool $isAdmin, ?int $currentPasif = null): array {
        $base = [
            0 => ['label' => 'Aktif', 'outline' => 'success'],
            1 => ['label' => 'Pasif', 'outline' => 'danger'],
        ];
        if ($isAdmin) {
            return $base + [
                -3 => ['label' => 'Bekleyen', 'outline' => 'info'],
                4 => ['label' => 'Araf', 'outline' => 'warning'],
                -1 => ['label' => 'Muhtemel ölen', 'outline' => 'warning'],
                5 => ['label' => 'Silinen', 'outline' => 'dark'],
            ];
        }
        $cur = $currentPasif ?? 0;
        if (self::canNormalUserChangePasifOnEdit($cur)) {
            return $base;
        }
        return [];
    }

    /** @return int[] */
    public static function allowedPasifValuesForEdit(bool $isAdmin, ?int $currentPasif = null): array {
        if ($isAdmin) {
            return array_keys(self::dosyaDurumuPasifOptions(true, $currentPasif));
        }
        $cur = is_numeric($currentPasif) ? (int) $currentPasif : 0;
        if (self::canNormalUserChangePasifOnEdit($cur)) {
            return [0, 1];
        }
        return is_numeric($currentPasif) ? [(int) $currentPasif] : [0];
    }

    /** Hasta listesi / rozet — pasif kodu etiketi. */
    public static function pasifBadgeMeta(mixed $pasif): array {
        $p = is_numeric($pasif) ? (int) $pasif : 0;
        $opts = self::dosyaDurumuPasifOptions(true, $p);
        if (isset($opts[$p])) {
            return [
                'label' => $opts[$p]['label'],
                'outline' => $opts[$p]['outline'],
            ];
        }

        return ['label' => 'Pasif (' . $p . ')', 'outline' => 'secondary'];
    }

    public static function normalizePasifForStore($submitted, $current, bool $isAdmin): int {
        $cur = is_numeric($current) ? (int) $current : 0;
        $allowed = self::allowedPasifValuesForEdit($isAdmin, $cur);
        if (!in_array($cur, $allowed, true)) {
            $cur = $allowed[0] ?? 0;
        }
        if ($submitted === null || $submitted === '') {
            return $cur;
        }
        $v = (int) $submitted;
        return in_array($v, $allowed, true) ? $v : $cur;
    }

    /** @return array<int, string> */
    public static function mamaCesitOptions(): array {
        return [
            0 => 'Bilinmiyor',
            1 => 'Abbott',
            2 => 'Nutricia',
            3 => 'Nestle',
        ];
    }

    public static function normalizeMamaCesit($raw): int {
        if ($raw === null || $raw === '') {
            return 0;
        }
        if (is_numeric($raw)) {
            $i = (int) $raw;
            return ($i >= 0 && $i <= 3) ? $i : 0;
        }
        return 0;
    }

    public static function mamaCesitLabel($stored): string {
        $i = self::normalizeMamaCesit($stored);
        return self::mamaCesitOptions()[$i] ?? 'Bilinmiyor';
    }

    /** 1: Kendi kurumu, 0: Dış kurum */
    public static function normalizeMamaRaporYeri($raw): int {
        if ($raw === null || $raw === '') {
            return 0;
        }
        return ((int) $raw) === 1 ? 1 : 0;
    }

    public static function mamaRaporYeriLabel($stored): string {
        return self::normalizeMamaRaporYeri($stored) === 1 ? 'Kendi kurumu' : 'Dış kurum';
    }

    /** @return array<string, string> */
    public static function kanGrubuOptions(): array
    {
        return [
            '' => 'Seçiniz...',
            'A+' => 'A Rh+',
            'A-' => 'A Rh−',
            'B+' => 'B Rh+',
            'B-' => 'B Rh−',
            'AB+' => 'AB Rh+',
            'AB-' => 'AB Rh−',
            '0+' => '0 Rh+',
            '0-' => '0 Rh−',
        ];
    }

    public static function normalizeKanGrubu(mixed $raw): ?string
    {
        $v = trim((string) ($raw ?? ''));
        if ($v === '') {
            return null;
        }
        $allowed = array_keys(self::kanGrubuOptions());
        unset($allowed['']);

        return in_array($v, $allowed, true) ? $v : null;
    }

    public static function kanGrubuLabel(mixed $stored): string
    {
        $v = self::normalizeKanGrubu($stored);
        if ($v === null) {
            return '';
        }

        return self::kanGrubuOptions()[$v] ?? $v;
    }
}
