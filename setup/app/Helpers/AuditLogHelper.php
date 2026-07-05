<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\AuditLog;

/**
 * KVKK / iç denetim — işlem günlüğü yazıcısı.
 */
final class AuditLogHelper
{
    public const ACTION_AUTH_LOGIN = 'auth.login';
    public const ACTION_AUTH_LOGOUT = 'auth.logout';

    public const ACTION_PATIENT_VIEW = 'patient.view';
    public const ACTION_PATIENT_EDIT_VIEW = 'patient.edit_view';
    public const ACTION_PATIENT_CREATE = 'patient.create';
    public const ACTION_PATIENT_UPDATE = 'patient.update';
    public const ACTION_PATIENT_EXPORT = 'patient.export';

    public const ACTION_VISIT_CREATE = 'visit.create';
    public const ACTION_VISIT_UPDATE = 'visit.update';
    public const ACTION_VISIT_DELETE = 'visit.delete';
    public const ACTION_VISIT_EXPORT = 'visit.export';
    public const ACTION_VISIT_CHECKIN_GEOFENCE = 'visit.checkin_geofence';

    public const ACTION_SETTINGS_UPDATE = 'settings.update';
    public const ACTION_KPS_LOOKUP = 'kps.lookup';
    public const ACTION_SMS_SEND = 'sms.send';
    public const ACTION_USER_CREATE = 'user.create';
    public const ACTION_USER_UPDATE = 'user.update';
    public const ACTION_USER_DELETE = 'user.delete';
    public const ACTION_ROLE_CREATE = 'role.create';
    public const ACTION_ROLE_UPDATE = 'role.update';
    public const ACTION_ROLE_DELETE = 'role.delete';
    public const ACTION_PATIENT_PORTAL_LOGIN = 'patient_portal.login';
    public const ACTION_PATIENT_PORTAL_SMS = 'patient_portal.sms_consent';
    public const ACTION_UHDS_VIDEO_START = 'uhds.video.start';
    public const ACTION_UHDS_VIDEO_COMPLETE = 'uhds.video.complete';
    public const ACTION_ESYS_EXPORT = 'esys.export';
    public const ACTION_ESYS_IMPORT = 'esys.import';
    public const ACTION_USBS_EXPORT = 'usbs.export';
    public const ACTION_USBS_IMPORT = 'usbs.import';
    public const ACTION_FEDERATION_EXPORT = 'federation.export';
    public const ACTION_FEDERATION_IMPORT = 'federation.import';
    public const ACTION_AUDIT_PURGE = 'audit.purge';

    public const ACTION_PLANNED_VISIT_EXPORT = 'planned_visit.export';
    public const ACTION_STATS_EXPORT = 'stats.export';
    public const ACTION_ERAPOR_EXPORT = 'erapor.export';

