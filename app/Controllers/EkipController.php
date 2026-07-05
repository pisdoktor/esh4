<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Helpers\TenantSqlHelper;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ThemeViewHelper;
use App\Core\Database;
use App\Helpers\DateHelper;
use App\Models\Ekip;
use App\Models\User;

/**
 * Admin ekip planlama (legacy admin/modules/ekip ile aynı iş akışı, MVC).
 */
class EkipController {

    public function __construct() {
        if (AuthHelper::sessionIsAdmin()) {
            return;
        }
        $action = (string) ($GLOBALS['actionName'] ?? '');
        $jsonActions = ['indexRows', 'getEkiplerJSON', 'saveDaily', 'deleteDay'];
        if (in_array($action, $jsonActions, true)) {
            AuthHelper::requireAdminJson();
        }
        AuthHelper::requireAdmin();
    }

    public function index() {
        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['tarih' => 'tarih', 'ekip_sayisi' => 'ekip_sayisi'],
            'tarih',
            'DESC'
        );
        $pagelink = esh_url('Ekip', 'index');
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Ekip',
            'action' => 'indexRows',
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
        ]);
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        $pageTitle = 'Ekip Yönetimi';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'ekip/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Ekip günlük liste satırları (JSON HTML parçası) — liste iskeletinden sonra XHR ile yüklenir.
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sort = \App\Helpers\QueryHelper::catalogSort(
            ['tarih' => 'tarih', 'ekip_sayisi' => 'ekip_sayisi'],
            'tarih',
            'DESC'
        );
        $model = new Ekip();
        $items = $model->getDailyTeamsList($sort['orderFragment']);

        ob_start();
        include ROOT_PATH . '/views/admin/ekip/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function edit() {
        $raw = isset($_GET['tarih']) ? trim((string) $_GET['tarih']) : (isset($_GET['date']) ? trim((string) $_GET['date']) : '');
        if ($raw === '' || $raw === '0000-00-00' || strtotime($raw) === false) {
            $tarih_db = date('Y-m-d');
        } else {
            $parsed = DateHelper::trDateToYmd($raw);
            if ($parsed !== null) {
                $tarih_db = $parsed;
            } else {
                $ts = strtotime(str_replace('.', '-', $raw));
                $tarih_db = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
            }
        }

        $date = $tarih_db;
        $userModel = new User();
        $users = $userModel->getList();
        if (!is_array($users)) {
            $users = [];
        }

        $model = new Ekip();
        $mevcutlar = $model->getEkipler($tarih_db);

        $pageTitle = 'Ekip planı — ' . DateHelper::toTrDotOrEmpty($tarih_db);
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'ekip/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveDaily() {
        $db = Database::getInstance();
        $tarih_ham = trim((string) ($_POST['tarih'] ?? DateHelper::todayTr()));
        $parsed = DateHelper::trDateToYmd($tarih_ham);
        if ($parsed !== null) {
            $tarih = $parsed;
        } else {
            $ts = strtotime(str_replace(['.', '/'], '-', $tarih_ham));
            $tarih = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        }

        $gelen_ekipler = $_POST['ekipler'] ?? [];
        $gelen_saatler = $_POST['saatler'] ?? [];

        $db->executePrepared(
            'DELETE FROM #__ekipler WHERE tarih = ?' . TenantSqlHelper::andBare(),
            [$tarih]
        );

        if (is_array($gelen_ekipler)) {
            foreach ($gelen_ekipler as $vID => $ekipler_list) {
                if (!is_array($ekipler_list)) {
                    continue;
                }
                $eSayac = 1;
                foreach ($ekipler_list as $eNo => $userIDs) {
                    if (!empty($userIDs)) {
                        $ekip = new Ekip();
                        TenantStoreHelper::applyKurumIdToModel($ekip);
                        $ekip->bind([
                            'tarih' => $tarih,
                            'vardiya' => (int) $vID,
                            'ekip_no' => $eSayac,
                            'user_ids' => implode(',', IdHelper::csvToEntityIds(implode(',', array_map('strval', (array) $userIDs)))),
                            'baslangic_saati' => isset($gelen_saatler[$vID]) ? (string) $gelen_saatler[$vID] : '09:00',
                            'kayit_tarihi' => date('Y-m-d H:i:s'),
                        ]);
                        $ekip->store();
                        $eSayac++;
                    }
                }
            }
        }

        $_SESSION['success'] = 'Ekip planı kaydedildi.';
        header('Location: ' . esh_url('Ekip', 'index'));
        exit;
    }

    /**
     * Bir güne ait tüm ekip satırlarını siler (legacy task=delete).
     */
    public function deleteDay() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Ekip', 'index'));
        $db = Database::getInstance();
        $raw = isset($_POST['tarih']) ? trim((string) $_POST['tarih']) : '';
        if ($raw === '') {
            header('Location: ' . esh_url('Ekip', 'index'));
            exit;
        }
        $parsed = DateHelper::trDateToYmd($raw);
        if ($parsed === null && preg_match('/^\d{4}-\d{2}-\d{2}/', $raw, $m)) {
            $parsed = $m[0];
        }
        if ($parsed === null) {
            $ts = strtotime($raw);
            $parsed = $ts ? date('Y-m-d', $ts) : null;
        }
        if ($parsed === null) {
            $_SESSION['error'] = 'Geçersiz tarih.';
            header('Location: ' . esh_url('Ekip', 'index'));
            exit;
        }
        $db->executePrepared(
            'DELETE FROM #__ekipler WHERE tarih = ?' . TenantSqlHelper::andBare(),
            [$parsed]
        );
        $_SESSION['success'] = 'Plan silindi.';
        header('Location: ' . esh_url('Ekip', 'index'));
        exit;
    }

    /**
     * PDF / pdfMake için JSON (legacy getEkiplerJSON).
     */
    public function getEkiplerJSON() {
        $db = Database::getInstance();
        $mod = isset($_GET['mod']) ? (string) $_GET['mod'] : 'gunluk';
        $dateRaw = isset($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d');
        $parsed = DateHelper::trDateToYmd($dateRaw);
        if ($parsed !== null) {
            $bas = $parsed;
        } else {
            $ts = strtotime($dateRaw);
            $bas = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        }

        if ($mod === 'haftalik') {
            $bit = date('Y-m-d', strtotime($bas . ' +6 days'));
        } elseif ($mod === 'aylik') {
            $bas = date('Y-m-01', strtotime($bas));
            $bit = date('Y-m-t', strtotime($bas));
        } else {
            $bit = $bas;
        }

        $sql = 'SELECT e.* FROM #__ekipler AS e WHERE e.tarih BETWEEN ? AND ?'
            . TenantSqlHelper::andEquals('e')
            . ' ORDER BY e.tarih ASC, e.vardiya ASC, e.ekip_no ASC';
        $rows = $db->fetchObjectListPrepared($sql, [$bas, $bit]);

        $data = [];
        foreach ($rows as $r) {
            $idList = IdHelper::csvToEntityIds((string) $r->user_ids);
            $p_names = [];
            if ($idList !== []) {
                [$inSql, $inParams] = $db->whereInClause($idList);
                $p_names = $db->fetchColumnListPrepared(
                    "SELECT name FROM #__users WHERE id IN ({$inSql})",
                    $inParams
                );
            }
            if ($p_names === null) {
                $p_names = [];
            }
            $vCode = \App\Helpers\ZamanDilimiHelper::fromVardiyaIndex((int) $r->vardiya);
            $v_ad = mb_strtoupper(\App\Helpers\ZamanDilimiHelper::label($vCode), 'UTF-8');
            $data[] = [
                'tarih' => DateHelper::toTrDotOrEmpty($r->tarih),
                'vardiya' => $v_ad,
                'vardiya_label' => $v_ad,
                'saat' => (string) $r->baslangic_saati,
                'ekip' => (int) $r->ekip_no . '. Ekip',
                'personeller' => implode(', ', $p_names),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
