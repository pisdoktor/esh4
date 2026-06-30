<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

/**
 * Platform rol ve izin yönetimi — yalnızca süper yönetici.
 */
class RoleController
{
    public function __construct()
    {
        AuthHelper::requireSuperAdmin();
    }

    public function index(): void
    {
        $pageTitle = 'Rol ve izin yönetimi';
        $indexRowsFetchUrl = esh_url('Role', 'indexRows');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'role/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Rol listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $roles = PermissionService::allRoles();

        ob_start();
        include ROOT_PATH . '/views/admin/role/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function create(): void
    {
        $role = null;
        $permissionGroups = PermissionService::permissionsGroupedByModule();
        $selectedPermissionIds = [];
        $pageTitle = 'Yeni rol';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'role/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $model = new Role();
        if ($id < 1 || !$model->load($id)) {
            $_SESSION['error'] = 'Rol bulunamadı.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        $role = $model;
        $permissionGroups = PermissionService::permissionsGroupedByModule();
        $selectedPermissionIds = PermissionService::permissionIdsForRole($id);
        $pageTitle = 'Rol düzenle: ' . (string) ($role->name ?? '');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'role/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validate()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $slug = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
        $name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
        $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 100;
        $unvanCodeRaw = isset($_POST['unvan_code']) ? trim((string) $_POST['unvan_code']) : '';
        $unvanCode = $unvanCodeRaw === '' ? null : User::normalizeUnvan($unvanCodeRaw);
        if ($unvanCodeRaw !== '' && $unvanCode === null) {
            $_SESSION['error'] = 'Geçersiz ünvan kodu.';
            header('Location: ' . esh_url('Role', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        if ($name === '') {
            $_SESSION['error'] = 'Rol adı zorunludur.';
            header('Location: ' . esh_url('Role', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        if (!preg_match('/^[a-z0-9_]+$/', $slug)) {
            $_SESSION['error'] = 'Slug yalnızca küçük harf, rakam ve alt çizgi içerebilir.';
            header('Location: ' . esh_url('Role', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        $model = new Role();
        if ($id > 0) {
            if (!$model->load($id)) {
                $_SESSION['error'] = 'Rol bulunamadı.';
                header('Location: ' . esh_url('Role', 'index'));
                exit;
            }
            if ((int) ($model->is_system ?? 0) === 1) {
                $slug = (string) $model->slug;
            }
        } else {
            $existing = new Role();
            if ($existing->loadBySlug($slug)) {
                $_SESSION['error'] = 'Bu slug zaten kullanılıyor.';
                header('Location: ' . esh_url('Role', 'create'));
                exit;
            }
        }

        if ($unvanCode !== null && PermissionService::hasUnvanLinkColumn()) {
            $db = $model->db;
            $dupId = (int) $db->loadResultPrepared(
                'SELECT id FROM #__roles WHERE unvan_code = ? AND id <> ? LIMIT 1',
                [$unvanCode, $id > 0 ? $id : 0]
            );
            if ($dupId > 0) {
                $_SESSION['error'] = 'Bu ünvan kodu başka bir role zaten bağlı.';
                header('Location: ' . esh_url('Role', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
                exit;
            }
        }

        $model->set('slug', $slug);
        $model->set('name', $name);
        if (PermissionService::hasUnvanLinkColumn()) {
            $model->set('unvan_code', $unvanCode);
        }
        $model->set('description', $description !== '' ? $description : null);
        if ($id <= 0) {
            $model->set('is_system', 0);
        }
        $model->set('sort_order', $sortOrder);

        if (!$model->store()) {
            $_SESSION['error'] = 'Rol kaydedilemedi.';
            header('Location: ' . esh_url('Role', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        $savedId = (int) $model->id;
        $permissionIds = [];
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            foreach ($_POST['permissions'] as $pid) {
                $pid = (int) $pid;
                if ($pid > 0) {
                    $permissionIds[] = $pid;
                }
            }
        }
        PermissionService::saveRolePermissions($savedId, $permissionIds);

        $_SESSION['success'] = 'Rol kaydedildi.';
        header('Location: ' . esh_url('Role', 'edit', ['id' => $savedId]));
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validate()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $model = new Role();
        if ($id < 1 || !$model->load($id)) {
            $_SESSION['error'] = 'Rol bulunamadı.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        if ((int) ($model->is_system ?? 0) === 1) {
            $_SESSION['error'] = 'Sistem rolleri silinemez.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        $db = $model->db;
        $userCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__user_roles WHERE role_id = ?',
            [$id]
        );
        if ($userCount > 0) {
            $_SESSION['error'] = 'Bu role atanmış kullanıcılar var; silinemez.';
            header('Location: ' . esh_url('Role', 'index'));
            exit;
        }

        $db->executePrepared('DELETE FROM #__role_permissions WHERE role_id = ?', [$id]);
        $db->executePrepared('DELETE FROM #__roles WHERE id = ?', [$id]);

        $_SESSION['success'] = 'Rol silindi.';
        header('Location: ' . esh_url('Role', 'index'));
        exit;
    }

    private function slugify(string $text): string
    {
        $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'];
        $text = strtr($text, $map);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text) ?? '';
        $text = trim($text, '_');

        return $text !== '' ? $text : 'rol';
    }
}
