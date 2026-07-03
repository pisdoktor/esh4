<?php
declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Helpers\PatientAccessHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;
use App\Models\PlannedVisit;

final class PlanResource
{
    /**
     * @return array{items: list<object>, total: int}
     */
    public function list(int $limit, int $offset, string $search = '', int $durum = -1, string $dateFrom = '', string $dateTo = ''): array
    {
        $model = new PlannedVisit();
        $durumFilter = $durum < 0 ? '' : (string) $durum;
        $items = $model->getAllPlanned($limit, $offset, $search, $durumFilter, 'planlanantarih ASC', $dateFrom, $dateTo, 0);
        $total = (int) $model->countAllPlanned($search, $durumFilter, $dateFrom, $dateTo, 0);

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
        $plan = new PlannedVisit();
        if (!$plan->load($id)) {
            return null;
        }
        $tc = ValidationHelper::tcDigitsOnly((string) ($plan->hastatckimlik ?? ''));
        if ($tc === '' || !ValidationHelper::isTcLength11($tc)) {
            return null;
        }
        $patient = (new Patient())->findByTc($tc);
        if (!$patient || !PatientAccessHelper::canAccessPatient((int) $patient->id, $patient)) {
            return null;
        }

        return $plan;
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
            'planlanantarih' => (string) ($row->planlanantarih ?? ''),
            'yapilacak' => (string) ($row->yapilacak ?? ''),
            'zaman' => (int) ($row->zaman ?? 0),
            'durum' => (int) ($row->durum ?? 0),
            'oncelik' => (int) ($row->oncelik ?? 1),
            'esys_plan_ref' => (string) ($row->esys_plan_ref ?? ''),
        ];
    }
}
