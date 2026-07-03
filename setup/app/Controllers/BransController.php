<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CatalogPickerHelper;
use App\Helpers\CatalogStoreHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Brans;
use App\Models\KurumBrans;

/**
 * Branş kataloğu (süper yönetici) ve kurum seçimi (kurum yöneticisi).
 */
class BransController {

    public function __construct() {
        AuthHelper::requireAdmin();
    }

    private function requireCatalogAdmin(): void {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $_SESSION['error'] = 'Platform kataloğunu yalnızca süper yönetici düzenleyebilir.';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }
    }

    public function index() {
        $isCatalogPickerMode = CatalogStoreHelper::isCatalogPickerMode();
        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'bransadi'),
            'name',
            'ASC'
        );
        $pagelink = esh_url('Brans', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Brans',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
        $saveSelectionUrl = $isCatalogPickerMode ? esh_url('Brans', 'saveSelection') : '';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'brans/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model = new Brans();
        if (CatalogStoreHelper::isCatalogPickerMode()) {
            try {
                $kurumId = CatalogStoreHelper::pickerKurumId();
            } catch (\Throwable) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $items = $model->getListWithAssignmentState($kurumId);
            $payload = CatalogPickerHelper::pickerJsonFromItems($items, 'bransadi', true);
            echo json_encode(['ok' => true, 'picker' => true] + $payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sort = \App\Helpers\QueryHelper::catalogSort(
            \App\Helpers\QueryHelper::catalogIdNameAllowed('id', 'bransadi'),
            'name',
            'ASC'
        );
        $items = $model->getCatalogList($sort['orderFragment']);
        ob_start();
        include ROOT_PATH . '/views/admin/brans/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function create() {
        $this->requireCatalogAdmin();
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'brans/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit() {
        $this->requireCatalogAdmin();
        $id = $_GET['id'] ?? null;
        $model = new Brans();

        if ($id && $model->load($id)) {
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
            $item = $model;
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('admin', 'brans/edit');
            include ThemeViewHelper::resolvePartial('footer');
        } else {
            $_SESSION['error'] = 'Düzenlenecek kayıt bulunamadı!';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }
    }

    public function store() {
        $this->requireCatalogAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }

        $model = new Brans();
        $postId = (int) ($_POST['id'] ?? 0);

        if ($postId > 0) {
            if (!$model->load($postId)) {
                $_SESSION['error'] = 'Düzenlenecek kayıt bulunamadı!';
                header('Location: ' . esh_url('Brans', 'index'));
                exit;
            }
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
        }

        $bransadi = trim((string) ($_POST['bransadi'] ?? ''));
        if ($bransadi === '') {
            $_SESSION['error'] = 'Branş adı boş olamaz.';
            header('Location: ' . ($postId > 0 ? esh_url('Brans', 'edit', ['id' => $postId]) : esh_url('Brans', 'create')));
            exit;
        }

        $model->set('bransadi', $bransadi);
        $model->set('hasta_kotasi', null);
        if ($postId <= 0) {
            CatalogStoreHelper::applyPlatformKurumId($model);
        }

        if ($model->store()) {
            $_SESSION['success'] = 'İşlem başarıyla tamamlandı.';
        } else {
            $_SESSION['error'] = 'Veri kaydedilirken bir hata oluştu!';
        }

        header('Location: ' . esh_url('Brans', 'index'));
        exit;
    }

    /**
     * Kurum seçimi ve branş kotalarını kaydet.
     */
    public function saveSelection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }
        if (!CatalogStoreHelper::isCatalogPickerMode()) {
            $_SESSION['error'] = 'Bu işlem yalnızca kurum seçim modunda kullanılabilir.';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }

        try {
            $kurumId = CatalogStoreHelper::pickerKurumId();
        } catch (\Throwable) {
            $_SESSION['error'] = 'Kurum kapsamı seçin.';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }

        $assigned = $_POST['assigned'] ?? [];
        if (!is_array($assigned)) {
            $assigned = [];
        }
        $assigned = array_values(array_filter(array_map('intval', $assigned)));

        $kotaRaw = $_POST['hasta_kotasi'] ?? [];
        if (!is_array($kotaRaw)) {
            $kotaRaw = [];
        }

        if (!KurumBrans::tableExists()) {
            $_SESSION['error'] = 'Kurum branş atama tablosu bulunamadı. Güncel database/schemas/schema.sql ile kurulum yapın.';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }

        $kb = new KurumBrans();
        $count = $kb->syncSelection($kurumId, $assigned, $kotaRaw);
        $kotaCount = $kb->saveKotas($kurumId, $kotaRaw);

        if ($assigned !== [] && $count === 0) {
            $_SESSION['error'] = 'Branş seçimi kaydedilemedi. Platform kataloğu (`kurum_id=0`) ve migrasyonu kontrol edin.';
            header('Location: ' . esh_url('Brans', 'index'));
            exit;
        }

        $_SESSION['success'] = 'Kurum branş seçimi kaydedildi (' . $count . ' branş'
            . ($kotaCount > 0 ? ', ' . $kotaCount . ' kota güncellendi' : '') . ').';
        header('Location: ' . esh_url('Brans', 'index'));
        exit;
    }

    /** @deprecated saveSelection kullanın */
    public function saveKotas(): void
    {
        $this->saveSelection();
    }

    public function delete() {
        $this->requireCatalogAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Brans', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        $model = new Brans();

        if ($id > 0 && $model->load($id)) {
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
            if ($model->delete()) {
                if (KurumBrans::tableExists()) {
                    $this->dbCleanupBransJunction($id);
                }
                $_SESSION['success'] = 'Branş başarıyla silindi.';
            } else {
                $_SESSION['error'] = 'Bu branş silinemez! Başka verilerle bağlantısı olabilir.';
            }
        }

        header('Location: ' . esh_url('Brans', 'index'));
        exit;
    }

    private function dbCleanupBransJunction(int $bransId): void
    {
        $db = \App\Core\Database::getInstance();
        $db->executePrepared('DELETE FROM #__kurum_brans WHERE brans_id = ?', [(int) $bransId]);
    }
}
