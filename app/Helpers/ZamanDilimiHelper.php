<?php

namespace App\Helpers;



/**

 * Zaman dilimi kodları (tüm formlar ve ilgili tablolar).

 *

 * Sabah = 1, Öğle = 2, Akşam = 3

 *

 * Dashboard / ekip nöbeti "vardiya" dizini ayrıdır: 0 = Sabah, 1 = Öğle, 2 = Akşam

 * ({@see toVardiyaIndex()} / {@see fromVardiyaIndex()}).

 *

 * Aktif vardiyalar ve saat aralıkları kurum ayarlarından okunur (operational.vardiya).

 */

final class ZamanDilimiHelper

{

    public const SABAH = 1;

    public const OGLE = 2;

    public const AKSAM = 3;

    public const MIN = 1;

    public const MAX = 3;



    /** @var list<int> */

    public const ALL = [self::SABAH, self::OGLE, self::AKSAM];



    /** @var array<int, array{key:string,label:string,icon:string,mod:string,btnCls:string,radioIcon:string}> */

    private const SLOT_META = [

        self::SABAH => [

            'key' => 'sabah',

            'label' => 'Sabah',

            'icon' => 'fa-sun',

            'mod' => 'sabah',

            'btnCls' => 'outline-warning',

            'radioIcon' => 'fa-regular fa-sun',

        ],

        self::OGLE => [

            'key' => 'ogle',

            'label' => 'Öğle',

            'icon' => 'fa-cloud-sun',

            'mod' => 'ogle',

            'btnCls' => 'outline-danger',

            'radioIcon' => 'fa-solid fa-sun',

        ],

        self::AKSAM => [

            'key' => 'aksam',

            'label' => 'Akşam',

            'icon' => 'fa-moon',

            'mod' => 'aksam',

            'btnCls' => 'outline-primary',

            'radioIcon' => 'fa-solid fa-moon',

        ],

    ];



    /** @var array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>|null */

    private static ?array $slotsCache = null;



    public static function label(int $z): string

    {

        $meta = self::SLOT_META[self::normalize($z)] ?? null;



        return $meta['label'] ?? 'Sabah';

    }



    /**

     * Eski 0–2 kayıtlarını 1–3'e çevirir; zaten 1–3 ise olduğu gibi döner.

     */

    public static function normalize(mixed $raw, int $default = self::SABAH): int

    {

        if ($raw === null || $raw === '') {

            return $default;

        }

        $z = (int) $raw;

        if ($z >= self::MIN && $z <= self::MAX) {

            return $z;

        }

        if ($z >= 0 && $z <= 2) {

            return $z + 1;

        }



        return $default;

    }



    public static function clamp(mixed $raw, int $default = self::SABAH): int

    {

        return self::normalize($raw, $default);

    }



    public static function isValid(int $z): bool

    {

        return $z >= self::MIN && $z <= self::MAX;

    }



    /** Ekip / rota sekmesi dizini (0–2) → zaman kodu (1–3). */

    public static function fromVardiyaIndex(int $vardiyaIndex): int

    {

        if ($vardiyaIndex < 0 || $vardiyaIndex > 2) {

            return self::SABAH;

        }



        return $vardiyaIndex + 1;

    }



    /** Zaman kodu (1–3) → ekip / rota sekmesi (0–2). */

    public static function toVardiyaIndex(int $zamanKodu): int

    {

        $z = self::normalize($zamanKodu);



        return $z - 1;

    }



    /**

     * @return array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>

     */

    public static function slotsConfig(): array

    {

        if (self::$slotsCache !== null) {

            return self::$slotsCache;

        }

        self::$slotsCache = OperationalSettings::vardiyaSlots();



        return self::$slotsCache;

    }



    public static function clearSlotsCache(): void

    {

        self::$slotsCache = null;

    }



    /**

     * @return list<int>

     */

    public static function activeCodes(): array

    {

        $out = [];

        foreach (self::ALL as $code) {

            if (self::isActive($code)) {

                $out[] = $code;

            }

        }

        if ($out === []) {

            return self::ALL;

        }



        return $out;

    }



    /**

     * @return list<int>

     */

    public static function activeVardiyaIndexes(): array

