<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Helpers\IdHelper;
use App\Models\Patient;
use App\Models\Uhds;

/**
 * Hasta / bakım veren self-servis portalı — TC + kayıtlı telefon doğrulama, oturum, özet veriler.
 */
final class PatientPortalHelper
{
    private const SESSION_KEY = 'esh_patient_portal';

    public static function isEnabled(): bool
    {
        return OperationalSettings::isPatientPortalEnabled();
    }

    public static function sessionHours(): int
    {
        return OperationalSettings::patientPortalSessionHours();
    }

    public static function normalizePhone(?string $raw): string
    {
        return ValidationHelper::phoneDigits($raw);
    }

    /**
     * @return 'hasta'|'bakimveren'|null
     */
    public static function resolveLoginRole(object $patient, string $inputPhone): ?string
    {
        $input = self::normalizePhone($inputPhone);
        if (strlen($input) !== 11) {
            return null;
        }
        foreach (['ceptel1', 'ceptel2'] as $col) {
            if (self::normalizePhone((string) ($patient->{$col} ?? '')) === $input) {
                return 'hasta';
            }
        }
        if (self::normalizePhone((string) ($patient->bakimveren_tel ?? '')) === $input) {
            return 'bakimveren';
        }

        return null;
    }

    /**
     * Aktif hasta kaydı (pasif=0) — portal girişi.
     */
    public static function findActivePatientForLogin(string $tc): ?object
    {
        $tc = ValidationHelper::tcDigitsOnly($tc);
        if (!ValidationHelper::isTcLength11($tc)) {
            return null;
        }
        $row = (new Patient())->findByTc($tc);
        if (!$row || (string) ($row->pasif ?? '') !== '0') {
            return null;
        }

        return $row;
    }

    public static function startSession(object $patient, string $role): void
    {
        $hours = self::sessionHours();
        $_SESSION[self::SESSION_KEY] = [
            'patient_id' => IdHelper::normalizeRequestId($patient->id ?? null),
            'tc' => ValidationHelper::tcDigitsOnly((string) ($patient->tckimlik ?? '')),
            'kurum_id' => (int) ($patient->kurum_id ?? 1),
            'role' => $role === 'bakimveren' ? 'bakimveren' : 'hasta',
            'expires' => time() + ($hours * 3600),
        ];
    }

    public static function clearSession(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function hasValidSession(): bool
    {
        $s = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($s)) {
            return false;
        }
        $exp = (int) ($s['expires'] ?? 0);
        $pid = IdHelper::normalizeRequestId($s['patient_id'] ?? null);
        if ($pid === null || $exp < time()) {
            self::clearSession();

            return false;
        }

        return true;
    }

    /**
     * @return array{patient_id:string,tc:string,kurum_id:int,role:string,expires:int}|null
     */
    public static function sessionClaims(): ?array
    {
        if (!self::hasValidSession()) {
            return null;
        }
        $s = $_SESSION[self::SESSION_KEY];
        if (!is_array($s)) {
            return null;
        }
        $pid = IdHelper::normalizeRequestId($s['patient_id'] ?? null);
        if ($pid === null) {
            return null;
        }

        return [
            'patient_id' => $pid,
            'tc' => (string) ($s['tc'] ?? ''),
            'kurum_id' => (int) ($s['kurum_id'] ?? 1),
            'role' => (string) ($s['role'] ?? 'hasta'),
            'expires' => (int) ($s['expires'] ?? 0),
        ];
    }

    public static function loadSessionPatient(): ?object
    {
        $claims = self::sessionClaims();
        if ($claims === null) {
            return null;
        }
        $patient = (new Patient())->getById($claims['patient_id']);
        if (!$patient || (string) ($patient->tckimlik ?? '') !== $claims['tc']) {
            self::clearSession();

            return null;
        }
        if ((string) ($patient->pasif ?? '') !== '0') {
            self::clearSession();

            return null;
        }

        return $patient;
    }

    public static function roleLabel(string $role): string
    {
        return $role === 'bakimveren' ? 'Bakım veren' : 'Hasta';
    }

