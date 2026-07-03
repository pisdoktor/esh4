<?php
declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Helpers\PatientAccessHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;
use App\Models\Visit;

final class VisitResource
{
    /**
     * @return array{items: list<object>, total: int}
     */
    public function list(int $limit, int $offset, string $search = '', string $dateFrom = '', string $dateTo = ''): array
    {
        $model = new Visit();
        $items = $model->getAllVisits($limit, $offset, $search, '', 'izlemtarihi DESC', $dateFrom, $dateTo, 0);
        $total = (int) $model->countAllVisits($search, '', $dateFrom, $dateTo, 0);

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
        ];
    }

    public function show(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        $visit = new Visit();
        if (!$visit->load($id)) {
            return null;
        }
        $tc = ValidationHelper::tcDigitsOnly((string) ($visit->hastatckimlik ?? ''));
        if ($tc === '' || !ValidationHelper::isTcLength11($tc)) {
            return null;
        }
        $patient = (new Patient())->findByTc($tc);
        if (!$patient || !PatientAccessHelper::canAccessPatient((int) $patient->id, $patient)) {
            return null;
        }

        return $visit;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'kurum_id' => (int) ($row->kurum_id ?? 0),
            'hastatckimlik' => (string) ($row->hastatckimlik ?? ''),
            'izlemtarihi' => (string) ($row->izlemtarihi ?? ''),
            'yapilan' => (string) ($row->yapilan ?? ''),
            'yapildimi' => (int) ($row->yapildimi ?? 0),
            'zaman' => (string) ($row->zaman ?? ''),
            'aciklama' => (string) ($row->aciklama ?? ''),
            'esys_izlem_ref' => (string) ($row->esys_izlem_ref ?? ''),
            'esys_konsultasyon_ref' => (string) ($row->esys_konsultasyon_ref ?? ''),
            'checkin_lat' => isset($row->checkin_lat) ? (float) $row->checkin_lat : null,
            'checkin_lon' => isset($row->checkin_lon) ? (float) $row->checkin_lon : null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, data?: array<string, mixed>, error?: string, status?: int}
     */
    public function applyCheckin(int $visitId, array $payload): array
    {
        $visit = $this->show($visitId);
        if ($visit === null) {
            return ['ok' => false, 'error' => 'İzlem bulunamadı.', 'status' => 404];
        }
        $lat = $payload['checkin_lat'] ?? $payload['lat'] ?? null;
        $lon = $payload['checkin_lon'] ?? $payload['lon'] ?? null;
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return ['ok' => false, 'error' => 'checkin_lat ve checkin_lon gerekli.', 'status' => 422];
        }
        $model = new Visit();
        $model->load($visitId);
        $model->checkin_lat = round((float) $lat, 7);
        $model->checkin_lon = round((float) $lon, 7);
        $model->checkin_at = date('Y-m-d H:i:s');
        if (isset($payload['checkin_accuracy']) && is_numeric($payload['checkin_accuracy'])) {
            $model->checkin_accuracy = round((float) $payload['checkin_accuracy'], 1);
        }
        if (!$model->store()) {
            return ['ok' => false, 'error' => 'Kayıt başarısız.', 'status' => 500];
        }

        return ['ok' => true, 'data' => $this->serialize($model)];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, data?: array<string, mixed>, error?: string, status?: int}
     */
    public function patch(int $id, array $payload): array
    {
        $visit = $this->show($id);
        if ($visit === null) {
            return ['ok' => false, 'error' => 'İzlem bulunamadı.', 'status' => 404];
        }
        $model = new Visit();
        $model->load($id);
        $allowed = ['yapildimi', 'aciklama'];
        $changed = false;
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            if ($key === 'yapildimi') {
                $model->yapildimi = (int) $payload[$key] === 1 ? 1 : 0;
                $changed = true;
            } elseif ($key === 'aciklama') {
                $model->aciklama = substr(trim((string) $payload[$key]), 0, 4000);
                $changed = true;
            }
        }
        if (!$changed) {
            return ['ok' => false, 'error' => 'Güncellenecek alan yok.', 'status' => 422];
        }
        if (!$model->store()) {
            return ['ok' => false, 'error' => 'Kayıt başarısız.', 'status' => 500];
        }

        return ['ok' => true, 'data' => $this->serialize($model)];
    }
}