    {

        $out = [];

        foreach (self::activeCodes() as $code) {

            $out[] = self::toVardiyaIndex($code);

        }



        return $out;

    }



    public static function isActive(int $code): bool

    {

        $code = self::normalize($code);

        $slots = self::slotsConfig();

        $key = (string) $code;

        if (!isset($slots[$key])) {

            return true;

        }



        return !empty($slots[$key]['active']);

    }



    /**

     * @return array{start:int,end:int}|null

     */

    public static function hourRange(int $code): ?array

    {

        $code = self::normalize($code);

        $slots = self::slotsConfig();

        $key = (string) $code;

        if (!isset($slots[$key])) {

            return match ($code) {

                self::OGLE => ['start' => 12, 'end' => 16],

                self::AKSAM => ['start' => 16, 'end' => 21],

                default => ['start' => 8, 'end' => 12],

            };

        }



        return [

            'start' => (int) $slots[$key]['hour_start'],

            'end' => (int) $slots[$key]['hour_end'],

        ];

    }



    public static function ekipBaslangicSaati(int $vardiyaIndex): string

    {

        $code = self::fromVardiyaIndex($vardiyaIndex);

        $slots = self::slotsConfig();

        $key = (string) $code;

        if (isset($slots[$key]['ekip_baslangic'])) {

            $t = trim((string) $slots[$key]['ekip_baslangic']);

            if ($t !== '') {

                return strlen($t) >= 5 ? substr($t, 0, 5) : $t;

            }

        }



        return match ($code) {

            self::OGLE => '13:00',

            self::AKSAM => '16:00',

            default => '09:00',

        };

    }



    /**

     * Saate göre zaman dilimi; kurum saat aralıklarına göre ilk eşleşen aktif vardiya.

     */

    public static function fromHour(?int $hour = null): ?int

    {

        $h = $hour ?? (int) date('G');

        foreach (self::activeCodes() as $code) {

            $range = self::hourRange($code);

            if ($range === null) {

                continue;

            }

            if ($h >= $range['start'] && $h < $range['end']) {

                return $code;

            }

        }



        return null;

    }



    /**

     * UI / JS için aktif vardiya bölümleri.

     *

     * @return list<array{key:string,code:int,label:string,icon:string,mod:string,hourStart:int,hourEnd:int,ekipBaslangic:string,btnCls:string,radioIcon:string}>

     */

    public static function uiSections(): array

    {

        $out = [];

        foreach (self::activeCodes() as $code) {

            $meta = self::SLOT_META[$code] ?? self::SLOT_META[self::SABAH];

            $range = self::hourRange($code) ?? ['start' => 8, 'end' => 12];

            $out[] = [

                'key' => $meta['key'],

                'code' => $code,

                'label' => $meta['label'],

                'icon' => $meta['icon'],

                'mod' => $meta['mod'],

                'hourStart' => $range['start'],

                'hourEnd' => $range['end'],

                'ekipBaslangic' => self::ekipBaslangicSaati(self::toVardiyaIndex($code)),

                'btnCls' => $meta['btnCls'],

                'radioIcon' => $meta['radioIcon'],

            ];

        }



        return $out;

    }



    /**

     * Radio blokları için (aktif + isteğe bağlı pasif seçili kod).

     *

     * @return list<array{v:int,cls:string,icon:string,label:string,readonly:bool}>

     */

    public static function radioBlocks(?int $selectedCode = null, bool $includeInactiveSelected = true): array

    {

        $blocks = [];

        $selected = ($selectedCode !== null && $selectedCode !== '')

            ? self::normalize($selectedCode)

            : null;



        foreach (self::uiSections() as $sec) {

            $blocks[] = [

                'v' => (int) $sec['code'],

                'cls' => (string) $sec['btnCls'],

                'icon' => (string) $sec['radioIcon'],

                'label' => (string) $sec['label'],

                'readonly' => false,

            ];

        }



        if (

            $includeInactiveSelected

            && $selected !== null

            && !self::isActive($selected)

        ) {

            $meta = self::SLOT_META[$selected] ?? self::SLOT_META[self::SABAH];

            $blocks[] = [

                'v' => $selected,

                'cls' => $meta['btnCls'],

                'icon' => $meta['radioIcon'],

                'label' => $meta['label'] . ' (pasif)',

                'readonly' => true,

            ];

        }



        return $blocks;

    }