    /**
     * @return list<object>
     */
    public static function upcomingPlannedVisits(string $tc, int $kurumId, int $limit = 8): array
    {
        if (!OperationalSettings::patientPortalShowPlannedVisits()) {
            return [];
        }
        $tc = ValidationHelper::tcDigitsOnly($tc);
        if (!ValidationHelper::isTcLength11($tc)) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $db = Database::getInstance();
        $sql = "SELECT p.planlanantarih, p.zaman, p.aciklama,
                       (SELECT GROUP_CONCAT(isl.islemadi ORDER BY isl.id SEPARATOR ', ')
                        FROM #__islemler isl
                        WHERE FIND_IN_SET(isl.id, REPLACE(p.yapilacak, ' ', '')) > 0) AS yapilacaklar
                FROM #__pizlemler p
                WHERE p.hastatckimlik = ?
                  AND p.kurum_id = ?
                  AND COALESCE(p.durum, 0) = 0
                  AND p.planlanantarih >= CURDATE()
                ORDER BY p.planlanantarih ASC, p.zaman ASC
                LIMIT {$limit}";
        $rows = $db->fetchObjectListPrepared($sql, [$tc, $kurumId]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    public static function recentVisitSummary(string $tc, int $kurumId, int $limit = 6): array
    {
        if (!OperationalSettings::patientPortalShowVisitHistory()) {
            return [];
        }
        $tc = ValidationHelper::tcDigitsOnly($tc);
        if (!ValidationHelper::isTcLength11($tc)) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $db = Database::getInstance();
        $sql = "SELECT izlemtarihi, zaman, yapildimi
                FROM #__izlemler
                WHERE hastatckimlik = ?
                  AND kurum_id = ?
                ORDER BY izlemtarihi DESC, id DESC
                LIMIT {$limit}";
        $rows = $db->fetchObjectListPrepared($sql, [$tc, $kurumId]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    public static function upcomingUhdsAppointments(string $tc, int $kurumId, int $limit = 6): array
    {
        if (!OperationalSettings::patientPortalShowUhdsAppointments()) {
            return [];
        }
        $tc = ValidationHelper::tcDigitsOnly($tc);
        if (!ValidationHelper::isTcLength11($tc)) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $db = Database::getInstance();
        $sql = "SELECT r.id, r.randevu_tarihi, r.zaman, r.hasta_geldi, r.video_room_id, b.bransadi
                FROM #__goruntulu_randevu r
                INNER JOIN #__branslar b ON b.id = r.brans_id
                WHERE r.hastatckimlik = ?
                  AND r.kurum_id = ?
                  AND r.randevu_tarihi >= CURDATE()
                ORDER BY r.randevu_tarihi ASC, r.zaman ASC
                LIMIT {$limit}";
        $rows = $db->fetchObjectListPrepared($sql, [$tc, $kurumId]);

        return is_array($rows) ? $rows : [];
    }

    public static function visitStatusLabel($yapildimi): string
    {
        if ($yapildimi === null || $yapildimi === '') {
            return 'Belirtilmedi';
        }

        return (int) $yapildimi === 1 ? 'Yapıldı' : 'Yapılmadı';
    }

    public static function uhdsStatusLabel($hastaGeldi): string
    {
        return Uhds::yapildiMiLabel($hastaGeldi);
    }

    public static function updateSmsConsent(int|string $patientId, bool $onay): bool
    {
        $patient = new Patient();
        if (!$patient->load($patientId)) {
            return false;
        }
        $patient->bind(['sms_bilgilendirme_onay' => $onay ? 1 : 0], true);

        return (bool) $patient->store();
    }

    public static function safeStatusMessageForPortal(?object $patient): string
    {
        if ($patient === null) {
            return 'Giriş bilgileri eşleşmedi. TC ve kayıtlı telefon numaranızı kontrol edin.';
        }
        $pasif = (string) ($patient->pasif ?? '');
        if ($pasif !== '0') {
            return 'Hasta dosyanız aktif portal kullanımına uygun görünmüyor. Kurumunuzla iletişime geçin.';
        }

        return '';
    }

    public static function isAppointmentRequestAllowed(object $patient): bool
    {
        return (string) ($patient->pasif ?? '') === '0';
    }

    /**
     * @return list<object>
     */
    public static function listAppointmentRequests(int|string $patientId, int $limit = 10): array
    {
        $patientId = IdHelper::entityIdOrFalse($patientId);
        if ($patientId === false) {
            return [];
        }
        $limit = max(1, min(50, $limit));
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT id, talep_tarihi, mevcut_tarih, talep_tarih, talep_zaman, neden, durum, durum_notu
             FROM #__portal_appointment_requests
             WHERE hasta_id = ?
             ORDER BY id DESC
             LIMIT ' . $limit,
            [$patientId]
        );

        return is_array($rows) ? $rows : [];
    }

    public static function createAppointmentRequest(
        int|string $patientId,
        int $kurumId,
        int|string $uhdsId,
        string $mevcutTarih,
        string $talepTarih,
        ?int $talepZaman,
        string $neden
    ): bool {
        $patientId = IdHelper::entityIdOrFalse($patientId);
        $uhdsId = IdHelper::entityIdOrFalse($uhdsId);
        $kurumId = max(1, $kurumId);
        if ($patientId === false || $uhdsId === false) {
            return false;
        }
        $talepZaman = ($talepZaman !== null && $talepZaman >= 0 && $talepZaman <= 2) ? $talepZaman : null;
        $neden = trim($neden);
        if ($neden === '') {
            return false;
        }
        if (strlen($neden) > 500) {
            $neden = substr($neden, 0, 500);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mevcutTarih) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $talepTarih)) {
            return false;
        }
        $db = Database::getInstance();

        return $db->executePrepared(
            'INSERT INTO #__portal_appointment_requests
             (hasta_id, kurum_id, uhds_id, talep_tarihi, mevcut_tarih, talep_tarih, talep_zaman, neden, durum, created_at)
             VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW())',
            [$patientId, $kurumId, $uhdsId, $mevcutTarih, $talepTarih, $talepZaman, $neden, 'queued']
        );
    }

