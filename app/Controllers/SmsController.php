<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\SmsSettings;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\SmsGonderim;
use App\Models\SmsSablon;
use App\Services\Sms\SmsService;

class SmsController
{
    private SmsService $service;

    public function __construct()
    {
        $this->service = new SmsService();
    }

    private function ensureModule(): void
    {
        if (!AppSettings::isModuleEnabled('sms_bildirim') || !SmsService::moduleReady()) {
            $_SESSION['error'] = 'SMS bildirim modülü kapalı veya kurulumu tamamlanmamış.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        if (!SmsService::canUseSms((int) ($_SESSION['user_id'] ?? 0))) {
            $_SESSION['error'] = 'SMS modülüne yalnızca yönetici ve süper yönetici erişebilir.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    private function jsonOut(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function kurumId(): int
    {
        return TenantContext::assignKurumIdForStore();
    }

    public function index(): void
    {
        $this->ensureModule();
        $kurumFilter = TenantContext::sessionKurumFilter();
        $kurumId = $kurumFilter > 0 ? $kurumFilter : null;
        $rows = (new SmsGonderim())->listRecent(50, $kurumId);
        $pageTitle = 'SMS Bildirimleri';
        $composeUrl = esh_url('Sms', 'compose');
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'sms/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function compose(): void
    {
        $this->ensureModule();
        $kurumId = $this->kurumId();
        $sablonlar = (new SmsSablon())->listForKurum($kurumId > 0 ? $kurumId : null);
        $defaultRoles = SmsSettings::defaultRoles();
        $segments = SmsSettings::SEGMENTS;
        $hastaId = (int) ($_GET['hasta_id'] ?? 0);
        $presetSegment = trim((string) ($_GET['segment'] ?? ''));
        $presetTarih = trim((string) ($_GET['tarih'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $presetTarih)) {
            $presetTarih = date('Y-m-d');
        }
        if ($hastaId > 0) {
            $presetSegment = 'tek_hasta';
        }
        $smsSablonMap = self::sablonMapForJs($sablonlar);
        $pageTitle = 'SMS Gönder';
        $previewUrl = esh_url('Sms', 'previewRecipients');
        $sendUrl = esh_url('Sms', 'send');
        $templatesUrl = esh_url('Sms', 'templates');
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'sms/compose');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function templates(): void
    {
        $this->ensureModule();
        $kurumId = $this->kurumId();
        $sablonlar = (new SmsSablon())->listForKurum($kurumId > 0 ? $kurumId : null, false);
        $smsSablonMap = self::sablonMapForJs($sablonlar);
        $pageTitle = 'SMS Şablonları';
        $saveTemplateUrl = esh_url('Sms', 'saveTemplate');
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'sms/templates');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveTemplate(): void
    {
        $this->ensureModule();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateRequest()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Sms', 'templates'));
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $baslik = self::normalizeUtf8Text(trim((string) ($_POST['baslik'] ?? '')));
        $govde = self::normalizeUtf8Text(trim((string) ($_POST['govde'] ?? '')));
        $kod = self::normalizeUtf8Text(trim((string) ($_POST['kod'] ?? '')));
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        if ($baslik === '' || $govde === '') {
            $_SESSION['error'] = 'Başlık ve mesaj zorunlu.';
            header('Location: ' . esh_url('Sms', 'templates'));
            exit;
        }
        $model = new SmsSablon();
        if ($id > 0) {
            $model->bind([
                'id' => $id,
                'baslik' => $baslik,
                'govde' => $govde,
                'kod' => $kod,
                'aktif' => $aktif,
            ]);
            $model->store();
            $_SESSION['success'] = 'Şablon güncellendi.';
        } else {
            $kid = $this->kurumId();
            $model->bind([
                'kurum_id' => $kid > 0 ? $kid : null,
                'baslik' => $baslik,
                'govde' => $govde,
                'kod' => $kod !== '' ? $kod : 'ozel_' . time(),
                'aktif' => $aktif,
            ]);
            $model->store();
            $_SESSION['success'] = 'Şablon eklendi.';
        }
        header('Location: ' . esh_url('Sms', 'templates'));
        exit;
    }

    public function previewRecipients(): void
    {
        $this->ensureModule();
        $input = $this->readJsonBody();
        $segment = trim((string) ($input['segment'] ?? 'tek_hasta'));
        $params = is_array($input['params'] ?? null) ? $input['params'] : [];
        $body = trim((string) ($input['govde'] ?? ''));
        $roles = is_array($input['roles'] ?? null) ? $input['roles'] : SmsSettings::defaultRoles();
        $result = $this->service->preview($segment, $params, $body, $roles);
        $this->jsonOut($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function send(): void
    {
        $this->ensureModule();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateRequest()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Sms', 'compose'));
            exit;
        }
        $segment = trim((string) ($_POST['segment'] ?? 'tek_hasta'));
        $govde = self::normalizeUtf8Text(trim((string) ($_POST['govde'] ?? '')));
        $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : SmsSettings::defaultRoles();
        $sablonId = (int) ($_POST['sablon_id'] ?? 0);
        $params = $this->segmentParamsFromPost($segment, $_POST);
        $kurumId = $this->kurumId();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $result = $this->service->sendBatch(
            $kurumId,
            $userId,
            $segment,
            $params,
            $govde,
            $roles,
            $sablonId > 0 ? $sablonId : null
        );
        if ($result['ok'] ?? false) {
            $_SESSION['success'] = (string) ($result['mesaj'] ?? 'SMS gönderildi.');
            $gid = (int) ($result['gonderim_id'] ?? 0);
            header('Location: ' . esh_url('Sms', 'historyDetail', $gid > 0 ? ['id' => $gid] : []));
            exit;
        }
        $_SESSION['error'] = (string) ($result['mesaj'] ?? 'Gönderim başarısız.');
        header('Location: ' . esh_url('Sms', 'compose'));
        exit;
    }

    public function history(): void
    {
        $this->index();
    }

    public function historyDetail(): void
    {
        $this->ensureModule();
        $id = (int) ($_GET['id'] ?? 0);
        $gonderim = (new SmsGonderim())->findById($id);
        if (!$gonderim) {
            $_SESSION['error'] = 'Gönderim bulunamadı.';
            header('Location: ' . esh_url('Sms', 'index'));
            exit;
        }
        $alicilar = (new \App\Models\SmsAlici())->listByGonderim($id);
        $pageTitle = 'SMS Gönderim #' . $id;
        $backUrl = esh_url('Sms', 'index');
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'sms/history_detail');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function testConnection(): void
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Yalnızca platform yöneticisi'], 403);
        }
        if (!AppSettings::isModuleEnabled('sms_bildirim')) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Modül kapalı'], 400);
        }
        $result = $this->service->testConnection();
        $this->jsonOut($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function quickFromPatient(): void
    {
        $this->ensureModule();
        $hastaId = (int) ($_GET['hasta_id'] ?? 0);
        $url = esh_url('Sms', 'compose');
        if ($hastaId > 0) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'hasta_id=' . $hastaId;
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * @param list<object> $sablonlar
     * @return array<int, array{id:int,baslik:string,kod:string,govde:string,aktif:int}>
     */
    private static function sablonMapForJs(array $sablonlar): array
    {
        $out = [];
        foreach ($sablonlar as $s) {
            $id = (int) ($s->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $out[$id] = [
                'id' => $id,
                'baslik' => self::normalizeUtf8Text((string) ($s->baslik ?? '')),
                'kod' => self::normalizeUtf8Text((string) ($s->kod ?? '')),
                'govde' => self::normalizeUtf8Text((string) ($s->govde ?? '')),
                'aktif' => (int) ($s->aktif ?? 0),
            ];
        }

        return $out;
    }

    private static function normalizeUtf8Text(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        $from1254 = @iconv('Windows-1254', 'UTF-8//IGNORE', $text);
        if (is_string($from1254) && $from1254 !== '' && mb_check_encoding($from1254, 'UTF-8')) {
            return $from1254;
        }
        $fromLatin5 = @iconv('ISO-8859-9', 'UTF-8//IGNORE', $text);
        if (is_string($fromLatin5) && $fromLatin5 !== '' && mb_check_encoding($fromLatin5, 'UTF-8')) {
            return $fromLatin5;
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return $_POST;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function segmentParamsFromPost(string $segment, array $post): array
    {
        return match ($segment) {
            'tek_hasta' => ['hasta_id' => (int) ($post['hasta_id'] ?? 0)],
            'coklu_hasta' => ['hasta_ids' => array_map('intval', (array) ($post['hasta_ids'] ?? []))],
            'gunun_plani', 'planli_izlem', 'ilk_ziyaret' => [
                'tarih' => trim((string) ($post['tarih'] ?? date('Y-m-d'))),
                'zaman' => (int) ($post['zaman'] ?? 0),
            ],
            'pansuman_liste' => [
                'gun' => trim((string) ($post['pansuman_gun'] ?? '')),
                'arama' => trim((string) ($post['arama'] ?? '')),
            ],
            'sonda_yaklasan' => ['gun_araligi' => (int) ($post['gun_araligi'] ?? 7)],
            default => [],
        };
    }
}
