<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CatalogPickerHelper;
use App\Helpers\CatalogStoreHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Hastalik;
use App\Models\HastalikCat;
use App\Models\KurumHastalik;

/**
 * Hastalık kataloğu (süper yönetici) ve kurum tanı seçimi.
 */
class HastalikController {

    public function __construct() {
        $action = isset($_GET['action']) ? trim((string) $_GET['action']) : 'index';
        if ($action === 'searchAssigned') {
            return;
        }
        AuthHelper::requireAdmin();
    }

    private function requireCatalogAdmin(): void {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $_SESSION['error'] = 'Platform kataloğunu yalnızca süper yönetici düzenleyebilir.';
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }
    }

    /**
     * @return int|null 0 = kategorisiz, >0 = kategori id, null = tümü
     */
    private function parseCatFilterFromRequest(): ?int {
        $catRaw = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
        if ($catRaw === '0') {
            return 0;
        }
        if ($catRaw !== '') {
            $c = (int) $catRaw;
            if ($c > 0) {
                return $c;
            }
        }

        return null;
    }

    /**
     * @param list<object> $categories
     */
    private function resolveCatFilter(?int $catFilter, array $categories): ?int {
        if ($catFilter !== null && $catFilter > 0) {
            $validIds = array_map(static fn ($row) => (int) ($row->id ?? 0), $categories);
            if (!in_array($catFilter, $validIds, true)) {
                return null;
            }
        }

        return $catFilter;
    }

    private function buildIndexRowsFetchUrl(?int $catFilter, array $sort, string $searchQ = ''): string {
        $q = [
            'controller' => 'Hastalik',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ];
        if ($catFilter !== null) {
            $q['cat'] = (string) $catFilter;
        }
        if ($searchQ !== '') {
            $q['q'] = $searchQ;
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    public function index() {
        $catModel = new HastalikCat();
        $categories = $catModel->getList() ?: [];
        $catFilter = $this->resolveCatFilter($this->parseCatFilterFromRequest(), $categories);
        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['icd' => 'h.icd', 'name' => 'h.hastalikadi', 'cat' => 'c.name'],
            'name',
            'ASC'
        );
        $pagelink = esh_url('Hastalik', 'index');
        $eshHastalikListCat = $catFilter !== null ? (string) $catFilter : '';
        $filterExpanded = $catFilter !== null;
        $searchQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $indexRowsFetchUrl = $this->buildIndexRowsFetchUrl($catFilter, $sort, $searchQ);
        $searchCatalogUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Hastalik',
            'action' => 'searchCatalog',
        ]);
        $treeNodesUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Hastalik',
            'action' => 'treeNodes',
        ]);
        if ($catFilter !== null) {
            $treeNodesUrl .= (str_contains($treeNodesUrl, '?') ? '&' : '?') . 'cat=' . rawurlencode((string) $catFilter);
        }
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
        $isCatalogPickerMode = CatalogStoreHelper::isCatalogPickerMode();
        $saveSelectionUrl = $isCatalogPickerMode ? esh_url('Hastalik', 'saveSelection') : '';
        $isCatalogAdmin = AuthHelper::sessionIsSuperAdmin();
        $pickerAssignedItems = [];
        if ($isCatalogPickerMode) {
            try {
                $kurumId = CatalogStoreHelper::pickerKurumId();
                $assignedRows = (new Hastalik())->getAssignedListForKurum($kurumId);
                $pickerAssignedItems = Hastalik::mapRowsToPickerLabels($assignedRows);
            } catch (\Throwable) {
                $pickerAssignedItems = [];
            }
        }
        $hastalikTreeConfig = [
            'treeNodesUrl' => $treeNodesUrl,
            'pickerExpandUrl' => \App\Helpers\UrlHelper::fromRequestParams([
                'controller' => 'Hastalik',
                'action' => 'pickerExpand',
            ]),
            'isPickerMode' => $isCatalogPickerMode,
            'isCatalogAdmin' => $isCatalogAdmin,
            'editUrlTemplate' => esh_url('Hastalik', 'edit', ['id' => '__ID__']),
            'deleteUrl' => esh_url('Hastalik', 'delete'),
            'assigned' => $pickerAssignedItems,
            'searchQ' => $searchQ,
            'catFilter' => $catFilter,
        ];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'hastalik/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model = new Hastalik();
        if (CatalogStoreHelper::isCatalogPickerMode()) {
            try {
                $kurumId = CatalogStoreHelper::pickerKurumId();
            } catch (\Throwable) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
            $catId = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int) $_GET['cat'] : null;
            if ($catId !== null && $catId <= 0) {
                $catId = null;
            }
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
            $catalogRows = $model->searchCatalog($q, $catId, max(50, min(500, $limit)));
            $items = $model->getListWithAssignmentState($kurumId, $catalogRows);
            $payload = CatalogPickerHelper::pickerJsonFromItems($items, 'hastalikadi', false);
            foreach ($payload['catalog'] as &$entry) {
                foreach ($catalogRows as $row) {
                    if ((int) ($row->id ?? 0) === (int) ($entry['id'] ?? 0)) {
                        $icd = trim((string) ($row->icd ?? ''));
                        if ($icd !== '') {
                            $entry['label'] = $icd . ' — ' . ($entry['label'] ?? '');
                        }
                        break;
                    }
                }
            }
            unset($entry);
            foreach ($payload['assigned'] as &$entry) {
                foreach ($items as $row) {
                    if ((int) ($row->id ?? 0) === (int) ($entry['id'] ?? 0)) {
                        $icd = trim((string) ($row->icd ?? ''));
                        $name = trim((string) ($row->hastalikadi ?? ''));
                        $entry['label'] = ($icd !== '' ? $icd . ' — ' : '') . $name;
                        break;
                    }
                }
            }
            unset($entry);
            echo json_encode(['ok' => true, 'picker' => true] + $payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $catModel = new HastalikCat();
        $categories = $catModel->getList() ?: [];
        $catFilter = $this->resolveCatFilter($this->parseCatFilterFromRequest(), $categories);
        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['icd' => 'h.icd', 'name' => 'h.hastalikadi', 'cat' => 'c.name'],
            'name',
            'ASC'
        );
        $searchQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $items = $model->getDetailedList($catFilter, $sort['orderFragment'], 200, $offset, $searchQ);
        $total = $model->countCatalog($catFilter, $searchQ);

        ob_start();
        include ROOT_PATH . '/views/admin/hastalik/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode([
            'ok' => true,
            'html' => $html,
            'total' => $total,
            'offset' => $offset,
            'limit' => 200,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function treeNodes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $catModel = new HastalikCat();
        $categories = $catModel->getList() ?: [];
        $catFilter = $this->resolveCatFilter($this->parseCatFilterFromRequest(), $categories);
        $parentIcd = isset($_GET['parent_icd']) ? trim((string) $_GET['parent_icd']) : '';
        if ($parentIcd === '') {
            $parentIcd = null;
        }
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 300;
        $model = new Hastalik();
        $rows = $model->getTreeNodes($parentIcd, $catFilter, $q, max(50, min(500, $limit)));

        $assignedIds = [];
        $rangeAssigned = [];
        $pickerMode = CatalogStoreHelper::isCatalogPickerMode();
        $kurumId = 0;
        if ($pickerMode) {
            try {
                $kurumId = CatalogStoreHelper::pickerKurumId();
                $assignedIds = array_flip((new KurumHastalik())->getAssignedIds($kurumId));
                if ($parentIcd === null && $assignedIds !== []) {
                    $rangeAssigned = $model->getVirtualRangeAssignmentState($kurumId);
                }
            } catch (\Throwable) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            $icd = trim((string) ($row->icd ?? ''));
            $isVirtual = !empty($row->virtual);
            $nodeAssigned = false;
            if ($pickerMode) {
                if ($isVirtual && $icd !== '') {
                    $nodeAssigned = !empty($rangeAssigned[$icd]);
                } elseif ($id > 0) {
                    $nodeAssigned = isset($assignedIds[$id]);
                }
            }
            $items[] = Hastalik::mapRowToTreeNode($row, $nodeAssigned, $pickerMode);
        }

        echo json_encode([
            'ok' => true,
            'items' => $items,
            'search' => mb_strlen($q) >= 2,
            'parent_icd' => $parentIcd,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Kurum seçimi: ağaç düğümünün kapsadığı tanı id listesi (1./2./3. seviye). */
    public function pickerExpand(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!CatalogStoreHelper::isCatalogPickerMode()) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Yalnızca kurum seçim modunda kullanılabilir.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            CatalogStoreHelper::pickerKurumId();
        } catch (\Throwable) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Kurum kapsamı seçin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $icd = isset($_GET['icd']) ? trim((string) $_GET['icd']) : '';
        $virtual = isset($_GET['virtual']) && (string) $_GET['virtual'] === '1';
        $selfId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($icd === '') {
            echo json_encode(['ok' => false, 'error' => 'ICD gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model = new Hastalik();
        $ids = $model->resolvePickerIds($icd, $virtual, $selfId);
        echo json_encode([
            'ok' => true,
            'items' => $model->getPickerItemsByIds($ids),
            'icd' => $icd,
            'virtual' => $virtual,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function searchCatalog(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $catId = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int) $_GET['cat'] : null;
        if ($catId !== null && $catId <= 0) {
            $catId = null;
        }
        $limit = max(10, min(500, (int) ($_GET['limit'] ?? 100)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $model = new Hastalik();
        $rows = $model->searchCatalog($q, $catId, $limit, $offset);
        $items = Hastalik::mapRowsToPickerLabels($rows);
        $kurumId = 0;
        if (CatalogStoreHelper::isCatalogPickerMode()) {
            try {
                $kurumId = CatalogStoreHelper::pickerKurumId();
                $rows = $model->getListWithAssignmentState($kurumId, $rows);
                $assignedIds = array_flip((new KurumHastalik())->getAssignedIds($kurumId));
                foreach ($items as &$item) {
                    $item['assigned'] = isset($assignedIds[(int) ($item['id'] ?? 0)]);
                }
                unset($item);
            } catch (\Throwable) {
                // süper admin kurum filtresi yok
            }
        }
        echo json_encode([
            'ok' => true,
            'items' => $items,
            'total' => $model->countCatalog($catId, $q),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Hasta formu: kuruma atanmış tanılarda ajax arama.
     */
    public function searchAssigned(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $kurumId = isset($_GET['kurum_id']) ? (int) $_GET['kurum_id'] : 0;
        if ($kurumId <= 0) {
            $kurumId = (int) (\App\Helpers\TenantContext::filterKurumId() ?? 0);
        }
        if ($kurumId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!\App\Helpers\TenantContext::canAccessKurum($kurumId)) {
            http_response_code(403);
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ensureRaw = isset($_GET['ensure_ids']) ? trim((string) $_GET['ensure_ids']) : '';
        $ensureIds = $ensureRaw === '' ? [] : array_values(array_filter(array_map('intval', explode(',', $ensureRaw))));
        if ($q === '' && $ensureIds === [] && !\App\Models\KurumHastalik::tableExists()) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 80;
        $limit = max(10, min(200, $limit));
        $rows = (new Hastalik())->searchAssignedForKurum($kurumId, $q, $limit, $ensureIds);
        echo json_encode(Hastalik::mapRowsToTomSelectOptions($rows), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function create() {
        $this->requireCatalogAdmin();
        $catModel = new HastalikCat();
        $categories = $catModel->getList();

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'hastalik/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit() {
        $this->requireCatalogAdmin();
        $id = $_GET['id'] ?? null;
        $model = new Hastalik();

        if ($id && $model->load($id)) {
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
            $catModel = new HastalikCat();
            $categories = $catModel->getList();
            $item = $model;
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('admin', 'hastalik/edit');
            include ThemeViewHelper::resolvePartial('footer');
        } else {
            $_SESSION['error'] = 'Hastalık kaydı bulunamadı!';
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }
    }

    public function store() {
        $this->requireCatalogAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }

        $model = new Hastalik();
        $postId = (int) ($_POST['id'] ?? 0);

        if ($postId > 0) {
            if (!$model->load($postId)) {
                $_SESSION['error'] = 'Hastalık kaydı bulunamadı.';
                header('Location: ' . esh_url('Hastalik', 'index'));
                exit;
            }
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
        }

        $model->bind($_POST);
        if ($postId <= 0) {
            CatalogStoreHelper::applyPlatformKurumId($model);
        }

        if ($model->store()) {
            $_SESSION['success'] = 'Hastalık/Tanı bilgileri başarıyla kaydedildi.';
        } else {
            $_SESSION['error'] = 'Hastalık kaydedilirken bir hata oluştu!';
        }

        header('Location: ' . esh_url('Hastalik', 'index'));
        exit;
    }

    public function saveSelection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }
        if (!CatalogStoreHelper::isCatalogPickerMode()) {
            $_SESSION['error'] = 'Bu işlem yalnızca kurum seçim modunda kullanılabilir.';
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }

        try {
            $kurumId = CatalogStoreHelper::pickerKurumId();
        } catch (\Throwable) {
            $_SESSION['error'] = 'Kurum kapsamı seçin.';
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }

        $assigned = $_POST['assigned'] ?? [];
        if (!is_array($assigned)) {
            $assigned = [];
        }
        $assigned = array_values(array_filter(array_map('intval', $assigned)));

        if (!KurumHastalik::tableExists()) {
            $_SESSION['error'] = 'Kurum tanı atama tablosu bulunamadı. Güncel schema migrasyonunu çalıştırın.';
            header('Location: ' . esh_url('Hastalik', 'index'));
            exit;
        }

        $count = (new KurumHastalik())->syncSelection($kurumId, $assigned);
        $_SESSION['success'] = 'Kurum tanı seçimi kaydedildi (' . $count . ' tanı).';
        header('Location: ' . esh_url('Hastalik', 'index'));
        exit;
    }

    public function delete() {
        $this->requireCatalogAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Hastalik', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        $model = new Hastalik();

        if ($id > 0 && $model->load($id)) {
            CatalogStoreHelper::assertPlatformCatalogRecord($model);
            if ($model->delete()) {
                if (KurumHastalik::tableExists()) {
                    $db = \App\Core\Database::getInstance();
                    $db->executePrepared('DELETE FROM #__kurum_hastalik WHERE hastalik_id = ?', [$id]);
                }
                $_SESSION['success'] = 'Hastalık kütüphaneden kalıcı olarak silindi.';
            } else {
                $_SESSION['error'] = 'Bu hastalık silinemez! Hastalarla ilişkili olabilir.';
            }
        }

        header('Location: ' . esh_url('Hastalik', 'index'));
        exit;
    }
}
