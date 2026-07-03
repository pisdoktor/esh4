<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Planning;

/**
 * Mahalle bazlı bölge ve gün planlaması (eski admin/modules/planlama).
 */
class PlanningController {

    public function __construct() {
        AuthHelper::requirePermission('planning.read');
    }

    /**
     * Planlama index: filtre ve sayfalama parametreleri.
     *
     * @return array{
     *   ilce_id:string,
     *   bolge_filter:string,
     *   bolge_max:int,
     *   page:int,
     *   limit:int,
     *   listQuery:array<string, string|int>
     * }
     */
    private function indexRequestState(): array {
        $ilce_id = isset($_GET['ilce']) ? trim((string) $_GET['ilce']) : '';
        $bolge_max = defined('ESH_MAHALLE_BOLGE_MAX') ? (int) ESH_MAHALLE_BOLGE_MAX : 15;
        $bolge_filter = '';
        if (isset($_GET['bolge'])) {
            $bolge_raw = trim((string) $_GET['bolge']);
            if ($bolge_raw === '0') {
                $bolge_filter = '0';
            } elseif ($bolge_raw !== '') {
                $b = (int) $bolge_raw;
                if ($b >= 1 && $b <= $bolge_max) {
                    $bolge_filter = (string) $b;
                }
            }
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $allowedLimits = [5, 10, 15, 20, 25, 30, 50, 100];
        $limit = (int) ($_GET['limit'] ?? 30);
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 30;
        }

        $listQuery = [
            'controller' => 'Planning',
            'action' => 'index',
        ];
        if ($ilce_id !== '' && $ilce_id !== '0') {
            $listQuery['ilce'] = $ilce_id;
        }
        if ($bolge_filter !== '') {
            $listQuery['bolge'] = $bolge_filter;
        }
        if ($limit !== 30) {
            $listQuery['limit'] = $limit;
        }

        $planningModel = new Planning();
        $sort = \App\Helpers\QueryHelper::planningSort($planningModel->bolgeOrderExpr('mp'), 'bolge', 'ASC');

        return [
            'ilce_id' => $ilce_id,
            'bolge_filter' => $bolge_filter,
            'bolge_max' => $bolge_max,
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
        $ilce_id = $st['ilce_id'];
        $bolge_filter = $st['bolge_filter'];
        $bolge_max = $st['bolge_max'];
        $limit = $st['limit'];

        $model = new Planning();
        $total = (int) $model->getPlanningCount($ilce_id, $bolge_filter);
        $paging = $this->indexResolvePaging($st['page'], $limit, $total);
        $page = $paging['page'];
        $totalPages = max(1, (int) ceil($total / $limit));

        $districts = $model->getDistricts() ?: [];

        $filterExpanded = ($ilce_id !== '' && $ilce_id !== '0') || $bolge_filter !== '';

        $gunler = [
            '1' => 'Pzt', '2' => 'Sal', '3' => 'Çar', '4' => 'Per', '5' => 'Cum', '6' => 'Cmt', '0' => 'Paz',
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

        $pageTitle = 'Mahalle bölge ve gün planlaması';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'planning/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Planlama tablo satırları (JSON HTML parçası) — liste iskeletinden sonra XHR ile yüklenir.
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexRequestState();
        $model = new Planning();
        $total = (int) $model->getPlanningCount($st['ilce_id'], $st['bolge_filter']);
        $paging = $this->indexResolvePaging($st['page'], $st['limit'], $total);
        $rows = $model->getPlanningList($st['ilce_id'], $st['limit'], $paging['offset'], $st['bolge_filter'], $st['orderFragment']);

        $gunler = [
            '1' => 'Pzt', '2' => 'Sal', '3' => 'Çar', '4' => 'Per', '5' => 'Cum', '6' => 'Cmt', '0' => 'Paz',
        ];
        $bolge_max = $st['bolge_max'];

        ob_start();
        include ROOT_PATH . '/views/admin/planning/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Haftalık tablo: gün sütunları × bölge kutuları (eski task=table). */
    public function table() {
        $model = new Planning();
        $rows = $model->getMasterPlanData();

        $gunSirasi = ['1', '2', '3', '4', '5', '6', '0'];
        $gunUzun = [
            '1' => 'Pazartesi', '2' => 'Salı', '3' => 'Çarşamba', '4' => 'Perşembe',
            '5' => 'Cuma', '6' => 'Cumartesi', '0' => 'Pazar',
        ];

        $grid = [];
        foreach ($gunSirasi as $gk) {
            $grid[$gk] = [];
        }

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $parts = array_filter(array_map('trim', explode(',', (string) ($row->gun ?? ''))));
                foreach ($parts as $gk) {
                    if ($gk === '' || !in_array($gk, $gunSirasi, true)) {
                        continue;
                    }
                    $b = (int) ($row->bolge ?? 0);
                    if ($b <= 0) {
                        continue;
                    }
                    if (!isset($grid[$gk][$b])) {
                        $grid[$gk][$b] = [];
                    }
                    $grid[$gk][$b][] = $row;
                }
            }
        }

        foreach ($gunSirasi as $gk) {
            if (!empty($grid[$gk])) {
                ksort($grid[$gk], SORT_NUMERIC);
            }
        }

        $pageTitle = 'Haftalık plan tablosu';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'planning/table');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Planning', 'index'));
            exit;
        }

