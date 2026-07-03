<?php

declare(strict_types=1);

namespace App\Services\Usbs;

use App\Core\Database;
use App\Helpers\OperationalSettings;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;
use App\Helpers\UsbsBridgeHelper;
use App\Helpers\UsbsComplianceHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;
use App\Models\UsbsSyncLog;
use App\Models\Visit;

/**
 * USBS / e-Nabız dosya köprüsü — bildirim paketi ve referans eşleme.
 */
final class UsbsBridgeService
{
    public function pushCurrentBundle(): array
    {
        $mode = OperationalSettings::usbsBridgeApiMode();
        if ($mode === 'file') {
            return ['ok' => false, 'error' => 'API modu file'];
        }
        $client = $this->apiClientForMode($mode);
        if ($client === null) {
            return ['ok' => false, 'error' => 'API istemcisi başlatılamadı'];
        }
        $bundle = $this->exportBundle();

        return $client->pushBundle($bundle);
    }

    public function retryFailedNotifications(int $limit = 200): int
    {
        $limit = max(1, min(1000, $limit));
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT id FROM #__izlemler
             WHERE usbs_bildirim_durum IN (\'failed\', \'hata\')'
             . TenantSqlHelper::andBare('kurum_id')
             . ' ORDER BY id DESC LIMIT ' . $limit,
            []
        );
        if (!is_array($rows)) {
            return 0;
        }
        $count = 0;
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ok = $db->executePrepared(
                'UPDATE #__izlemler SET usbs_bildirim_durum = ?, usbs_bildirim_at = NULL WHERE id = ?'
                . TenantSqlHelper::andBare('kurum_id'),
                ['pending', $id]
            );
            if ($ok) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportBundle(): array
    {
        $kurumId = TenantContext::filterKurumId();
        $visitDays = OperationalSettings::usbsBridgeExportVisitDays();
        $onlyPending = OperationalSettings::usbsBridgeExportOnlyPending();

        $db = Database::getInstance();
        $patientSql = 'SELECT * FROM #__hastalar WHERE pasif = ?'
            . TenantSqlHelper::andBare('kurum_id')
            . ' ORDER BY id ASC LIMIT 500';
        $patientRows = $db->fetchObjectListPrepared($patientSql, ['0']);
        if (!is_array($patientRows)) {
            $patientRows = [];
        }

        $patientsOut = [];
        foreach ($patientRows as $row) {
            $patientsOut[] = [
                'esh_id' => (int) ($row->id ?? 0),
                'tckimlik' => (string) ($row->tckimlik ?? ''),
                'refs' => [
                    'enabiz_hasta_ref' => UsbsComplianceHelper::normalizeRef((string) ($row->enabiz_hasta_ref ?? '')),
                    'usbs_hasta_ref' => UsbsComplianceHelper::normalizeRef((string) ($row->usbs_hasta_ref ?? '')),
                ],
                'usbs_payload' => UsbsBridgeHelper::mapRowToUsbsPayload($row, 'patient'),
            ];
        }

        $visitSince = date('Y-m-d', strtotime('-' . $visitDays . ' days'));
        $visitSql = 'SELECT * FROM #__izlemler WHERE yapildimi = 1 AND izlemtarihi >= ?'
            . TenantSqlHelper::andBare('kurum_id');
        if ($onlyPending) {
            $visitSql .= " AND usbs_bildirim_durum IN ('pending', '')";
        }
        $visitSql .= ' ORDER BY izlemtarihi DESC, id DESC LIMIT 2000';
        $visitRows = $db->fetchObjectListPrepared($visitSql, [$visitSince]);
        if (!is_array($visitRows)) {
            $visitRows = [];
        }

        $visitsOut = [];
        foreach ($visitRows as $row) {
            $visitsOut[] = [
                'esh_id' => (int) ($row->id ?? 0),
                'tckimlik' => (string) ($row->hastatckimlik ?? ''),
                'refs' => [
                    'usbs_bildirim_ref' => UsbsComplianceHelper::normalizeRef((string) ($row->usbs_bildirim_ref ?? '')),
                    'usbs_bildirim_durum' => UsbsComplianceHelper::normalizeBildirimDurum(
                        (string) ($row->usbs_bildirim_durum ?? '')
                    ),
                    'erecete_ref' => UsbsComplianceHelper::normalizeRef((string) ($row->erecete_ref ?? '')),
                ],
                'usbs_payload' => UsbsBridgeHelper::mapRowToUsbsPayload($row, 'visit_bildirim'),
                'erecete_payload' => OperationalSettings::usbsBridgeIncludeEreceteStub()
                    ? UsbsBridgeHelper::mapRowToUsbsPayload($row, 'erecete')
                    : null,
            ];
        }

        return [
            'bundle_version' => UsbsBridgeHelper::BUNDLE_VERSION,
            'direction' => 'esh_to_usbs',
            'system' => 'USBS',
            'compatible_with' => ['e-Nabız', 'USBS', 'e-Reçete'],
            'generated_at' => gmdate('c'),
            'kurum_id' => $kurumId,
            'meta' => [
                'patient_count' => count($patientsOut),
                'visit_count' => count($visitsOut),
                'visit_since' => $visitSince,
                'only_pending_bildirim' => $onlyPending,
            ],
            'patients' => $patientsOut,
            'visits' => $visitsOut,
        ];
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array{ok:bool,stats:array<string,int>,errors:list<string>,direction?:string}
     */
    public function importRefs(array $bundle): array
    {
        $valid = UsbsBridgeHelper::validateImportBundle($bundle);
        if (!$valid['ok']) {
            return [
                'ok' => false,
                'stats' => [],
                'errors' => [(string) ($valid['error'] ?? 'Geçersiz paket')],
            ];
        }

        $stats = [
            'patients_updated' => 0,
            'visits_updated' => 0,
            'patients_skipped' => 0,
            'visits_skipped' => 0,
        ];
        $errors = [];

        $patients = is_array($bundle['patients'] ?? null) ? $bundle['patients'] : [];
        foreach ($patients as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $result = $this->importPatientItem($item);
            if ($result === true) {
                $stats['patients_updated']++;
            } elseif ($result === false) {
                $stats['patients_skipped']++;
            } else {
                $errors[] = 'Hasta satır ' . ($idx + 1) . ': ' . $result;
            }
        }

        $visits = is_array($bundle['visits'] ?? null) ? $bundle['visits'] : [];
        foreach ($visits as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $result = $this->importVisitItem($item);
            if ($result === true) {
                $stats['visits_updated']++;
            } elseif ($result === false) {
                $stats['visits_skipped']++;
            } else {
                $errors[] = 'İzlem satır ' . ($idx + 1) . ': ' . $result;
            }
        }

        $updatedTotal = $stats['patients_updated'] + $stats['visits_updated'];
        $ok = $updatedTotal > 0 || ($stats['patients_skipped'] + $stats['visits_skipped']) > 0;

        return [
            'ok' => $ok,
            'stats' => $stats,
            'errors' => $errors,
            'direction' => 'usbs_to_esh',
        ];
    }

    public function queueVisitNotification(int $visitId): void
    {
        if (!UsbsBridgeHelper::isReady() || !OperationalSettings::usbsAutoQueueOnVisitSave()) {
            return;
        }
        if (!UsbsComplianceHelper::enabled()) {
            return;
        }
        $visit = new Visit();
        if (!$visit->load($visitId)) {
            return;
        }
        if ((int) ($visit->yapildimi ?? 0) !== 1) {
            return;
        }
        $durum = UsbsComplianceHelper::normalizeBildirimDurum((string) ($visit->usbs_bildirim_durum ?? ''));
        if ($durum === 'sent') {
            return;
        }
        $visit->bind([
            'usbs_bildirim_durum' => 'pending',
            'usbs_bildirim_at' => null,
        ], true);
        $visit->store();
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function logSync(
        string $direction,
        string $status,
        ?string $fileName,
        array $stats = [],
        ?string $errorMessage = null
    ): void {
        $kurumId = TenantContext::filterKurumId();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        (new UsbsSyncLog())->record(
            $direction,
            $status,
            $kurumId,
            $userId > 0 ? $userId : null,
            $fileName,
            $stats,
            $errorMessage
        );
    }

    /**
     * @param array<string, mixed> $item
     * @return true|false|string
     */
    private function importPatientItem(array $item)
    {
        $refs = UsbsBridgeHelper::extractPatientRefs($item);
        $hasValue = false;
        foreach ($refs as $v) {
            if ($v !== null && $v !== '') {
                $hasValue = true;
                break;
            }
        }
        if (!$hasValue) {
            return false;
        }

        $patient = $this->resolvePatient($item);
        if ($patient === null) {
            return 'Kayıt bulunamadı';
        }

        $bind = [];
        foreach ($refs as $k => $v) {
            if ($v !== null && $v !== '') {
                $bind[$k] = $v;
            }
        }
        if ($bind === []) {
            return false;
        }
        $patient->bind($bind, true);

        return $patient->store() ? true : 'Kaydedilemedi';
    }

    /**
     * @param array<string, mixed> $item
     * @return true|false|string
     */
    private function importVisitItem(array $item)
    {
        $refs = UsbsBridgeHelper::extractVisitRefs($item);
        $hasValue = false;
        foreach ($refs as $k => $v) {
            if ($k === 'usbs_bildirim_durum') {
                if ($v !== '') {
                    $hasValue = true;
                    break;
                }
                continue;
            }
            if ($v !== null && $v !== '') {
                $hasValue = true;
                break;
            }
        }
        if (!$hasValue) {
            return false;
        }

        $visit = $this->resolveVisit($item);
        if ($visit === null) {
            return 'Kayıt bulunamadı';
        }

        $bind = [];
        foreach ($refs as $k => $v) {
            if ($k === 'usbs_bildirim_durum') {
                if ($v !== '') {
                    $bind[$k] = $v;
                    if ($v === 'sent') {
                        $bind['usbs_bildirim_at'] = date('Y-m-d H:i:s');
                    }
                }
                continue;
            }
            if ($v !== null && $v !== '') {
                $bind[$k] = $v;
            }
        }
        if ($bind === []) {
            return false;
        }
        $visit->bind($bind, true);

        return $visit->store() ? true : 'Kaydedilemedi';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolvePatient(array $item): ?Patient
    {
        $model = new Patient();
        $eshId = (int) ($item['esh_id'] ?? 0);
        if ($eshId > 0 && $model->load($eshId)) {
            return $model;
        }
        $tc = ValidationHelper::tcDigitsOnly((string) ($item['tckimlik'] ?? ''));
        if (!ValidationHelper::isTcLength11($tc)) {
            return null;
        }
        $row = $model->findByTc($tc);
        if (!$row || empty($row->id)) {
            return null;
        }
        if (!$model->load((int) $row->id)) {
            return null;
        }

        return $model;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveVisit(array $item): ?Visit
    {
        $model = new Visit();
        $eshId = (int) ($item['esh_id'] ?? 0);
        if ($eshId > 0 && $model->load($eshId)) {
            return $model;
        }
        $usbsRef = UsbsComplianceHelper::normalizeRef((string) ($item['usbs_bildirim_ref'] ?? ''));
        if ($usbsRef !== null && $usbsRef !== '') {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT id FROM #__izlemler WHERE usbs_bildirim_ref = ? LIMIT 1',
                [$usbsRef]
            );
            if ($row && !empty($row->id) && $model->load((int) $row->id)) {
                return $model;
            }
        }

        return null;
    }

    private function apiClientForMode(string $mode): ?UsbsApiClientInterface
    {
        if ($mode === 'stub') {
            return new StubUsbsApiClient();
        }
        if ($mode === 'http') {
            $base = OperationalSettings::usbsBridgeApiBaseUrl();
            if ($base === '') {
                return null;
            }

            return new HttpUsbsApiClient($base);
        }

        return null;
    }
}
