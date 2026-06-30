<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\ThemeViewHelper;
use App\Models\NobetPlan;
use App\Models\User;

class NobetController
{
    private NobetPlan $model;

    public function __construct()
    {
        $this->model = new NobetPlan();
    }

    private function ensureAdmin(): void
    {
        AuthHelper::requireAdmin();
    }

    private function currentUser(): object
    {
        $u = new User();
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid > 0 && $u->load($uid)) {
            return (object) [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'unvan' => (string) $u->unvan,
            ];
        }
        return (object) ['id' => 0, 'name' => '', 'unvan' => ''];
    }

    /** Yalnız ayarlarda tanımlı ünvanlar; aksi halde panele yönlendir. */
    private function ensureNobetMineEligible(): void
    {
        if (!User::canAccessNobetMine()) {
            $labels = [];
            foreach (OperationalSettings::nobetAllowedUnvanlar() as $code) {
                $labels[] = User::unvanLabel($code);
            }
            $listText = $labels !== [] ? implode(', ', $labels) : 'tanımlı ünvanlar';
            $_SESSION['error'] = 'İzin/Mazeret ekranı yalnızca şu ünvanlar için açıktır: ' . $listText . '.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    private function toYmd(string $trDate): string
    {
        $s = trim($trDate);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }
        $p = preg_split('/[.\-\/]/', $s);
        if (is_array($p) && count($p) === 3) {
            if (strlen($p[0]) === 4) {
                return sprintf('%04d-%02d-%02d', (int) $p[0], (int) $p[1], (int) $p[2]);
            }
            return sprintf('%04d-%02d-%02d', (int) $p[2], (int) $p[1], (int) $p[0]);
        }
        return '';
    }

    public function mine()
    {
        $this->ensureNobetMineEligible();
        $mineIzinRowsFetchUrl = esh_url('Nobet', 'mineIzinRows');
        $mineIstekRowsFetchUrl = esh_url('Nobet', 'mineIstekRows');

        $pageTitle = 'Nöbet Planı — İzin/Mazeret';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'nobet/mine');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Kendi izin/rapor taleplerim — tablo satırları (JSON HTML parçası).
     */
    public function mineIzinRows()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->ensureNobetMineEligibleJson();
        $me = $this->currentUser();
        $izinler = $this->model->getPersonelOwnIzin((int) $me->id);

        ob_start();
        include ROOT_PATH . '/views/site/nobet/partials/mine_izin_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Kendi nöbet muafiyet isteklerim — tablo satırları (JSON HTML parçası).
     */
    public function mineIstekRows()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->ensureNobetMineEligibleJson();
        $me = $this->currentUser();
        $istekler = $this->model->getPersonelOwnIstek((int) $me->id);

        ob_start();
        include ROOT_PATH . '/views/site/nobet/partials/mine_istek_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** JSON uç noktaları için ünvan kontrolü (HTML yönlendirme yerine JSON). */
    private function ensureNobetMineEligibleJson(): void
    {
        if (!User::canAccessNobetMine()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Bu ekrana erişim yetkiniz yok.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function saveMineIzin()
    {
        $this->ensureNobetMineEligible();
        $me = $this->currentUser();
        $bas = $this->toYmd((string) ($_POST['baslangic_tarihi'] ?? ''));
        $bit = $this->toYmd((string) ($_POST['bitis_tarihi'] ?? ''));
        $sebep = trim((string) ($_POST['sebep'] ?? ''));
        if ($bas === '' || $bit === '' || $bas < date('Y-m-d')) {
            $_SESSION['error'] = 'Geçerli bir tarih aralığı giriniz.';
        } else {
            $this->model->saveIzin((int) $me->id, $bas, $bit, $sebep);
            $_SESSION['success'] = 'İzin kaydedildi.';
        }
        header('Location: ' . esh_url('Nobet', 'mine'));
        exit;
    }

    public function saveMineIstek()
    {
        $this->ensureNobetMineEligible();
        $me = $this->currentUser();
        $bas = $this->toYmd((string) ($_POST['baslangic_tarihi'] ?? ''));
        $bit = $this->toYmd((string) ($_POST['bitis_tarihi'] ?? ''));
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
        if ($bas === '' || $bit === '' || $bas < date('Y-m-d')) {
            $_SESSION['error'] = 'Geçerli bir tarih aralığı giriniz.';
        } else {
            $this->model->saveIstek((int) $me->id, $bas, $bit, $aciklama);
            $_SESSION['success'] = 'Nöbet isteği kaydedildi.';
        }
        header('Location: ' . esh_url('Nobet', 'mine'));
        exit;
    }

    public function deleteMineIzin()
    {
        $this->ensureNobetMineEligible();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Nobet', 'mine'));
        $me = $this->currentUser();
        $id = (int) ($_POST['id'] ?? 0);
        $rows = $this->model->getPersonelOwnIzin((int) $me->id);
        foreach ($rows as $r) {
            if ((int) ($r->id ?? 0) === $id) {
                $this->model->deleteIzin($id);
                break;
            }
        }
        $_SESSION['success'] = 'Kayıt silindi.';
        header('Location: ' . esh_url('Nobet', 'mine'));
        exit;
    }

    public function deleteMineIstek()
    {
        $this->ensureNobetMineEligible();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Nobet', 'mine'));
        $me = $this->currentUser();
        $id = (int) ($_POST['id'] ?? 0);
        $rows = $this->model->getPersonelOwnIstek((int) $me->id);
        foreach ($rows as $r) {
            if ((int) ($r->id ?? 0) === $id) {
                $this->model->deleteIstek($id);
                break;
            }
        }
        $_SESSION['success'] = 'Kayıt silindi.';
        header('Location: ' . esh_url('Nobet', 'mine'));
        exit;
    }

    public function index()
    {
        $this->ensureAdmin();
        $ay = (int) ($_GET['ay'] ?? date('n'));
        $yil = (int) ($_GET['yil'] ?? date('Y'));
        if ($ay < 1 || $ay > 12) { $ay = (int) date('n'); }
        if ($yil < 2000 || $yil > 2100) { $yil = (int) date('Y'); }

        $personeller = $this->model->getPersonnelForNobet();
        $izinlerAktif = $this->model->getIzinList(true);
        $izinlerGecmis = $this->model->getIzinList(false);
        $istekler = $this->model->getIstekList();
        $tatiller = $this->model->getTatilList();
        $tatilGunleri = $this->model->getTatilGunleri($ay, $yil);
        $takvim = $this->model->getNobetCalendar($ay, $yil);
        $personelAylikNobetSayilari = $this->model->getPersonnelNobetCountByMonth($ay, $yil);

        $pageTitle = 'Nöbet Planı';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'nobet/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function monthlySummary()
    {
        $this->ensureAdmin();
        $ay = (int) ($_GET['ay'] ?? date('n'));
        $yil = (int) ($_GET['yil'] ?? date('Y'));
        if ($ay < 1 || $ay > 12) { $ay = (int) date('n'); }
        if ($yil < 2000 || $yil > 2100) { $yil = (int) date('Y'); }
        $personelOzet = $this->model->getMonthlyPersonnelSummary($ay, $yil);
        include ThemeViewHelper::resolveAreaView('admin', 'nobet/monthly_summary');
    }

    public function yearlyStats()
    {
        $this->ensureAdmin();
        $istatistikYil = (int) ($_GET['istatistik_yil'] ?? date('Y'));
        if ($istatistikYil < 2000 || $istatistikYil > 2100) {
            $istatistikYil = (int) date('Y');
        }
        $yillikIstatistik = $this->model->getYearlyStats($istatistikYil);
        include ThemeViewHelper::resolveAreaView('admin', 'nobet/yearly_stats');
    }

    public function rebuild()
    {
        $this->ensureAdmin();
        $ay = (int) ($_POST['ay'] ?? date('n'));
        $yil = (int) ($_POST['yil'] ?? date('Y'));
        $n = $this->model->rebuildMonthNobet($ay, $yil);
        $_SESSION['success'] = 'Nöbet dağıtımı tamamlandı. Eklenen kayıt: ' . (int) $n;
        header('Location: ' . esh_url('Nobet', 'index', ['ay' => $ay, 'yil' => $yil]));
        exit;
    }

    public function saveIzin()
    {
        $this->ensureAdmin();
        $pid = (int) ($_POST['personel_id'] ?? 0);
        $bas = $this->toYmd((string) ($_POST['baslangic_tarihi'] ?? ''));
        $bit = $this->toYmd((string) ($_POST['bitis_tarihi'] ?? ''));
        $sebep = trim((string) ($_POST['sebep'] ?? ''));
        if ($pid > 0 && $bas !== '' && $bit !== '') {
            $this->model->saveIzin($pid, $bas, $bit, $sebep);
            $_SESSION['success'] = 'İzin kaydedildi.';
        } else {
            $_SESSION['error'] = 'İzin kaydı için alanları kontrol edin.';
        }
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function saveIstek()
    {
        $this->ensureAdmin();
        $pid = (int) ($_POST['personel_id'] ?? 0);
        $bas = $this->toYmd((string) ($_POST['baslangic_tarihi'] ?? ''));
        $bit = $this->toYmd((string) ($_POST['bitis_tarihi'] ?? ''));
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
        if ($pid > 0 && $bas !== '' && $bit !== '') {
            $this->model->saveIstek($pid, $bas, $bit, $aciklama);
            $_SESSION['success'] = 'Nöbet isteği kaydedildi.';
        } else {
            $_SESSION['error'] = 'Nöbet isteği alanlarını kontrol edin.';
        }
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function saveTatil()
    {
        $this->ensureAdmin();
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
        $bas = $this->toYmd((string) ($_POST['baslangic_tarihi'] ?? ''));
        $bit = $this->toYmd((string) ($_POST['bitis_tarihi'] ?? ''));
        $tip = trim((string) ($_POST['tatil_tipi'] ?? 'resmi_tatil'));
        if ($aciklama !== '' && $bas !== '' && $bit !== '') {
            $this->model->saveTatil($aciklama, $bas, $bit, $tip);
            $_SESSION['success'] = 'Tatil kaydedildi.';
        } else {
            $_SESSION['error'] = 'Tatil kaydı alanlarını kontrol edin.';
        }
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function deleteIzin()
    {
        $this->ensureAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Nobet', 'index'));
        $this->model->deleteIzin((int) ($_POST['id'] ?? 0));
        $_SESSION['success'] = 'İzin silindi.';
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function deleteIstek()
    {
        $this->ensureAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Nobet', 'index'));
        $this->model->deleteIstek((int) ($_POST['id'] ?? 0));
        $_SESSION['success'] = 'İstek silindi.';
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function deleteTatil()
    {
        $this->ensureAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Nobet', 'index'));
        $this->model->deleteTatil((int) ($_POST['id'] ?? 0));
        $_SESSION['success'] = 'Tatil silindi.';
        header('Location: ' . esh_url('Nobet', 'index'));
        exit;
    }

    public function addNobet()
    {
        $this->ensureAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $pid = (int) ($_POST['pid'] ?? 0);
        $tarih = $this->toYmd((string) ($_POST['tarih'] ?? ''));
        echo json_encode($this->model->addNobet($pid, $tarih), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function moveNobet()
    {
        $this->ensureAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int) ($_POST['id'] ?? 0);
        $tarih = $this->toYmd((string) ($_POST['tarih'] ?? ''));
        echo json_encode($this->model->moveNobet($id, $tarih), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function deleteNobet()
    {
        $this->ensureAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int) ($_POST['id'] ?? 0);
        $ok = $this->model->deleteNobet($id);
        echo json_encode(['status' => $ok ? 'success' : 'error'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