        $model = new Planning();
        $allowedGun = ['0', '1', '2', '3', '4', '5', '6'];
        $gunOrder = array_flip(['1', '2', '3', '4', '5', '6', '0']);
        $bolge_max = defined('ESH_MAHALLE_BOLGE_MAX') ? (int) ESH_MAHALLE_BOLGE_MAX : 15;

        $singleId = isset($_POST['single_id']) ? trim((string) $_POST['single_id']) : '';
        $gunMap = isset($_POST['gun']) && is_array($_POST['gun']) ? $_POST['gun'] : [];
        $bolgeMap = isset($_POST['bolge']) && is_array($_POST['bolge']) ? $_POST['bolge'] : [];

        if ($singleId !== '') {
            $targetIds = [$singleId];
        } else {
            $targetIds = array_unique(array_merge(array_keys($gunMap), array_keys($bolgeMap)));
        }

        $okCount = 0;
        foreach ($targetIds as $id) {
            $id = trim((string) $id);
            if ($id === '') {
                continue;
            }

            $gunPost = isset($gunMap[$id]) && is_array($gunMap[$id]) ? $gunMap[$id] : [];
            $gunler = [];
            foreach ($gunPost as $g) {
                $g = (string) $g;
                if (in_array($g, $allowedGun, true)) {
                    $gunler[] = $g;
                }
            }
            usort($gunler, function ($a, $b) use ($gunOrder) {
                return ($gunOrder[$a] ?? 99) <=> ($gunOrder[$b] ?? 99);
            });
            $gunlerStr = implode(',', $gunler);

            $bolgeNo = isset($bolgeMap[$id]) ? (int) $bolgeMap[$id] : 0;
            if ($bolgeNo < 0) {
                $bolgeNo = 0;
            }
            if ($bolgeNo > $bolge_max) {
                $bolgeNo = $bolge_max;
            }

            if ($model->saveMahallePlan($id, $bolgeNo, $gunlerStr)) {
                $okCount++;
            }
        }

        if ($singleId !== '') {
            $_SESSION[$okCount > 0 ? 'success' : 'error'] = $okCount > 0
                ? 'Mahalle planı güncellendi.'
                : 'Kayıt güncellenemedi.';
        } else {
            $_SESSION[$okCount > 0 ? 'success' : 'error'] = $okCount > 0
                ? $okCount . ' kayıt için plan güncellendi.'
                : 'Toplu kayıtta güncellenecek kayıt bulunamadı.';
        }

        $ilce = isset($_POST['current_ilce']) ? trim((string) $_POST['current_ilce']) : '';
        $bolge_filter = isset($_POST['current_bolge']) ? trim((string) $_POST['current_bolge']) : '';
        $page = max(1, (int) ($_POST['current_page'] ?? 1));
        $limit = max(1, (int) ($_POST['current_limit'] ?? 30));
        $orderby = isset($_POST['orderby']) ? trim((string) $_POST['orderby']) : '';
        $orderdir = isset($_POST['orderdir']) ? trim((string) $_POST['orderdir']) : '';
        $bolge_max = defined('ESH_MAHALLE_BOLGE_MAX') ? (int) ESH_MAHALLE_BOLGE_MAX : 15;
        if ($bolge_filter !== '0') {
            $b = (int) $bolge_filter;
            $bolge_filter = ($b >= 1 && $b <= $bolge_max) ? (string) $b : '';
        }
        $q = http_build_query(array_filter([
            'controller' => 'Planning',
            'action' => 'index',
            'ilce' => $ilce !== '' && $ilce !== '0' ? $ilce : null,
            'bolge' => $bolge_filter !== '' ? $bolge_filter : null,
            'limit' => $limit !== 30 ? $limit : null,
            'page' => $page > 1 ? $page : null,
            'orderby' => $orderby !== '' ? $orderby : null,
            'orderdir' => $orderdir !== '' ? $orderdir : null,
        ]));
        header('Location: ' . \App\Helpers\UrlHelper::fromQueryString($q));
        exit;
    }
}