    public static function appointmentRequestsTableReady(): bool
    {
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__portal_appointment_requests');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$tbl]
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<object>
     */
    public static function listAdminAppointmentRequests(?int $kurumId = null, string $durum = 'queued', int $limit = 50): array
    {
        if (!self::appointmentRequestsTableReady()) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = ['1=1'];
        if ($durum !== '' && $durum !== 'all') {
            $where[] = 'r.durum = ?';
            $params[] = $durum;
        }
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'r.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'r', 'kurum_id');
        }
        $sql = 'SELECT r.*, h.isim, h.soyisim, h.tckimlik
                FROM #__portal_appointment_requests AS r
                INNER JOIN #__hastalar AS h ON h.id = r.hasta_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY r.id DESC
                LIMIT ' . $limit;
        $rows = Database::getInstance()->fetchObjectListPrepared($sql, $params);

        return is_array($rows) ? $rows : [];
    }

    public static function countQueuedAppointmentRequests(?int $kurumId = null): int
    {
        if (!self::appointmentRequestsTableReady()) {
            return 0;
        }
        $params = ['queued'];
        $where = ['r.durum = ?'];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'r.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'r', 'kurum_id');
        }
        $count = Database::getInstance()->loadResultPrepared(
            'SELECT COUNT(*) FROM #__portal_appointment_requests AS r WHERE ' . implode(' AND ', $where),
            $params
        );

        return (int) $count;
    }

    public static function updateAppointmentRequestStatus(int $id, string $durum, ?string $notu = null): bool
    {
        if (!self::appointmentRequestsTableReady() || $id <= 0) {
            return false;
        }
        $allowed = ['queued', 'approved', 'rejected', 'cancelled'];
        $durum = strtolower(trim($durum));
        if (!in_array($durum, $allowed, true)) {
            return false;
        }
        $notu = $notu !== null ? trim($notu) : null;
        if ($notu !== null && strlen($notu) > 500) {
            $notu = substr($notu, 0, 500);
        }
        $params = [$durum, $notu, $id];
        $sql = 'UPDATE #__portal_appointment_requests SET durum = ?, durum_notu = ?, updated_at = NOW() WHERE id = ?'
            . TenantSqlHelper::andBare('kurum_id');

        return Database::getInstance()->executePrepared($sql, $params);
    }
}
