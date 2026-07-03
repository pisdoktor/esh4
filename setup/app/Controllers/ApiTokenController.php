<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\ApiToken;
use App\Services\Api\ApiTokenService;

/**
 * REST API bearer token yönetimi (süper yönetici).
 */
class ApiTokenController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
    }

    public function index(): void
    {
        $tokens = (new ApiToken())->listActive();
        $newTokenPlain = $_SESSION['api_token_plain_once'] ?? null;
        unset($_SESSION['api_token_plain_once']);
        $pageTitle = 'REST API tokenları';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'api_token/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function store(): void
    {
        CsrfHelper::requirePostMethod(esh_url('ApiToken', 'index'));
        if (!ApiTokenService::tableReady()) {
            $_SESSION['error'] = 'API token tablosu kurulu değil.';
            header('Location: ' . esh_url('ApiToken', 'index'));
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $scopes = trim((string) ($_POST['scopes'] ?? 'read'));
        $expires = trim((string) ($_POST['expires_at'] ?? ''));
        $expiresAt = null;
        if ($expires !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) {
            $expiresAt = $expires . ' 23:59:59';
        }

        $result = ApiTokenService::create($userId, $label, $scopes, $expiresAt);
        if (empty($result['ok'])) {
            $_SESSION['error'] = (string) ($result['message'] ?? 'Token oluşturulamadı.');
            header('Location: ' . esh_url('ApiToken', 'index'));
            exit;
        }

        $_SESSION['api_token_plain_once'] = (string) ($result['token'] ?? '');
        $_SESSION['success'] = 'API token oluşturuldu. Anahtarı yalnızca bir kez gösterilir — kopyalayın.';
        header('Location: ' . esh_url('ApiToken', 'index'));
        exit;
    }

    public function revoke(): void
    {
        CsrfHelper::requirePostMethod(esh_url('ApiToken', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && (new ApiToken())->revoke($id)) {
            $_SESSION['success'] = 'Token iptal edildi.';
        } else {
            $_SESSION['error'] = 'Token iptal edilemedi.';
        }
        header('Location: ' . esh_url('ApiToken', 'index'));
        exit;
    }
}
