<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\IdHelper;
use App\Models\User;

/**
 * RBAC izin servisi — oturum senkronu, rota eşlemesi ve erişim kontrolü.
 */
final class PermissionService
{
    /** @var bool|null */
    private static $tablesReady = null;

    /** @var array<string, array<string, mixed>>|null */
    private static $crudMap = null;

    /** @var array<string, string>|null controller::action => permission slug */
    private static $routePermissionCache = null;

    /** @var array<string, array<string, mixed>>|null */
    private static $settings = null;

    public static function enabled(): bool
    {
        $settings = self::settings();
        if (empty($settings['enabled'])) {
            return false;
        }
        if (!empty($settings['tables_required']) && !self::tablesReady()) {
            return false;
        }

        return true;
    }

    public static function tablesReady(): bool
    {
        if (self::$tablesReady !== null) {
            return self::$tablesReady;
        }
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__roles');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$tbl]
            );
            self::$tablesReady = $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            self::$tablesReady = false;
        }

        return self::$tablesReady;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function crudMap(): array
    {
        if (self::$crudMap === null) {
            $path = ROOT_PATH . '/config/permission-crud-map.php';
            self::$crudMap = is_file($path) ? (require $path) : [];
        }

        return self::$crudMap;
    }

    public static function moduleLabel(string $moduleKey): string
    {
        if ($moduleKey === '') {
            return '';
        }
        $map = self::crudMap();
        $fromCrud = $map[$moduleKey]['label'] ?? null;
        if (is_string($fromCrud) && $fromCrud !== '') {
            return $fromCrud;
        }
        $registry = AppSettings::registry();
        $fromRegistry = $registry[$moduleKey]['label'] ?? null;
        if (is_string($fromRegistry) && $fromRegistry !== '') {
            return $fromRegistry;
        }

        return $moduleKey;
    }

    /**
     * @return array<string, mixed>
     */
    private static function settings(): array
    {
        if (self::$settings === null) {
            $path = ROOT_PATH . '/config/permission-settings.php';
            self::$settings = is_file($path) ? (require $path) : ['enabled' => false];
        }

        return self::$settings;
    }

    public static function moduleKeyFromSlug(string $permissionSlug): ?string
    {
        $parts = explode('.', $permissionSlug, 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            return null;
        }

        return $parts[0];
    }

    public static function adminBypassForPermission(string $permissionSlug): bool
    {
        $moduleKey = self::moduleKeyFromSlug($permissionSlug);
        if ($moduleKey === null) {
            return false;
        }
        $map = self::crudMap();
        $entry = $map[$moduleKey] ?? null;
        if (!is_array($entry)) {
            return false;
        }

        return !empty($entry['admin_bypass']);
    }

    public static function permissionSlugForRoute(string $controller, string $action): ?string
    {
        $controller = trim($controller);
        $action = trim($action);
        if ($controller === '' || $action === '') {
            return null;
        }

        $cacheKey = $controller . '::' . $action;
        if (self::$routePermissionCache !== null && array_key_exists($cacheKey, self::$routePermissionCache)) {
            $cached = self::$routePermissionCache[$cacheKey];

            return $cached === '' ? null : $cached;
        }

        $moduleKey = AppSettings::moduleForRoute($controller, $action);
        if ($moduleKey === null) {
            self::$routePermissionCache[$cacheKey] = '';

            return null;
        }

        $map = self::crudMap();
        $entry = $map[$moduleKey] ?? null;
        if (!is_array($entry) || empty($entry['rbac'])) {
            self::$routePermissionCache[$cacheKey] = '';

            return null;
        }

        $crudBuckets = $entry['crud'] ?? [];
        if (!is_array($crudBuckets)) {
            self::$routePermissionCache[$cacheKey] = '';

            return null;
        }

        foreach ($crudBuckets as $crud => $actions) {
            if (!is_string($crud) || $crud === '' || !is_array($actions)) {
                continue;
            }
            if (in_array($action, $actions, true)) {
                $slug = $moduleKey . '.' . $crud;
                self::$routePermissionCache[$cacheKey] = $slug;

                return $slug;
            }
        }

        $fallback = $moduleKey . '.read';
        self::$routePermissionCache[$cacheKey] = $fallback;

        return $fallback;
    }

    /**
     * @return list<string>
     */
    public static function permissionSlugsForRole(int $roleId): array
    {
        if (!self::tablesReady() || $roleId <= 0) {
            return [];
        }
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT p.slug FROM #__role_permissions rp'
            . ' INNER JOIN #__permissions p ON p.id = rp.permission_id'
            . ' WHERE rp.role_id = ? ORDER BY p.slug',
            [$roleId]
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $slug = (string) ($row->slug ?? '');
            if ($slug !== '') {
                $out[] = $slug;
            }
        }

        return $out;
    }

    public static function defaultRoleId(): int
    {
        if (!self::tablesReady()) {
            return 0;
        }
        $db = Database::getInstance();
        $id = $db->loadResultPrepared(
            'SELECT id FROM #__roles WHERE slug = ? LIMIT 1',
            ['personel']
        );

        return $id !== null && $id !== false ? (int) $id : 0;
    }

    public static function roleIdForUser(int|string $userId): int
    {
        if (!self::tablesReady() || IdHelper::isEmptyEntityId($userId)) {
            return 0;
        }
        $db = Database::getInstance();
        $id = $db->loadResultPrepared(
            'SELECT role_id FROM #__user_roles WHERE user_id = ? LIMIT 1',
            [$userId]
        );

        return $id !== null && $id !== false ? (int) $id : 0;
    }

    public static function hasUnvanLinkColumn(): bool
    {
        if (!self::tablesReady()) {
            return false;
        }
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__roles');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.COLUMNS'
                . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$tbl, 'unvan_code']
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ünvan koduna bağlı rol kimliği; eşleşme yoksa personel (varsayılan).
     */
    public static function roleIdForUnvanCode(?string $unvanCode): int
    {
        if (!self::tablesReady()) {
            return 0;
        }
        $code = is_string($unvanCode) ? trim($unvanCode) : '';
        $db = Database::getInstance();
        if ($code !== '' && self::hasUnvanLinkColumn()) {
            $id = $db->loadResultPrepared(
                'SELECT id FROM #__roles WHERE unvan_code = ? LIMIT 1',
                [$code]
            );
            if ($id !== null && $id !== false && (int) $id > 0) {
                return (int) $id;
            }
        }

        return self::defaultRoleId();
    }

    /**
     * @return array{id:int, slug:string, name:string}|null
     */
    public static function roleSummaryForUnvanCode(?string $unvanCode): ?array
    {
        $roleId = self::roleIdForUnvanCode($unvanCode);
        if ($roleId <= 0) {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT id, slug, name FROM #__roles WHERE id = ? LIMIT 1',
            [$roleId]
        );
        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) ($row->id ?? 0),
            'slug' => (string) ($row->slug ?? ''),
            'name' => (string) ($row->name ?? ''),
        ];
    }

    /**
     * Personel kullanıcıya ünvana göre rol atar (gerontolog/sekreter/diger → personel).
     */
    public static function syncUserRoleFromUnvan(int|string $userId, ?string $unvanCode): bool
    {
        if (!self::tablesReady() || IdHelper::isEmptyEntityId($userId)) {
            return false;
        }
        $roleId = self::roleIdForUnvanCode($unvanCode);

        return $roleId > 0 && self::assignRoleToUser($userId, $roleId);
    }

    /**
     * @return array<string, array{id:int, name:string, slug:string}>
     */
    public static function unvanRoleMapForUi(): array
    {
        if (!self::tablesReady() || !self::hasUnvanLinkColumn()) {
            return [];
        }
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT id, slug, name, unvan_code FROM #__roles'
            . ' WHERE unvan_code IS NOT NULL AND TRIM(unvan_code) <> \'\''
            . ' ORDER BY sort_order, name'
        );
        $out = [];
        if (!is_array($rows)) {
            return $out;
        }
        $defaultId = self::defaultRoleId();
        $defaultName = 'Personel';
        if ($defaultId > 0) {
            $name = $db->loadResultPrepared(
                'SELECT name FROM #__roles WHERE id = ? LIMIT 1',
                [$defaultId]
            );
            if (is_string($name) && $name !== '') {
                $defaultName = $name;
            }
        }
        $out[''] = ['id' => $defaultId, 'name' => $defaultName, 'slug' => 'personel'];
        foreach (User::unvanChoices() as $code => $label) {
            if ($code === '') {
                continue;
            }
            $summary = self::roleSummaryForUnvanCode($code);
            if ($summary !== null) {
                $out[$code] = $summary;
            } else {
                $out[$code] = ['id' => $defaultId, 'name' => $defaultName, 'slug' => 'personel'];
            }
        }

        return $out;
    }

    /**
     * Süper yönetici: ünvan eşlemesi olmayan veya manuel atanabilir roller.
     *
     * @return list<array{id:int, slug:string, name:string, unvan_code:?string}>
     */
    public static function manualAssignableRoles(): array
    {
        $roles = self::allRoles();
        $out = [];
        foreach ($roles as $role) {
            $uc = isset($role['unvan_code']) ? trim((string) $role['unvan_code']) : '';
            if ($uc === '') {
                $out[] = $role;
            }
        }

        return $out;
    }

    public static function assignRoleToUser(int|string $userId, int $roleId): bool
    {
        if (!self::tablesReady() || IdHelper::isEmptyEntityId($userId) || $roleId <= 0) {
            return false;
        }
        $db = Database::getInstance();
        $existing = self::roleIdForUser($userId);
        if ($existing === $roleId) {
            return true;
        }
        if ($existing > 0) {
            return (bool) $db->updatePrepared(
                '#__user_roles',
                ['role_id' => $roleId],
                'user_id = ?',
                [$userId]
            );
        }

        return $db->insertPrepared('#__user_roles', [
            'user_id' => $userId,
            'role_id' => $roleId,
        ]) !== false;
    }

    public static function syncSessionPermissions(int|string $userId, int $isadmin): void
    {
        if ($isadmin >= AuthHelper::ROLE_ADMIN || !self::tablesReady()) {
            $_SESSION['permissions'] = [];
            $_SESSION['role_id'] = null;
            $_SESSION['role_slug'] = null;

            return;
        }

        $roleId = self::roleIdForUser($userId);
        if ($roleId <= 0) {
            $roleId = self::defaultRoleId();
            if ($roleId > 0 && !IdHelper::isEmptyEntityId($userId)) {
                self::assignRoleToUser($userId, $roleId);
            }
        }

        $_SESSION['role_id'] = $roleId > 0 ? $roleId : null;
        $_SESSION['role_slug'] = null;
        if ($roleId > 0) {
            $db = Database::getInstance();
            $slug = $db->loadResultPrepared(
                'SELECT slug FROM #__roles WHERE id = ? LIMIT 1',
                [$roleId]
            );
            if (is_string($slug) && $slug !== '') {
                $_SESSION['role_slug'] = $slug;
            }
        }
        $_SESSION['permissions'] = $roleId > 0 ? self::permissionSlugsForRole($roleId) : [];
    }

    public static function invalidateUserSession(int|string $userId): void
    {
        if (IdHelper::isEmptyEntityId($userId) || !IdHelper::idsMatch($_SESSION['user_id'] ?? null, $userId)) {
            return;
        }
        $level = AuthHelper::sessionAdminLevel();
        self::syncSessionPermissions($userId, $level);
    }

    /** Personel «İzin/Mazeret» uçları — ünvan + modül; nobet.* RBAC gerekmez. */
    private static function isNobetStaffMineRoute(string $controller, string $action): bool
    {
        if ($controller !== 'Nobet') {
            return false;
        }

        static $actions = [
            'mine',
            'mineIstekRows',
            'mineIzinRows',
            'saveMineIstek',
            'saveMineIzin',
            'deleteMineIstek',
            'deleteMineIzin',
        ];

        return in_array($action, $actions, true);
    }

    public static function assertRouteAllowed(string $controller, string $action): void
    {
        if (!self::enabled() || !isset($_SESSION['user_id'])) {
            return;
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            return;
        }

        // Kurum yöneticisi personel RBAC kapısından muaf
        if (AuthHelper::sessionIsAdmin()) {
            return;
        }

        if (self::isNobetStaffMineRoute($controller, $action)) {
            if (User::canAccessNobetMine()) {
                return;
            }
            self::denyAccess();
        }

        $slug = self::permissionSlugForRoute($controller, $action);
        if ($slug === null) {
            return;
        }

        if (str_ends_with($slug, '.platform')) {
            if (!AuthHelper::sessionIsPlatformOwner()) {
                self::denyAccess();
            }

            return;
        }

        if (str_ends_with($slug, '.superadmin')) {
            if (!AuthHelper::sessionIsSuperAdmin()) {
                self::denyAccess();
            }

            return;
        }

        if (str_ends_with($slug, '.admin')) {
            if (!AuthHelper::sessionIsAdmin()) {
                self::denyAccess();
            }

            return;
        }

        if (!AuthHelper::can($slug)) {
            self::denyAccess();
        }
    }

    private static function denyAccess(): void
    {
        if (CsrfHelper::isJsonClientRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            http_response_code(403);
            echo json_encode(
                ['ok' => false, 'error' => 'Bu alana erişim yetkiniz bulunmamaktadır!'],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
        $_SESSION['error'] = 'Bu alana erişim yetkiniz bulunmamaktadır!';
        header('Location: ' . esh_url('Dashboard', 'index'));
        exit;
    }

    /**
     * @return list<array{id:int, slug:string, name:string, description:?string, is_system:int, sort_order:int, unvan_code:?string}>
     */
    public static function allRoles(): array
    {
        if (!self::tablesReady()) {
            return [];
        }
        $db = Database::getInstance();
        $hasUnvan = self::hasUnvanLinkColumn();
        $sql = $hasUnvan
            ? 'SELECT id, slug, unvan_code, name, description, is_system, sort_order FROM #__roles ORDER BY sort_order, name'
            : 'SELECT id, slug, name, description, is_system, sort_order FROM #__roles ORDER BY sort_order, name';
        $rows = $db->fetchObjectListPrepared($sql);
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row->id ?? 0),
                'slug' => (string) ($row->slug ?? ''),
                'name' => (string) ($row->name ?? ''),
                'description' => isset($row->description) ? (string) $row->description : null,
                'is_system' => (int) ($row->is_system ?? 0),
                'sort_order' => (int) ($row->sort_order ?? 0),
                'unvan_code' => $hasUnvan && isset($row->unvan_code) && $row->unvan_code !== null && trim((string) $row->unvan_code) !== ''
                    ? (string) $row->unvan_code
                    : null,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id:int, module_key:string, crud:string, slug:string, label:string}>
     */
    public static function allPermissions(): array
    {
        if (!self::tablesReady()) {
            return [];
        }
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT id, module_key, crud, slug, label FROM #__permissions ORDER BY module_key, crud'
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row->id ?? 0),
                'module_key' => (string) ($row->module_key ?? ''),
                'crud' => (string) ($row->crud ?? ''),
                'slug' => (string) ($row->slug ?? ''),
                'label' => (string) ($row->label ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, true>
     */
    public static function permissionIdsForRole(int $roleId): array
    {
        if (!self::tablesReady() || $roleId <= 0) {
            return [];
        }
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT permission_id FROM #__role_permissions WHERE role_id = ?',
            [$roleId]
        );
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $pid = (int) ($row->permission_id ?? 0);
                if ($pid > 0) {
                    $out[$pid] = true;
                }
            }
        }

        return $out;
    }

    /**
     * @param list<int> $permissionIds
     */
    public static function saveRolePermissions(int $roleId, array $permissionIds): bool
    {
        if (!self::tablesReady() || $roleId <= 0) {
            return false;
        }
        $db = Database::getInstance();
        $db->executePrepared('DELETE FROM #__role_permissions WHERE role_id = ?', [$roleId]);
        foreach ($permissionIds as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) {
                continue;
            }
            $db->insertPrepared('#__role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $pid,
            ]);
        }

        return true;
    }

    /**
     * @return array<string, list<array{crud:string, slug:string, label:string, id:int}>>
     */
    public static function permissionsGroupedByModule(): array
    {
        $grouped = [];
        foreach (self::allPermissions() as $perm) {
            $mk = $perm['module_key'];
            if (!isset($grouped[$mk])) {
                $grouped[$mk] = [];
            }
            $grouped[$mk][] = $perm;
        }

        return $grouped;
    }

    /**
     * @return list<string> Modül menü bağlantıları için okuma izni slug'ları
     */
    public static function navModuleReadPermissions(): array
    {
        return [
            'patient' => 'patient.read',
            'visit' => 'visit.read',
            'planned_visit' => 'planned_visit.read',
            'stats' => 'stats.read',
            'erapor' => 'erapor.read',
            'randevu' => 'randevu.read',
            'uhds' => 'uhds.read',
            'ilac_rehber' => 'ilac_rehber.read',
            'mesajlasma' => 'mesajlasma.read',
            'archive' => 'archive.read',
            'ekip' => 'ekip.read',
            'nobet' => 'nobet.read',
            'planning' => 'planning.read',
            'pansuman' => 'pansuman.read',
        ];
    }
}
