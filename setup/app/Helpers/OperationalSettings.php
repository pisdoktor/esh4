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

        return self::persistScopedSection('nobet', $nobetPayload);
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

        $result = self::persistScopedSection('vardiya', $vardiyaPayload);
        if ($result === true) {
            ZamanDilimiHelper::clearSlotsCache();
        }

        return $result;
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

    public static function isPatientPortalEnabled(): bool
    {
        return self::bool('patient_portal', 'enabled', true);
    }

    public static function patientPortalBilgilendirmeMetni(): string
    {
        return self::string('patient_portal', 'bilgilendirme_metni', '');
    }

    public static function patientPortalSessionHours(): int
    {
        $h = self::int('patient_portal', 'session_hours', 4);

        return max(1, min(24, $h));
    }

    public static function patientPortalShowPlannedVisits(): bool
    {
        return self::bool('patient_portal', 'show_planned_visits', true);
    }

    public static function patientPortalShowVisitHistory(): bool
    {
        return self::bool('patient_portal', 'show_visit_history', true);
    }

    public static function patientPortalShowUhdsAppointments(): bool
    {
        return self::bool('patient_portal', 'show_uhds_appointments', true);
    }

    public static function patientPortalOtpSmsEnabled(): bool
    {
        return self::bool('patient_portal', 'otp_sms_enabled', false);
    }

    public static function esysBridgeEnabled(): bool
    {
        return self::bool('esys_bridge', 'enabled', true);
    }

    public static function esysBridgeMode(): string
    {
        $mode = self::esysBridgeApiMode();
        if ($mode === 'http') {
            return 'api';
        }

        return 'file';
    }

    public static function esysBridgeApiMode(): string
    {
        $m = self::string('esys_bridge', 'api_mode', '');
        if ($m === '') {
            $legacy = self::string('esys_bridge', 'mode', 'file');
            if ($legacy === 'api') {
                return 'http';
            }

            return 'file';
        }
        $allowed = ['file', 'stub', 'http'];
        if (!in_array($m, $allowed, true)) {
            return 'file';
        }

        return $m;
    }

    public static function esysBridgeExportPatientLimit(): int
    {
        $n = self::int('esys_bridge', 'export_patient_limit', 500);

        return max(10, min(5000, $n));
    }

    public static function esysBridgeExportVisitDays(): int
    {
        $n = self::int('esys_bridge', 'export_visit_days', 90);

        return max(7, min(365, $n));
    }

    public static function esysBridgeExportOnlyMissingRefs(): bool
    {
        return self::bool('esys_bridge', 'export_only_missing_refs', false);
    }

    public static function esysBridgeApiEnabled(): bool
    {
        return self::bool('esys_bridge', 'api_enabled', false);
    }

    public static function esysBridgeApiBaseUrl(): string
    {
        $url = trim(self::string('esys_bridge', 'api_base_url', ''));
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    public static function usbsBridgeEnabled(): bool
    {
        return self::bool('usbs_bridge', 'enabled', true);
    }

    public static function usbsBridgeExportVisitDays(): int
    {
        $n = self::int('usbs_bridge', 'export_visit_days', 90);

        return max(7, min(365, $n));
    }

    public static function usbsBridgeExportOnlyPending(): bool
    {
        return self::bool('usbs_bridge', 'export_only_pending_bildirim', true);
    }

    public static function usbsAutoQueueOnVisitSave(): bool
    {
        return self::bool('usbs_bridge', 'auto_queue_on_visit_save', true);
    }

    public static function usbsBridgeIncludeEreceteStub(): bool
    {
        return self::bool('usbs_bridge', 'include_erecete_stub', true);
    }

    public static function usbsBridgeApiEnabled(): bool
    {
        return self::bool('usbs_bridge', 'api_enabled', false);
    }

    public static function usbsBridgeApiBaseUrl(): string
    {
        $url = trim(self::string('usbs_bridge', 'api_base_url', ''));
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    public static function usbsBridgeApiMode(): string
    {
        $m = self::string('usbs_bridge', 'api_mode', '');
        if ($m === '') {
            return self::usbsBridgeApiEnabled() ? 'http' : 'file';
        }
        $allowed = ['file', 'stub', 'http'];
        if (!in_array($m, $allowed, true)) {
            return 'file';
        }

        return $m;
    }

    public static function federationEnabled(): bool
    {
        return self::bool('federation', 'enabled', true);
    }

    public static function federationNodeRef(): string
    {
        $ref = trim(self::string('federation', 'node_ref', ''));

        return $ref !== '' ? substr($ref, 0, 64) : 'node-local';
    }

    public static function federationExportIncludeStats(): bool
    {
        return self::bool('federation', 'export_include_stats', true);
    }

    public static function federationHubApiEnabled(): bool
    {
        return self::bool('federation', 'hub_api_enabled', false);
    }

    public static function federationHubBaseUrl(): string
    {
        $url = trim(self::string('federation', 'hub_base_url', ''));
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    public static function federationCentralManifestUrl(): string
    {
        $url = trim(self::string('federation', 'central_manifest_url', ''));
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    public static function modernFrontendEnabled(): bool
    {
        return self::bool('modern_frontend', 'enabled', false);
    }

    public static function modernFrontendDashboardPilot(): bool
    {
        return self::bool('modern_frontend', 'dashboard_pilot', false);
    }

    public static function modernFrontendPlanningPilot(): bool
    {
        return self::bool('modern_frontend', 'planning_pilot', false);
    }

    public static function modernFrontendUseBuiltBundle(): bool
    {
        return self::bool('modern_frontend', 'use_built_bundle', false);
    }

    public static function modernFrontendPilotRolloutPercent(): int
    {
        $n = self::int('modern_frontend', 'pilot_rollout_percent', 100);

        return max(0, min(100, $n));
    }

    public static function modernFrontendVueCdnUrl(): string
    {
        return trim(self::string('modern_frontend', 'vue_cdn_url', ''));
    }

    public static function clinicalDecisionSupportEnabled(): bool
    {
        return self::bool('clinical_decision_support', 'enabled', true);
    }

    public static function clinicalDecisionOverdueDays(): int
    {
        $n = self::int('clinical_decision_support', 'overdue_days', 30);

        return max(7, min(180, $n));
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
     * Saha izlem GPS check-in modu: optional | recommended | required_completed
     */
    public static function fieldVisitCheckinMode(): string
    {
        $mode = self::string('field_visit', 'checkin_mode', 'recommended');
        $allowed = ['optional', 'recommended', 'required_completed'];
        if (!in_array($mode, $allowed, true)) {
            return 'recommended';
        }

        return $mode;
    }

    public static function fieldVisitOfflinePlanCacheEnabled(): bool
    {
        return self::bool('field_visit', 'offline_plan_cache', true);
    }

    public static function fieldVisitGeofenceRadiusM(): int
    {
        return max(0, min(50000, self::int('field_visit', 'geofence_radius_m', 500)));
    }

    public static function fieldVisitMinGpsAccuracyM(): int
    {
        return max(0, min(5000, self::int('field_visit', 'min_gps_accuracy_m', 100)));
    }

    public static function fieldVisitOfflineDraftEnabled(): bool
    {
        return self::bool('field_visit', 'offline_draft_enabled', true);
    }

    public static function auditLogRetentionDays(): int
    {
        return max(30, min(3650, self::int('audit_log', 'retention_days', 365)));
    }

    /**
     * @return array{mode:string,offlinePlanCache:bool,geofenceRadiusM:int,minGpsAccuracyM:int,offlineDraftEnabled:bool,patientLat:?float,patientLon:?float,syncDraftUrl:string}
     */
    public static function fieldVisitClientConfig(?object $patient = null): array
    {
        $patientLat = null;
        $patientLon = null;
        if ($patient !== null) {
            $coords = FieldVisitGeoHelper::patientCoords($patient);
            if ($coords !== null) {
                $patientLat = $coords['lat'];
                $patientLon = $coords['lon'];
            }
        }

        return [
            'mode' => self::fieldVisitCheckinMode(),
            'offlinePlanCache' => self::fieldVisitOfflinePlanCacheEnabled(),
            'geofenceRadiusM' => self::fieldVisitGeofenceRadiusM(),
            'minGpsAccuracyM' => self::fieldVisitMinGpsAccuracyM(),
            'offlineDraftEnabled' => self::fieldVisitOfflineDraftEnabled(),
            'patientLat' => $patientLat,
            'patientLon' => $patientLon,
            'syncDraftUrl' => esh_url('Visit', 'syncDraft'),
        ];
    }

    public static function uhdsTelehealthEnabled(): bool
    {
        return self::bool('uhds_telehealth', 'enabled', true);
    }

    public static function uhdsTelehealthProvider(): string
    {
        $p = self::string('uhds_telehealth', 'provider', 'jitsi');
        $allowed = ['jitsi', 'disabled'];
        if (!in_array($p, $allowed, true)) {
            return 'jitsi';
        }

        return $p;
    }

    public static function uhdsTelehealthJitsiDomain(): string
    {
        $domain = self::string('uhds_telehealth', 'jitsi_domain', 'meet.jit.si');
        $domain = preg_replace('#^https?://#i', '', $domain) ?? '';
        $domain = trim($domain, '/');
        if ($domain === '' || !preg_match('/^[a-z0-9][a-z0-9.-]+$/i', $domain)) {
            return 'meet.jit.si';
        }

        return strtolower($domain);
    }

    public static function uhdsTelehealthPatientJoinHours(): int
    {
        $h = self::int('uhds_telehealth', 'patient_join_hours', 4);

        return max(1, min(72, $h));
    }

    public static function uhdsTelehealthAutoPromptVisit(): bool
    {
        return self::bool('uhds_telehealth', 'auto_prompt_visit', true);
    }

    /**
     * @return array{enabled:bool,provider:string,jitsiDomain:string,autoPromptVisit:bool}
     */
    public static function uhdsTelehealthClientConfig(): array
    {
        return [
            'enabled' => self::uhdsTelehealthEnabled() && self::uhdsTelehealthProvider() === 'jitsi',
            'provider' => self::uhdsTelehealthProvider(),
            'jitsiDomain' => self::uhdsTelehealthJitsiDomain(),
            'autoPromptVisit' => self::uhdsTelehealthAutoPromptVisit(),
        ];
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
                'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
            ];
        }

        return $rows;
    }

    /**
     * Aktif harita/rota sağlayıcısı kodu.
     */
    public static function mapProvider(): string
    {
        $code = strtolower(trim(self::string('map', 'provider', 'tomtom')));
        if (!in_array($code, \App\Services\MapRouting\MapRoutingProviderFactory::SUPPORTED, true)) {
            return 'tomtom';
        }

        return $code;
    }

    public static function useMatrixBatch(): bool
    {
        if (self::raw('planning', 'use_matrix_batch') !== null) {
            return self::bool('planning', 'use_matrix_batch', true);
        }

        return self::bool('planning', 'use_tomtom_matrix_batch', true);
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
     * @return list<array{code:string,label:string,configured:bool,masked:string,source:string,config_key:string,env_key:string}>
     */
    public static function mapProviderStatusesForAdmin(): array
    {
        return \App\Services\MapRouting\MapRoutingProviderFactory::allProviderStatusesForAdmin();
    }

    /**
     * @return array{code:string,label:string,configured:bool}
     */
    public static function activeMapProviderStatusForAdmin(): array
    {
        $code = self::mapProvider();
        $statuses = self::mapProviderStatusesForAdmin();
        foreach ($statuses as $row) {
            if (($row['code'] ?? '') === $code) {
                return [
                    'code' => $code,
                    'label' => (string) ($row['label'] ?? $code),
                    'configured' => !empty($row['configured']),
                ];
            }
        }

        return ['code' => $code, 'label' => $code, 'configured' => false];
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
     * Kurum / bölge / platform hedefine operasyonel bölüm yazar.
     *
     * @param array<string, mixed> $payload
     * @return true|string
     */
    private static function persistScopedSection(string $section, array $payload)
    {
        if (!KurumCorporateSettings::isKurumOperationalSection($section)) {
            return 'Bu bölüm tenant kapsamında kaydedilemez.';
        }

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null) {
            return KurumCorporateSettings::saveSection($kurumId, $section, $payload);
        }

        $target = SettingsWriteScope::resolveSaveTarget();
        if (is_string($target)) {
            return $target;
        }
        if (($target['target'] ?? '') === SettingsWriteScope::TARGET_BOLGE) {
            return BolgeCorporateSettings::saveSection((int) $target['bolge_id'], $section, $payload);
        }
        if (($target['target'] ?? '') !== SettingsWriteScope::TARGET_PLATFORM) {
            return 'Kayıt kapsamı belirlenemedi.';
        }
        if (!SettingsWriteScope::canWritePlatformDefaults()) {
            return 'Platform varsayılanları yalnızca sistem sahibi tarafından değiştirilebilir.';
        }

        return self::writePlatformSection($section, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return true|string
     */
    private static function writePlatformSection(string $section, array $payload)
    {
        $current = AppSettingsStore::read();
        $sections = is_array($current) ? $current : [];
        $existing = is_array($sections[$section] ?? null) ? $sections[$section] : [];
        $sections[$section] = array_merge($existing, $payload);
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

        if (KurumCorporateSettings::isKurumOperationalSection($section)) {
            return self::persistScopedSection($section, $toSave);
        }

        if (!SettingsWriteScope::canWritePlatformDefaults()) {
            return 'Platform varsayılanları yalnızca sistem sahibi tarafından değiştirilebilir.';
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
            if (class_exists(BolgeCorporateSettings::class)) {
                $bolgeVal = BolgeCorporateSettings::getSectionValue($section, $key);
                if ($bolgeVal !== null) {
                    return $bolgeVal;
                }
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
        foreach (['map', 'planning', 'durations', 'field_visit', 'audit_log', 'uhds_telehealth', 'corporate', 'public_hastaarama', 'patient_portal', 'esys_bridge', 'usbs_bridge', 'federation', 'modern_frontend', 'clinical_decision_support', 'debug', 'maintenance', 'nobet', 'vardiya', 'kps'] as $section) {
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
        if ($type === 'enum') {
            $options = $field['options'] ?? [];
            if (!is_array($options)) {
                $options = [];
            }
            $allowed = array_map('strval', array_keys($options));
            if (!in_array($raw, $allowed, true)) {
                return '«' . $label . '» için geçersiz seçim.';
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
                [
                    'key' => 'provider',
                    'label' => 'Harita / rota sağlayıcısı',
                    'description' => 'Rota hesaplama, harita gösterimi ve geocode için kullanılacak servis.',
                    'type' => 'enum',
                    'options' => [
                        'tomtom' => 'TomTom',
                        'openrouteservice' => 'OpenRouteService',
                        'mapbox' => 'Mapbox',
                        'google' => 'Google Maps',
                    ],
                ],
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
                ['key' => 'izolasyon_oncelik_bonusu', 'label' => 'İzolasyon öncelik bonusu', 'description' => 'İzolasyon gerektiren hastalar rota skorunda öne alınır.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'izolasyon_karisim_cezasi', 'label' => 'İzolasyon karışım cezası', 'description' => 'İzolasyonlu ve izolasyonsuz hastaların aynı ekibe atanması için ek maliyet.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'yetkinlik_eslesme_bonusu', 'label' => 'Yetkinlik eşleşme bonusu', 'description' => 'Görev tipi ile ekip ünvanı uyumlu olduğunda skor bonusu.', 'type' => 'int', 'min' => 0, 'max' => 500],
                ['key' => 'varsayilan_arac_kapasitesi', 'label' => 'Varsayılan araç kapasitesi', 'description' => 'Ekip–araç bağlantısı yoksa kullanılan hasta kapasitesi.', 'type' => 'int', 'min' => 1, 'max' => 20],
                ['key' => 'travel_time_weight', 'label' => 'Sürüş süresi ağırlığı', 'description' => 'TomTom sürüş süresinin skor formülündeki çarpanı.', 'type' => 'int', 'min' => 1, 'max' => 10],
                ['key' => 'use_matrix_batch', 'label' => 'Matrix toplu sorgu', 'description' => 'Açıkken tek API isteğiyle çoklu hedef süresi alınır; kapalıyken tekil rota çağrısı.', 'type' => 'bool'],
                ['key' => 'use_tomtom_matrix_batch', 'label' => 'TomTom Matrix toplu sorgu (eski)', 'description' => 'Geriye uyumluluk; use_matrix_batch yoksa kullanılır.', 'type' => 'bool'],
            ],
            'durations' => [
                ['key' => 'sure_pansuman', 'label' => 'Pansuman süresi (dk)', 'description' => 'Rota planlama varsayılan pansuman süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
                ['key' => 'sure_muayene', 'label' => 'Muayene süresi (dk)', 'description' => 'Rota planlama varsayılan muayene süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
                ['key' => 'sure_izlem', 'label' => 'İzlem süresi (dk)', 'description' => 'Rota planlama varsayılan izlem süresi.', 'type' => 'int', 'min' => 1, 'max' => 240],
            ],
            'field_visit' => [
                [
                    'key' => 'checkin_mode',
                    'label' => 'GPS konum kaydı',
                    'description' => 'Yeni/düzenlenen izlem kaydında saha konumu. «Yapıldı izlemlerde zorunlu» yalnızca yapıldı=evet kayıtlarında GPS ister.',
                    'type' => 'enum',
                    'options' => [
                        'optional' => 'İsteğe bağlı',
                        'recommended' => 'Önerilen (uyarı göster)',
                        'required_completed' => 'Yapıldı izlemlerde zorunlu',
                    ],
                ],
                [
                    'key' => 'offline_plan_cache',
                    'label' => 'Günlük plan önbelleği',
                    'description' => 'Dashboard günün planı verisi çevrimdışıyken son bilinen kayıttan gösterilir (PWA).',
                    'type' => 'bool',
                ],
                [
                    'key' => 'geofence_radius_m',
                    'label' => 'Geofence yarıçapı (m)',
                    'description' => 'Hasta adres koordinatından bu mesafeden uzak check-in için uyarı gösterilir (kaydı engellemez). 0 = kapalı.',
                    'type' => 'int',
                    'min' => 0,
                    'max' => 50000,
                ],
                [
                    'key' => 'min_gps_accuracy_m',
                    'label' => 'Min. GPS doğruluğu (m)',
                    'description' => 'Yapıldı + zorunlu modda cihaz doğruluğu bu değerden kötüyse kayıt reddedilir. 0 = kontrol yok.',
                    'type' => 'int',
                    'min' => 0,
                    'max' => 5000,
                ],
                [
                    'key' => 'offline_draft_enabled',
                    'label' => 'Çevrimdışı izlem taslağı',
                    'description' => 'İzlem formu tarayıcıda taslak olarak saklanır; bağlantı gelince senkronize edilir.',
                    'type' => 'bool',
                ],
            ],
            'audit_log' => [
                [
                    'key' => 'retention_days',
                    'label' => 'Saklama süresi (gün)',
                    'description' => 'Bu süreden eski denetim kayıtları «Eski kayıtları temizle» ile silinebilir.',
                    'type' => 'int',
                    'min' => 30,
                    'max' => 3650,
                ],
            ],
            'uhds_telehealth' => [
                [
                    'key' => 'enabled',
                    'label' => 'Görüntülü görüşme',
                    'description' => 'UHDS randevularında Jitsi tabanlı video oda ve hasta davet bağlantısı.',
                    'type' => 'bool',
                ],
                [
                    'key' => 'provider',
                    'label' => 'Sağlayıcı',
                    'description' => 'Şu an yalnızca Jitsi Meet desteklenir.',
                    'type' => 'enum',
                    'options' => [
                        'jitsi' => 'Jitsi Meet',
                        'disabled' => 'Kapalı',
                    ],
                ],
                [
                    'key' => 'jitsi_domain',
                    'label' => 'Jitsi sunucu adresi',
                    'description' => 'Örn. meet.jit.si veya kurum içi Jitsi sunucusu (https olmadan).',
                    'type' => 'text',
                ],
                [
                    'key' => 'room_prefix',
                    'label' => 'Oda adı öneki',
                    'description' => 'Jitsi oda adlarında kullanılır (esh-uhds vb.).',
                    'type' => 'text',
                ],
                [
                    'key' => 'patient_join_hours',
                    'label' => 'Hasta davet süresi (saat)',
                    'description' => 'Hasta katılım bağlantısının geçerlilik süresi.',
                    'type' => 'int',
                    'min' => 1,
                    'max' => 72,
                ],
                [
                    'key' => 'auto_prompt_visit',
                    'label' => 'Görüşme sonrası izlem öner',
                    'description' => 'Görüşme bitince izlem kaydı oluşturma bağlantısı gösterilir.',
                    'type' => 'bool',
                ],
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
            'patient_portal' => [
                ['key' => 'enabled', 'label' => 'Hasta / bakım veren portalı', 'description' => 'TC + kayıtlı telefon ile oturum; plan, ziyaret özeti ve SMS onayı.', 'type' => 'bool'],
                ['key' => 'bilgilendirme_metni', 'label' => 'Giriş bilgilendirme metni', 'description' => 'Portal giriş formunun üstünde gösterilir (KVKK).', 'type' => 'textarea'],
                ['key' => 'session_hours', 'label' => 'Oturum süresi (saat)', 'description' => 'Başarılı girişten sonra portal oturumunun geçerlilik süresi.', 'type' => 'int', 'min' => 1, 'max' => 24],
                ['key' => 'show_planned_visits', 'label' => 'Planlı ziyaretler', 'description' => 'Yaklaşan planlı izlemleri göster.', 'type' => 'bool'],
                ['key' => 'show_visit_history', 'label' => 'Ziyaret geçmişi özeti', 'description' => 'Son izlemlerin tarih ve yapıldı/yapılmadı özetini göster (klinik detay yok).', 'type' => 'bool'],
                ['key' => 'show_uhds_appointments', 'label' => 'UHDS randevuları', 'description' => 'Yaklaşan görüntülü görüşme randevularını göster.', 'type' => 'bool'],
                ['key' => 'otp_sms_enabled', 'label' => 'SMS OTP doğrulaması', 'description' => 'Açıkken portal girişinden sonra tek kullanımlık SMS kodu doğrulaması zorunlu olur.', 'type' => 'bool'],
            ],
            'esys_bridge' => [
                ['key' => 'enabled', 'label' => 'ESYS köprüsü', 'description' => 'Dosya tabanlı dışa/içe aktarma ve referans eşleme.', 'type' => 'bool'],
                [
                    'key' => 'api_mode',
                    'label' => 'API modu',
                    'description' => 'Dosya=yalnızca JSON, Stub=lokal test sunucusu, HTTP=gerçek uç noktaya gönderim.',
                    'type' => 'enum',
                    'options' => [
                        'file' => 'Dosya (JSON)',
                        'stub' => 'Stub API',
                        'http' => 'HTTP API',
                    ],
                ],
                ['key' => 'export_patient_limit', 'label' => 'Dışa aktarma hasta üst sınırı', 'description' => 'Tek pakette en fazla hasta kaydı.', 'type' => 'int', 'min' => 10, 'max' => 5000],
                ['key' => 'export_visit_days', 'label' => 'İzlem dışa aktarma günü', 'description' => 'Son kaç günün izlemleri pakete dahil edilir.', 'type' => 'int', 'min' => 7, 'max' => 365],
                ['key' => 'export_only_missing_refs', 'label' => 'Yalnızca eksik ESYS ref', 'description' => 'Hasta dışa aktarmada esys_hasta_ref veya esys_basvuru_ref boş olanlar.', 'type' => 'bool'],
                ['key' => 'api_enabled', 'label' => 'API köprüsü (deneysel)', 'description' => 'Kapalıyken HTTP gönderimi yapılmaz; yalnızca dosya modu kullanılır.', 'type' => 'bool'],
                ['key' => 'api_base_url', 'label' => 'API taban URL', 'description' => 'Örn. https://hsys.saglik.gov.tr/api — resmi uç yayınlandığında.', 'type' => 'text'],
            ],
            'usbs_bridge' => [
                ['key' => 'enabled', 'label' => 'USBS köprüsü', 'description' => 'İzlem bildirimi JSON dışa/içe aktarma ve referans eşleme.', 'type' => 'bool'],
                ['key' => 'export_visit_days', 'label' => 'İzlem dışa aktarma günü', 'description' => 'Son kaç günün yapılmış izlemleri pakete dahil edilir.', 'type' => 'int', 'min' => 7, 'max' => 365],
                ['key' => 'export_only_pending_bildirim', 'label' => 'Yalnızca bekleyen bildirim', 'description' => 'Dışa aktarmada usbs_bildirim_durum pending veya boş olan izlemler.', 'type' => 'bool'],
                ['key' => 'auto_queue_on_visit_save', 'label' => 'İzlem kaydında kuyruğa al', 'description' => 'Yapılmış izlem kaydedilince usbs_bildirim_durum = pending (sent değilse).', 'type' => 'bool'],
                ['key' => 'include_erecete_stub', 'label' => 'e-Reçete stub', 'description' => 'Dışa aktarma paketine erecete_payload alanını ekle.', 'type' => 'bool'],
                [
                    'key' => 'api_mode',
                    'label' => 'API modu',
                    'description' => 'Dosya=yalnızca JSON, Stub=lokal test sunucusu, HTTP=gerçek uç noktaya gönderim.',
                    'type' => 'enum',
                    'options' => [
                        'file' => 'Dosya (JSON)',
                        'stub' => 'Stub API',
                        'http' => 'HTTP API',
                    ],
                ],
                ['key' => 'api_enabled', 'label' => 'API köprüsü (deneysel)', 'description' => 'Kapalıyken HTTP gönderimi yapılmaz; yalnızca dosya modu kullanılır.', 'type' => 'bool'],
                ['key' => 'api_base_url', 'label' => 'API taban URL', 'description' => 'Resmi USBS / e-Nabız HTTP uçları yayınlandığında.', 'type' => 'text'],
            ],
            'federation' => [
                ['key' => 'enabled', 'label' => 'Federasyon köprüsü', 'description' => 'Çok bölgeli SaaS dosya senkronu ve bölge filtreleri.', 'type' => 'bool'],
                ['key' => 'node_ref', 'label' => 'Düğüm referansı', 'description' => 'Bu kurulumun hub tarafındaki benzersiz kimliği (örn. denizli-esh-01).', 'type' => 'text'],
                ['key' => 'export_include_stats', 'label' => 'Özet istatistik', 'description' => 'Dışa aktarma paketine kurum başına aktif hasta / izlem sayısı ekle.', 'type' => 'bool'],
                ['key' => 'hub_api_enabled', 'label' => 'Hub API (hazırlık)', 'description' => 'Kapalıyken yalnızca dosya köprüsü kullanılır.', 'type' => 'bool'],
                ['key' => 'hub_base_url', 'label' => 'Hub taban URL', 'description' => 'Merkezi federasyon hub HTTP adresi (gelecek).', 'type' => 'text'],
                ['key' => 'central_manifest_url', 'label' => 'Merkezi güncelleme manifest URL', 'description' => 'Sürüm / paket kontrolü için JSON manifest (opsiyonel).', 'type' => 'text'],
            ],
            'modern_frontend' => [
                ['key' => 'enabled', 'label' => 'Modern frontend', 'description' => 'Vue 3 pilot bileşenleri (mevcut jQuery sayfalarına ek; yerine geçmez).', 'type' => 'bool'],
                ['key' => 'dashboard_pilot', 'label' => 'Dashboard pilot', 'description' => 'Ana panelde günün planı özeti kartı (Vue ESM).', 'type' => 'bool'],
                ['key' => 'planning_pilot', 'label' => 'Plan listesi pilot', 'description' => 'Planlı izlem listesinde özet şeridi.', 'type' => 'bool'],
                ['key' => 'pilot_rollout_percent', 'label' => 'Pilot rollout yüzdesi', 'description' => '0-100 arası; kullanıcıların yalnızca bir kısmında modern pilot aktif olur.', 'type' => 'int', 'min' => 0, 'max' => 100],
                ['key' => 'use_built_bundle', 'label' => 'Derlenmiş Vite bundle', 'description' => 'npm run build çıktısını kullan (frontend/modern). Kapalıyken CDN Vue + .mjs.', 'type' => 'bool'],
                ['key' => 'vue_cdn_url', 'label' => 'Vue ESM CDN URL', 'description' => 'Boş bırakılırsa jsDelivr vue@3.5.13 prod ESM. Özel URL kullanıyorsanız *.prod.js tercih edin (CSP unsafe-eval gerektirmez).', 'type' => 'text'],
            ],
            'clinical_decision_support' => [
                ['key' => 'enabled', 'label' => 'Klinik karar desteği', 'description' => 'Braden/İTAKİ/Harizmi/MNA/Barthel skorlarına göre uyarılar ve yüksek riskli izlenmeyen hasta listesi.', 'type' => 'bool'],
                ['key' => 'overdue_days', 'label' => 'İzlem gecikme eşiği (gün)', 'description' => 'Yüksek riskli hastada son yapılmış izlemden bu kadar gün geçince uyarı.', 'type' => 'int', 'min' => 7, 'max' => 180],
                ['key' => 'braden_high_threshold', 'label' => 'Braden yüksek risk üst sınırı', 'description' => 'Bu skor ve altı yüksek bası riski sayılır (düşük skor = yüksek risk).', 'type' => 'int', 'min' => 6, 'max' => 18],
                ['key' => 'fall_risk_threshold', 'label' => 'Düşme riski eşiği (İTAKİ/Harizmi)', 'description' => 'Bu skor ve üzeri yüksek düşme riski.', 'type' => 'int', 'min' => 5, 'max' => 25],
                ['key' => 'mna_risk_threshold', 'label' => 'MNA risk eşiği', 'description' => 'Bu skorun altı malnütrisyon riski veya malnütrisyon.', 'type' => 'int', 'min' => 5, 'max' => 11],
                ['key' => 'barthel_severe_threshold', 'label' => 'Barthel tam bağımlılık eşiği', 'description' => 'Bu skor ve altı tam bağımlılık (düşük skor = yüksek bağımlılık).', 'type' => 'int', 'min' => 0, 'max' => 40],
                ['key' => 'barthel_dependency_threshold', 'label' => 'Barthel yüksek bağımlılık eşiği', 'description' => 'Bu skor ve altı yüksek bağımlılık uyarısı verilir.', 'type' => 'int', 'min' => 20, 'max' => 90],
                ['key' => 'show_on_dashboard', 'label' => 'Dashboard özeti', 'description' => 'Ana sayfada yüksek riskli izlenmeyen hasta sayısı kartı.', 'type' => 'bool'],
                ['key' => 'show_on_patient_detail', 'label' => 'Hasta detayı uyarıları', 'description' => 'Hasta dosyasında klinik uyarı paneli.', 'type' => 'bool'],
                ['key' => 'show_on_visit_form', 'label' => 'İzlem formu uyarıları', 'description' => 'Yeni izlem kaydında klinik uyarı bandı.', 'type' => 'bool'],
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
