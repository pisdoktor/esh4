<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Pansuman;
use App\Models\Patient;

class PansumanController {

    public function __construct() {
        AuthHelper::requirePermission('pansuman.read');
    }

    /**
     * Pansuman index: filtre ve sayfalama parametreleri.
     *
     * @return array{
     *   search:string,
     *   filter_day:string,
     *   kurumIdFilter:?int,
     *   eshPansumanListKurum:string,
     *   page:int,
     *   limit:int,
     *   listQuery:array<string, string|int>
     * }
     */
    private function indexRequestState(): array {
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $filter_day = isset($_GET['filter_day']) ? trim((string) $_GET['filter_day']) : '';
        if ($filter_day !== '' && !in_array($filter_day, ['1', '2', '3', '4', '5', '6', '7'], true)) {
            $filter_day = '';
        }

        $kurumIdFilter = null;
        $eshPansumanListKurum = '';
        if (AuthHelper::sessionIsSuperAdmin()) {
            $kurumRaw = isset($_GET['kurum_id']) ? trim((string) $_GET['kurum_id']) : '';
            if ($kurumRaw !== '' && ctype_digit($kurumRaw) && (int) $kurumRaw > 0) {
                $kurumIdFilter = (int) $kurumRaw;
                $eshPansumanListKurum = $kurumRaw;
            }
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $allowedLimits = [5, 10, 15, 20, 25, 30, 50, 100];
        $limit = (int) ($_GET['limit'] ?? 20);
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 20;
        }

        $listQuery = [
            'controller' => 'Pansuman',
            'action' => 'index',
        ];
        if ($search !== '') {
            $listQuery['search'] = $search;
        }
        if ($filter_day !== '') {
            $listQuery['filter_day'] = $filter_day;
        }
        if ($eshPansumanListKurum !== '') {
            $listQuery['kurum_id'] = $eshPansumanListKurum;
        }
        if ($limit !== 20) {
            $listQuery['limit'] = $limit;
        }

        $sort = \App\Helpers\QueryHelper::pansumanSort('h.isim', 'ASC');

        return [
            'search' => $search,
            'filter_day' => $filter_day,
            'kurumIdFilter' => $kurumIdFilter,
            'eshPansumanListKurum' => $eshPansumanListKurum,
            'page' => $page,
            'limit' => $limit,
            'listQuery' => $listQuery,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
        ];
    }

    /**
     * @return array{page:int, offset:int}
     */
    private function indexResolvePaging(int $page, int $limit, int $total): array {
        $totalPages = max(1, (int) ceil($total / $limit));
        if ($total > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'page' => $page,
            'offset' => ($page - 1) * $limit,
        ];
    }

    public function index() {
        $st = $this->indexRequestState();
        $search = $st['search'];
        $filter_day = $st['filter_day'];
        $limit = $st['limit'];

        $kurumIdFilter = $st['kurumIdFilter'];
        $eshPansumanListKurum = $st['eshPansumanListKurum'];

        $model = new Pansuman();
        $total = (int) $model->getPansumanCount($search, $filter_day, $kurumIdFilter);
        $paging = $this->indexResolvePaging($st['page'], $limit, $total);
        $page = $paging['page'];
        $totalPages = max(1, (int) ceil($total / $limit));

        $filterExpanded = $search !== '' || $filter_day !== '' || $kurumIdFilter !== null;

        $gunler = [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];
        $zamanlar = [
            \App\Helpers\ZamanDilimiHelper::SABAH => 'Sabah',
            \App\Helpers\ZamanDilimiHelper::OGLE => 'Öğle',
            \App\Helpers\ZamanDilimiHelper::AKSAM => 'Akşam',
        ];

        $indexRowsFetchParams = array_merge($st['listQuery'], [
            'action' => 'indexRows',
            'page' => $page,
            'limit' => $limit,
            'orderby' => $st['orderby'],
            'orderdir' => $st['orderdir'],
        ]);
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams($indexRowsFetchParams);
        $orderby = $st['orderby'];
        $orderdir = $st['orderdir'];
        $ordering = trim($orderby . ' ' . $orderdir);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => \App\Helpers\UrlHelper::fromRequestParams($st['listQuery'])];

