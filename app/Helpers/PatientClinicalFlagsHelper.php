<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Hasta klinik bayrakları — tek kaynak (form, görünüm, istatistik).
 */
class PatientClinicalFlagsHelper
{
    /** @return array<string, string> */
    private static function labelMap(): array
    {
        return [
            'gecici' => 'Geçici takipli',
            'erapor' => 'E-Rapor hastası',
            'o2bagimli' => 'Oksijen bağımlı',
            'cpap' => 'CPAP / BiPAP',
            'ventilator' => 'Ventilatör bağımlı',
            'trakeostomi' => 'Trakeostomi',
            'aspirasyon' => 'Aspirasyon ihtiyacı',
            'ng' => 'NG (Beslenme tüpü)',
            'peg' => 'PEG',
            'kolostomi' => 'Kolostomi',
            'ileostomi' => 'İleostomi',
            'urostomi' => 'Ürostomi',
            'sonda' => 'Mesane sonda',
            'port' => 'Port kateter',
            'picc' => 'PICC / Santral kateter (CVC)',
            'ivtedavi' => 'Aktif IV / enjeksiyon tedavisi',
            'diyaliz' => 'Ev diyalizi (HD/PD)',
            'dren' => 'Aktif dren',
            'basiyarasi' => 'Aktif bası yarası',
            'yatak' => 'Hasta yatağı',
            'izolasyon' => 'İzolasyon / enfeksiyon önlemi',
            'bez' => 'Bez kullanımı',
            'mama' => 'Mama kullanımı',
        ];
    }

    /** @return array<string, string> */
    private static function shortLabelMap(): array
    {
        return [
            'o2bagimli' => 'O₂',
            'cpap' => 'CPAP',
            'ventilator' => 'Vent',
            'trakeostomi' => 'Trakeo',
            'aspirasyon' => 'Aspir.',
            'ng' => 'NG',
            'peg' => 'PEG',
            'kolostomi' => 'Kolo.',
            'ileostomi' => 'İleo.',
            'urostomi' => 'Üro.',
            'sonda' => 'Sonda',
            'port' => 'Port',
            'picc' => 'PICC',
            'ivtedavi' => 'IV',
            'diyaliz' => 'Diyaliz',
            'dren' => 'Dren',
            'basiyarasi' => 'Bası',
            'yatak' => 'Yatak',
            'izolasyon' => 'İzo.',
        ];
    }

