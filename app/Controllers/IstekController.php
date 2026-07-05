<?php

namespace App\Controllers;



use App\Helpers\AuthHelper;

use App\Helpers\CatalogPickerHelper;

use App\Helpers\CatalogStoreHelper;

use App\Helpers\ThemeViewHelper;

use App\Models\Istek;

use App\Models\KurumIstek;



/**

 * EK-3 başvuru amacı kataloğu (süper yönetici) ve kurum seçimi.

 */

class IstekController {



    public function __construct() {

        AuthHelper::requireAdmin();

    }



    private function requireCatalogAdmin(): void {

        if (!AuthHelper::sessionIsSuperAdmin()) {

            $_SESSION['error'] = 'Platform kataloğunu yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), 'UTF-8')
                . ' düzenleyebilir.';

            header('Location: ' . esh_url('Istek', 'index'));

            exit;

        }

    }



    public function index() {

        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'istek_adi'),
            'name',
            'ASC'
        );
        $pagelink = esh_url('Istek', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Istek',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        $isCatalogPickerMode = CatalogStoreHelper::isCatalogPickerMode();

        $saveSelectionUrl = $isCatalogPickerMode ? esh_url('Istek', 'saveSelection') : '';



        include ThemeViewHelper::resolvePartial('header');

        include ThemeViewHelper::resolveAreaView('admin', 'istek/index');

        include ThemeViewHelper::resolvePartial('footer');

    }



    public function indexRows() {

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {

            http_response_code(401);

            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);

            exit;

        }



        $model = new Istek();

        if (CatalogStoreHelper::isCatalogPickerMode()) {

            try {

                $kurumId = CatalogStoreHelper::pickerKurumId();

            } catch (\Throwable) {

                http_response_code(400);

                echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);

                exit;

            }

            $items = $model->getListWithAssignmentState($kurumId);

            $payload = CatalogPickerHelper::pickerJsonFromItems($items, 'istek_adi', false);

            echo json_encode(['ok' => true, 'picker' => true] + $payload, JSON_UNESCAPED_UNICODE);

            exit;

        }



        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'istek_adi'),
            'name',
            'ASC'
        );
        $items = $model->getCatalogList($sort['orderFragment']);

        ob_start();

        include ROOT_PATH . '/views/admin/istek/partials/index_table_rows.php';

        $html = ob_get_clean();



        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);

        exit;

    }



    public function create() {

        $this->requireCatalogAdmin();

        include ThemeViewHelper::resolvePartial('header');

        include ThemeViewHelper::resolveAreaView('admin', 'istek/create');

        include ThemeViewHelper::resolvePartial('footer');

    }



    public function edit() {

        $this->requireCatalogAdmin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $model = new Istek();



        if ($id > 0 && $model->load($id)) {

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

            $item = $model;

            include ThemeViewHelper::resolvePartial('header');

            include ThemeViewHelper::resolveAreaView('admin', 'istek/edit');

            include ThemeViewHelper::resolvePartial('footer');

            return;

        }



        $_SESSION['error'] = 'Düzenlenecek kayıt bulunamadı.';

        header('Location: ' . esh_url('Istek', 'index'));

        exit;

    }



    public function store() {

        $this->requireCatalogAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . esh_url('Istek', 'index'));

            exit;

        }



        $model = new Istek();

        $postId = (int) ($_POST['id'] ?? 0);

        if ($postId > 0) {

            if (!$model->load($postId)) {

                $_SESSION['error'] = 'Düzenlenecek kayıt bulunamadı.';

                header('Location: ' . esh_url('Istek', 'index'));

                exit;

            }

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

        }



        $model->bind($_POST);

        if ($postId <= 0) {

            CatalogStoreHelper::applyPlatformKurumId($model);

        }



        if ($model->store()) {

            $_SESSION['success'] = 'Başvuru amacı kaydedildi.';

        } else {

            $_SESSION['error'] = 'Kayıt sırasında bir hata oluştu.';

        }



        header('Location: ' . esh_url('Istek', 'index'));

        exit;

    }



    public function saveSelection(): void

    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . esh_url('Istek', 'index'));

            exit;

        }

        if (!CatalogStoreHelper::isCatalogPickerMode()) {

            $_SESSION['error'] = 'Bu işlem yalnızca kurum seçim modunda kullanılabilir.';

            header('Location: ' . esh_url('Istek', 'index'));

            exit;

        }



        try {

            $kurumId = CatalogStoreHelper::pickerKurumId();

        } catch (\Throwable) {

            $_SESSION['error'] = 'Kurum kapsamı seçin.';

            header('Location: ' . esh_url('Istek', 'index'));

            exit;

        }



        $assigned = $_POST['assigned'] ?? [];

        if (!is_array($assigned)) {

            $assigned = [];

        }

        $assigned = array_values(array_filter(array_map('intval', $assigned)));



        $count = (new KurumIstek())->syncSelection($kurumId, $assigned);

        $_SESSION['success'] = 'Kurum başvuru amacı seçimi kaydedildi (' . $count . ' kayıt).';

        header('Location: ' . esh_url('Istek', 'index'));

        exit;

    }



    public function delete() {

        $this->requireCatalogAdmin();

        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Istek', 'index'));

        $id = (int) ($_POST['id'] ?? 0);

        $model = new Istek();



        if ($id > 0 && $model->load($id)) {

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

            if ($model->delete()) {

                if (KurumIstek::tableExists()) {

                    $db = \App\Core\Database::getInstance();

                    $db->executePrepared('DELETE FROM #__kurum_istek WHERE istek_id = ?', [(int) $id]);

                }

                $_SESSION['success'] = 'Başvuru amacı silindi.';

            } else {

                $_SESSION['error'] = 'Kayıt silinemedi.';

            }

        }



        header('Location: ' . esh_url('Istek', 'index'));

        exit;

    }

}

