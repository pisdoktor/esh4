<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\KurumAdresScope;
use App\Helpers\ThemeViewHelper;
use App\Models\Archive;

class ArchiveController {

    public function __construct()
    {
        if (AuthHelper::sessionIsAdmin()) {
            return;
        }
        $action = (string) ($GLOBALS['actionName'] ?? '');
        if ($action === 'indexRows') {
            AuthHelper::requireAdminJson();
        }
        AuthHelper::requireAdmin();
    }

    /**
     * @return array{isim:string, soyisim:string, mahalle:list<string>}
     */
    private function parseFiltersFromRequest(): array {
        $mahalle = $_GET['mahalle'] ?? [];
        if (!is_array($mahalle)) {
            $mahalle = ($mahalle !== '' && $mahalle !== null) ? [(string) $mahalle] : [];
        }

        $mahalle = array_values(array_filter(array_map('strval', $mahalle)));
        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid !== null && KurumAdresScope::shouldFilter($kid)) {
            $mahalle = array_values(array_filter(
                $mahalle,
                static fn(string $id): bool => KurumAdresScope::isAllowed($kid, $id)
            ));
        }

        return [
            'isim' => trim((string) ($_GET['isim'] ?? '')),
            'soyisim' => trim((string) ($_GET['soyisim'] ?? '')),
            'mahalle' => $mahalle,
        ];
    }

    /**
     * @return array{filters:array, page:int, limit:int}
     */
    private function indexPagingState(): array {
        $filters = $this->parseFiltersFromRequest();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $allowedLimits = [5, 10, 15, 20, 25, 30, 50, 100];
        $limit = (int) ($_GET['limit'] ?? 15);
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 15;
        }
        $sort = \App\Helpers\QueryHelper::archivePatientSort('h.isim', 'ASC');

        return [
            'filters' => $filters,
            'page' => $page,
            'limit' => $limit,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
        ];
    }

    /**
     * @param array{isim:string, soyisim:string, mahalle:list<string>} $filters
     * @param array{orderby?: string, orderdir?: string}|null $sort
     * @return array<string, string|int|list<string>>
     */
    private function buildListQueryParams(array $filters, int $limit, ?int $page = null, string $action = 'index', ?array $sort = null): array {
        $q = [
            'controller' => 'Archive',
            'action' => $action,
        ];
        if ($filters['isim'] !== '') {
            $q['isim'] = $filters['isim'];
        }
        if ($filters['soyisim'] !== '') {
            $q['soyisim'] = $filters['soyisim'];
        }
        if (!empty($filters['mahalle'])) {
            foreach ($filters['mahalle'] as $mid) {
                $q['mahalle'][] = $mid;
            }
        }
        if ($page !== null && $page > 1) {
            $q['page'] = $page;
        }
        if ($limit !== 15) {
            $q['limit'] = $limit;
        }
        if ($sort !== null && !empty($sort['orderby'])) {
            $q['orderby'] = $sort['orderby'];
            $q['orderdir'] = $sort['orderdir'];
        }

        return $q;
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
        $st = $this->indexPagingState();
        $filters = $st['filters'];
        $limit = $st['limit'];

        $model = new Archive();
        $total = (int) $model->getCount($filters);
        $paging = $this->indexResolvePaging($st['page'], $limit, $total);
        $page = $paging['page'];
        $totalPages = max(1, (int) ceil($total / $limit));

        $locations = $model->getLocationHierarchy();
        $alfabe = ['A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 'N', 'O', 'Ö', 'P', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'V', 'Y', 'Z'];

        $pagelink = \App\Helpers\UrlHelper::fromRequestParams($this->buildListQueryParams($filters, $limit, null, 'index', $st));
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams($this->buildListQueryParams($filters, $limit, $page, 'indexRows', $st));
        $orderby = $st['orderby'];
        $orderdir = $st['orderdir'];
        $ordering = trim($orderby . ' ' . $orderdir);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'archive/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Arşiv hasta listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexPagingState();
        $model = new Archive();
        $total = (int) $model->getCount($st['filters']);
        $paging = $this->indexResolvePaging($st['page'], $st['limit'], $total);
        $rows = $model->getArchivedPatients($st['filters'], $st['limit'], $paging['offset'], $st['orderFragment']);

        ob_start();
        include ROOT_PATH . '/views/admin/archive/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
