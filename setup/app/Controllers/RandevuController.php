<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ValidationHelper;
use App\Models\Brans;
use App\Models\Istek;
use App\Models\KonsRandevu;
use App\Models\Patient;

class RandevuController
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

        $kr = new KonsRandevu();
        $countsByDay = $kr->countByDayInMonth($y, $m);

        $dayAppointmentRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Randevu',
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

        $usedByBrans = $selectedDate !== '' ? $kr->countByBransForDate($selectedDate) : [];
        $bransKotaMap = [];
        foreach ($branslar as $b) {
            $bid = (int) ($b->id ?? 0);
            if ($bid <= 0) {
                continue;
            }
            $bransKotaMap[$bid] = Brans::kotaInfo(
                Brans::normalizeHastaKotasi($b->hasta_kotasi ?? null),
                (int) ($usedByBrans[$bid] ?? 0)
            );
        }

        $monthTitle = $this->turkishMonthTitle($y, $m);
        $prev = $m === 1
            ? ['y' => $y - 1, 'm' => 12]
            : ['y' => $y, 'm' => $m - 1];
        $next = $m === 12
            ? ['y' => $y + 1, 'm' => 1]
            : ['y' => $y, 'm' => $m + 1];

        $pageTitle = 'Branş randevu takvimi';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'randevu/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Seçili gün randevu tablosu satırları (JSON HTML parçası).
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

        $dayRows = (new KonsRandevu())->getByDate($selectedDate);

        ob_start();
        include ROOT_PATH . '/views/site/randevu/partials/day_appointments_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Randevu', 'index'));
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

        $kr = new KonsRandevu();
        if ($kr->existsDuplicate($ymd, $bransId, $tc)) {
            $_SESSION['error'] = 'Bu hasta için seçilen gün ve branşta zaten bir randevu var. Aynı güne farklı branş ekleyebilirsiniz.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $bransRow = new Brans();
        if (!$bransRow->load($bransId)) {
            $_SESSION['error'] = 'Branş bulunamadı.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }
        $kurumId = \App\Helpers\TenantContext::filterKurumId();
        if ($kurumId === null || $kurumId <= 0) {
            $_SESSION['error'] = 'Kurum kapsamı gerekli.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }
        if (!(new \App\Models\KurumBrans())->isAssigned((int) $kurumId, $bransId)) {
            $_SESSION['error'] = 'Seçilen branş kurumunuzda aktif değil.';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }
        $used = $kr->countForBransOnDate($bransId, $ymd);
        $kotaVal = $bransRow->getHastaKotasiForKurum((int) $kurumId, $bransId);
        $kotaInfo = Brans::kotaInfo($kotaVal, $used);
        if ($kotaInfo['full']) {
            $_SESSION['error'] = 'Bu branş için günlük hasta kotası dolu ('
                . (int) $kotaInfo['used'] . ' / ' . (int) $kotaInfo['kota'] . ').';
            header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
            exit;
        }

        $uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        $rec = new KonsRandevu();
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
            $_SESSION['error'] = 'Randevu kaydedilemedi (veritabanı). Güncel database/schemas/schema.sql kurulumunu kontrol edin.';
        }

        header('Location: ' . $this->indexUrl($y, $m, $ymd, $tc));
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Randevu', 'index'));
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
        $kr = new KonsRandevu();
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
            header('Location: ' . esh_url('Randevu', 'index'));
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

        $kr = new KonsRandevu();
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
            $_SESSION['success'] = 'Katılım durumu güncellendi.';
        } else {
            $_SESSION['error'] = 'Güncellenemedi. Güncel database/schemas/schema.sql kurulumunu kontrol edin.';
        }
        header('Location: ' . $this->indexUrl($y, $m, $date, $retc));
        exit;
    }

    /**
     * JSON — seçilen gün + branş için kota özeti.
     */
    public function bransKota(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $bransId = (int) ($_GET['brans_id'] ?? 0);
        $ymd = trim((string) ($_GET['date'] ?? ''));
        if ($bransId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            echo json_encode(['error' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $bransRow = new Brans();
        if (!$bransRow->load($bransId)) {
            echo json_encode(['error' => 'Branş bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $kurumId = \App\Helpers\TenantContext::filterKurumId();
        if ($kurumId === null || $kurumId <= 0) {
            echo json_encode(['error' => 'Kurum kapsamı gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $used = (new KonsRandevu())->countForBransOnDate($bransId, $ymd);
        $kotaVal = $bransRow->getHastaKotasiForKurum((int) $kurumId, $bransId);
        echo json_encode(
            Brans::kotaInfo($kotaVal, $used),
            JSON_UNESCAPED_UNICODE
        );
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
        $q = ['controller' => 'Randevu', 'action' => 'index'];
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
     * @return int|null 1=geldi, 0=gelmedi, null=belirtilmedi
     */
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
