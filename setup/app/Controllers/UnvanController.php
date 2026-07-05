<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Unvan;
use App\Services\UnvanRoleService;

/**
 * Personel ünvanları kataloğu (platform geneli — yalnızca platform sahibi).
 */
class UnvanController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
    }

    public function index(): void
    {
        $sort = \App\Helpers\QueryHelper::catalogSort(
            [
                'id' => 'id',
                'kod' => 'kod',
                'name' => 'ad',
                'sort' => 'sort_order',
            ],
            'sort',
            'ASC'
        );
        $pagelink = esh_url('Unvan', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Unvan',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'unvan/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function indexRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requirePlatformOwnerJson();

        $sort = \App\Helpers\QueryHelper::catalogSort(
            [
                'id' => 'id',
                'kod' => 'kod',
                'name' => 'ad',
                'sort' => 'sort_order',
            ],
            'sort',
            'ASC'
        );
        $items = (new Unvan())->getList($sort['orderFragment']);
        $kategoriLabels = Unvan::kategoriChoices();

        ob_start();
        include ROOT_PATH . '/views/admin/unvan/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function create(): void
    {
        $kategoriChoices = Unvan::kategoriChoices();
        $izinSablonuChoices = Unvan::izinSablonuChoices();

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'unvan/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $model = new Unvan();
        if ($id < 1 || !$model->load($id)) {
            $_SESSION['error'] = 'Ünvan kaydı bulunamadı.';
            header('Location: ' . esh_url('Unvan', 'index'));
            exit;
        }

        $item = $model;
        $kategoriChoices = Unvan::kategoriChoices();
        $izinSablonuChoices = Unvan::izinSablonuChoices();
        $userCount = $model->countUsersWithKod((string) $model->kod);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'unvan/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Unvan', 'index'));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validate()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Unvan', 'index'));
            exit;
        }

        $model = new Unvan();
        $isUpdate = !empty($_POST['id']);
        if ($isUpdate) {
            $model->load((int) $_POST['id']);
        }

        $kod = Unvan::normalizeKod($_POST['kod'] ?? '');
        $ad = trim((string) ($_POST['ad'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? 'diger'));
        $izinSablonu = trim((string) ($_POST['izin_sablonu'] ?? 'personel'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 100);
        $aktif = isset($_POST['aktif']) && (string) $_POST['aktif'] !== '0' ? 1 : 0;
        $mevzuatNotu = trim((string) ($_POST['mevzuat_notu'] ?? ''));

        $kategoriAllowed = array_keys(Unvan::kategoriChoices());
        $izinAllowed = array_keys(Unvan::izinSablonuChoices());
        if (!in_array($kategori, $kategoriAllowed, true)) {
            $kategori = 'diger';
        }
        if (!in_array($izinSablonu, $izinAllowed, true)) {
            $izinSablonu = 'personel';
        }

        if ($ad === '') {
            $_SESSION['error'] = 'Ünvan adı zorunludur.';
            header('Location: ' . esh_url('Unvan', $isUpdate ? 'edit' : 'create', $isUpdate ? ['id' => (int) $model->id] : []));
            exit;
        }

        if ((int) ($model->is_system ?? 0) === 1) {
            $kod = (string) $model->kod;
            $izinSablonu = (string) ($model->izin_sablonu ?? $izinSablonu);
        } elseif ($kod === null) {
            $_SESSION['error'] = 'Geçerli bir ünvan kodu girin (küçük harf, rakam, alt çizgi).';
            header('Location: ' . esh_url('Unvan', $isUpdate ? 'edit' : 'create', $isUpdate ? ['id' => (int) $model->id] : []));
            exit;
        } elseif (Unvan::kodExists($kod, $isUpdate ? (int) $model->id : null)) {
            $_SESSION['error'] = 'Bu ünvan kodu zaten kullanılıyor.';
            header('Location: ' . esh_url('Unvan', $isUpdate ? 'edit' : 'create', $isUpdate ? ['id' => (int) $model->id] : []));
            exit;
        }

        if (!$isUpdate) {
            $model = new Unvan();
            $model->is_system = 0;
        }

        $model->kod = $kod;
        $model->ad = $ad;
        $model->kategori = $kategori;
        if ((int) ($model->is_system ?? 0) !== 1) {
            $model->izin_sablonu = $izinSablonu;
        }
        $model->sort_order = $sortOrder;
        $model->aktif = $aktif;
        $model->mevzuat_notu = $mevzuatNotu !== '' ? $mevzuatNotu : null;

        if (!$model->store()) {
            $_SESSION['error'] = 'Ünvan kaydedilemedi.';
            header('Location: ' . esh_url('Unvan', $isUpdate ? 'edit' : 'create', $isUpdate ? ['id' => (int) $model->id] : []));
            exit;
        }

        Unvan::clearChoicesCache();
        UnvanRoleService::ensureRoleForUnvan($model);
        $_SESSION['success'] = $isUpdate ? 'Ünvan güncellendi.' : 'Ünvan ve bağlı rol oluşturuldu.';

        header('Location: ' . esh_url('Unvan', 'index'));
        exit;
    }

    public function delete(): void
    {
        CsrfHelper::requirePostMethod(esh_url('Unvan', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        $model = new Unvan();
        if ($id < 1 || !$model->load($id)) {
            $_SESSION['error'] = 'Ünvan kaydı bulunamadı.';
            header('Location: ' . esh_url('Unvan', 'index'));
            exit;
        }

        $kod = (string) $model->kod;
        $userCount = $model->countUsersWithKod($kod);

        if ((int) ($model->is_system ?? 0) === 1 || $userCount > 0) {
            if (UnvanRoleService::deactivateUnvan($model)) {
                Unvan::clearChoicesCache();
                $_SESSION['success'] = $userCount > 0
                    ? 'Ünvanda kayıtlı kullanıcı olduğu için kayıt pasifleştirildi.'
                    : 'Sistem ünvanı pasifleştirildi.';
            } else {
                $_SESSION['error'] = 'Ünvan pasifleştirilemedi.';
            }
            header('Location: ' . esh_url('Unvan', 'index'));
            exit;
        }

        UnvanRoleService::deleteRoleForUnvan($kod);
        if ($model->delete()) {
            Unvan::clearChoicesCache();
            $_SESSION['success'] = 'Ünvan ve bağlı rol silindi.';
        } else {
            $_SESSION['error'] = 'Ünvan silinemedi.';
        }

        header('Location: ' . esh_url('Unvan', 'index'));
        exit;
    }
}