        $pageTitle = 'Pansuman Planlama';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'pansuman/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Pansuman tablo satırları (JSON HTML parçası) — liste iskeletinden sonra XHR ile yüklenir.
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexRequestState();
        $model = new Pansuman();
        $total = (int) $model->getPansumanCount($st['search'], $st['filter_day'], $st['kurumIdFilter']);
        $paging = $this->indexResolvePaging($st['page'], $st['limit'], $total);
        $rows = $model->getPansumanList(
            $st['search'],
            $st['filter_day'],
            $st['limit'],
            $paging['offset'],
            $st['kurumIdFilter'],
            $st['orderFragment']
        );

        $gunler = [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];

        ob_start();
        include ROOT_PATH . '/views/admin/pansuman/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Hastanın pansuman günlerini kaydeder
     */
    public function saveDays() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $singleId = isset($_POST['single_id']) ? (int) $_POST['single_id'] : 0;
            $pgunleriMap = isset($_POST['pgunleri']) && is_array($_POST['pgunleri']) ? $_POST['pgunleri'] : [];
            $pzamanMap = isset($_POST['pzaman']) && is_array($_POST['pzaman']) ? $_POST['pzaman'] : [];

            $targetIds = [];
            if ($singleId > 0) {
                $targetIds[] = $singleId;
            } else {
                $targetIds = array_unique(array_merge(
                    array_map('intval', array_keys($pgunleriMap)),
                    array_map('intval', array_keys($pzamanMap))
                ));
                $targetIds = array_values(array_filter($targetIds, static function ($v) {
                    return $v > 0;
                }));
            }

            $okCount = 0;
            foreach ($targetIds as $id) {
                $model = new Patient();
                if (!$model->load($id)) {
                    continue;
                }
                $daysArr = isset($pgunleriMap[$id]) && is_array($pgunleriMap[$id]) ? $pgunleriMap[$id] : [];
                $days = implode(',', $daysArr);
                $pzaman = isset($pzamanMap[$id])
                    ? (string) \App\Helpers\ZamanDilimiHelper::clamp($pzamanMap[$id])
                    : (string) \App\Helpers\ZamanDilimiHelper::SABAH;
                $data = [
                    'id' => $id,
                    'pgunleri' => $days,
                    'pzaman' => $pzaman,
                ];
                if ($model->save($data)) {
                    $okCount++;
                }
            }

            if ($singleId > 0) {
                $_SESSION[$okCount > 0 ? 'success' : 'error'] = $okCount > 0
                    ? 'Pansuman planı güncellendi.'
                    : 'Pansuman planı kaydedilemedi.';
            } else {
                $_SESSION[$okCount > 0 ? 'success' : 'error'] = $okCount > 0
                    ? $okCount . ' kayıt için pansuman planı güncellendi.'
                    : 'Toplu kayıtta güncellenecek kayıt bulunamadı.';
            }

            $search = isset($_POST['search']) ? trim((string) $_POST['search']) : '';
            $filter_day = isset($_POST['filter_day']) ? trim((string) $_POST['filter_day']) : '';
            if ($filter_day !== '' && !in_array($filter_day, ['1', '2', '3', '4', '5', '6', '7'], true)) {
                $filter_day = '';
            }
            $kurum_id = '';
            if (AuthHelper::sessionIsSuperAdmin()) {
                $kurumRaw = isset($_POST['kurum_id']) ? trim((string) $_POST['kurum_id']) : '';
                if ($kurumRaw !== '' && ctype_digit($kurumRaw) && (int) $kurumRaw > 0) {
                    $kurum_id = $kurumRaw;
                }
            }
            $page = max(1, (int) ($_POST['page'] ?? 1));
            $limit = max(1, (int) ($_POST['current_limit'] ?? 20));
            $allowedLimits = [5, 10, 15, 20, 25, 30, 50, 100];
            if (!in_array($limit, $allowedLimits, true)) {
                $limit = 20;
            }

            $orderby = isset($_POST['orderby']) ? trim((string) $_POST['orderby']) : '';
            $orderdir = isset($_POST['orderdir']) ? trim((string) $_POST['orderdir']) : '';

            $params = array_filter([
                'controller' => 'Pansuman',
                'action' => 'index',
                'search' => $search !== '' ? $search : null,
                'filter_day' => $filter_day !== '' ? $filter_day : null,
                'kurum_id' => $kurum_id !== '' ? $kurum_id : null,
                'limit' => $limit !== 20 ? $limit : null,
                'page' => $page > 1 ? $page : null,
                'orderby' => $orderby !== '' ? $orderby : null,
                'orderdir' => $orderdir !== '' ? $orderdir : null,
            ], static fn ($v) => $v !== null);

            header('Location: ' . \App\Helpers\UrlHelper::fromRequestParams($params));
            exit();
        }
    }
}