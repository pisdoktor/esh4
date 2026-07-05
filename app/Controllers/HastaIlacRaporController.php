<?php
namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
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
        $patientId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = $this->resolvePatientOrRedirect($patientId);
        if ($patient === null) {
            return;
        }

        $hastalikAdByIcd = $this->hastalikAdMapForPatient($patient);
        $hastalikOptions = $this->hastalikOptionsFromMap($hastalikAdByIcd);
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

        $patientId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
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
        $patientId = (string) ($patient->id ?? '');

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

        $patientId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Hasta bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $hastalikAdByIcd = $this->hastalikAdMapForPatient($patient);
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

        $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
        $patient = $this->resolvePatientOrRedirect($patientId, false);
        if ($patient === null) {
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $tc = preg_replace('/\D+/', '', (string) $patient->tckimlik);
        $hastalikIcd = Patient::normalizeHastalikIcd((string) ($_POST['hastalik_icd'] ?? ''));
        $allowed = Patient::mergedHastalikIcdsForIlacRapor($patient);
        if ($hastalikIcd === '' || !in_array($hastalikIcd, $allowed, true)) {
            $_SESSION['error'] = 'Geçersiz tanı seçimi.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $rapor = (isset($_POST['rapor']) && (string) $_POST['rapor'] === '1') ? 1 : 0;
        $raporRowId = IdHelper::normalizeRequestId($_POST['rapor_id'] ?? null);
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

        if ($rapor === 0 && $raporRowId === null) {
            $_SESSION['success'] = 'Bu tanı için zaten rapor kaydı yok; yapılacak bir şey yok.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]));
            exit;
        }

        $model = new HastaIlacRapor();

        if ($raporRowId !== null) {
            if (!$model->findByIdForTc($raporRowId, $tc) || Patient::normalizeHastalikIcd((string) $model->hastalikicd) !== $hastalikIcd) {
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
        } elseif ($raporRowId !== null) {
            $model->bind([
                'rapor' => 1,
                'bitistarihi' => $bitisYmd,
                'brans' => $bransCsv,
                'raporyeri' => $raporyeri,
            ], true);
        } else {
            $model->bind([
                'hastatckimlik' => $tc,
                'hastalikicd' => $hastalikIcd,
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
        $patientId = IdHelper::normalizeRequestId($_POST['return'] ?? null);
        if ($patientId === null) {
            $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
        }
        $redirect = $patientId !== null
            ? esh_url('HastaIlacRapor', 'index', ['id' => $patientId])
            : esh_url('Patient', 'unified', array (
  'status' => 'active',
));
        \App\Helpers\CsrfHelper::requirePostMethod($redirect);

        $raporId = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($raporId === null) {
            $_SESSION['error'] = 'Geçersiz kayıt.';
            header('Location: ' . $redirect);
            exit;
        }

        $patient = $patientId !== null ? (new Patient())->getById($patientId) : null;
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

        $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
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

        $hastalikicd = $this->normalizeOptionalHastalikIcd($patient, $_POST['hastalik_icd'] ?? $_POST['hastalikid'] ?? null);
        if ($hastalikicd === false) {
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
            'hastalikicd' => $hastalikicd,
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

        $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
        $ilacId = IdHelper::normalizeRequestId($_POST['ilac_id'] ?? null);
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

        $hastalikicd = $this->normalizeOptionalHastalikIcd($patient, $_POST['hastalik_icd'] ?? $_POST['hastalikid'] ?? null);
        if ($hastalikicd === false) {
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
            exit;
        }

        if ($ilacId === null) {
            $_SESSION['error'] = 'Geçersiz ilaç kaydı.';
            header('Location: ' . esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar');
            exit;
        }

        $model = new HastaIlac();
        $model->ensureTable();
        if (!$model->findByIdForHasta($ilacId, $patientId ?? '')) {
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
            'hastalikicd' => $hastalikicd,
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
        $patientId = IdHelper::normalizeRequestId($_POST['return'] ?? null);
        if ($patientId === null) {
            $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
        }
        $redirect = $patientId !== null
            ? esh_url('HastaIlacRapor', 'index', ['id' => $patientId]) . '#tab-ilaclar'
            : esh_url('Patient', 'unified', array (
  'status' => 'active',
));
        \App\Helpers\CsrfHelper::requirePostMethod($redirect);

        $ilacId = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($ilacId === null || $patientId === null) {
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
        if (!$model->findByIdForHasta($ilacId ?? '', $patientId ?? '')) {
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
    private function resolvePatientOrRedirect(?string $patientId, bool $redirectOnError = true): ?object {
        if (IdHelper::isEmptyEntityId($patientId)) {
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
     * @return string|null|false null = bağlantı yok; false = geçersiz tanı
     */
    private function normalizeOptionalHastalikIcd(object $patient, $raw) {
        if ($raw === null || $raw === '' || $raw === '0' || $raw === 0) {
            return null;
        }
        $icd = Patient::normalizeHastalikIcd((string) $raw);
        if ($icd === '') {
            return null;
        }
        $allowed = Patient::mergedHastalikIcdsForIlacRapor($patient);
        if (!in_array($icd, $allowed, true)) {
            $_SESSION['error'] = 'Geçersiz tanı seçimi.';

            return false;
        }

        return $icd;
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
     * @return array<string, string>
     */
    private function hastalikAdMapForPatient(object $patient): array {
        $hastalikIcds = Patient::mergedHastalikIcdsForIlacRapor($patient);
        $map = [];
        if ($hastalikIcds === []) {
            return $map;
        }

        $rows = (new HastaIlacRapor())->getReportRowsForPatient((string) $patient->tckimlik, $hastalikIcds);
        foreach ($rows as $r) {
            $icd = Patient::normalizeHastalikIcd((string) ($r->hastalik_icd ?? ''));
            if ($icd !== '') {
                $map[$icd] = (string) ($r->hastalikadi ?? '');
            }
        }

        $ilacModel = new HastaIlac();
        $ilacModel->ensureTable();
        $patientId = (string) ($patient->id ?? '');
        $extraIcds = [];
        foreach ($ilacModel->getByHastaId($patientId) as $il) {
            $icd = Patient::normalizeHastalikIcd((string) ($il->hastalikicd ?? ''));
            if ($icd !== '' && !isset($map[$icd])) {
                $extraIcds[] = $icd;
            }
        }
        foreach (array_diff($hastalikIcds, array_keys($map)) as $icd) {
            $extraIcds[] = $icd;
        }
        $extraIcds = array_values(array_unique(array_filter(array_map(
            static fn ($v) => Patient::normalizeHastalikIcd((string) $v),
            $extraIcds
        ), static fn ($v) => $v !== '')));
        if ($extraIcds !== []) {
            [$inSql, $inParams] = (new Hastalik())->db->whereInClause($extraIcds);
            $params = array_merge([\App\Helpers\CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
            foreach ((new Hastalik())->db->fetchObjectListPrepared(
                "SELECT icd, hastalikadi FROM #__hastaliklar WHERE kurum_id = ? AND icd IN ({$inSql})",
                $params
            ) as $hRow) {
                $icd = Patient::normalizeHastalikIcd((string) ($hRow->icd ?? ''));
                if ($icd !== '') {
                    $map[$icd] = (string) ($hRow->hastalikadi ?? '');
                }
            }
        }

        foreach ($hastalikIcds as $icd) {
            if (!isset($map[$icd])) {
                $map[$icd] = $icd;
            }
        }

        return $map;
    }

    /**
     * @param array<string, string> $hastalikAdByIcd
     * @return array<string, string>
     */
    private function hastalikOptionsFromMap(array $hastalikAdByIcd): array {
        $opts = $hastalikAdByIcd;
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
        $hastalikIcds = Patient::mergedHastalikIcdsForIlacRapor($patient);
        $rows = $hastalikIcds === []
            ? []
            : (new HastaIlacRapor())->getReportRowsForPatient((string) $patient->tckimlik, $hastalikIcds);

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
