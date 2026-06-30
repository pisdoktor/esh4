<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Kurum;

/**
 * Kurum yönetimi — yalnızca süper yönetici.
 */
class KurumController
{
    public function __construct()
    {
        AuthHelper::requireSuperAdmin();
    }

    public function index(): void
    {
        $sort = self::kurumListSort();
        $pagelink = esh_url('Kurum', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Kurum',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'kurum/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Kurum listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Yetkisiz'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sort = self::kurumListSort();
        $items = (new Kurum())->getList(false, $sort['orderFragment']);

        ob_start();
        include ROOT_PATH . '/views/admin/kurum/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @return array{orderby: string, orderdir: string, orderFragment: string} */
    private static function kurumListSort(): array
    {
        return \App\Helpers\QueryHelper::catalogSort(
            [
                'id' => 'id',
                'name' => 'ad',
                'kod' => 'kod',
                'telefon' => 'telefon',
                'aktif' => 'aktif',
            ],
            'name',
            'ASC'
        );
    }

    public function create(): void
    {
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'kurum/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id < 1) {
            header('Location: ' . esh_url('Kurum', 'index'));
            exit;
        }
        $kurum = new Kurum();
        if (!$kurum->load($id)) {
            $_SESSION['error'] = 'Kurum bulunamadı.';
            header('Location: ' . esh_url('Kurum', 'index'));
            exit;
        }
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'kurum/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function store(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Kurum', 'index'));
            exit;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
        $model = new Kurum();
        if ($id > 0 && !$model->load($id)) {
            $_SESSION['error'] = 'Kurum bulunamadı.';
            header('Location: ' . esh_url('Kurum', 'index'));
            exit;
        }

        $ad = trim((string) ($_POST['ad'] ?? ''));
        $kod = Kurum::normalizeKod((string) ($_POST['kod'] ?? ''));
        if ($ad === '') {
            $_SESSION['error'] = 'Kurum adı zorunludur.';
            header('Location: ' . esh_url($id > 0 ? 'Kurum' : 'Kurum', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }
        if (!$model->kodUnique($kod, $id > 0 ? $id : null)) {
            $_SESSION['error'] = 'Bu kurum kodu zaten kullanılıyor.';
            header('Location: ' . esh_url($id > 0 ? 'Kurum' : 'Kurum', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        $ayarlar = array_merge($id > 0 ? $model->ayarlarArray() : [], [
            'esh_app_name' => trim((string) ($_POST['esh_app_name'] ?? '')),
            'ek3_form_baslik' => trim((string) ($_POST['ek3_form_baslik'] ?? '')),
            'hekim_degerlendirme_form_baslik' => trim((string) ($_POST['hekim_degerlendirme_form_baslik'] ?? '')),
        ]);

        $_POST['ad'] = $ad;
        $_POST['kod'] = $kod;
        $_POST['aktif'] = isset($_POST['aktif']) ? 1 : 0;
        $_POST['telefon'] = trim((string) ($_POST['telefon'] ?? ''));
        $_POST['adres'] = trim((string) ($_POST['adres'] ?? ''));
        $model->bind($_POST);
        $model->setAyarlarArray($ayarlar);

        if ($model->store()) {
            $_SESSION['success'] = 'Kurum kaydedildi.';
        } else {
            $_SESSION['error'] = 'Kayıt sırasında hata oluştu.';
        }

        header('Location: ' . esh_url('Kurum', 'index'));
        exit;
    }

    public function delete(): void
    {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Kurum', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 1) {
            $_SESSION['error'] = 'Varsayılan kurum silinemez.';
            header('Location: ' . esh_url('Kurum', 'index'));
            exit;
        }
        $model = new Kurum();
        if ($model->load($id) && $model->delete($id)) {
            $_SESSION['success'] = 'Kurum silindi.';
        } else {
            $_SESSION['error'] = 'Kurum silinemedi.';
        }
        header('Location: ' . esh_url('Kurum', 'index'));
        exit;
    }

    /** Süper yönetici liste filtresi (oturum). */
    public function setFilter(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        $kid = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
        \App\Helpers\TenantContext::setSessionKurumFilter($kid > 0 ? $kid : null);
        $redirect = trim((string) ($_POST['redirect'] ?? ''));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            header('Location: ' . $redirect);
        } else {
            header('Location: ' . esh_url('Dashboard', 'index'));
        }
        exit;
    }
}
