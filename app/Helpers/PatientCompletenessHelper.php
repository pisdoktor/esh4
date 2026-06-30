<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Guvence;
use App\Models\Patient;

/**
 * Hasta kartı doluluk skoru — esh_hastalar anlamlı alanları (tek kaynak).
 */
class PatientCompletenessHelper
{
    /** @var array<string, string> Alan etiketi → düzenleme modal bölüm anahtarı */
    private const FIELD_EDIT_SECTION = [
        'tckimlik' => 'kimlik_iletisim',
        'isim' => 'kimlik_iletisim',
        'soyisim' => 'kimlik_iletisim',
        'anneAdi' => 'kimlik_iletisim',
        'babaAdi' => 'kimlik_iletisim',
        'dogumtarihi' => 'kimlik_iletisim',
        'cinsiyet' => 'kimlik_iletisim',
        'guvence' => 'kimlik_iletisim',
        'yupasno' => 'kimlik_iletisim',
        'kangrubu' => 'kimlik_iletisim',
        'ceptel1' => 'kimlik_iletisim',
        'bakimveren_ad' => 'kimlik_iletisim',
        'bakimveren_tel' => 'kimlik_iletisim',
        'bakimveren_yakinlik' => 'kimlik_iletisim',
        'ailehekimi' => 'kimlik_iletisim',
        'ailehekimitel' => 'kimlik_iletisim',
        'ilce' => 'adres',
        'mahalle' => 'adres',
        'sokak' => 'adres',
        'kapino' => 'adres',
        'coords' => 'adres',
        'boy' => 'fiziksel_olcumler',
        'kilo' => 'fiziksel_olcumler',
        'bagimlilik' => 'fiziksel_olcumler',
        'barbeslenme' => 'fiziksel_olcumler',
        'barbanyo' => 'fiziksel_olcumler',
        'barbakim' => 'fiziksel_olcumler',
        'bargiyinme' => 'fiziksel_olcumler',
        'barbarsak' => 'fiziksel_olcumler',
        'barmesane' => 'fiziksel_olcumler',
        'bartuvalet' => 'fiziksel_olcumler',
        'bartransfer' => 'fiziksel_olcumler',
        'barmobilite' => 'fiziksel_olcumler',
        'barmerdiven' => 'fiziksel_olcumler',
        'hastaliklar' => 'klinik_tanilar',
        'kayittarihi' => 'kimlik_iletisim',
        'randevutarihi' => 'kimlik_iletisim',
        'zaman' => 'kimlik_iletisim',
        'sondatarihi' => 'bakim_sarf',
        'pgunleri' => 'bakim_sarf',
        'pzaman' => 'bakim_sarf',
        'mamacesit' => 'bakim_sarf',
        'mamaraporbitis' => 'bakim_sarf',
        'bezraporbitis' => 'bakim_sarf',
        'pasiftarihi' => 'dosya_durumu',
        'pasifnedeni' => 'dosya_durumu',
        'profil_foto' => 'kimlik_iletisim',
    ];

    /**
     * @return array{
     *   pct: float,
     *   filled: int,
     *   total: int,
     *   profile: string,
     *   groups: list<array{key: string, label: string, pct: float, filled: int, total: int, missing: list<string>}>,
     *   missing: list<string>,
     *   clinical_info: string
     * }
     */
    public static function evaluate(object $patient): array
    {
        $profile = self::resolveProfile($patient);
        $groups = [];
        $allMissing = [];
        $filled = 0;
        $total = 0;

        foreach (self::groupDefinitions($profile, $patient) as $groupKey => $groupDef) {
            $groupFilled = 0;
            $groupTotal = 0;
            $groupMissing = [];

            foreach ($groupDef['fields'] as $fieldDef) {
                if (!self::fieldApplies($patient, $fieldDef)) {
                    continue;
                }
                $groupTotal++;
                $label = (string) $fieldDef['label'];
                if (self::isFieldFilled($patient, $fieldDef)) {
                    $groupFilled++;
                } else {
                    $groupMissing[] = $label;
                    $allMissing[] = $label;
                }
            }

            if ($groupTotal === 0) {
                continue;
            }

            $filled += $groupFilled;
            $total += $groupTotal;
            $groups[] = [
                'key' => $groupKey,
                'label' => (string) $groupDef['label'],
                'section' => (string) ($groupDef['section'] ?? ''),
                'pct' => round($groupFilled / $groupTotal * 100, 1),
                'filled' => $groupFilled,
                'total' => $groupTotal,
                'missing' => $groupMissing,
            ];
        }

        $pct = $total > 0 ? round($filled / $total * 100, 1) : 100.0;

        return [
            'pct' => $pct,
            'filled' => $filled,
            'total' => $total,
            'profile' => $profile,
            'groups' => $groups,
            'missing' => $allMissing,
            'clinical_info' => 'Klinik cihazlar: kayıtlı',
        ];
    }

