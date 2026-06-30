<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Harita merkezi, planlama skorları, kurumsal ad, kamu TC sorgusu vb.
 * Varsayılanlar: config/operational-settings.defaults.json
 * Runtime: public/assets/data/app-settings.json (ilgili bölümler).
 */
final class OperationalSettings
{
    private const DEFAULTS_REL = 'config/operational-settings.defaults.json';

    /** @var bool */
    private static $booted = false;

    /** @var array<string, array<string, mixed>>|null */
    private static $defaults = null;

    /** @var array<string, array<string, mixed>>|null */
    private static $runtime = null;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::$defaults = self::loadDefaultsFile();
        self::$runtime = self::loadRuntimeSections();
    }

    public static function string(string $section, string $key, string $fallback = ''): string
    {
        self::boot();
        $v = self::raw($section, $key);
        if ($v === null || $v === '') {
            return $fallback;
        }

        return trim((string) $v);
    }

    public static function int(string $section, string $key, int $fallback = 0): int
    {
        self::boot();
        $v = self::raw($section, $key);
        if ($v === null || $v === '') {
            return $fallback;
        }

        return (int) $v;
    }

    public static function float(string $section, string $key, float $fallback = 0.0): float
    {
        self::boot();
        $v = self::raw($section, $key);
        if ($v === null || $v === '') {
            return $fallback;
        }

        return (float) $v;
    }

    public static function bool(string $section, string $key, bool $fallback = false): bool
    {
        self::boot();
        $v = self::raw($section, $key);
        if ($v === null) {
            return $fallback;
        }
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (int) $v !== 0;
        }
        $s = strtolower(trim((string) $v));
        if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on') {
            return true;
        }
        if ($s === '0' || $s === 'false' || $s === 'no' || $s === 'off' || $s === '') {
            return false;
        }

        return $fallback;
    }

    /** @var list<string> */
    private const NOBET_DEFAULT_ALLOWED_UNVANLAR = ['hemsire', 'tekniker'];

    /** @var array<string, int> */
    private const NOBET_DEFAULT_UNVAN_SLOTS = ['hemsire' => 2, 'tekniker' => 1];

    private const NOBET_SLOT_MIN = 1;

    private const NOBET_SLOT_MAX = 20;

    public const DEFAULT_EK3_FORM_BASLIK = "T.C. SAĞLIK BAKANLIĞI\nDENİZLİ DEVLET HASTANESİ\nEVDE SAĞLIK HİZMETLERİ POLİKLİNİĞİ\nBAŞVURU FORMU / EK-3";

    public const DEFAULT_HEKIM_DEGERLENDIRME_FORM_BASLIK = "T.C. SAĞLIK BAKANLIĞI\nDENİZLİ DEVLET HASTANESİ\nEVDE SAĞLIK HİZMETLERİ POLİKLİNİĞİ\nHEKİM DEĞERLENDİRME FORMU";

    /**
     * Nöbet modülünde personel havuzu, otomatik dağıtım ve İzin/Mazeret için geçerli ünvan kodları.
     *
     * @return list<string>
     */
    public static function nobetDefaultAllowedUnvanlar(): array
    {
        return self::NOBET_DEFAULT_ALLOWED_UNVANLAR;
    }

    /**
     * @return list<string>
     */
    public static function nobetAllowedUnvanlar(): array
    {
        self::boot();
        $list = self::normalizeNobetUnvanList(self::raw('nobet', 'allowed_unvanlar'));
        if ($list === []) {
            return self::nobetDefaultAllowedUnvanlar();
        }

        return $list;
    }

    /**
     * Ayar paneli: seçili ünvan kodları.
     *
     * @return list<string>
     */
    public static function nobetAllowedUnvanlarForAdmin(): array
    {
        self::boot();
        $list = self::normalizeNobetUnvanList(self::raw('nobet', 'allowed_unvanlar'));
        if ($list === []) {
            return self::nobetDefaultAllowedUnvanlar();
        }

        return $list;
    }

    /**
     * Ayar paneli: tüm ünvanlar için günlük nöbetçi sayısı (kayıtlı veya varsayılan).
     *
     * @return array<string, int>
     */
    public static function nobetUnvanSlotsForAdmin(): array
    {
        self::boot();
        $saved = self::raw('nobet', 'unvan_slots');
        $defaults = self::nobetDefaultUnvanSlots();
        $choices = \App\Models\User::unvanChoices();
        unset($choices['']);
        $out = [];
        foreach (array_keys($choices) as $code) {
            $code = (string) $code;
            if (is_array($saved) && array_key_exists($code, $saved)) {
                $out[$code] = self::clampNobetSlot((int) $saved[$code]);
                continue;
            }
            $out[$code] = $defaults[$code] ?? 1;
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public static function nobetDefaultUnvanSlots(): array
    {
        return self::NOBET_DEFAULT_UNVAN_SLOTS;
    }

    /**
     * @param array<string, mixed> $posted
     * @return true|string
     */
    public static function saveNobetAllowedUnvanlar(array $posted)
    {
        self::boot();
        $raw = $posted['allowed_unvanlar'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }
        $list = self::normalizeNobetUnvanList($raw);
        if ($list === []) {
            return 'En az bir ünvan seçin. Hiçbiri seçilmezse nöbet modülü varsayılan (hemşire, tekniker) listesine döner.';
        }

        $rawSlots = $posted['unvan_slots'] ?? [];
        if (!is_array($rawSlots)) {
            $rawSlots = [];
        }
        $slots = self::normalizeNobetUnvanSlots($rawSlots, $list);

        $nobetPayload = [
            'allowed_unvanlar' => $list,
            'unvan_slots' => $slots,
        ];

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null) {
            return KurumCorporateSettings::saveSection($kurumId, 'nobet', $nobetPayload);
        }

        $current = AppSettingsStore::read();
        $sections = is_array($current) ? $current : [];
        $existingNobet = is_array($sections['nobet'] ?? null) ? $sections['nobet'] : [];
        $sections['nobet'] = array_merge($existingNobet, $nobetPayload);
        $sections['version'] = max(1, (int) ($sections['version'] ?? 1));
        if (!isset($sections['modules']) || !is_array($sections['modules'])) {
            $sections['modules'] = is_array($current['modules'] ?? null) ? $current['modules'] : [];
        }
        if (!isset($sections['islem_ids']) || !is_array($sections['islem_ids'])) {
            $sections['islem_ids'] = is_array($current['islem_ids'] ?? null) ? $current['islem_ids'] : [];
        }

        $written = AppSettingsStore::write($sections);
        if ($written !== true) {
            return $written;
        }
        self::$runtime = self::loadRuntimeSections();

        return true;
    }

    /** @var array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}> */
    private const VARDIYA_DEFAULT_SLOTS = [
        '1' => ['active' => true, 'hour_start' => 8, 'hour_end' => 12, 'ekip_baslangic' => '09:00'],
        '2' => ['active' => true, 'hour_start' => 12, 'hour_end' => 16, 'ekip_baslangic' => '13:00'],
        '3' => ['active' => true, 'hour_start' => 16, 'hour_end' => 21, 'ekip_baslangic' => '16:00'],
    ];

    /**
     * @return array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>
     */
    public static function vardiyaDefaultSlots(): array
    {
        return self::VARDIYA_DEFAULT_SLOTS;
    }

    /**
     * @return array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>
     */
    public static function vardiyaSlots(): array
    {
        self::boot();
        $saved = self::raw('vardiya', 'slots');

        return self::normalizeVardiyaSlots(is_array($saved) ? $saved : []);
    }

    /**
     * @return array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>
     */
    public static function vardiyaSlotsForAdmin(): array
    {
        return self::vardiyaSlots();
    }

    /**
     * @param array<string, mixed> $posted
     * @return true|string
     */
    public static function saveVardiyaSettings(array $posted)
    {
        self::boot();
        $rawSlots = $posted['slots'] ?? [];
        if (!is_array($rawSlots)) {
            $rawSlots = [];
        }
        $normalized = self::normalizeVardiyaSlots($rawSlots, true);
        if (is_string($normalized)) {
            return $normalized;
        }

        $activeCount = 0;
        foreach ($normalized as $slot) {
            if (!empty($slot['active'])) {
                $activeCount++;
            }
        }
        if ($activeCount < 1) {
            return 'En az bir zaman dilimi (vardiya) aktif olmalıdır.';
        }

        $vardiyaPayload = ['slots' => $normalized];

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null) {
            $result = KurumCorporateSettings::saveSection($kurumId, 'vardiya', $vardiyaPayload);
            if ($result === true) {
                ZamanDilimiHelper::clearSlotsCache();
            }

            return $result;
        }

        $current = AppSettingsStore::read();
        $sections = is_array($current) ? $current : [];
        $existingVardiya = is_array($sections['vardiya'] ?? null) ? $sections['vardiya'] : [];
        $sections['vardiya'] = array_merge($existingVardiya, $vardiyaPayload);
        $sections['version'] = max(1, (int) ($sections['version'] ?? 1));
        if (!isset($sections['modules']) || !is_array($sections['modules'])) {
            $sections['modules'] = is_array($current['modules'] ?? null) ? $current['modules'] : [];
        }
        if (!isset($sections['islem_ids']) || !is_array($sections['islem_ids'])) {
            $sections['islem_ids'] = is_array($current['islem_ids'] ?? null) ? $current['islem_ids'] : [];
        }

        $written = AppSettingsStore::write($sections);
        if ($written !== true) {
            return $written;
        }
        self::$runtime = self::loadRuntimeSections();
        ZamanDilimiHelper::clearSlotsCache();

        return true;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, array{active:bool,hour_start:int,hour_end:int,ekip_baslangic:string}>|string
     */
    private static function normalizeVardiyaSlots(array $raw, bool $fromForm = false)
    {
        $defaults = self::vardiyaDefaultSlots();
        $labels = ['1' => 'Sabah', '2' => 'Öğle', '3' => 'Akşam'];
        $out = [];

        foreach ($defaults as $code => $def) {
            $src = is_array($raw[$code] ?? null) ? $raw[$code] : [];
            $label = $labels[$code] ?? ('Vardiya ' . $code);

            if ($fromForm) {
                $active = isset($src['active']) && (string) $src['active'] !== '' && (string) $src['active'] !== '0';
            } else {
                $active = array_key_exists('active', $src)
                    ? self::boolValue($src['active'])
                    : (bool) $def['active'];
            }

            $hourStart = array_key_exists('hour_start', $src) ? (int) $src['hour_start'] : (int) $def['hour_start'];
            $hourEnd = array_key_exists('hour_end', $src) ? (int) $src['hour_end'] : (int) $def['hour_end'];

            if ($hourStart < 0 || $hourStart > 23 || $hourEnd < 0 || $hourEnd > 23) {
                return '«' . $label . '» saat aralığı 0–23 arasında olmalıdır.';
            }
            if ($hourStart >= $hourEnd) {
                return '«' . $label . '» için bitiş saati başlangıçtan büyük olmalıdır.';
            }

            $ekipBaslangic = array_key_exists('ekip_baslangic', $src)
                ? trim((string) $src['ekip_baslangic'])
                : (string) $def['ekip_baslangic'];
            if (!preg_match('/^\d{1,2}:\d{2}$/', $ekipBaslangic)) {
                return '«' . $label . '» ekip başlangıç saati HH:MM formatında olmalıdır.';
            }
            $parts = explode(':', $ekipBaslangic);
            $h = (int) $parts[0];
            $m = (int) $parts[1];
            if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
                return '«' . $label . '» ekip başlangıç saati geçersiz.';
            }
            $ekipBaslangic = sprintf('%02d:%02d', $h, $m);

            $out[$code] = [
                'active' => $active,
                'hour_start' => $hourStart,
                'hour_end' => $hourEnd,
                'ekip_baslangic' => $ekipBaslangic,
            ];
        }

        return $out;
    }

    /**
     * Otomatik aylık dağıtımda ünvan başına günlük nöbetçi sayısı.
     */
    public static function nobetRebuildSlotsForUnvan(string $unvan): int
    {
        self::boot();
        $saved = self::raw('nobet', 'unvan_slots');
        if (is_array($saved) && array_key_exists($unvan, $saved)) {
            return self::clampNobetSlot((int) $saved[$unvan]);
        }

        return self::nobetDefaultUnvanSlots()[$unvan] ?? 1;
    }

    public static function isPublicHastaaramaEnabled(): bool
    {
        return self::bool('public_hastaarama', 'enabled', true);
    }

    public static function publicHastaaramaBilgilendirmeMetni(): string
    {
        return self::string('public_hastaarama', 'bilgilendirme_metni', '');
    }

    public static function isMaintenanceModeEnabled(): bool
    {
        return self::bool('maintenance', 'enabled', false);
    }

    public static function maintenanceMessage(): string
    {
        $msg = trim(self::string('maintenance', 'message', ''));
        if ($msg === '') {
            return 'Sistem bakımda. Lütfen kısa süre sonra tekrar deneyin.';
        }

        return $msg;
    }

    public static function kpsWsdlUrl(): string
    {
        return self::string('kps', 'wsdl_url', 'https://kpv2.saglik.gov.tr/');
    }

    public static function kpsTimeoutSeconds(): int
    {
        return max(5, min(120, self::int('kps', 'timeout_seconds', 15)));
    }

    public static function kpsLogQueries(): bool
    {
        return self::bool('kps', 'log_queries', true);
    }

    public static function kpsVefatEnabled(): bool
    {
        return self::bool('kps', 'vefat_enabled', false);
    }

    public static function kpsVefatFallbackBelediye(): bool
    {
        return self::bool('kps', 'vefat_fallback_belediye', true);
    }

    /**
     * Yazılım firmasına SB tarafından verilen firma kodu (config.local / ortam).
     *
     * @return array{configured:bool,value:string,source:string}
     */
    public static function kpsFirmaKoduStatusForAdmin(): array
    {
        $code = '';
        $source = '';
        $env = getenv('KPS_FIRMA_KODU');
        if (is_string($env) && trim($env) !== '') {
            $code = trim($env);
            $source = 'Ortam değişkeni KPS_FIRMA_KODU';
        } elseif (function_exists('esh_config_local')) {
            $local = esh_config_local('kps_firma_kodu', null);
            if (is_string($local) && trim($local) !== '') {
                $code = trim($local);
                $source = 'config.local.php → kps_firma_kodu';
            }
        }
        if ($code === '' && defined('KPS_FIRMA_KODU')) {
            $code = trim((string) KPS_FIRMA_KODU);
            if ($code !== '' && $source === '') {
                $source = 'Yapılandırma (define KPS_FIRMA_KODU)';
            }
        }
        if ($code === '') {
            return ['configured' => false, 'value' => '', 'source' => ''];
        }

        return [
            'configured' => true,
            'value' => $code,
            'source' => $source,
        ];
    }

    /**
     * EK-3 pdfMake üst başlık metni (satırlar \n ile; PDF için sonda boş satır).
     */
    public static function ek3FormBaslik(): string
    {
        $text = self::string('corporate', 'ek3_form_baslik', self::DEFAULT_EK3_FORM_BASLIK);
        if ($text === '') {
            $text = self::DEFAULT_EK3_FORM_BASLIK;
        }

        return rtrim($text) . "\n\n";
    }

    /**
     * Bekleyen hasta pdfMake üst başlık metni (satırlar \n ile).
     */
    public static function hekimDegerlendirmeFormBaslik(): string
    {
        $text = self::string('corporate', 'hekim_degerlendirme_form_baslik', self::DEFAULT_HEKIM_DEGERLENDIRME_FORM_BASLIK);
        if ($text === '') {
            $text = self::DEFAULT_HEKIM_DEGERLENDIRME_FORM_BASLIK;
        }

        return rtrim($text);
    }

    /**
     * Ayar paneli: bölüm alanları + mevcut değerler.
     *
     * @return list<array{section:string,key:string,label:string,description:string,type:string,value:string,default:string}>
     */
    public static function fieldsForAdmin(string $section): array
    {
        self::boot();
        $defs = self::sectionDefaults($section);
        $schema = self::adminSchema($section);
        $rows = [];
        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            $def = $defs[$key] ?? '';
            $raw = self::raw($section, $key);
            $rows[] = [
                'section' => $section,
                'key' => $key,
                'label' => (string) ($field['label'] ?? $key),
                'description' => (string) ($field['description'] ?? ''),
                'type' => $type,
                'value' => self::formatForAdmin($type, $raw ?? $def),
                'default' => self::formatForAdmin($type, $def),
            ];
        }

        return $rows;
    }

    /**
     * TomTom anahtarı yalnızca config.local / ortam — panelde gösterim.
     *
     * @return array{configured:bool,masked:string,source:string}
     */
    public static function tomtomKeyStatusForAdmin(): array
    {
        $key = '';
        $source = '';
        $env = getenv('TOMTOM_KEY');
        if (is_string($env) && trim($env) !== '') {
            $key = trim($env);
            $source = 'Ortam değişkeni TOMTOM_KEY';
        } elseif (function_exists('esh_config_local')) {
            $local = esh_config_local('tomtom_key', null);
            if (is_string($local) && trim($local) !== '') {
                $key = trim($local);
                $source = 'config.local.php → tomtom_key';
            }
        }
        if ($key === '' && defined('TOMTOM_KEY')) {
            $key = trim((string) TOMTOM_KEY);
            if ($key !== '' && $source === '') {
                $source = 'Yapılandırma (define TOMTOM_KEY)';
            }
        }
        if ($key === '') {
            return ['configured' => false, 'masked' => '', 'source' => ''];
        }
        $len = strlen($key);

        return [
            'configured' => true,
            'masked' => $len <= 8 ? str_repeat('•', $len) : substr($key, 0, 4) . str_repeat('•', max(4, $len - 8)) . substr($key, -4),
            'source' => $source,
        ];
    }

    /**
     * E-imza salt okunur özet (gizli dosya yolları kısaltılmış).
     *
     * @return list<array{label:string,value:string,hint:string}>
     */
    public static function eimzaInfoRowsForAdmin(): array
    {
        $moduleOn = AppSettings::isModuleEnabled('eimza_login');
        $trust = defined('ESH_EIMZA_TRUST_STORE_PATH') ? (string) ESH_EIMZA_TRUST_STORE_PATH : '';
        $trustShort = $trust !== '' ? basename($trust) : '—';
        $bridge = defined('ESH_EIMZA_LOCAL_BRIDGE_BASE_URL') ? (string) ESH_EIMZA_LOCAL_BRIDGE_BASE_URL : '';

        return [
            [
                'label' => 'Modül (Uygulama modülleri)',
                'value' => $moduleOn ? 'Açık' : 'Kapalı',
                'hint' => 'eimza_login anahtarı — «Uygulama modülleri» sekmesinden değiştirilir.',
            ],
            [
                'label' => 'Challenge TTL (sn)',
                'value' => (string) (defined('ESH_EIMZA_CHALLENGE_TTL_SECONDS') ? ESH_EIMZA_CHALLENGE_TTL_SECONDS : '—'),
                'hint' => 'config.local.php → eimza_challenge_ttl_seconds',
            ],
            [
                'label' => 'Güven zinciri dosyası',
                'value' => $trustShort,
                'hint' => 'config.local.php → eimza_trust_store_path (PEM)',
            ],
            [
                'label' => 'İptal kontrolü zorunlu',
                'value' => (defined('ESH_EIMZA_REQUIRE_REVOCATION_CHECK') && ESH_EIMZA_REQUIRE_REVOCATION_CHECK) ? 'Evet' : 'Hayır',
                'hint' => 'eimza_require_revocation_check',
            ],
            [
                'label' => 'Kullanıcı sertifika sabitleme',
                'value' => (defined('ESH_EIMZA_ENFORCE_USER_CERT_PINNING') && ESH_EIMZA_ENFORCE_USER_CERT_PINNING) ? 'Evet' : 'Hayır',
                'hint' => 'eimza_enforce_user_cert_pinning',
            ],
            [
                'label' => 'Hız sınırı penceresi (sn)',
                'value' => (string) (defined('ESH_EIMZA_RATE_WINDOW_SECONDS') ? ESH_EIMZA_RATE_WINDOW_SECONDS : '—'),
                'hint' => 'eimza_rate_window_seconds',
            ],
            [
                'label' => 'IP başına max başarısız',
                'value' => (string) (defined('ESH_EIMZA_MAX_FAILED_PER_IP') ? ESH_EIMZA_MAX_FAILED_PER_IP : '—'),
                'hint' => 'eimza_max_failed_per_ip',
            ],
            [
                'label' => 'TC başına max başarısız',
                'value' => (string) (defined('ESH_EIMZA_MAX_FAILED_PER_TC') ? ESH_EIMZA_MAX_FAILED_PER_TC : '—'),
                'hint' => 'eimza_max_failed_per_tc',
            ],
            [
                'label' => 'Yerel köprü',
                'value' => (defined('ESH_EIMZA_LOCAL_BRIDGE_ENABLED') && ESH_EIMZA_LOCAL_BRIDGE_ENABLED)
                    ? ('Açık — ' . ($bridge !== '' ? $bridge : '—'))
                    : 'Kapalı',
                'hint' => 'eimza_local_bridge_enabled / eimza_local_bridge_base_url',
            ],
        ];
    }

    /**
     * Güvenlik sekmesi gibi aynı formda birden fazla bölüm varken POST anahtarlarını ayırır.
     * Form: operational[maintenance_enabled], operational[public_hastaarama_enabled] …
     *
     * @param array<string, mixed> $posted
     * @return array<string, mixed>
     */
    public static function postedForSection(string $section, array $posted): array
    {
        self::boot();
        $schema = self::adminSchema($section);
        $prefix = $section . '_';
        $out = [];
        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            $formKey = $prefix . $key;
            if ($type === 'bool') {
                $out[$key] = isset($posted[$formKey]) && (string) $posted[$formKey] === '1';
                continue;
            }
            if (array_key_exists($formKey, $posted)) {
                $out[$key] = $posted[$formKey];
            } elseif (array_key_exists($key, $posted)) {
                $out[$key] = $posted[$key];
            }
        }

        return $out;
    }

    /**
     * @param array<string, string> $posted sectionKey_fieldKey veya düz key => value
     * @return true|string
     */
    public static function saveSection(string $section, array $posted)
    {
        self::boot();
        $schema = self::adminSchema($section);
        if ($schema === []) {
            return 'Geçersiz ayar bölümü.';
        }
        $toSave = [];
        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'bool') {
                $toSave[$key] = isset($posted[$key]) && (string) $posted[$key] === '1';
                continue;
            }
            if (!array_key_exists($key, $posted)) {
                continue;
            }
            $raw = trim((string) $posted[$key]);
            $normalized = self::normalizePosted($section, $key, $type, $raw, $field);
            if (is_string($normalized) && str_starts_with($normalized, '«')) {
                return $normalized;
            }
            $toSave[$key] = $normalized;
        }
        if ($toSave === [] && !in_array($section, ['public_hastaarama', 'debug', 'maintenance'], true)) {
            return 'Kaydedilecek alan bulunamadı.';
        }

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null && KurumCorporateSettings::isKurumOperationalSection($section)) {
            return KurumCorporateSettings::saveSection($kurumId, $section, $toSave);
        }

        $current = AppSettingsStore::read();
        $sections = is_array($current) ? $current : [];
        $existing = is_array($sections[$section] ?? null) ? $sections[$section] : [];
        $merged = array_merge($existing, $toSave);
        foreach (self::sectionDefaults($section) as $dk => $_dv) {
            if (!array_key_exists($dk, $merged)) {
                $merged[$dk] = self::raw($section, $dk) ?? $_dv;
            }
        }
        $sections[$section] = $merged;
        $sections['version'] = max(1, (int) ($sections['version'] ?? 1));
        if (!isset($sections['modules']) || !is_array($sections['modules'])) {
            $sections['modules'] = is_array($current['modules'] ?? null) ? $current['modules'] : [];
        }
        if (!isset($sections['islem_ids']) || !is_array($sections['islem_ids'])) {
            $sections['islem_ids'] = is_array($current['islem_ids'] ?? null) ? $current['islem_ids'] : [];
        }

        $written = AppSettingsStore::write($sections);
        if ($written !== true) {
            return $written;
        }
        self::$runtime = self::loadRuntimeSections();

        return true;
    }

    /**
     * @return mixed|null
     */
    private static function raw(string $section, string $key)
    {
        self::boot();
        if (class_exists(KurumCorporateSettings::class) && KurumCorporateSettings::isKurumOperationalSection($section)) {
            $kurumVal = KurumCorporateSettings::getSectionValue($section, $key);
            if ($kurumVal !== null) {
                return $kurumVal;
            }
        }
        $rt = self::$runtime[$section] ?? [];
        if (is_array($rt) && array_key_exists($key, $rt)) {
            return $rt[$key];
        }
        $defs = self::sectionDefaults($section);
        if (array_key_exists($key, $defs)) {
            return $defs[$key];
        }
        if (function_exists('esh_config_local')) {
            $localKey = $section . '_' . $key;
            $local = esh_config_local($localKey, null);
            if ($local !== null && $local !== '') {
                return $local;
            }
            $flat = esh_config_local($key, null);
            if ($flat !== null && $flat !== '') {
                return $flat;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function sectionDefaults(string $section): array
    {
        self::boot();
        $defs = self::$defaults[$section] ?? [];

        return is_array($defs) ? $defs : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function loadDefaultsFile(): array
    {
        $path = rtrim((string) ROOT_PATH, '/\\') . '/' . self::DEFAULTS_REL;
        if (!is_readable($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        unset($decoded['version']);
        $out = [];
        foreach ($decoded as $section => $data) {
            if (is_array($data)) {
                $out[(string) $section] = $data;
            }
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function loadRuntimeSections(): array
    {
        $file = AppSettingsStore::read();
        $out = [];
        foreach (['map', 'planning', 'durations', 'corporate', 'public_hastaarama', 'debug', 'maintenance', 'nobet', 'vardiya', 'kps'] as $section) {
            if (isset($file[$section]) && is_array($file[$section])) {
                $out[$section] = $file[$section];
            }
        }

        return $out;
    }

    /**
     * @param mixed $value
     */
    private static function formatForAdmin(string $type, $value): string
    {
        if ($type === 'bool') {
            return self::boolValue($value) ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @param mixed $value
     */
    private static function boolValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }
        $s = strtolower(trim((string) $value));

        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $field
     * @return true|string|int|float|bool
     */
    private static function normalizePosted(string $section, string $key, string $type, string $raw, array $field)
    {
        $label = (string) ($field['label'] ?? $key);
        if ($type === 'int') {
            if ($raw === '' && !empty($field['allow_empty'])) {
                return 0;
            }
            if (!is_numeric($raw)) {
                return '«' . $label . '» için geçerli bir tam sayı girin.';
            }
            $n = (int) $raw;
            if (isset($field['min']) && $n < (int) $field['min']) {
                return '«' . $label . '» en az ' . (int) $field['min'] . ' olmalıdır.';
            }
            if (isset($field['max']) && $n > (int) $field['max']) {
                return '«' . $label . '» en fazla ' . (int) $field['max'] . ' olmalıdır.';
            }

            return $n;
        }
        if ($type === 'float') {
            if ($raw === '') {
                return '«' . $label . '» boş bırakılamaz.';
            }
            if (!is_numeric($raw)) {
                return '«' . $label . '» için geçerli bir sayı girin.';
            }
            $f = (float) $raw;
            if (isset($field['min']) && $f < (float) $field['min']) {
                return '«' . $label . '» en az ' . (float) $field['min'] . ' olmalıdır.';
            }
            if (isset($field['max']) && $f > (float) $field['max']) {
                return '«' . $label . '» en fazla ' . (float) $field['max'] . ' olmalıdır.';
            }

            return $raw;
        }
        if ($type === 'text' || $type === 'textarea') {
            if (!empty($field['required']) && $raw === '') {
                return '«' . $label . '» boş bırakılamaz.';
            }

            return $raw;
        }

        return $raw;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function adminSchema(string $section): array
    {
        $schemas = [
            'map' => [
                ['key' => 'start_lat', 'label' => 'Operasyon merkezi enlem', 'description' => 'Rota ve harita başlangıç noktası (START_LAT).', 'type' => 'float', 'min' => -90, 'max' => 90],
                ['key' => 'start_lng', 'label' => 'Operasyon merkezi boylam', 'description' => 'Rota ve harita başlangıç noktası (START_LNG).', 'type' => 'float', 'min' => -180, 'max' => 180],
                ['key' => 'start_name', 'label' => 'Merkez etiketi', 'description' => 'Haritada ve rota görünümünde gösterilen ad (START_NAME).', 'type' => 'text'],
            ],
            'planning' => [
                ['key' => 'oncelik_yuksek_bonusu', 'label' => 'Yüksek öncelik bonusu', 'description' => 'Dashboard rota skorunda düşürülen maliyet.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'mahalle_bonusu', 'label' => 'Mahalle bonusu', 'description' => 'Aynı mahalledeki hastalar için skor bonusu.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'bolge_bonusu', 'label' => 'Bölge bonusu', 'description' => 'Aynı bölgedeki hastalar için skor bonusu.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'is_yuku_cezasi', 'label' => 'İş yükü cezası', 'description' => 'Personel dosya sayısı eşiğini aşınca eklenen ceza.', 'type' => 'int', 'min' => 0, 'max' => 200],
                ['key' => 'personel_dosya_sayisi', 'label' => 'Personel dosya eşiği', 'description' => 'İş yükü cezası uygulanmadan önceki hasta sayısı.', 'type' => 'int', 'min' => 1, 'max' => 100],
                ['key' => 'mahalle_bolge_max', 'label' => 'Mahalle bölge üst sınırı', 'description' => 'Mahalle planlamasında seçilebilecek en büyük bölge no (ESH_MAHALLE_BOLGE_MAX).', 'type' => 'int', 'min' => 1, 'max' => 99],
            ],
            'durations' => [
                ['key' => 'sure_pansuman', 'label' => 'Pansuman süresi (dk)', 'description' => 'Rota planlama varsayılan pansuman süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
                ['key' => 'sure_muayene', 'label' => 'Muayene süresi (dk)', 'description' => 'Rota planlama varsayılan muayene süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
                ['key' => 'sure_izlem', 'label' => 'İzlem süresi (dk)', 'description' => 'Rota planlama varsayılan izlem süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
            ],
            'corporate' => [
                ['key' => 'esh_app_name', 'label' => 'Kurumsal görünen ad', 'description' => 'Giriş, başlık ve misafir sayfalarında (ESH_APP_NAME).', 'type' => 'text', 'required' => true],
                [
                    'key' => 'ek3_form_baslik',
                    'label' => 'EK-3 form üst başlığı',
                    'description' => 'Konsültasyon EK-3 PDF ve yazdırma çıktısının ortalanmış üst metni. Her satır ayrı satırda yazılır; boş bırakılırsa varsayılan kurum başlığı kullanılır.',
                    'type' => 'textarea',
                ],
                [
                    'key' => 'hekim_degerlendirme_form_baslik',
                    'label' => 'Hekim değerlendirme formu üst başlığı',
                    'description' => 'Bekleyen hasta başvuru formu (Patient/waitingForm) pdfMake çıktısının ortalanmış üst metni. Her satır ayrı satırda yazılır; boş bırakılırsa varsayılan kurum başlığı kullanılır.',
                    'type' => 'textarea',
                ],
            ],
            'public_hastaarama' => [
                ['key' => 'enabled', 'label' => 'Kamu TC sorgusu', 'description' => 'Kapalıyken misafir sorgu sayfası erişilemez; giriş bağlantısı gizlenir.', 'type' => 'bool'],
                ['key' => 'bilgilendirme_metni', 'label' => 'Bilgilendirme / KVKK metni', 'description' => 'Sorgu formunun üstünde gösterilir.', 'type' => 'textarea'],
            ],
            'maintenance' => [
                [
                    'key' => 'enabled',
                    'label' => 'Bakım modu',
                    'description' => 'Açıkken yalnızca süper yönetici erişebilir; personel, admin ve misafir sayfaları kapanır.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'message',
                    'label' => 'Bakım mesajı',
                    'description' => 'Erişim engellendiğinde ve giriş sayfasında gösterilir.',
                    'type' => 'textarea',
                ],
            ],
            'debug' => [
                [
                    'key' => 'display_errors',
                    'label' => 'PHP hatalarını tarayıcıda göster',
                    'description' => 'Açıkken istisna ve uyarı metinleri ziyaretçilere görünür; canlı ortamda kapalı tutun. Yeni isteklerde geçerlidir.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'db_debug',
                    'label' => 'SQL hata ayıklama konsolu',
                    'description' => 'Açıkken çalıştırılan sorgular sayfa altında listelenir (DB_DEBUG). Performans ve gizlilik için üretimde kapalı tutun.',
                    'type' => 'bool',
                ],
            ],
            'kps' => [
                [
                    'key' => 'wsdl_url',
                    'label' => 'KPS WSDL / servis adresi',
                    'description' => 'Sağlık Bakanlığı KPS web servis uç noktası (kpv2.saglik.gov.tr). Yetki sonrası resmi dokümandaki adresle güncelleyin.',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'key' => 'timeout_seconds',
                    'label' => 'Zaman aşımı (sn)',
                    'description' => 'SOAP isteği için maksimum bekleme süresi.',
                    'type' => 'int',
                    'min' => 5,
                    'max' => 120,
                ],
                [
                    'key' => 'log_queries',
                    'label' => 'Sorgu günlüğü',
                    'description' => 'Açıkken KPS sorguları storage/logs/kps_queries.log dosyasına maskeli TC ile yazılır.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'vefat_enabled',
                    'label' => 'KPS vefat sorgusu',
                    'description' => 'Açıkken vefat kontrolü önce KPS üzerinden yapılır (modül de açık olmalı). Kapalıyken yalnızca belediye mezarlık sorgusu kullanılır.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'vefat_fallback_belediye',
                    'label' => 'KPS yoksa belediye yedeği',
                    'description' => 'KPS vefat açıkken SOAP henüz hazır değilse veya yapılandırma eksikse Denizli belediye mezarlık sorgusuna düşülür.',
                    'type' => 'bool',
                ],
            ],
            'sms' => [
                [
                    'key' => 'test_mode',
                    'label' => 'Test modu',
                    'description' => 'Açıkken gerçek SMS API çağrısı yapılmaz; gönderimler loglanır.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'daily_limit',
                    'label' => 'Günlük gönderim limiti',
                    'description' => 'Kurum başına günde en fazla gönderilecek SMS sayısı.',
                    'type' => 'int',
                    'min' => 1,
                    'max' => 50000,
                ],
                [
                    'key' => 'kvkk_metni',
                    'label' => 'KVKK bilgilendirme metni',
                    'description' => 'Hasta kayıt formunda gösterilecek SMS aydınlatma metni.',
                    'type' => 'textarea',
                ],
                [
                    'key' => 'default_roles',
                    'label' => 'Varsayılan alıcı rolleri',
                    'description' => 'Virgülle: hasta, hasta2, bakimveren, ailehekimi',
                    'type' => 'text',
                ],
            ],
        ];

        return $schemas[$section] ?? [];
    }

    private static function clampNobetSlot(int $value): int
    {
        return max(self::NOBET_SLOT_MIN, min(self::NOBET_SLOT_MAX, $value));
    }

    /**
     * @param array<string, mixed> $raw
     * @param list<string> $allowedUnvanlar
     * @return array<string, int>
     */
    private static function normalizeNobetUnvanSlots(array $raw, array $allowedUnvanlar): array
    {
        $defaults = self::nobetDefaultUnvanSlots();
        $out = [];
        foreach ($allowedUnvanlar as $code) {
            if (array_key_exists($code, $raw)) {
                $out[$code] = self::clampNobetSlot((int) $raw[$code]);
                continue;
            }
            $out[$code] = $defaults[$code] ?? 1;
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private static function normalizeNobetUnvanList($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $choices = \App\Models\User::unvanChoices();
        unset($choices['']);
        $valid = array_keys($choices);
        $out = [];
        foreach ($raw as $item) {
            if (!is_string($item)) {
                continue;
            }
            $code = trim($item);
            if ($code === '' || !in_array($code, $valid, true) || in_array($code, $out, true)) {
                continue;
            }
            $out[] = $code;
        }

        return $out;
    }
}
