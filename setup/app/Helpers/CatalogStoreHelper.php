<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\BaseModel;

/** Platform geneli tanım kataloğu (kurum_id = 0) yazma kuralları. */
final class CatalogStoreHelper
{
    public const PLATFORM_KURUM_ID = 0;

    public static function applyPlatformKurumId(BaseModel $model): void
    {
        if (!property_exists($model, 'kurum_id')) {
            return;
        }
        $model->set('kurum_id', self::PLATFORM_KURUM_ID);
    }

    public static function assertPlatformCatalogRecord(BaseModel $model): void
    {
        if (!property_exists($model, 'kurum_id')) {
            return;
        }
        $kid = (int) ($model->kurum_id ?? -1);
        if ($kid !== self::PLATFORM_KURUM_ID) {
            $_SESSION['error'] = 'Bu kayıt platform kataloğunda değil.';
            if (!headers_sent()) {
                header('Location: ' . esh_url('Dashboard', 'index'));
            }
            exit;
        }
        if (!AuthHelper::sessionIsPlatformOwner()) {
            $_SESSION['error'] = 'Platform kataloğunu yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8')
                . ' düzenleyebilir.';
            if (!headers_sent()) {
                header('Location: ' . esh_url('Dashboard', 'index'));
            }
            exit;
        }
    }

    /** Kurum seçim ekranı: platform sahibi (kurum filtresi yok) dışındaki yöneticiler ve PS kurum filtresinde. */
    public static function isCatalogPickerMode(): bool
    {
        if (!AuthHelper::sessionIsPlatformOwner()) {
            return true;
        }

        return TenantContext::sessionKurumFilter() !== null;
    }

    /** Seçim kaydı için hedef kurum id. */
    public static function pickerKurumId(): int
    {
        return TenantContext::requireKurumScope();
    }
}