    public static function pctColorClass(float $pct): string
    {
        if ($pct >= 85.0) {
            return 'success';
        }
        if ($pct >= 60.0) {
            return 'warning';
        }

        return 'danger';
    }

    /** Doluluk göstergesi yalnızca aktif hastada (pasif = 0). */
    public static function isVisibleForPatient(object $patient): bool
    {
        return Patient::isAktif($patient->pasif ?? null);
    }

    public static function renderListIndicator(object $patient): string
    {
        if (!self::isVisibleForPatient($patient)) {
            return '';
        }

        $eval = self::evaluate($patient);
        $pct = (float) $eval['pct'];
        $color = self::pctColorClass($pct);
        $pctInt = (int) round($pct);
        $missing = $eval['missing'];
        $tipMissing = array_slice($missing, 0, 5);
        $tip = 'Kart doluluk: %' . $pctInt . ' (' . $eval['filled'] . '/' . $eval['total'] . ')';
        if ($tipMissing !== []) {
            $tip .= "\nEksik: " . implode(', ', $tipMissing);
            if (count($missing) > 5) {
                $tip .= '…';
            }
        }
        $tip .= "\nDetay için hasta kartına gidin.";

        $barW = max(0, min(100, $pctInt));
        $title = htmlspecialchars($tip, ENT_QUOTES, 'UTF-8');

        return '<div class="esh-hasta-doluluk esh-hasta-doluluk--list mt-1" title="' . $title . '" data-bs-toggle="tooltip" data-bs-placement="top">'
            . '<div class="d-flex align-items-center gap-1">'
            . '<div class="progress flex-grow-1 esh-hasta-doluluk__bar" role="progressbar" aria-valuenow="' . $pctInt . '" aria-valuemin="0" aria-valuemax="100" aria-label="Kart doluluk %' . $pctInt . '">'
            . '<div class="progress-bar bg-' . $color . '" style="width:' . $barW . '%"></div>'
            . '</div>'
            . '<span class="esh-hasta-doluluk__pct text-' . $color . '">%' . $pctInt . '</span>'
            . '</div></div>';
    }

    public static function renderDetailTrigger(object $patient): string
    {
        if (!self::isVisibleForPatient($patient)) {
            return '';
        }

        $eval = self::evaluate($patient);
        $pct = (float) $eval['pct'];
        $color = self::pctColorClass($pct);
        $pctInt = (int) round($pct);
        $filled = (int) $eval['filled'];
        $total = (int) $eval['total'];
        $missingCount = count($eval['missing']);
        $barW = max(0, min(100, $pctInt));

        $missingHint = '';
        if ($missingCount > 0) {
            $missingHint = '<span class="esh-hasta-doluluk__missing-hint text-muted">'
                . $missingCount . ' eksik alan</span>';
        }

        return '<button type="button"'
            . ' class="esh-hasta-doluluk esh-hasta-doluluk--detail-trigger btn btn-light border w-100 text-start mb-3 py-2 px-3"'
            . ' data-bs-toggle="modal" data-bs-target="#patientDolulukModal"'
            . ' aria-controls="patientDolulukModal"'
            . ' aria-label="Kart doluluk detayı, yüzde ' . $pctInt . '">'
            . '<div class="d-flex flex-wrap align-items-center gap-2">'
            . '<span class="fw-semibold text-primary small text-uppercase flex-shrink-0">'
            . '<i class="fa-solid fa-chart-pie me-1" aria-hidden="true"></i>Kart Doluluk</span>'
            . '<div class="progress flex-grow-1 esh-hasta-doluluk__bar esh-hasta-doluluk__bar--detail" role="progressbar"'
            . ' aria-valuenow="' . $pctInt . '" aria-valuemin="0" aria-valuemax="100"'
            . ' aria-label="Kart doluluk yüzde ' . $pctInt . '">'
            . '<div class="progress-bar bg-' . $color . '" style="width:' . $barW . '%"></div>'
            . '</div>'
            . '<span class="esh-hasta-doluluk__pct text-' . $color . ' flex-shrink-0">%' . $pctInt
            . ' <span class="fw-normal opacity-75">(' . $filled . '/' . $total . ')</span></span>'
            . $missingHint
            . '<i class="fa-solid fa-chevron-right text-muted ms-auto flex-shrink-0" aria-hidden="true"></i>'
            . '</div></button>';
    }