    /**

     * POST kaydı için zaman kodu doğrulama; pasif vardiya reddedilir.

     *

     * @return true|string

     */

    public static function validateForSave(mixed $raw, bool $allowInactiveExisting = false)

    {

        if ($raw === null || $raw === '') {

            return 'Zaman dilimi seçilmelidir.';

        }

        $code = self::normalize($raw);

        if (!self::isValid($code)) {

            return 'Geçersiz zaman dilimi.';

        }

        if (!self::isActive($code) && !$allowInactiveExisting) {

            return 'Seçilen zaman dilimi bu kurumda aktif değil.';

        }



        return true;

    }



    /**

     * Liste rozetleri için (anahtarlar '1','2','3').

     *

     * @return array<string, array{text: string, icon: string, class: string}>

     */

    public static function badgeConfigByKey(): array

    {

        return [

            '1' => ['text' => 'Sabah', 'icon' => 'fa-sun', 'class' => 'warning'],

            '2' => ['text' => 'Öğle', 'icon' => 'fa-sun', 'class' => 'danger'],

            '3' => ['text' => 'Akşam', 'icon' => 'fa-moon', 'class' => 'primary'],

        ];

    }



    /**

     * @return array{text: string, icon: string, class: string}

     */

    public static function badgeFor(mixed $raw): array

    {

        $key = (string) self::normalize($raw);

        $cfg = self::badgeConfigByKey();



        return $cfg[$key] ?? ['text' => '—', 'icon' => 'fa-minus', 'class' => 'secondary'];

    }



    /** @return list<string> */

    public static function slotKeysWithOther(): array

    {

        $keys = [];

        foreach (self::activeCodes() as $code) {

            $meta = self::SLOT_META[$code] ?? null;

            if ($meta !== null) {

                $keys[] = $meta['key'];

            }

        }



        return array_merge($keys, ['diger']);

    }



    /** @return array<string, string> */

    public static function slotKeyLabels(): array

    {

        $labels = [];

        foreach (self::activeCodes() as $code) {

            $meta = self::SLOT_META[$code] ?? null;

            if ($meta !== null) {

                $labels[$meta['key']] = $meta['label'];

            }

        }

        $labels['diger'] = 'Diğer';



        return $labels;

    }



    /**

     * SQL: zaman sütununu 1–3 koduna normalize eder ({@see normalize()}); boş/geçersiz → NULL.

     * Eski 0–2 kayıtları +1 ile eşlenir (migrate_zaman_dilimi_1_2_3.sql sonrası 1–3 beklenir).

     */

    public static function sqlNormalizeCaseExpr(string $columnExpr): string

    {

        $c = $columnExpr;



        return "(CASE

            WHEN {$c} IS NULL OR TRIM(CAST({$c} AS CHAR)) = '' THEN NULL

            WHEN CAST({$c} AS SIGNED) BETWEEN " . self::MIN . ' AND ' . self::MAX . " THEN CAST({$c} AS SIGNED)

            WHEN CAST({$c} AS SIGNED) BETWEEN 0 AND 2 THEN CAST({$c} AS SIGNED) + 1

            ELSE NULL

        END)";

    }



    /**

     * SQL: normalize edilmiş zaman → sabah|ogle|aksam|diger slot anahtarı.

     */

    public static function sqlSlotKeyCaseExpr(string $columnExpr, string $elseKey = 'diger'): string

    {

        $norm = self::sqlNormalizeCaseExpr($columnExpr);

        $else = $elseKey !== '' ? self::quoteSqlString($elseKey) : 'NULL';



        return "(CASE

            WHEN {$norm} IS NULL THEN {$else}

            WHEN {$norm} = " . self::SABAH . " THEN 'sabah'

            WHEN {$norm} = " . self::OGLE . " THEN 'ogle'

            WHEN {$norm} = " . self::AKSAM . " THEN 'aksam'

            ELSE {$else}

        END)";

    }



    private static function quoteSqlString(string $value): string

    {

        return "'" . str_replace("'", "''", $value) . "'";

    }

}


