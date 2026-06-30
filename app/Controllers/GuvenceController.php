<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Guvence;

/**
 * Sağlık güvencesi yönetimi (platform geneli — yalnızca süper yönetici).
 */
class GuvenceController {

    public function __construct() {
        AuthHelper::requireSuperAdmin();
    }
    
    /**
     * Güvence Listesi
     * Görünüm: views/admin/guvence/index.php
     */
    public function index() {
        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'guvenceadi'),
            'name',
            'ASC'
        );
        $pagelink = esh_url('Guvence', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Guvence',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'guvence/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Sağlık güvencesi listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows() {
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

        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'guvenceadi'),
            'name',
            'ASC'
        );
        $items = (new Guvence())->getList($sort['orderFragment']);

        ob_start();
        include ROOT_PATH . '/views/admin/guvence/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Yeni Güvence Ekleme Formu
     * Görünüm: views/admin/guvence/create.php
     */
    public function create() {
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'guvence/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Güvence Düzenleme Formu
     * Görünüm: views/admin/guvence/edit.php
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        $model = new Guvence();
        
        if ($id && $model->load($id)) {
            $item = $model;
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('admin', 'guvence/edit');
            include ThemeViewHelper::resolvePartial('footer');
        } else {
            $_SESSION['error'] = "Güvence kaydı bulunamadı!";
            header('Location: ' . esh_url('Guvence', 'index'));
            exit;
        }
    }

    /**
     * Kaydetme ve Güncelleme (Store)
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $model = new Guvence();
        
        // Eğer id boş gelmişse onu $_POST dizisinden tamamen sil
        if (isset($_POST['id']) && $_POST['id'] === '') {
            unset($_POST['id']);
        }
        
        // Eğer ID doluysa mevcut kaydı yükle (Update için)
        if (!empty($_POST['id'])) {
            $model->load($_POST['id']);
        }
        
        $model->bind($_POST);
        
        if ($model->store()) {
            $_SESSION['success'] = "Kayıt başarılı.";
        }
            
            header('Location: ' . esh_url('Guvence', 'index'));
            exit;
        }
    }

    /**
     * Güvence Silme
     */
    public function delete() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Guvence', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        $model = new Guvence();
        
        if ($id > 0 && $model->load($id)) {
            if ($model->delete()) {
                $_SESSION['success'] = "Güvence tanımı başarıyla silindi.";
            } else {
                $_SESSION['error'] = "Bu güvence silinemez! Hastalarla ilişkili olabilir.";
            }
        }
        
        header('Location: ' . esh_url('Guvence', 'index'));
        exit;
    }
}