    /**
     * Kart detay partial için veri (view include eder).
     *
     * @return array<string, mixed>
     */
    public static function cardContext(object $patient): array
    {
        $eval = self::evaluate($patient);
        $eval['color'] = self::pctColorClass((float) $eval['pct']);
        $eval['pct_int'] = (int) round((float) $eval['pct']);
        $eval['field_sections'] = self::FIELD_EDIT_SECTION;

        return $eval;
    }

    public static function editSectionForField(string $fieldKey): string
    {
        return self::FIELD_EDIT_SECTION[$fieldKey] ?? 'kimlik_iletisim';
    }

    private static function resolveProfile(object $patient): string
    {
        $pasif = trim((string) ($patient->pasif ?? '0'));

        return $pasif === '-3' ? 'waiting' : 'full';
    }

    /**
     * @return array<string, array{label: string, section?: string, fields: list<array<string, mixed>>}>
     */
    private static function groupDefinitions(string $profile, object $patient): array
    {
        $groups = [
            'kimlik' => [
                'label' => 'Kimlik',
                'section' => 'kimlik_iletisim',
                'fields' => [
                    ['key' => 'tckimlik', 'label' => 'TC Kimlik No', 'type' => 'tc'],
                    ['key' => 'isim', 'label' => 'Ad', 'type' => 'text'],
                    ['key' => 'soyisim', 'label' => 'Soyad', 'type' => 'text'],
                    ['key' => 'anneAdi', 'label' => 'Anne adı', 'type' => 'parent_name'],
                    ['key' => 'babaAdi', 'label' => 'Baba adı', 'type' => 'parent_name'],
                    ['key' => 'dogumtarihi', 'label' => 'Doğum tarihi', 'type' => 'date'],
                    ['key' => 'cinsiyet', 'label' => 'Cinsiyet', 'type' => 'gender'],
                    ['key' => 'guvence', 'label' => 'Güvence', 'type' => 'guvence'],
                    ['key' => 'yupasno', 'label' => 'YUPAS no', 'type' => 'text', 'when_yupas_guvence' => true],
                    ['key' => 'kangrubu', 'label' => 'Kan grubu', 'type' => 'text'],
                ],
            ],
            'iletisim' => [
                'label' => 'İletişim',
                'section' => 'kimlik_iletisim',
                'fields' => [
                    ['key' => 'ceptel1', 'label' => 'Cep telefonu 1', 'type' => 'phone_strict'],
                    ['key' => 'bakimveren_ad', 'label' => 'Bakım veren adı', 'type' => 'text'],
                    ['key' => 'bakimveren_tel', 'label' => 'Bakım veren telefonu', 'type' => 'phone_loose'],
                    ['key' => 'bakimveren_yakinlik', 'label' => 'Bakım veren yakınlık', 'type' => 'text'],
                    ['key' => 'ailehekimi', 'label' => 'Aile hekimi', 'type' => 'text'],
                    ['key' => 'ailehekimitel', 'label' => 'Aile hekimi telefonu', 'type' => 'phone_loose'],
                ],
            ],
            'adres' => [
                'label' => 'Adres',
                'section' => 'adres',
                'fields' => [
                    ['key' => 'ilce', 'label' => 'İlçe', 'type' => 'address_id'],
                    ['key' => 'mahalle', 'label' => 'Mahalle', 'type' => 'address_id'],
                    ['key' => 'sokak', 'label' => 'Sokak / cadde', 'type' => 'address_id'],
                    ['key' => 'kapino', 'label' => 'Kapı no', 'type' => 'address_id'],
                    ['key' => 'coords', 'label' => 'Koordinat', 'type' => 'text'],
                ],
            ],
            'olcum' => [
                'label' => 'Ölçüm',
                'section' => 'fiziksel_olcumler',
                'fields' => [
                    ['key' => 'boy', 'label' => 'Boy', 'type' => 'metric'],
                    ['key' => 'kilo', 'label' => 'Kilo', 'type' => 'metric'],
                ],
            ],
            'barthel' => [
                'label' => 'Barthel',
                'section' => 'fiziksel_olcumler',
                'fields' => [
                    ['key' => 'bagimlilik', 'label' => 'Bağımlılık skoru', 'type' => 'text'],
                    ['key' => 'barbeslenme', 'label' => 'Barthel — beslenme', 'type' => 'barthel_score'],
                    ['key' => 'barbanyo', 'label' => 'Barthel — banyo', 'type' => 'barthel_score'],
                    ['key' => 'barbakim', 'label' => 'Barthel — bakım', 'type' => 'barthel_score'],
                    ['key' => 'bargiyinme', 'label' => 'Barthel — giyinme', 'type' => 'barthel_score'],
                    ['key' => 'barbarsak', 'label' => 'Barthel — barsak', 'type' => 'barthel_score'],
                    ['key' => 'barmesane', 'label' => 'Barthel — mesane', 'type' => 'barthel_score'],
                    ['key' => 'bartuvalet', 'label' => 'Barthel — tuvalet', 'type' => 'barthel_score'],
                    ['key' => 'bartransfer', 'label' => 'Barthel — transfer', 'type' => 'barthel_score'],
                    ['key' => 'barmobilite', 'label' => 'Barthel — mobilite', 'type' => 'barthel_score'],
                    ['key' => 'barmerdiven', 'label' => 'Barthel — merdiven', 'type' => 'barthel_score'],
                ],
            ],
            'bakim' => [
                'label' => 'Bakım',
                'section' => 'bakim_sarf',
                'fields' => [
                    ['key' => 'sondatarihi', 'label' => 'Sonda tarihi', 'type' => 'date', 'when' => ['sonda' => 1]],
                    ['key' => 'pgunleri', 'label' => 'Pansuman günleri', 'type' => 'text', 'when' => ['pansuman' => 1]],
                    ['key' => 'pzaman', 'label' => 'Pansuman zamanı', 'type' => 'text', 'when' => ['pansuman' => 1]],
                    ['key' => 'mamacesit', 'label' => 'Mama çeşidi', 'type' => 'mama_cesit', 'when' => ['mama' => 1]],
                    ['key' => 'mamaraporbitis', 'label' => 'Mama rapor bitiş', 'type' => 'date', 'when' => ['mama' => 1]],
                    ['key' => 'bezraporbitis', 'label' => 'Bez rapor bitiş', 'type' => 'date', 'when' => ['bez' => 1]],
                ],
            ],
            'ek' => [
                'label' => 'Ek',
                'section' => 'kimlik_iletisim',
                'fields' => [
                    ['key' => 'profil_foto', 'label' => 'Profil fotoğrafı', 'type' => 'text'],
                ],
            ],
        ];

        if ($profile === 'waiting') {
            unset($groups['kayit_randevu'], $groups['tani']);
        } else {
            $groups['tani'] = [
                'label' => 'Tanı',
                'section' => 'klinik_tanilar',
                'fields' => [
                    ['key' => 'hastaliklar', 'label' => 'Hastalıklar (tanılar)', 'type' => 'hastaliklar'],
                ],
            ];
            $groups['kayit_randevu'] = [
                'label' => 'Kayıt / randevu',
                'section' => 'kimlik_iletisim',
                'fields' => [
                    ['key' => 'kayittarihi', 'label' => 'Kayıt tarihi', 'type' => 'date'],
                    ['key' => 'randevutarihi', 'label' => 'Randevu tarihi', 'type' => 'date'],
                    ['key' => 'zaman', 'label' => 'Randevu zamanı', 'type' => 'zaman'],
                ],
            ];
        }

        if (self::isPassivePatient($patient)) {
            $groups['dosya'] = [
                'label' => 'Dosya durumu',
                'section' => 'dosya_durumu',
                'fields' => [
                    ['key' => 'pasiftarihi', 'label' => 'Pasif tarihi', 'type' => 'date'],
                    ['key' => 'pasifnedeni', 'label' => 'Pasif nedeni', 'type' => 'text'],
                ],
            ];
        }

        return $groups;
    }

