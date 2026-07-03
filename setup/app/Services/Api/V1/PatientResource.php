<?php
declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Helpers\PatientAccessHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;

final class PatientResource
{
    /**
     * @return array{items: list<object>, total: int}
     */
    public function list(int $limit, int $offset, string $status = 'active', string $search = ''): array
    {
        $model = new Patient();
        $items = $model->getUnified($limit, $offset, 'h.isim ASC', $status, $search);
        $total = (int) $model->countUnified($status, $search);

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
        $patient = (new Patient())->getById($id);
        if (!$patient || (int) ($patient->id ?? 0) !== $id) {
            return null;
        }
        if (!PatientAccessHelper::canAccessPatient($id, $patient)) {
            return null;
        }

        return $patient;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'kurum_id' => (int) ($row->kurum_id ?? 0),
            'tckimlik' => (string) ($row->tckimlik ?? ''),
            'isim' => (string) ($row->isim ?? ''),
            'soyisim' => (string) ($row->soyisim ?? ''),
            'dogumtarihi' => (string) ($row->dogumtarihi ?? ''),
            'cinsiyet' => (string) ($row->cinsiyet ?? ''),
            'pasif' => (string) ($row->pasif ?? '0'),
            'ilce' => (string) ($row->ilce ?? ''),
            'mahalle' => (string) ($row->mahalle ?? ''),
            'kayittarihi' => (string) ($row->kayittarihi ?? ''),
            'ceptel1' => ValidationHelper::formatPhoneDisplay((string) ($row->ceptel1 ?? '')),
            'esys_hasta_ref' => (string) ($row->esys_hasta_ref ?? ''),
            'esys_basvuru_ref' => (string) ($row->esys_basvuru_ref ?? ''),
        ];
    }
}
