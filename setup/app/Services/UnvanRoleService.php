<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Role;
use App\Models\Unvan;

/**
 * Ünvan ↔ RBAC rol senkronizasyonu.
 */
class UnvanRoleService
{
    /**
     * Ünvan için rol oluşturur veya günceller; izin yoksa şablondan klonlar.
     */
    public static function ensureRoleForUnvan(Unvan $unvan): int
    {
        if (!Role::isTablesReady() || !Unvan::tableExists()) {
            return 0;
        }
        $kod = Unvan::normalizeKod((string) ($unvan->kod ?? ''));
        if ($kod === null) {
            return 0;
        }
        $ad = trim((string) ($unvan->ad ?? ''));
        if ($ad === '') {
            return 0;
        }
        $sortOrder = (int) ($unvan->sort_order ?? 100);
        $description = 'Ünvan rolü: ' . $ad;

        $role = new Role();
        $roleId = 0;
        if ($role->loadBySlug($kod)) {
            $roleId = (int) $role->id;
            $role->unvan_code = $kod;
            $role->name = $ad;
            $role->description = $description;
            $role->is_system = 1;
            $role->sort_order = $sortOrder;
            $role->store();
        } else {
            $db = Database::getInstance();
            $existingByUnvan = $db->loadResultPrepared(
                'SELECT id FROM #__roles WHERE unvan_code = ? LIMIT 1',
                [$kod]
            );
            if ($existingByUnvan !== null && $existingByUnvan !== false && (int) $existingByUnvan > 0) {
                $roleId = (int) $existingByUnvan;
                $role->load($roleId);
                $role->slug = $kod;
                $role->unvan_code = $kod;
                $role->name = $ad;
                $role->description = $description;
                $role->is_system = 1;
                $role->sort_order = $sortOrder;
                $role->store();
            } else {
                $role->slug = $kod;
                $role->unvan_code = $kod;
                $role->name = $ad;
                $role->description = $description;
                $role->is_system = 1;
                $role->sort_order = $sortOrder;
                if ($role->store()) {
                    $roleId = (int) $role->id;
                }
            }
        }

        if ($roleId > 0) {
            self::clonePermissionsIfEmpty($roleId, self::resolveTemplateSlug($unvan));
        }

        return $roleId;
    }

    public static function syncRoleOnUnvanSave(Unvan $unvan): bool
    {
        return self::ensureRoleForUnvan($unvan) > 0;
    }

    /**
     * Özel ünvan silinirken bağlı rolü kaldırır (sistem rolleri korunur).
     */
    public static function deleteRoleForUnvan(string $kod): bool
    {
        $kod = Unvan::normalizeKod($kod);
        if ($kod === null || !Role::isTablesReady()) {
            return false;
        }
        $db = Database::getInstance();
        $roleId = (int) $db->loadResultPrepared(
            'SELECT id FROM #__roles WHERE unvan_code = ? LIMIT 1',
            [$kod]
        );
        if ($roleId <= 0) {
            return true;
        }
        $inUse = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__user_roles WHERE role_id = ?',
            [$roleId]
        );
        if ($inUse > 0) {
            return false;
        }
        $db->executePrepared('DELETE FROM #__role_permissions WHERE role_id = ?', [$roleId]);

        return (bool) $db->executePrepared('DELETE FROM #__roles WHERE id = ?', [$roleId]);
    }

    public static function deactivateUnvan(Unvan $unvan): bool
    {
        $unvan->aktif = 0;

        return $unvan->store();
    }

    private static function resolveTemplateSlug(Unvan $unvan): string
    {
        $tpl = trim((string) ($unvan->izin_sablonu ?? 'personel'));
        $allowed = array_keys(Unvan::izinSablonuChoices());
        if (!in_array($tpl, $allowed, true)) {
            return 'personel';
        }

        return $tpl;
    }

    private static function clonePermissionsIfEmpty(int $destRoleId, string $templateSlug): void
    {
        if ($destRoleId <= 0 || !Role::isTablesReady()) {
            return;
        }
        $db = Database::getInstance();
        $hasPerms = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__role_permissions WHERE role_id = ?',
            [$destRoleId]
        );
        if ($hasPerms > 0) {
            return;
        }
        $tplRoleId = (int) $db->loadResultPrepared(
            'SELECT id FROM #__roles WHERE slug = ? LIMIT 1',
            [$templateSlug]
        );
        if ($tplRoleId <= 0) {
            $tplRoleId = PermissionService::defaultRoleId();
        }
        if ($tplRoleId <= 0) {
            return;
        }
        $db->executePrepared(
            'INSERT IGNORE INTO #__role_permissions (role_id, permission_id)
             SELECT ?, rp.permission_id FROM #__role_permissions rp WHERE rp.role_id = ?',
            [$destRoleId, $tplRoleId]
        );
    }
}
