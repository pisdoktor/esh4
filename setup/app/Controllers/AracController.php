<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Models\Arac;

/**
 * Araç tanımları (admin) — #__araclar
 */
class AracController {

    public function __construct() {
        \App\Helpers\AuthHelper::requireAdmin();
    }

    public function index() {
        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['id' => 'a.id', 'plaka' => 'a.plaka', 'arac_bilgisi' => 'a.arac_bilgisi'],
            'plaka',
            'ASC'
        );
        $pagelink = esh_url('Arac', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Arac',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'arac/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Araç listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['id' => 'a.id', 'plaka' => 'a.plaka', 'arac_bilgisi' => 'a.arac_bilgisi'],
            'plaka',
            'ASC'
        );
        $items = (new Arac())->getList($sort['orderFragment']);

        ob_start();
        include ROOT_PATH . '/views/admin/arac/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function create() {
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'arac/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $model = new Arac();

        if ($id > 0 && $model->load($id)) {
            $item = $model;
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('admin', 'arac/edit');
            include ThemeViewHelper::resolvePartial('footer');
            return;
        }

        $_SESSION['error'] = 'Düzenlenecek araç kaydı bulunamadı.';
        header('Location: ' . esh_url('Arac', 'index'));
        exit;
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Arac', 'index'));
            exit;
        }

        $model = new Arac();
        if (!empty($_POST['id'])) {
            $model->load((int) $_POST['id']);
        }

        $model->bind($_POST);

        if ($model->store()) {
            $_SESSION['success'] = 'Araç tanımı kaydedildi.';
        } else {
            $_SESSION['error'] = 'Kayıt sırasında bir hata oluştu.';
        }

        header('Location: ' . esh_url('Arac', 'index'));
        exit;
    }

    public function delete() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Arac', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        $model = new Arac();

        if ($id > 0 && $model->load($id)) {
            if ($model->delete()) {
                $_SESSION['success'] = 'Araç tanımı silindi.';
            } else {
                $_SESSION['error'] = 'Araç silinemedi.';
            }
        }

        header('Location: ' . esh_url('Arac', 'index'));
        exit;
    }
}
