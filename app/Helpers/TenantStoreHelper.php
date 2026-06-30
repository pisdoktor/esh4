<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\BaseModel;

/** Yeni kayıtlara oturum kurum_id ataması. */
final class TenantStoreHelper
{
    public static function applyKurumIdToModel(BaseModel $model, ?int $requestedKurumId = null): void
    {
        if (!property_exists($model, 'kurum_id')) {
            return;
        }
        $kid = TenantContext::assignKurumIdForStore($requestedKurumId);
        $model->set('kurum_id', $kid);
    }

    public static function assertModelKurum(BaseModel $model): void
    {
        if (!property_exists($model, 'kurum_id')) {
            return;
        }
        TenantContext::assertRecordKurum(isset($model->kurum_id) ? (int) $model->kurum_id : null);
    }
}
