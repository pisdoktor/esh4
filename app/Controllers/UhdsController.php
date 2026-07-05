<?php
namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ValidationHelper;
use App\Helpers\UhdsTelehealthHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\AuditLogHelper;
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

        $uhdsTelehealthEnabled = UhdsTelehealthHelper::isEnabled() && UhdsTelehealthHelper::provider() === 'jitsi';

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
        $uhdsTelehealthEnabled = UhdsTelehealthHelper::isEnabled() && UhdsTelehealthHelper::provider() === 'jitsi';

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

        $uid = AuthHelper::sessionUserId();
        $rec = new Uhds();
        $rec->bind([
            'randevu_tarihi' => $ymd,
            'zaman' => $zaman,
            'kons_istekler' => $konsIsteklerCsv,
            'brans_id' => $bransId,
            'hastatckimlik' => $tc,
            'notlar' => $notlar !== '' ? $notlar : null,
            'hasta_geldi' => $hastaGeldi,
            'olusturan_id' => IdHelper::isEmptyEntityId((string) $uid) ? null : (string) $uid,
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
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $y = (int) ($_POST['y'] ?? 0);
        $m = (int) ($_POST['m'] ?? 0);
        $date = trim((string) ($_POST['date'] ?? ''));
        $retc = ValidationHelper::tcDigitsOnly($_POST['tc'] ?? '');
        if (!ValidationHelper::isTcLength11($retc)) {
            $retc = '';
        }
        if ($id === null) {
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

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $y = (int) ($_POST['y'] ?? 0);
        $m = (int) ($_POST['m'] ?? 0);
        $date = trim((string) ($_POST['date'] ?? ''));
        $retc = ValidationHelper::tcDigitsOnly($_POST['tc'] ?? '');
        if (!ValidationHelper::isTcLength11($retc)) {
            $retc = '';
        }

        if ($id === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
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

    /**
     * Personel görüntülü görüşme odası.
     */
    public function video(): void
    {
        if (!UhdsTelehealthHelper::isEnabled() || UhdsTelehealthHelper::provider() !== 'jitsi') {
            $_SESSION['error'] = 'Görüntülü görüşme özelliği kapalı.';
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz randevu.';
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $appointment = $this->loadAppointmentForStaff($id);
        if ($appointment === null) {
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $roomId = UhdsTelehealthHelper::ensureRoomId($appointment);
        (new Uhds())->markVideoStarted($id);

        $patient = (new Patient())->findByTc((string) $appointment->hastatckimlik);
        $hastaLabel = $patient
            ? trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''))
            : ValidationHelper::formatTc((string) $appointment->hastatckimlik);

        $staffName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Personel'));
        $patientJoinUrl = UhdsTelehealthHelper::patientJoinUrl($id, (string) $appointment->randevu_tarihi);
        $visitCreateUrl = UhdsTelehealthHelper::visitCreateUrl($appointment);
        $autoPromptVisit = OperationalSettings::uhdsTelehealthAutoPromptVisit();
        $inviteMessage = UhdsTelehealthHelper::patientInviteMessage($patientJoinUrl, $hastaLabel);
        $patientId = $patient ? (string) ($patient->id ?? '') : '';
        $patientPhone = '';
        if ($patient !== null) {
            $patientPhone = trim((string) ($patient->ceptel1 ?? ''));
            if ($patientPhone === '') {
                $patientPhone = trim((string) ($patient->ceptel2 ?? ''));
            }
        }
        $waPhone = UhdsTelehealthHelper::phoneForWaMe($patientPhone);
        $whatsappShareUrl = $waPhone !== '' && $inviteMessage !== ''
            ? 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($inviteMessage)
            : '';
        $smsPhoneNormalized = \App\Services\Sms\SmsPhoneNormalizer::normalize($patientPhone);
        $nativeSmsShareUrl = $smsPhoneNormalized !== null && $inviteMessage !== ''
            ? 'sms:+' . $smsPhoneNormalized . '?body=' . rawurlencode($inviteMessage)
            : '';
        $userId = AuthHelper::sessionUserId();
        $canUseSmsModule = \App\Services\Sms\SmsService::canUseSms($userId);
        $smsComposeUrl = ($canUseSmsModule && $patientId !== '' && $inviteMessage !== '')
            ? esh_url('Sms', 'compose', ['hasta_id' => $patientId, 'govde' => $inviteMessage])
            : '';

        AuditLogHelper::log('uhds.video.start', 'uhds', $id, (string) ($appointment->hastatckimlik ?? ''), [
            'room' => $roomId,
        ]);

        $pageTitle = 'UHDS görüntülü görüşme';
        $uhdsAppointmentId = $id;
        $uhdsRoomId = $roomId;
        $uhdsStaffDisplayName = $staffName;
        $uhdsHastaLabel = $hastaLabel;
        $uhdsPatientJoinUrl = $patientJoinUrl;
        $uhdsInviteMessage = $inviteMessage;
        $uhdsWhatsappShareUrl = $whatsappShareUrl;
        $uhdsNativeSmsShareUrl = $nativeSmsShareUrl;
        $uhdsSmsComposeUrl = $smsComposeUrl;
        $uhdsVisitCreateUrl = $visitCreateUrl;
        $uhdsAutoPromptVisit = $autoPromptVisit;
        $uhdsJitsiScriptUrl = UhdsTelehealthHelper::externalApiScriptUrl();
        $uhdsVideoConfigUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Uhds',
            'action' => 'videoConfig',
            'id' => (string) $id,
        ]);
        $uhdsCompleteUrl = esh_url('Uhds', 'completeSession');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'uhds/video');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Jitsi istemci yapılandırması (JSON).
     */
    public function videoConfig(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!UhdsTelehealthHelper::isEnabled() || UhdsTelehealthHelper::provider() !== 'jitsi') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Telehealth kapalı'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $appointment = $this->loadAppointmentForStaff($id, false);
        if ($appointment === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Randevu bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $roomId = UhdsTelehealthHelper::ensureRoomId($appointment);
        $staffName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Personel'));
        $cfg = UhdsTelehealthHelper::jitsiClientConfig($roomId, $staffName, true);
        echo json_encode(['ok' => true] + $cfg, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Hasta davet bağlantısı (JSON, personel).
     */
    public function patientJoinLink(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!UhdsTelehealthHelper::isEnabled()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Telehealth kapalı'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $appointment = $this->loadAppointmentForStaff($id, false);
        if ($appointment === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Randevu bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $url = UhdsTelehealthHelper::patientJoinUrl($id, (string) $appointment->randevu_tarihi);
        echo json_encode(['ok' => true, 'url' => $url], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Görüşme tamamlama (personel, POST JSON veya form).
     */
    public function completeSession(): void
    {
        $isJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'POST gerekli'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . esh_url('Auth', 'login'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $summary = trim((string) ($_POST['telehealth_summary'] ?? $_POST['summary'] ?? ''));
        $durationSeconds = max(0, (int) ($_POST['duration_seconds'] ?? 0));
        $participantCount = max(0, (int) ($_POST['participant_count'] ?? 0));
        if (strlen($summary) > 4000) {
            $summary = substr($summary, 0, 4000);
        }
        $markDone = array_key_exists('hasta_geldi', $_POST)
            ? $this->parseHastaGeldiFromPost($_POST)
            : 1;

        $appointment = $this->loadAppointmentForStaff($id, false);
        if ($appointment === null) {
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Randevu bulunamadı'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['error'] = 'Randevu bulunamadı.';
            header('Location: ' . esh_url('Uhds', 'index'));
            exit;
        }

        $model = new Uhds();
        $model->completeTelehealthSession($id, $summary, $markDone);

        AuditLogHelper::log('uhds.video.complete', 'uhds', $id, (string) ($appointment->hastatckimlik ?? ''), [
            'summary_len' => strlen($summary),
            'duration_seconds' => $durationSeconds,
            'participant_count' => $participantCount,
        ]);

        $appointment->telehealth_summary = $summary !== '' ? $summary : ($appointment->telehealth_summary ?? null);
        $visitUrl = OperationalSettings::uhdsTelehealthAutoPromptVisit()
            ? UhdsTelehealthHelper::visitCreateUrl($appointment, $summary)
            : '';

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'visitCreateUrl' => $visitUrl,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['success'] = 'Görüntülü görüşme kaydı tamamlandı.';
        if ($visitUrl !== '') {
            $_SESSION['success'] .= ' İzlem kaydı oluşturabilirsiniz.';
            $_SESSION['uhds_visit_draft_link'] = $visitUrl;
            header('Location: ' . $visitUrl);
            exit;
        }
        header('Location: ' . $this->indexUrl(
            (int) date('Y', strtotime((string) $appointment->randevu_tarihi)),
            (int) date('n', strtotime((string) $appointment->randevu_tarihi)),
            (string) $appointment->randevu_tarihi,
            (string) $appointment->hastatckimlik
        ));
        exit;
    }

    /**
     * Yönetim panelinde Jitsi alan adını doğrulamak için hafif sağlık kontrolü.
     */
    public function jitsiDomainTest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id']) || !\App\Helpers\AuthHelper::sessionIsAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Yetki gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $domain = UhdsTelehealthHelper::jitsiDomain();
        $url = 'https://' . $domain . '/external_api.js';
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        $ok = is_string($body) && str_contains($body, 'JitsiMeetExternalAPI');
        echo json_encode([
            'ok' => $ok,
            'domain' => $domain,
            'url' => $url,
            'message' => $ok ? 'Jitsi domain erişilebilir.' : 'Jitsi domain testi başarısız.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Hasta / misafir görüntülü görüşme (jeton ile, girişsiz).
     */
    public function patientVideo(): void
    {
        if (!UhdsTelehealthHelper::isEnabled() || UhdsTelehealthHelper::provider() !== 'jitsi') {
            $this->renderPatientVideoError('Görüntülü görüşme hizmeti şu an kullanılamıyor.');
            return;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $claims = UhdsTelehealthHelper::verifyPatientJoinToken($token);
        if ($claims === null) {
            $this->renderPatientVideoError('Bağlantı geçersiz veya süresi dolmuş. Lütfen kurumunuzdan yeni davet isteyin.');
            return;
        }

        $id = IdHelper::normalizeRequestId($claims['id'] ?? null);
        if ($id === null) {
            $this->renderPatientVideoError('Bağlantı geçersiz veya süresi dolmuş. Lütfen kurumunuzdan yeni davet isteyin.');
            return;
        }
        $kr = new Uhds();
        if (!$kr->load($id)) {
            $this->renderPatientVideoError('Randevu bulunamadı.');
            return;
        }
        if ($claims['d'] !== '' && (string) $kr->randevu_tarihi !== $claims['d']) {
            $this->renderPatientVideoError('Randevu tarihi uyuşmuyor.');
            return;
        }

        $roomId = UhdsTelehealthHelper::ensureRoomId($kr);
        $kr->markVideoStarted($id);

        $patient = (new Patient())->findByTc((string) $kr->hastatckimlik);
        $displayName = $patient
            ? trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''))
            : 'Hasta';

        $eshGuestPageTitle = 'Görüntülü görüşme';
        $eshGuestScript = 'uhds-video';
        $uhdsAppointmentId = $id;
        $uhdsRoomId = $roomId;
        $uhdsStaffDisplayName = $displayName;
        $uhdsIsPatientJoin = true;
        $uhdsJitsiScriptUrl = UhdsTelehealthHelper::externalApiScriptUrl();
        $uhdsVideoConfigUrl = esh_url('Uhds', 'patientVideoConfig', ['token' => $token], true);
        $eshGuestInnerFile = ROOT_PATH . '/views/guest/uhds_video_inner.php';
        include ROOT_PATH . '/views/guest/uhds_video_shell.php';
    }

    /**
     * Hasta Jitsi yapılandırması (jeton ile).
     */
    public function patientVideoConfig(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!UhdsTelehealthHelper::isEnabled() || UhdsTelehealthHelper::provider() !== 'jitsi') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Kapalı'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $claims = UhdsTelehealthHelper::verifyPatientJoinToken($token);
        if ($claims === null) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz jeton'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = IdHelper::normalizeRequestId($claims['id'] ?? null);
        if ($id === null) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz jeton'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $kr = new Uhds();
        if (!$kr->load($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Randevu yok'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $roomId = UhdsTelehealthHelper::ensureRoomId($kr);
        $patient = (new Patient())->findByTc((string) $kr->hastatckimlik);
        $displayName = $patient
            ? trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''))
            : 'Hasta';
        $cfg = UhdsTelehealthHelper::jitsiClientConfig($roomId, $displayName, false);
        echo json_encode(['ok' => true] + $cfg, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function loadAppointmentForStaff(int|string|null $id, bool $redirectOnError = true): ?object
    {
        $rid = IdHelper::normalizeRequestId($id);
        if ($rid === null) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Geçersiz randevu.';
            }

            return null;
        }

        $kr = new Uhds();
        if (!$kr->load($rid)) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Randevu bulunamadı.';
            }

            return null;
        }

        $patient = (new Patient())->findByTc((string) $kr->hastatckimlik);
        if (!$patient || empty($patient->id)) {
            if ($redirectOnError) {
                $_SESSION['error'] = 'Hasta bulunamadı.';
                header('Location: ' . esh_url('Uhds', 'index'));
                exit;
            }

            return null;
        }
        PatientAccessHelper::requirePatientAccess((string) $patient->id, $patient);

        return $kr;
    }

    private function renderPatientVideoError(string $message): void
    {
        http_response_code(403);
        $eshGuestPageTitle = 'Görüntülü görüşme';
        $eshGuestError = $message;
        include ROOT_PATH . '/views/guest/uhds_video_error.php';
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
            $id = (string) ($row->id ?? '');
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