    /**
     * @return array<string, string>
     */
    public static function actionOptions(): array
    {
        return [
            self::ACTION_AUTH_LOGIN => 'Oturum açma',
            self::ACTION_AUTH_LOGOUT => 'Oturum kapatma',
            self::ACTION_PATIENT_VIEW => 'Hasta görüntüleme',
            self::ACTION_PATIENT_EDIT_VIEW => 'Hasta düzenleme ekranı',
            self::ACTION_PATIENT_CREATE => 'Hasta oluşturma',
            self::ACTION_PATIENT_UPDATE => 'Hasta güncelleme',
            self::ACTION_PATIENT_EXPORT => 'Hasta listesi dışa aktarma',
            self::ACTION_VISIT_CREATE => 'İzlem oluşturma',
            self::ACTION_VISIT_UPDATE => 'İzlem güncelleme',
            self::ACTION_VISIT_DELETE => 'İzlem silme',
            self::ACTION_VISIT_EXPORT => 'İzlem listesi dışa aktarma',
            self::ACTION_VISIT_CHECKIN_GEOFENCE => 'Saha konumu geofence uyarısı',
            self::ACTION_SETTINGS_UPDATE => 'Sistem ayarı güncelleme',
            self::ACTION_KPS_LOOKUP => 'KPS sorgusu',
            self::ACTION_SMS_SEND => 'SMS gönderimi',
            self::ACTION_USER_CREATE => 'Kullanıcı oluşturma',
            self::ACTION_USER_UPDATE => 'Kullanıcı güncelleme',
            self::ACTION_USER_DELETE => 'Kullanıcı silme',
            self::ACTION_ROLE_CREATE => 'Rol oluşturma',
            self::ACTION_ROLE_UPDATE => 'Rol güncelleme',
            self::ACTION_ROLE_DELETE => 'Rol silme',
            self::ACTION_PATIENT_PORTAL_LOGIN => 'Hasta portalı girişi',
            self::ACTION_PATIENT_PORTAL_SMS => 'Hasta portalı SMS onayı',
            self::ACTION_UHDS_VIDEO_START => 'UHDS görüntülü görüşme başlangıcı',
            self::ACTION_UHDS_VIDEO_COMPLETE => 'UHDS görüntülü görüşme tamamlama',
            self::ACTION_ESYS_EXPORT => 'ESYS dışa aktarma',
            self::ACTION_ESYS_IMPORT => 'ESYS içe aktarma',
            self::ACTION_USBS_EXPORT => 'USBS dışa aktarma',
            self::ACTION_USBS_IMPORT => 'USBS içe aktarma',
            self::ACTION_FEDERATION_EXPORT => 'Federasyon dışa aktarma',
            self::ACTION_FEDERATION_IMPORT => 'Federasyon içe aktarma',
            self::ACTION_AUDIT_PURGE => 'Denetim kaydı temizleme',
            self::ACTION_PLANNED_VISIT_EXPORT => 'Planlı izlem dışa aktarma',
            self::ACTION_STATS_EXPORT => 'İstatistik raporu dışa aktarma',
            self::ACTION_ERAPOR_EXPORT => 'e-Rapor listesi dışa aktarma',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function entityTypeOptions(): array
    {
        return [
            'auth' => 'Oturum',
            'patient' => 'Hasta',
            'visit' => 'İzlem',
            'planned_visit' => 'Planlı izlem',
            'stats' => 'İstatistik',
            'erapor' => 'e-Rapor',
            'settings' => 'Ayarlar',
            'kps' => 'KPS',
            'sms' => 'SMS',
            'user' => 'Kullanıcı',
            'role' => 'Rol',
            'uhds' => 'UHDS',
            'esys' => 'ESYS',
            'usbs' => 'USBS',
            'federation' => 'Federasyon',
            'patient_portal' => 'Hasta portalı',
            'audit' => 'Denetim',
        ];
    }

    public static function actionLabel(string $action): string
    {
        $opts = self::actionOptions();

        return $opts[$action] ?? $action;
    }

    public static function entityTypeLabel(string $type): string
    {
        $opts = self::entityTypeOptions();

        return $opts[$type] ?? $type;
    }

    public static function enabled(): bool
    {
        return AuditLog::tableReady();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function log(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?string $entityRef = null,
        array $context = [],
        ?int $kurumId = null
    ): void {
        if (!self::enabled()) {
            return;
        }

        try {
            $userId = AuthHelper::sessionUserId();
            if ($kurumId === null || $kurumId <= 0) {
                $kurumId = TenantContext::sessionKurumId();
            }

            $ref = $entityRef;
            if ($ref !== null) {
                $ref = trim($ref);
                if ($ref === '') {
                    $ref = null;
                }
            }

            $ctxJson = null;
            if ($context !== []) {
                $ctxJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }

            $row = [
                'kurum_id' => ($kurumId !== null && $kurumId > 0) ? $kurumId : null,
                'user_id' => $userId,
                'action' => substr($action, 0, 64),
                'entity_type' => substr($entityType, 0, 32),
                'entity_id' => IdHelper::normalizeRequestId($entityId),
                'entity_ref' => $ref !== null ? substr($ref, 0, 64) : null,
                'ip_address' => self::clientIp(),
                'user_agent' => self::userAgent(),
                'request_uri' => self::requestUri(),
                'context_json' => $ctxJson,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            (new AuditLog())->insertRow($row);
        } catch (\Throwable $e) {
            // Ana iş akışını kesme
        }
    }

    public static function patientView(object $patient, array $context = []): void
    {
        self::log(
            self::ACTION_PATIENT_VIEW,
            'patient',
            IdHelper::normalizeRequestId($patient->id ?? null),
            self::patientTcRef($patient),
            $context,
            (int) ($patient->kurum_id ?? 0) ?: null
        );
    }

    public static function patientEditView(object $patient): void
    {
        self::log(
            self::ACTION_PATIENT_EDIT_VIEW,
            'patient',
            IdHelper::normalizeRequestId($patient->id ?? null),
            self::patientTcRef($patient),
            [],
            (int) ($patient->kurum_id ?? 0) ?: null
        );
    }

    public static function patientCreate(object $patient): void
    {
        self::log(
            self::ACTION_PATIENT_CREATE,
            'patient',
            IdHelper::normalizeRequestId($patient->id ?? null),
            self::patientTcRef($patient),
            [],
            (int) ($patient->kurum_id ?? 0) ?: null
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function patientUpdate(object $patient, array $context = []): void
    {
        self::log(
            self::ACTION_PATIENT_UPDATE,
            'patient',
            IdHelper::normalizeRequestId($patient->id ?? null),
            self::patientTcRef($patient),
            $context,
            (int) ($patient->kurum_id ?? 0) ?: null
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function patientExport(array $context = []): void
    {
        self::log(self::ACTION_PATIENT_EXPORT, 'patient', null, null, $context);
    }

    public static function visitCreate(object $visit, ?object $patient = null): void
    {
        $kurumId = (int) ($visit->kurum_id ?? ($patient->kurum_id ?? 0));
        self::log(
            self::ACTION_VISIT_CREATE,
            'visit',
            IdHelper::normalizeRequestId($visit->id ?? null),
            (string) ($visit->hastatckimlik ?? ''),
            [],
            $kurumId > 0 ? $kurumId : null
        );
    }

    public static function visitUpdate(object $visit): void
    {
        self::log(
            self::ACTION_VISIT_UPDATE,
            'visit',
            IdHelper::normalizeRequestId($visit->id ?? null),
            (string) ($visit->hastatckimlik ?? ''),
            [],
            (int) ($visit->kurum_id ?? 0) ?: null
        );
    }

    /**
     * @param array{outside:bool,distance_m:float|null,patient_has_coords:bool} $status
     */
    public static function visitCheckinGeofence(object $visit, object $patient, array $status): void
    {
        self::log(
            self::ACTION_VISIT_CHECKIN_GEOFENCE,
            'visit',
            IdHelper::normalizeRequestId($visit->id ?? null),
            (string) ($visit->hastatckimlik ?? ''),
            [
                'distance_m' => $status['distance_m'] ?? null,
                'radius_m' => OperationalSettings::fieldVisitGeofenceRadiusM(),
            ],
            (int) ($visit->kurum_id ?? $patient->kurum_id ?? 0) ?: null
        );
    }

    public static function visitDelete(object $visit): void
    {
        self::log(
            self::ACTION_VISIT_DELETE,
            'visit',
            IdHelper::normalizeRequestId($visit->id ?? null),
            (string) ($visit->hastatckimlik ?? ''),
            [],
            (int) ($visit->kurum_id ?? 0) ?: null
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function visitExport(array $context = []): void
    {
        self::log(self::ACTION_VISIT_EXPORT, 'visit', null, null, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function plannedVisitExport(array $context = []): void
    {
        self::log(self::ACTION_PLANNED_VISIT_EXPORT, 'planned_visit', null, null, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function statsExport(array $context = []): void
    {
        self::log(self::ACTION_STATS_EXPORT, 'stats', null, null, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function eraporExport(array $context = []): void
    {
        self::log(self::ACTION_ERAPOR_EXPORT, 'erapor', null, null, $context);
    }

    public static function authLogin(int|string $userId, ?int $kurumId = null): void
    {
        self::log(self::ACTION_AUTH_LOGIN, 'auth', IdHelper::normalizeRequestId($userId), null, [], $kurumId);
    }

    public static function authLogout(): void
    {
        self::log(self::ACTION_AUTH_LOGOUT, 'auth', AuthHelper::sessionUserId());
    }

    public static function settingsUpdate(string $tab, array $context = []): void
    {
        $context['tab'] = $tab;
        self::log(self::ACTION_SETTINGS_UPDATE, 'settings', null, null, $context);
    }

    public static function kpsLookup(string $tc): void
    {
        self::log(self::ACTION_KPS_LOOKUP, 'kps', null, ValidationHelper::tcDigitsOnly($tc));
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function smsSend(array $context = []): void
    {
        self::log(self::ACTION_SMS_SEND, 'sms', null, null, $context);
    }

    public static function userCreate(object $user): void
    {
        self::log(self::ACTION_USER_CREATE, 'user', IdHelper::normalizeRequestId($user->id ?? null), (string) ($user->username ?? ''));
    }

    public static function userUpdate(object $user): void
    {
        self::log(self::ACTION_USER_UPDATE, 'user', IdHelper::normalizeRequestId($user->id ?? null), (string) ($user->username ?? ''));
    }

    public static function userDelete(object $user): void
    {
        self::log(self::ACTION_USER_DELETE, 'user', IdHelper::normalizeRequestId($user->id ?? null), (string) ($user->username ?? ''));
    }

    public static function roleCreate(object $role): void
    {
        self::log(self::ACTION_ROLE_CREATE, 'role', (int) ($role->id ?? 0) ?: null, (string) ($role->slug ?? ''));
    }

    public static function roleUpdate(object $role): void
    {
        self::log(self::ACTION_ROLE_UPDATE, 'role', (int) ($role->id ?? 0) ?: null, (string) ($role->slug ?? ''));
    }

    public static function roleDelete(object $role): void
    {
        self::log(self::ACTION_ROLE_DELETE, 'role', (int) ($role->id ?? 0) ?: null, (string) ($role->slug ?? ''));
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function auditPurge(array $context = []): void
    {
        self::log(self::ACTION_AUDIT_PURGE, 'audit', null, null, $context);
    }

    private static function patientTcRef(object $patient): ?string
    {
        $tc = ValidationHelper::tcDigitsOnly((string) ($patient->tckimlik ?? ''));

        return ValidationHelper::isTcLength11($tc) ? $tc : null;
    }

    private static function clientIp(): ?string
    {
        if (class_exists(RateLimitHelper::class, false)) {
            $ip = RateLimitHelper::clientIp();

            return $ip !== '' ? substr($ip, 0, 64) : null;
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return $ip !== '' ? substr($ip, 0, 64) : null;
    }

    private static function userAgent(): ?string
    {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return $ua !== '' ? substr($ua, 0, 255) : null;
    }

    private static function requestUri(): ?string
    {
        $uri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''));

        return $uri !== '' ? substr($uri, 0, 512) : null;
    }
}
