<?php
declare(strict_types=1);

namespace App\Services\Esys;

use App\Core\Database;
use App\Helpers\EsysBridgeHelper;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;
use App\Helpers\ValidationHelper;
use App\Models\EsysSyncLog;
use App\Models\Patient;
use App\Models\PlannedVisit;
use App\Models\Visit;

/**
 * ESYS / AHBS dosya köprüsü — JSON paket dışa/içe aktarma ve referans eşleme.
 */
final class EsysBridgeService
{
    public function pushCurrentBundle(): array
    {
        $mode = \App\Helpers\OperationalSettings::esysBridgeApiMode();
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

    public function retryFailedSyncs(int $limit = 10): int
    {
        $limit = max(1, min(100, $limit));
        $rows = (new EsysSyncLog())->recent($limit, TenantContext::filterKurumId());
        $count = 0;
        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row->status ?? '')));
            if ($status !== 'failed' && $status !== 'error') {
                continue;
            }
            $res = $this->pushCurrentBundle();
            if (!empty($res['ok'])) {
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
        $patientLimit = \App\Helpers\OperationalSettings::esysBridgeExportPatientLimit();
        $visitDays = \App\Helpers\OperationalSettings::esysBridgeExportVisitDays();
        $onlyMissing = \App\Helpers\OperationalSettings::esysBridgeExportOnlyMissingRefs();

        $db = Database::getInstance();
        $patientSql = 'SELECT * FROM #__hastalar WHERE pasif = ?'
            . TenantSqlHelper::andBare('kurum_id');
        $patientParams = ['0'];
        if ($onlyMissing) {
            $patientSql .= ' AND (COALESCE(esys_hasta_ref, \'\') = \'\' OR COALESCE(esys_basvuru_ref, \'\') = \'\')';
        }
        $patientSql .= ' ORDER BY id ASC LIMIT ' . (int) $patientLimit;
        $patientRows = $db->fetchObjectListPrepared($patientSql, $patientParams);
        if (!is_array($patientRows)) {
            $patientRows = [];
        }

        $patientsOut = [];
        foreach ($patientRows as $row) {
            $patientsOut[] = [
                'esh_id' => (int) ($row->id ?? 0),
                'tckimlik' => (string) ($row->tckimlik ?? ''),
                'refs' => [
                    'esys_hasta_ref' => EsysBridgeHelper::normalizeRef((string) ($row->esys_hasta_ref ?? '')),
                    'esys_basvuru_ref' => EsysBridgeHelper::normalizeRef((string) ($row->esys_basvuru_ref ?? '')),
                ],
                'esys_payload' => EsysBridgeHelper::mapRowToEsysPayload($row, 'patient'),
            ];
        }

        $visitSince = date('Y-m-d', strtotime('-' . $visitDays . ' days'));
        $visitSql = 'SELECT * FROM #__izlemler WHERE izlemtarihi >= ?'
            . TenantSqlHelper::andBare('kurum_id')
            . ' ORDER BY izlemtarihi DESC, id DESC LIMIT 2000';
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
                    'esys_izlem_ref' => EsysBridgeHelper::normalizeRef((string) ($row->esys_izlem_ref ?? '')),
                    'esys_konsultasyon_ref' => EsysBridgeHelper::normalizeRef((string) ($row->esys_konsultasyon_ref ?? '')),
                ],
                'esys_payload' => EsysBridgeHelper::mapRowToEsysPayload($row, 'visit'),
            ];
        }

        $planSql = 'SELECT * FROM #__pizlemler WHERE COALESCE(durum, 0) = 0 AND planlanantarih >= CURDATE()'
            . TenantSqlHelper::andBare('kurum_id')
            . ' ORDER BY planlanantarih ASC LIMIT 1000';
        $planRows = $db->fetchObjectListPrepared($planSql, []);
        if (!is_array($planRows)) {
            $planRows = [];
        }

        $plansOut = [];
        foreach ($planRows as $row) {
            $plansOut[] = [
                'esh_id' => (int) ($row->id ?? 0),
                'tckimlik' => (string) ($row->hastatckimlik ?? ''),
                'refs' => [
                    'esys_plan_ref' => EsysBridgeHelper::normalizeRef((string) ($row->esys_plan_ref ?? '')),
                ],
                'esys_payload' => EsysBridgeHelper::mapRowToEsysPayload($row, 'planned_visit'),
            ];
        }

        return [
            'bundle_version' => EsysBridgeHelper::BUNDLE_VERSION,
            'direction' => 'esh_to_esys',
            'system' => 'ESYS',
            'compatible_with' => ['ESYS', 'AHBS'],
            'generated_at' => gmdate('c'),
            'kurum_id' => $kurumId,
            'meta' => [
                'patient_count' => count($patientsOut),
                'visit_count' => count($visitsOut),
                'plan_count' => count($plansOut),
                'visit_since' => $visitSince,
                'only_missing_refs' => $onlyMissing,
            ],
            'patients' => $patientsOut,
            'visits' => $visitsOut,
            'plans' => $plansOut,
        ];
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array{ok:bool,stats:array<string,int>,errors:list<string>,direction?:string}
     */
    public function importRefs(array $bundle): array
    {
        $valid = EsysBridgeHelper::validateImportBundle($bundle);
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
            'plans_updated' => 0,
            'patients_skipped' => 0,
            'visits_skipped' => 0,
            'plans_skipped' => 0,
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

        $plans = is_array($bundle['plans'] ?? null) ? $bundle['plans'] : [];
        foreach ($plans as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $result = $this->importPlanItem($item);
            if ($result === true) {
                $stats['plans_updated']++;
            } elseif ($result === false) {
                $stats['plans_skipped']++;
            } else {
                $errors[] = 'Plan satır ' . ($idx + 1) . ': ' . $result;
            }
        }

        $updatedTotal = $stats['patients_updated'] + $stats['visits_updated'] + $stats['plans_updated'];
        $ok = $updatedTotal > 0 || ($stats['patients_skipped'] + $stats['visits_skipped'] + $stats['plans_skipped']) > 0;

        return [
            'ok' => $ok,
            'stats' => $stats,
            'errors' => $errors,
            'direction' => (string) ($valid['direction'] ?? 'esys_to_esh'),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return true|false|string true=updated, false=skipped no match, string=error
     */
    private function importPatientItem(array $item)
    {
        $refs = EsysBridgeHelper::extractPatientRefs($item);
        if ($refs === []) {
            return false;
        }
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
        $refs = EsysBridgeHelper::extractVisitRefs($item);
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

        $visit = $this->resolveVisit($item);
        if ($visit === null) {
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
        $visit->bind($bind, true);

        return $visit->store() ? true : 'Kaydedilemedi';
    }

    /**
     * @param array<string, mixed> $item
     * @return true|false|string
     */
    private function importPlanItem(array $item)
    {
        $ref = EsysBridgeHelper::extractPlanRef($item);
        if ($ref === null || $ref === '') {
            return false;
        }

        $plan = $this->resolvePlan($item);
        if ($plan === null) {
            return 'Kayıt bulunamadı';
        }

        $plan->bind(['esys_plan_ref' => $ref], true);

        return $plan->store() ? true : 'Kaydedilemedi';
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
        $tc = ValidationHelper::tcDigitsOnly((string) ($item['tckimlik'] ?? ''));
        $esysRef = EsysBridgeHelper::normalizeRef((string) ($item['esys_izlem_ref'] ?? ($item['refs']['esys_izlem_ref'] ?? '')));
        if ($esysRef !== null && $esysRef !== '') {
            $db = Database::getInstance();
            $sql = 'SELECT id FROM #__izlemler WHERE esys_izlem_ref = ?'
                . TenantSqlHelper::andBare('kurum_id')
                . ' LIMIT 1';
            $id = (int) $db->loadResultPrepared($sql, [$esysRef]);
            if ($id > 0 && $model->load($id)) {
                return $model;
            }
        }
        if (!ValidationHelper::isTcLength11($tc)) {
            return null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolvePlan(array $item): ?PlannedVisit
    {
        $model = new PlannedVisit();
        $eshId = (int) ($item['esh_id'] ?? 0);
        if ($eshId > 0 && $model->load($eshId)) {
            return $model;
        }

        return null;
    }

    /**
     * @param array<string, int> $stats
     */
    public function logSync(
        string $direction,
        string $status,
        ?string $fileName,
        array $stats,
        ?string $errorMessage = null
    ): void {
        $kurumId = TenantContext::filterKurumId();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        (new EsysSyncLog())->record(
            $direction,
            $status,
            $kurumId,
            $userId,
            $fileName,
            $stats,
            $errorMessage
        );
    }

    private function apiClientForMode(string $mode): ?EsysApiClientInterface
    {
        if ($mode === 'stub') {
            return new StubEsysApiClient();
        }
        if ($mode === 'http') {
            $base = \App\Helpers\OperationalSettings::esysBridgeApiBaseUrl();
            if ($base === '') {
                return null;
            }

            return new HttpEsysApiClient($base);
        }

        return null;
    }
}