    /** Genel sekmede tıklanarak değiştirilebilir bayrak anahtarları. @return list<string> */
    public static function generalTabToggleKeys(): array
    {
        $keys = [];
        foreach (self::generalTabGroups() as $group) {
            foreach ($group['keys'] as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** Genel sekme grupları (görüntüleme). @return array<string, array{title: string, keys: list<string>}> */
    public static function generalTabGroups(): array
    {
        return [
            'dosya' => [
                'title' => 'Dosya',
                'keys' => ['gecici', 'erapor'],
            ],
            'solunum' => [
                'title' => 'Solunum',
                'keys' => ['o2bagimli', 'cpap', 'ventilator', 'trakeostomi', 'aspirasyon'],
            ],
            'beslenme' => [
                'title' => 'Beslenme',
                'keys' => ['ng', 'peg'],
            ],
            'stoma' => [
                'title' => 'Stoma / idrar',
                'keys' => ['kolostomi', 'ileostomi', 'urostomi', 'sonda'],
            ],
            'vaskuler' => [
                'title' => 'Vasküler / tedavi',
                'keys' => ['port', 'picc', 'ivtedavi', 'diyaliz'],
            ],
            'diger' => [
                'title' => 'Diğer',
                'keys' => ['dren', 'basiyarasi', 'yatak', 'izolasyon'],
            ],
        ];
    }

    /** Tıbbi cihaz düzenleme grupları (izolasyon hariç — klinik_uyarilar; sonda ayrı blok). @return array<string, array{title: string, keys: list<string>}> */
    public static function deviceEditGroups(): array
    {
        $groups = self::generalTabGroups();
        unset($groups['dosya']);
        $groups['stoma']['keys'] = array_values(array_filter(
            $groups['stoma']['keys'],
            static fn (string $k): bool => $k !== 'sonda'
        ));
        $groups['diger']['keys'] = array_values(array_filter(
            $groups['diger']['keys'],
            static fn (string $k): bool => $k !== 'yatak' && $k !== 'izolasyon'
        ));

        return $groups;
    }

    /** Tam formda checkbox sıfırlama + bind için tüm boolean bayraklar. @return list<string> */
    public static function allBooleanKeys(): array
    {
        $keys = [];
        foreach (self::generalTabGroups() as $group) {
            foreach ($group['keys'] as $key) {
                $keys[] = $key;
            }
        }
        $keys[] = 'bezrapor';

        return array_values(array_unique($keys));
    }

    /** İstatistik özel cihaz / adres filtresi anahtarları. @return list<string> */
    public static function statsReportKeys(): array
    {
        $keys = [];
        foreach (self::generalTabGroups() as $group) {
            foreach ($group['keys'] as $key) {
                $keys[] = $key;
            }
        }
        $keys[] = 'bez';
        $keys[] = 'mama';

        return array_values(array_unique($keys));
    }

    /** Kimlik kartı özet chip’leri (dosya/sarf hariç). @return list<string> */
    public static function summaryChipKeys(): array
    {
        $exclude = ['gecici', 'erapor', 'bez', 'mama', 'yatak', 'izolasyon'];
        $keys = [];
        foreach (self::generalTabGroups() as $group) {
            foreach ($group['keys'] as $key) {
                if (!in_array($key, $exclude, true)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    public static function label(string $key): string
    {
        return self::labelMap()[$key] ?? $key;
    }

    public static function shortLabel(string $key): string
    {
        return self::shortLabelMap()[$key] ?? self::label($key);
    }

    /** @return array<string, string> */
    public static function statsReportLabels(): array
    {
        $out = [];
        foreach (self::statsReportKeys() as $key) {
            $out[$key] = self::label($key);
        }

        return $out;
    }

    /** @return array<string, string> */
    public static function adresFilterLabels(): array
    {
        $out = [];
        foreach (self::statsReportKeys() as $key) {
            $out[$key] = self::label($key) . (in_array($key, ['gecici'], true) ? '' : '');
        }
        $out['gecici'] = 'Geçici kayıtlı hastalar';
        $out['ng'] = 'NG takılı hastalar';
        $out['peg'] = 'PEGli hastalar';
        $out['port'] = 'PORTlu hastalar';
        $out['o2bagimli'] = 'O2 bağımlı hastalar';
        $out['ventilator'] = 'Ventilatör takılı hastalar';
        $out['kolostomi'] = 'Kolostomili hastalar';
        $out['ileostomi'] = 'İleostomili hastalar';
        $out['urostomi'] = 'Ürostomili hastalar';
        $out['sonda'] = 'Sondalı hastalar';
        $out['mama'] = 'Mama kullanan hastalar';
        $out['yatak'] = 'Hasta yatağı olan hastalar';
        $out['bez'] = 'Alt bezi kullanan hastalar';
        $out['trakeostomi'] = 'Trakeostomili hastalar';
        $out['cpap'] = 'CPAP / BiPAP kullanan hastalar';
        $out['aspirasyon'] = 'Aspirasyon ihtiyacı olan hastalar';
        $out['picc'] = 'PICC / CVC takılı hastalar';
        $out['dren'] = 'Aktif dreni olan hastalar';
        $out['diyaliz'] = 'Ev diyalizi hastaları';
        $out['basiyarasi'] = 'Aktif bası yarası olan hastalar';
        $out['ivtedavi'] = 'IV / enjeksiyon tedavisi olan hastalar';
        $out['izolasyon'] = 'İzolasyon önlemi gereken hastalar';

        return $out;
    }

    /**
     * @return array<string, string> key => kısa rozet harfi
     */
    public static function listBadgeFlags(): array
    {
        return [
            'o2bagimli' => 'O',
            'ventilator' => 'V',
            'trakeostomi' => 'T',
            'sonda' => 'S',
            'alerji' => 'A',
            'izolasyon' => 'I',
        ];
    }

    /** @return list<string> */
    public static function listBadgeFilterKeys(): array
    {
        return array_keys(self::listBadgeFlags());
    }

    /**
     * Birleşik hasta listesi — klinik rozet filtresi seçenekleri.
     *
     * @return array<string, string>
     */
    public static function listBadgeFilterChoices(): array
    {
        $titles = [
            'o2bagimli' => 'Oksijen bağımlı',
            'ventilator' => 'Ventilatör bağımlı',
            'trakeostomi' => 'Trakeostomi',
            'sonda' => 'Mesane sonda',
            'alerji' => 'Alerji kayıtlı',
            'izolasyon' => 'İzolasyon / enfeksiyon önlemi',
        ];
        $out = [];
        foreach (self::listBadgeFlags() as $key => $letter) {
            $title = $titles[$key] ?? self::label($key);
            $out[$key] = $title . ' (' . $letter . ')';
        }

        return $out;
    }

    /** Yara fotoğrafları modülü — aktif bası yarası işaretli hastalarda açılır. */
    public static function isWoundPhotosModuleEnabled(object $patient): bool
    {
        return (int) ($patient->basiyarasi ?? 0) === 1;
    }

    /** Birleşik liste admin özellik filtresi — klinik rozet SQL parçası. */
    public static function unifiedFeatureFilterSql(string $featureKey): string
    {
        if (!array_key_exists($featureKey, self::listBadgeFlags())) {
            return '';
        }
        if ($featureKey === 'alerji') {
            return " AND (TRIM(COALESCE(h.alerji, '')) != '')";
        }

        return ' AND (CAST(h.' . $featureKey . ' AS SIGNED) != 0)';
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function activeFlags(object $patient): array
    {
        $out = [];
        foreach (self::summaryChipKeys() as $key) {
            if (!empty($patient->$key)) {
                $out[] = ['key' => $key, 'label' => self::shortLabel($key)];
            }
        }

        return $out;
    }

    /** SQL: IF(col=1,1,0)+... ifadesi (tablo öneki opsiyonel). */
    public static function sqlFlagSumExpression(?string $tableAlias = null): string
    {
        $prefix = $tableAlias !== null && $tableAlias !== '' ? $tableAlias . '.' : '';
        $parts = [];
        foreach (self::statsReportKeys() as $key) {
            $parts[] = 'IF(' . $prefix . '`' . $key . '`=1,1,0)';
        }

        return '(' . implode('+', $parts) . ')';
    }
}
