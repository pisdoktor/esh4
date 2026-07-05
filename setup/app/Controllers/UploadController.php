<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\TenantContext;
use App\Models\User;

/**
 * Yüklenen dosyalar — oturumlu proxy (public/uploads doğrudan erişime kapalı).
 */
class UploadController
{
    /** @var list<string> */
    private const ALLOWED_TYPES = ['patients', 'wounds', 'profile', 'temp'];

    /** @var array<string, string> */
    private const MIME_BY_EXT = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public function serve(): void
    {
        if (IdHelper::isEmptyEntityId(AuthHelper::sessionUserId())) {
            http_response_code(403);
            exit;
        }

        $type = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : '';
        $file = isset($_GET['file']) ? basename((string) $_GET['file']) : '';

        if (!in_array($type, self::ALLOWED_TYPES, true) || $file === '' || $file === '.' || $file === '..') {
            http_response_code(404);
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $file)) {
            http_response_code(404);
            exit;
        }

        if (!$this->authorize($type, $file)) {
            http_response_code(403);
            exit;
        }

        $dir = rtrim((string) UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $type;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        $realDir = realpath($dir);
        $realPath = is_file($path) ? realpath($path) : false;
        if ($realDir === false || $realPath === false || strpos($realPath, $realDir) !== 0) {
            http_response_code(404);
            exit;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = self::MIME_BY_EXT[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realPath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($realPath);
        exit;
    }

    private function authorize(string $type, string $file): bool
    {
        return match ($type) {
            'patients' => $this->authorizePatientPhoto($file),
            'wounds' => $this->authorizeWoundPhoto($file),
            'profile' => $this->authorizeProfilePhoto($file),
            'temp' => $this->authorizeTempPhoto($file),
            default => false,
        };
    }

    private function authorizePatientPhoto(string $file): bool
    {
        $db = Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT id FROM #__hastalar WHERE profil_foto = :f LIMIT 1',
            [':f' => $file]
        );
        if (!$row || IdHelper::isEmptyEntityId($row->id ?? null)) {
            return false;
        }

        return PatientAccessHelper::canAccessPatient((string) $row->id);
    }

    private function authorizeWoundPhoto(string $file): bool
    {
        $db = Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT wf.hasta_id, h.basiyarasi
             FROM #__hasta_yara_fotolar wf
             INNER JOIN #__hastalar h ON h.id = wf.hasta_id
             WHERE wf.dosya_adi = :f
             LIMIT 1',
            [':f' => $file]
        );
        if (!$row || IdHelper::isEmptyEntityId($row->hasta_id ?? null)) {
            return false;
        }
        if ((int) ($row->basiyarasi ?? 0) !== 1) {
            return false;
        }

        return PatientAccessHelper::canAccessPatient((string) $row->hasta_id);
    }

    private function authorizeProfilePhoto(string $file): bool
    {
        if (in_array(strtolower($file), User::defaultProfileImageNames(), true)) {
            return true;
        }

        $db = Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT id, kurum_id FROM #__users
             WHERE image = :f OR image LIKE :suffix OR image LIKE :suffix2
             LIMIT 1',
            [
                ':f' => $file,
                ':suffix' => '%/' . $file,
                ':suffix2' => '%\\' . $file,
            ]
        );
        if (!$row || IdHelper::isEmptyEntityId($row->id ?? null)) {
            return false;
        }

        $viewerId = AuthHelper::sessionUserId();
        if (IdHelper::idsMatch($viewerId, $row->id)) {
            return true;
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            return true;
        }

        $viewerKurum = TenantContext::sessionKurumId();
        if ($viewerKurum !== null && (int) ($row->kurum_id ?? 0) === $viewerKurum) {
            return true;
        }

        return false;
    }

    private function authorizeTempPhoto(string $file): bool
    {
        $path = $_SESSION['temp_image_path'] ?? null;
        if (is_string($path) && $path !== '' && basename($path) === $file) {
            return true;
        }

        $web = (string) ($_SESSION['temp_image'] ?? '');
        if ($web !== '') {
            $webBase = basename(parse_url($web, PHP_URL_PATH) ?: '');
            if ($webBase === $file) {
                return true;
            }
        }

        return false;
    }
}
