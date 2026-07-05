<?php

namespace App\Controllers;



use App\Helpers\AuthHelper;

use App\Helpers\CatalogPickerHelper;

use App\Helpers\CatalogStoreHelper;

use App\Helpers\ThemeViewHelper;

use App\Models\Islem;

use App\Models\KurumIslem;



/**

 * Tıbbi işlem kataloğu (platform sahibi) ve kurum seçimi.

 */

class IslemController {



    public function __construct() {

        AuthHelper::requireAdmin();

    }



    private function requireCatalogAdmin(): void {

        if (!AuthHelper::sessionIsPlatformOwner()) {

            $_SESSION['error'] = 'Platform kataloğunu yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8')
                . ' düzenleyebilir.';

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }

    }



    public function index() {

        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'islemadi'),
            'name',
            'ASC'
        );
        $pagelink = esh_url('Islem', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Islem',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        $isCatalogPickerMode = CatalogStoreHelper::isCatalogPickerMode();

        $saveSelectionUrl = $isCatalogPickerMode ? esh_url('Islem', 'saveSelection') : '';



        include ThemeViewHelper::resolvePartial('header');

        include ThemeViewHelper::resolveAreaView('admin', 'islem/index');

        include ThemeViewHelper::resolvePartial('footer');

    }



    public function indexRows() {

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {

            http_response_code(401);

            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);

            exit;

        }



        $model = new Islem();

        if (CatalogStoreHelper::isCatalogPickerMode()) {

            try {

                $kurumId = CatalogStoreHelper::pickerKurumId();

            } catch (\Throwable) {

                http_response_code(400);

                echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);

                exit;

            }

            $items = $model->getListWithAssignmentState($kurumId);

            $payload = CatalogPickerHelper::pickerJsonFromItems($items, 'islemadi', false);

            echo json_encode(['ok' => true, 'picker' => true] + $payload, JSON_UNESCAPED_UNICODE);

            exit;

        }



        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'islemadi'),
            'name',
            'ASC'
        );
        $items = $model->getCatalogList($sort['orderFragment']);

        ob_start();

        include ROOT_PATH . '/views/admin/islem/partials/index_table_rows.php';

        $html = ob_get_clean();



        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);

        exit;

    }



    public function create() {

        $this->requireCatalogAdmin();

        include ThemeViewHelper::resolvePartial('header');

        include ThemeViewHelper::resolveAreaView('admin', 'islem/create');

        include ThemeViewHelper::resolvePartial('footer');

    }



    public function edit() {

        $this->requireCatalogAdmin();

        $id = $_GET['id'] ?? null;

        $model = new Islem();



        if ($id && $model->load($id)) {

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

            $item = $model;

            include ThemeViewHelper::resolvePartial('header');

            include ThemeViewHelper::resolveAreaView('admin', 'islem/edit');

            include ThemeViewHelper::resolvePartial('footer');

        } else {

            $_SESSION['error'] = 'Düzenlenecek işlem kaydı bulunamadı!';

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }

    }



    public function store() {

        $this->requireCatalogAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }



        $model = new Islem();

        $postId = (int) ($_POST['id'] ?? 0);



        if ($postId > 0) {

            if (!$model->load($postId)) {

                $_SESSION['error'] = 'Düzenlenecek kayıt bulunamadı!';

                header('Location: ' . esh_url('Islem', 'index'));

                exit;

            }

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

        }



        $model->bind($_POST);

        if ($postId <= 0) {

            CatalogStoreHelper::applyPlatformKurumId($model);

        }



        if ($model->store()) {

            $_SESSION['success'] = 'İşlem tanımı başarıyla kaydedildi.';

        } else {

            $_SESSION['error'] = 'Kayıt sırasında teknik bir hata oluştu!';

        }



        header('Location: ' . esh_url('Islem', 'index'));

        exit;

    }



    public function saveSelection(): void

    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }

        if (!CatalogStoreHelper::isCatalogPickerMode()) {

            $_SESSION['error'] = 'Bu işlem yalnızca kurum seçim modunda kullanılabilir.';

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }



        try {

            $kurumId = CatalogStoreHelper::pickerKurumId();

        } catch (\Throwable) {

            $_SESSION['error'] = 'Kurum kapsamı seçin.';

            header('Location: ' . esh_url('Islem', 'index'));

            exit;

        }



        $assigned = $_POST['assigned'] ?? [];

        if (!is_array($assigned)) {

            $assigned = [];

        }

        $assigned = array_values(array_filter(array_map('intval', $assigned)));



        $count = (new KurumIslem())->syncSelection($kurumId, $assigned);

        $_SESSION['success'] = 'Kurum işlem seçimi kaydedildi (' . $count . ' kayıt).';

        header('Location: ' . esh_url('Islem', 'index'));

        exit;

    }



    public function delete() {

        $this->requireCatalogAdmin();

        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Islem', 'index'));

        $id = (int) ($_POST['id'] ?? 0);

        $model = new Islem();



        if ($id > 0 && $model->load($id)) {

            CatalogStoreHelper::assertPlatformCatalogRecord($model);

            if ($model->delete()) {

                if (KurumIslem::tableExists()) {

                    $db = \App\Core\Database::getInstance();

                    $db->executePrepared('DELETE FROM #__kurum_islem WHERE islem_id = ?', [(int) $id]);

                }

                $_SESSION['success'] = 'İşlem tanımı sistemden kaldırıldı.';

            } else {

                $_SESSION['error'] = 'Bu işlem silinemez! Geçmiş ziyaret kayıtlarında kullanılmış görünüyor.';

            }

        }



        header('Location: ' . esh_url('Islem', 'index'));

        exit;

    }

}

