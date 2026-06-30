<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ValidationHelper;
use App\Models\Brans;
use App\Models\Uhds;
use App\Models\Istek;
use App\Models\Patient;

class UhdsController
{
    public function index(): void
    {
        $now = new \DateTimeImmutable('first day of this month');
        $y = isset($_GET['y']) ? (int) $_GET['y'] : (int) $now->format('Y');
        $m = isset($_GET['m']) ? (int) $_GET['m'] : (int) $now->format('n');
        if ($y < 2000 || $y > 2100) {
            $y = (int) $now->format('Y');
        }
        if ($m < 1 || $m > 12) {
            $m = (int) $now->format('n');
        }

        $dateRaw = trim((string) ($_GET['date'] ?? ''));
        $selectedDate = '';
        $dateExplicitFromGet = false;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw)) {
            $selectedDate = $dateRaw;
            $dateExplicitFromGet = true;
        }

        $prefillTc = ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');
        $prefillHastaLabel = '';
        if (ValidationHelper::isTcLength11($prefillTc)) {
            $pt = (new Patient())->findByTc($prefillTc);
            if (!$pt || (string) ($pt->pasif ?? '') !== '0') {
                $prefillTc = '';
            } else {
                $isim = trim((string) ($pt->isim ?? '') . ' ' . (string) ($pt->soyisim ?? ''));
                $prefillHastaLabel = $isim !== ''
                    ? ($isim . ' — ' . ValidationHelper::formatTc($prefillTc))
                    : ValidationHelper::formatTc($prefillTc);
            }
        } else {
            $prefillTc = '';
        }

        if ($selectedDate === '') {
            $today = new \DateTimeImmutable('today');
            $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $y, $m));
            if ($monthStart instanceof \DateTimeImmutable) {
                $monthEnd = $monthStart->modify('last day of this month');
                if ($today >= $monthStart && $today <= $monthEnd) {
                    $selectedDate = $today->format('Y-m-d');
                } else {
                    $selectedDate = $monthStart->format('Y-m-d');
                }
            } else {
                $selectedDate = $today->format('Y-m-d');
            }
        }

        if ($selectedDate !== '' && $dateExplicitFromGet) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate);
            if ($dt instanceof \DateTimeImmutable) {
                $y = (int) $dt->format('Y');
                $m = (int) $dt->format('n');
            }
        }

        $kr = new Uhds();
        $countsByDay = $kr->countByDayInMonth($y, $m);

        $dayAppointmentRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Uhds',
            'action' => 'dayAppointmentRows',
            'y' => (string) $y,
            'm' => (string) $m,
            'date' => $selectedDate,
        ] + ($prefillTc !== '' ? ['tc' => $prefillTc] : []));

        $branslar = (new Brans())->getList();
        if (!is_array($branslar)) {
            $branslar = [];
        }

        $istekler = (new Istek())->getList();
        if (!is_array($istekler)) {
            $istekler = [];
        }

        $monthTitle = $this->turkishMonthTitle($y, $m);
        $prev = $m === 1
            ? ['y' => $y - 1, 'm' => 12]
            : ['y' => $y, 'm' => $m - 1];
        $next = $m === 12
            ? ['y' => $y + 1, 'm' => 1]
            : ['y' => $y, 'm' => $m + 1];

        $pageTitle = 'Uhds';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'uhds/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Seçili gün UHDS randevu tablosu satırları (JSON HTML parçası).
     */
    public function dayAppointmentRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $y = isset($_GET['y']) ? (int) $_GET['y'] : (int) date('Y');
        $m = isset($_GET['m']) ? (int) $_GET['m'] : (int) date('n');
        $selectedDate = trim((string) ($_GET['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            echo json_encode(['ok' => true, 'html' => ''], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $prefillTc = ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');
        if (!ValidationHelper::isTcLength11($prefillTc)) {
            $prefillTc = '';
        }

        $dayRows = (new Uhds())->getByDate($selectedDate);

        ob_start();
        include ROOT_PATH . '/views/site/uhds/partials/day_appointments_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $y = (int) ($_POST['y'] ?? 0);
        $m = (int) ($_POST['m'] ?? 0);
        $datePost = trim((string) ($_POST['randevu_tarihi'] ?? ''));
        $ymd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePost) ? $datePost : '';
        if ($ymd === '') {
            $_SESSION['error'] = 'Geçerli bir randevu tarihi seçin.';
            header('Location: ' . $this->indexUrl($y, $m, ''));
            exit;
        }

        $tc = ValidationHelper::tcDigitsOnly($_POST['hastatckimlik'] ?? '');
        if (!ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Hasta TC kimlik numarası 11 hane olmalıdır.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $hastaGeldi = $this->parseHastaGeldiFromPost($_POST);

        $konsIsteklerCsv = $this->parseKonsIsteklerFromPost($_POST);
        if ($konsIsteklerCsv === null) {
            $_SESSION['error'] = 'En az bir istek seçmelisiniz.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $bransId = (int) ($_POST['brans_id'] ?? 0);
        if ($bransId <= 0) {
            $_SESSION['error'] = 'Branş seçiniz.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $zaman = \App\Helpers\ZamanDilimiHelper::clamp($_POST['zaman'] ?? null);
        $zamanValid = \App\Helpers\ZamanDilimiHelper::validateForSave($zaman);
        if ($zamanValid !== true) {
            $_SESSION['error'] = is_string($zamanValid) ? $zamanValid : 'Geçersiz zaman dilimi.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        if (strlen($notlar) > 500) {
            $notlar = substr($notlar, 0, 500);
        }

        $p = new Patient();
        $hasta = $p->findByTc($tc);
        if (!$hasta || (string) ($hasta->pasif ?? '') !== '0') {
            $_SESSION['error'] = 'Hasta bulunamadı veya aktif değil (yalnızca aktif hastaya randevu).';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $kr = new Uhds();
        if ($kr->existsDuplicate($ymd, $bransId, $tc)) {
            $_SESSION['error'] = 'Bu hasta için seçilen gün ve branşta zaten bir randevu var. Aynı güne farklı branş ekleyebilirsiniz.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        $rec = new Uhds();
        $rec->bind([
            'randevu_tarihi' => $ymd,
            'zaman' => $zaman,
            'kons_istekler' => $konsIsteklerCsv,
            'brans_id' => $bransId,
            'hastatckimlik' => $tc,
            'notlar' => $notlar !== '' ? $notlar : null,
            'hasta_geldi' => $hastaGeldi,
            'olusturan_id' => $uid > 0 ? $uid : null,
        ], true);
        TenantStoreHelper::applyKurumIdToModel($rec);

        if ($rec->store()) {
            $_SESSION['success'] = 'Randevu kaydedildi.';
        } else {
            $_SESSION['error'] = 'Randevu kaydedilemedi (veritabanı). `#__goruntulu_randevu` ve `database/schemas/schema.sql` / `database/archive/migrate_esh_goruntulu_randevu_kons_istekler.sql` güncellemelerini kontrol edin.';
        }

        header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $y = (int) ($_POST['y'] ?? 0);
        $m = (int) ($_POST['m'] ?? 0);
        $date = trim((string) ($_POST['date'] ?? ''));
        $retc = ValidationHelper::tcDigitsOnly($_POST['tc'] ?? '');
        if (!ValidationHelper::isTcLength11($retc)) {
            $retc = '';
        }
        if ($id <= 0) {
            header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
            exit;
        }
        $kr = new Uhds();
        if ($kr->deleteById($id)) {
            $_SESSION['success'] = 'Randevu silindi.';
        } else {
            $_SESSION['error'] = 'Randevu silinemedi.';
        }
        header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
        exit;
    }

    public function updateGeldi(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $y = (int) ($_POST['y'] ?? 0);
        $m = (int) ($_POST['m'] ?? 0);
        $date = trim((string) ($_POST['date'] ?? ''));
        $retc = ValidationHelper::tcDigitsOnly($_POST['tc'] ?? '');
        if (!ValidationHelper::isTcLength11($retc)) {
            $retc = '';
        }

        if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
            exit;
        }

        $hastaGeldi = $this->parseHastaGeldiFromPost($_POST);

        $kr = new Uhds();
        if (!$kr->load($id)) {
            $_SESSION['error'] = 'Randevu bulunamadı.';
            header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
            exit;
        }
        if ((string) $kr->randevu_tarihi !== $date) {
            $_SESSION['error'] = 'Tarih uyuşmuyor; sayfayı yenileyip tekrar deneyin.';
            header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
            exit;
        }

        $kr->bind(['hasta_geldi' => $hastaGeldi], true);
        if ($kr->store()) {
            $_SESSION['success'] = 'Yapılım durumu güncellendi.';
        } else {
            $_SESSION['error'] = 'Güncellenemedi.';
        }
        header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
        exit;
    }

    /**
     * JSON hasta arama (aktif hastalar; TC veya ad/soyad).
     */
    public function patientSearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if (strlen($q) < 2) {
            echo '[]';
            exit;
        }
        $list = (new Patient())->searchForBransRandevu($q, 14);
        echo json_encode(Patient::mapRandevuPatientSearchJson($list), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function indexUrl(int $y, int $m, string $date, string $tc = ''): string
    {
        $q = ['controller' => 'Uhds', 'action' => 'index'];
        if ($y >= 2000 && $y <= 2100) {
            $q['y'] = $y;
        }
        if ($m >= 1 && $m <= 12) {
            $q['m'] = $m;
        }
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $q['date'] = $date;
        }
        $tcClean = ValidationHelper::tcDigitsOnly($tc);
        if (ValidationHelper::isTcLength11($tcClean)) {
            $q['tc'] = $tcClean;
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * POST istekler[] → virgüllü #__istekler.id; geçersiz/boşsa null.
     */
    private function parseKonsIsteklerFromPost(array $post): ?string
    {
        if (empty($post['istekler']) || !is_array($post['istekler'])) {
            return null;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $post['istekler']))));
        if ($ids === []) {
            return null;
        }
        $valid = [];
        foreach ((new Istek())->getList() as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id > 0 && in_array($id, $ids, true)) {
                $valid[] = $id;
            }
        }
        if ($valid === []) {
            return null;
        }
        sort($valid);

        return implode(',', $valid);
    }

    /**
     * @return int|null 1=geldi, 0=gelmedi, null=belirtilmedi
     */
    private function parseHastaGeldiFromPost(array $post): ?int
    {
        if (!array_key_exists('hasta_geldi', $post)) {
            return null;
        }
        $raw = trim((string) $post['hasta_geldi']);
        if ($raw === '') {
            return null;
        }
        if ($raw === '1') {
            return 1;
        }
        if ($raw === '0') {
            return 0;
        }

        return null;
    }

    private function turkishMonthTitle(int $y, int $m): string
    {
        $names = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        return ($names[$m] ?? (string) $m) . ' ' . $y;
    }
}
