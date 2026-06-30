<?php
namespace App\Controllers;

use App\Helpers\DateHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Brans;
use App\Models\HastaIlac;
use App\Models\HastaIlacRapor;
use App\Models\Hastalik;
use App\Models\Patient;

/**
 * Hasta — tanı bazlı ilaç/rapor bilgisi (eski hastailacrapor modülü).
 */
class HastaIlacRaporController {

    public function index(): void {
        $patientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId);
        if ($patient === null) {
            return;
        }

        $hastalikAdById = $this->hastalikAdMapForPatient($patient);
        $hastalikOptions = $this->hastalikOptionsFromMap($hastalikAdById);
        $branslar = (new Brans())->getList();
        $pageTitle = 'İlaç / tanı raporu bilgileri';

        $raporRowsFetchUrl = esh_url('HastaIlacRapor', 'raporRows', ['id' => $patientId]);
        $ilacRowsFetchUrl = esh_url('HastaIlacRapor', 'ilacRows', ['id' => $patientId]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hastailacrapor/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Tanı raporları tablosu satırları (JSON HTML parçası + özet sayıları).
     */
    public function raporRows(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Hasta bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ctx = $this->buildRaporListContext($patient);
        $rows = $ctx['rows'];
        $rowMetas = $ctx['rowMetas'];
        $bransById = $ctx['bransById'];
        $patientId = (int) ($patient->id ?? 0);

        ob_start();
        include ROOT_PATH . '/views/site/hastailacrapor/partials/rapor_table_rows.php';
        $html = ob_get_clean();

        echo json_encode([
            'ok' => true,
            'html' => $html,
            'summary' => [
                'cntRaporlu' => $ctx['cntRaporlu'],
                'cntExpired' => $ctx['cntExpired'],
                'cntExpiring' => $ctx['cntExpiring'],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * İlaç listesi tablo satırları (JSON HTML parçası).
     */
    public function ilacRows(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Hasta bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $hastalikAdById = $this->hastalikAdMapForPatient($patient);
        $ilacModel = new HastaIlac();
        $ilacModel->ensureTable();
        $ilaclar = $ilacModel->getByHastaId($patientId);

        ob_start();
        include ROOT_PATH . '/views/site/hastailacrapor/partials/ilac_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $tc = preg_replace('/\D+/', '', (string) $patient->tckimlik);
        $hastalikId = isset($_POST['hastalik_id']) ? (int) $_POST['hastalik_id'] : 0;
        $allowed = Patient::mergedHastalikIdsForIlacRapor($patient);
        if ($hastalikId < 1 || !in_array($hastalikId, $allowed, true)) {
            $_SESSION['error'] = 'Geçersiz tanı seçimi.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $rapor = (isset($_POST['rapor']) && (string) $_POST['rapor'] === '1') ? 1 : 0;
        $raporRowId = isset($_POST['rapor_id']) ? (int) $_POST['rapor_id'] : 0;
        $raporyeri = (isset($_POST['raporyeri']) && (string) $_POST['raporyeri'] === '1') ? 1 : 0;
        $bransCsv = HastaIlacRapor::normalizeBransCsv($_POST['brans'] ?? []);

        $bitisYmd = null;
        if ($rapor === 1) {
            $bitisTr = trim((string) ($_POST['bitistarihi'] ?? ''));
            $bitisYmd = DateHelper::trDateToYmd($bitisTr);
            if ($bitisYmd === null || $bitisYmd === '') {
                $_SESSION['error'] = 'Raporlu iken geçerli bir bitiş tarihi girin (GG-AA-YYYY).';
                header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
                exit;
            }
        }

        if ($rapor === 0 && $raporRowId < 1) {
            $_SESSION['success'] = 'Bu tanı için zaten rapor kaydı yok; yapılacak bir şey yok.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $model = new HastaIlacRapor();

        if ($raporRowId > 0) {
            if (!$model->findByIdForTc($raporRowId, $tc) || (int) $model->hastalikid !== $hastalikId) {
                $_SESSION['error'] = 'Güncellenecek rapor satırı bulunamadı veya hasta ile eşleşmiyor.';
                header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
                exit;
            }
        }

        if ($rapor === 0) {
            $model->bind([
                'rapor' => 0,
                'bitistarihi' => null,
                'brans' => '',
                'raporyeri' => 0,
            ], true);
        } elseif ($raporRowId > 0) {
            $model->bind([
                'rapor' => 1,
                'bitistarihi' => $bitisYmd,
                'brans' => $bransCsv,
                'raporyeri' => $raporyeri,
            ], true);
        } else {
            $model->bind([
                'hastatckimlik' => $tc,
                'hastalikid' => $hastalikId,
                'rapor' => 1,
                'bitistarihi' => $bitisYmd,
                'brans' => $bransCsv,
                'raporyeri' => $raporyeri,
            ], true);
        }

        if ($model->store()) {
            $_SESSION['success'] = 'Rapor bilgisi kaydedildi.';
        } else {
            $_SESSION['error'] = 'Kayıt sırasında bir hata oluştu.';
        }

        header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
        exit;
    }

    public function delete(): void {
        $patientId = isset($_POST['return']) ? (int) $_POST['return'] : 0;
        $redirect = $patientId > 0
            ? esh_url('HastaIlacRapor', 'index', ['id' => $patientId])
            : esh_url('Patient', 'unified', array (
  'status' => 'active',
));
        \App\Helpers\CsrfHelper::requirePostMethod($redirect);

        $raporId = (int) ($_POST['id'] ?? 0);
        if ($raporId < 1) {
            $_SESSION['error'] = 'Geçersiz kayıt.';
            header('Location: ' . $redirect);
            exit;
        }

        $patient = $patientId > 0 ? (new Patient())->getById($patientId) : null;
        if (!$patient || empty($patient->tckimlik)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $tc = preg_replace('/\D+/', '', (string) $patient->tckimlik);
        $model = new HastaIlacRapor();
        if (!$model->findByIdForTc($raporId, $tc)) {
            $_SESSION['error'] = 'Silinecek kayıt bulunamadı.';
            header('Location: ' . $redirect);
            exit;
        }

        if ($model->delete()) {
            $_SESSION['success'] = 'Rapor satırı silindi.';
        } else {
            $_SESSION['error'] = 'Silme işlemi başarısız.';
        }

        header('Location: ' . $redirect);
        exit;
    }

    public function storeIlac(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $ilacAdi = trim((string) ($_POST['ilac_adi'] ?? ''));
        if ($ilacAdi === '') {
            $_SESSION['error'] = 'İlaç adı zorunludur.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $hastalikid = $this->normalizeOptionalHastalikId($patient, $_POST['hastalikid'] ?? null);
        if ($hastalikid === false) {
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $model = new HastaIlac();
        $model->ensureTable();
        $not = trim((string) ($_POST['not'] ?? ''));
        $receteTuru = $this->normalizeReceteTuruFromPost();
        $model->bind([
            'hasta_id' => $patientId,
            'ilac_adi' => $ilacAdi,
            'etken_madde' => null,
            'recete_turu' => $receteTuru,
            'not' => $not !== '' ? $not : null,
            'hastalikid' => $hastalikid,
            'sira' => $model->nextSiraForHasta($patientId),
        ], true);

        if ($model->store()) {
            $_SESSION['success'] = 'İlaç kaydı eklendi.';
        } else {
            $_SESSION['error'] = 'İlaç kaydı sırasında bir hata oluştu.';
        }

        header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
        exit;
    }

    public function updateIlac(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patientId = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
        $ilacId = isset($_POST['ilac_id']) ? (int) $_POST['ilac_id'] : 0;
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $ilacAdi = trim((string) ($_POST['ilac_adi'] ?? ''));
        if ($ilacAdi === '') {
            $_SESSION['error'] = 'İlaç adı zorunludur.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
            exit;
        }

        $hastalikid = $this->normalizeOptionalHastalikId($patient, $_POST['hastalikid'] ?? null);
        if ($hastalikid === false) {
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
            exit;
        }

        $model = new HastaIlac();
        $model->ensureTable();
        if (!$model->findByIdForHasta($ilacId, $patientId)) {
            $_SESSION['error'] = 'Güncellenecek ilaç kaydı bulunamadı.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
            exit;
        }

        $not = trim((string) ($_POST['not'] ?? ''));
        $receteTuru = $this->normalizeReceteTuruFromPost();
        $model->bind([
            'ilac_adi' => $ilacAdi,
            'etken_madde' => null,
            'recete_turu' => $receteTuru,
            'not' => $not !== '' ? $not : null,
            'hastalikid' => $hastalikid,
        ], true);

        if ($model->store()) {
            $_SESSION['success'] = 'İlaç kaydı güncellendi.';
        } else {
            $_SESSION['error'] = 'İlaç güncelleme sırasında bir hata oluştu.';
        }

        header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
        exit;
    }

    public function deleteIlac(): void {
        $patientId = isset($_POST['return']) ? (int) $_POST['return'] : 0;
        if ($patientId < 1 && isset($_POST['patient_id'])) {
            $patientId = (int) $_POST['patient_id'];
        }
        $redirect = $patientId > 0
            ? esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar'
            : esh_url('Patient', 'unified', array (
  'status' => 'active',
));
        \App\Helpers\CsrfHelper::requirePostMethod($redirect);

        $ilacId = (int) ($_POST['id'] ?? 0);
        if ($ilacId < 1 || $patientId < 1) {
            $_SESSION['error'] = 'Geçersiz kayıt.';
            header('Location: ' . $redirect);
            exit;
        }

        $patient = (new Patient())->getById($patientId);
        if (!$patient) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $model = new HastaIlac();
        $model->ensureTable();
        if (!$model->findByIdForHasta($ilacId, $patientId)) {
            $_SESSION['error'] = 'Silinecek ilaç kaydı bulunamadı.';
            header('Location: ' . $redirect);
            exit;
        }

        if ($model->delete()) {
            $_SESSION['success'] = 'İlaç kaydı silindi.';
        } else {
            $_SESSION['error'] = 'Silme işlemi başarısız.';
        }

        header('Location: ' . $redirect);
        exit;
    }

    /**
     * @return object|null
     */
    private function resolvePatientOrRedirect(int $patientId, bool $redirectOnError = true): ?object {
        if ($patientId < 1) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Geçersiz hasta.';
                header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
                exit;
            }

            return null;
        }

        $patient = (new Patient())->getById($patientId);
        if (!$patient || empty($patient->tckimlik)) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Hasta bulunamadı.';
                header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
                exit;
            }

            return null;
        }

        if (!PatientAccessHelper::canAccessPatient($patientId, $patient)) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Bu hasta kaydına erişim yetkiniz bulunmamaktadır.';
                header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
                exit;
            }

            return null;
        }

        return $patient;
    }

    /**
     * @param mixed $raw
     * @return int|null|false null = bağlantı yok; false = geçersiz tanı
     */
    private function normalizeOptionalHastalikId(object $patient, $raw) {
        if ($raw === null || $raw === '' || $raw === '0' || $raw === 0) {
            return null;
        }
        $hid = (int) $raw;
        if ($hid < 1) {
            return null;
        }
        $allowed = Patient::mergedHastalikIdsForIlacRapor($patient);
        if (!in_array($hid, $allowed, true)) {
            $_SESSION['error'] = 'Geçersiz tanı seçimi.';

            return false;
        }

        return $hid;
    }

    private function normalizeReceteTuruFromPost(): ?string
    {
        $recete = trim((string) ($_POST['recete_turu'] ?? ''));
        if ($recete === '') {
            return null;
        }
        if (strlen($recete) > 128) {
            $recete = mb_substr($recete, 0, 128, 'UTF-8');
        }

        return $recete;
    }

    /**
     * @return array<int, string>
     */
    private function hastalikAdMapForPatient(object $patient): array {
        $hastalikIds = Patient::mergedHastalikIdsForIlacRapor($patient);
        $map = [];
        if ($hastalikIds === []) {
            return $map;
        }

        $rows = (new HastaIlacRapor())->getReportRowsForPatient((string) $patient->tckimlik, $hastalikIds);
        foreach ($rows as $r) {
            $hid = (int) ($r->hastalik_id ?? 0);
            if ($hid > 0) {
                $map[$hid] = (string) ($r->hastalikadi ?? '');
            }
        }

        $ilacModel = new HastaIlac();
        $ilacModel->ensureTable();
        $patientId = (int) ($patient->id ?? 0);
        $extraIds = [];
        foreach ($ilacModel->getByHastaId($patientId) as $il) {
            $hid = (int) ($il->hastalikid ?? 0);
            if ($hid > 0 && !isset($map[$hid])) {
                $extraIds[] = $hid;
            }
        }
        foreach (array_diff($hastalikIds, array_keys($map)) as $hid) {
            $extraIds[] = (int) $hid;
        }
        $extraIds = array_values(array_unique(array_filter(array_map('intval', $extraIds))));
        if ($extraIds !== []) {
            $placeholders = implode(',', array_fill(0, count($extraIds), '?'));
            $db = (new Hastalik())->db;
            foreach ($db->fetchObjectListPrepared(
                "SELECT id, hastalikadi FROM #__hastaliklar WHERE id IN ({$placeholders})",
                $extraIds
            ) as $hRow) {
                $map[(int) $hRow->id] = (string) ($hRow->hastalikadi ?? '');
            }
        }

        return $map;
    }

    /**
     * @param array<int, string> $hastalikAdById
     * @return array<int, string>
     */
    private function hastalikOptionsFromMap(array $hastalikAdById): array {
        $opts = $hastalikAdById;
        asort($opts, SORT_FLAG_CASE | SORT_NATURAL);

        return $opts;
    }

    /**
     * @return array{
     *   rows: list<object>,
     *   rowMetas: list<array{raporlu:bool,badge:string,badgeText:string,expired:bool,expiring:bool}>,
     *   bransById: array<int,string>,
     *   cntRaporlu: int,
     *   cntExpired: int,
     *   cntExpiring: int
     * }
     */
    private function buildRaporListContext(object $patient): array {
        $hastalikIds = Patient::mergedHastalikIdsForIlacRapor($patient);
        $rows = $hastalikIds === []
            ? []
            : (new HastaIlacRapor())->getReportRowsForPatient((string) $patient->tckimlik, $hastalikIds);

        $bransById = [];
        foreach ((new Brans())->getList() as $b) {
            $bransById[(int) ($b->id ?? 0)] = (string) ($b->bransadi ?? '');
        }

        $today = new \DateTimeImmutable('today');
        $plus30 = $today->modify('+30 days');
        $cntRaporlu = 0;
        $cntExpired = 0;
        $cntExpiring = 0;
        $rowMetas = [];
        foreach ($rows as $r) {
            $rowMetas[] = $this->evaluateRaporRow($r, $today, $plus30, $cntRaporlu, $cntExpired, $cntExpiring);
        }

        return [
            'rows' => $rows,
            'rowMetas' => $rowMetas,
            'bransById' => $bransById,
            'cntRaporlu' => $cntRaporlu,
            'cntExpired' => $cntExpired,
            'cntExpiring' => $cntExpiring,
        ];
    }

    /**
     * @return array{raporlu:bool,badge:string,badgeText:string,expired:bool,expiring:bool}
     */
    private function evaluateRaporRow(
        object $r,
        \DateTimeImmutable $today,
        \DateTimeImmutable $plus30,
        int &$cntRaporlu,
        int &$cntExpired,
        int &$cntExpiring
    ): array {
        $raporlu = (int) ($r->rapor ?? 0) === 1;
        $badge = 'secondary';
        $badgeText = 'Raporlu değil';
        $expired = false;
        $expiring = false;

        if ($raporlu) {
            $cntRaporlu++;
            $badge = 'success';
            $badgeText = 'Raporlu';
            $bitis = trim((string) ($r->bitistarihi ?? ''));
            if ($bitis !== '' && substr($bitis, 0, 4) !== '0000') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d', substr($bitis, 0, 10));
                if ($dt instanceof \DateTimeImmutable) {
                    if ($dt < $today) {
                        $expired = true;
                        $cntExpired++;
                        $badge = 'danger';
                        $badgeText = 'Süresi doldu';
                    } elseif ($dt <= $plus30) {
                        $expiring = true;
                        $cntExpiring++;
                        $badge = 'warning';
                        $badgeText = '30 gün içinde bitiyor';
                    }
                }
            }
        }

        return [
            'raporlu' => $raporlu,
            'badge' => $badge,
            'badgeText' => $badgeText,
            'expired' => $expired,
            'expiring' => $expiring,
        ];
    }
}