    /**
     * @param array<string, mixed> $fieldDef
     */
    private static function fieldApplies(object $patient, array $fieldDef): bool
    {
        if (!empty($fieldDef['when_yupas_guvence']) && !self::isYupasGuvenceSelected($patient)) {
            return false;
        }

        if (!isset($fieldDef['when']) || !is_array($fieldDef['when'])) {
            return true;
        }
        foreach ($fieldDef['when'] as $col => $expected) {
            $actual = (int) ($patient->$col ?? 0);
            if ($actual !== (int) $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $fieldDef
     */
    private static function isFieldFilled(object $patient, array $fieldDef): bool
    {
        $key = (string) $fieldDef['key'];
        $type = (string) ($fieldDef['type'] ?? 'text');
        $value = $patient->$key ?? null;

        return match ($type) {
            'tc' => ValidationHelper::isTcLength11(ValidationHelper::tcDigitsOnly((string) $value)),
            'text' => trim((string) $value) !== '',
            'parent_name' => self::isParentNameFilled($value),
            'date' => self::isDateFilled($value),
            'gender' => self::normalizeGender($value) !== null,
            'guvence' => self::isGuvenceFilled($value),
            'phone_strict' => strlen(ValidationHelper::phoneDigits((string) $value)) === 11,
            'phone_loose' => self::isPhoneLooseFilled($value),
            'address_id' => self::isAddressIdFilled($value),
            'metric' => self::isPositiveMetricFilled($value),
            'barthel_score' => $value !== null && $value !== '',
            'hastaliklar' => self::isHastaliklarFilled($value),
            'zaman' => self::isZamanFilled($value),
            'mama_cesit' => PatientCareHelper::normalizeMamaCesit($value) > 0,
            default => trim((string) $value) !== '',
        };
    }

    private static function isPassivePatient(?object $patient): bool
    {
        if ($patient === null) {
            return false;
        }

        return trim((string) ($patient->pasif ?? '')) === '1';
    }

    /** @var list<int>|null */
    private static ?array $yupasGuvenceIdsCache = null;

    private static function isYupasGuvenceSelected(object $patient): bool
    {
        $guvenceId = (int) ($patient->guvence ?? 0);
        if ($guvenceId < 1) {
            return false;
        }

        return in_array($guvenceId, self::yupasGuvenceIds(), true);
    }

    /** @return list<int> */
    private static function yupasGuvenceIds(): array
    {
        if (self::$yupasGuvenceIdsCache !== null) {
            return self::$yupasGuvenceIdsCache;
        }

        $ids = [];
        $list = (new Guvence())->getList();
        if (is_array($list)) {
            foreach ($list as $row) {
                $name = isset($row->guvenceadi) ? trim((string) $row->guvenceadi) : '';
                if ($name !== '' && strcasecmp($name, 'YUPAS') === 0) {
                    $id = (int) ($row->id ?? 0);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }
        }

        self::$yupasGuvenceIdsCache = array_values(array_unique($ids));

        return self::$yupasGuvenceIdsCache;
    }

    private static function isParentNameFilled(mixed $value): bool
    {
        $trim = trim((string) $value);
        if ($trim === '') {
            return false;
        }

        return !in_array($trim, ['.', '..', '...'], true);
    }

    private static function isDateFilled(mixed $value): bool
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '0000-00-00') {
            return false;
        }

        return DateHelper::trDateToYmd($raw) !== null;
    }

    private static function normalizeGender(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '' || $v === '0') {
            return null;
        }
        if ($v === '1' || $v === 'E' || $v === 'ERKEK') {
            return 'E';
        }
        if ($v === '2' || $v === 'K' || $v === 'KADIN') {
            return 'K';
        }

        return null;
    }

    private static function isGuvenceFilled(mixed $value): bool
    {
        $g = trim((string) $value);

        return $g !== '' && $g !== '0';
    }

    private static function isPhoneLooseFilled(mixed $value): bool
    {
        $digits = ValidationHelper::phoneDigits((string) $value);
        if (strlen($digits) === 11) {
            return true;
        }

        return trim((string) $value) !== '';
    }

    private static function isAddressIdFilled(mixed $value): bool
    {
        $s = trim((string) $value);

        return $s !== '' && $s !== '0';
    }

    private static function isPositiveMetricFilled(mixed $value): bool
    {
        $norm = ValidationHelper::normalizeDecimalDotInput($value);
        if ($norm === '') {
            return false;
        }

        return ValidationHelper::parseDecimalDot($norm) > 0;
    }

    private static function isHastaliklarFilled(mixed $value): bool
    {
        return Patient::parseHastalikCsvToIntIds($value) !== [];
    }

    private static function isZamanFilled(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        $z = (int) $value;

        return $z >= 0 && $z <= 2;
    }
}
