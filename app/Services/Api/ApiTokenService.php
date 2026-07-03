<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiToken;
use App\Models\User;

/**
 * REST API token üretimi ve doğrulama.
 */
final class ApiTokenService
{
    private const TOKEN_PREFIX = 'esh_live_';

    public static function tableReady(): bool
    {
        return ApiToken::tableReady();
    }

    /**
     * @return array{ok: bool, token?: string, id?: int, message?: string}
     */
    public static function create(int $userId, string $label, string $scopes = 'read', ?string $expiresAt = null): array
    {
        if (!self::tableReady()) {
            return ['ok' => false, 'message' => 'API token tablosu kurulu değil.'];
        }
        if ($userId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz kullanıcı.'];
        }
        $user = new User();
        if (!$user->load($userId)) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $plain = self::TOKEN_PREFIX . bin2hex(random_bytes(24));
        $lookupPrefix = substr($plain, 0, 16);
        $hash = self::hashToken($plain);
        $label = trim($label);
        if ($label === '') {
            $label = 'API token ' . date('Y-m-d H:i');
        }
        $scopes = self::normalizeScopes($scopes);

        $model = new ApiToken();
        $model->bind([
            'user_id' => $userId,
            'kurum_id' => isset($user->kurum_id) ? (int) $user->kurum_id : null,
            'label' => $label,
            'token_prefix' => $lookupPrefix,
            'token_hash' => $hash,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);
        if (!$model->store()) {
            return ['ok' => false, 'message' => 'Token kaydedilemedi.'];
        }

        return [
            'ok' => true,
            'token' => $plain,
            'id' => (int) ($model->id ?? 0),
        ];
    }

    /**
     * @return array{ok: bool, token_row?: object, user?: object, message?: string}
     */
    public static function authenticate(string $bearerToken): array
    {
        if (!self::tableReady()) {
            return ['ok' => false, 'message' => 'API token tablosu kurulu değil.'];
        }
        $plain = trim($bearerToken);
        if (!str_starts_with($plain, self::TOKEN_PREFIX) || strlen($plain) < 32) {
            return ['ok' => false, 'message' => 'Geçersiz token biçimi.'];
        }

        $lookupPrefix = substr($plain, 0, 16);
        $hash = self::hashToken($plain);
        $row = (new ApiToken())->findByPrefixAndHash($lookupPrefix, $hash);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Token geçersiz veya iptal edilmiş.'];
        }

        if (!empty($row->expires_at)) {
            $exp = strtotime((string) $row->expires_at);
            if ($exp !== false && $exp < time()) {
                return ['ok' => false, 'message' => 'Token süresi dolmuş.'];
            }
        }

        $user = new User();
        $uid = (int) ($row->user_id ?? 0);
        if ($uid <= 0 || !$user->load($uid) || empty($user->activated)) {
            return ['ok' => false, 'message' => 'Token kullanıcısı aktif değil.'];
        }

        (new ApiToken())->touchLastUsed((int) $row->id);

        return ['ok' => true, 'token_row' => $row, 'user' => $user];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken . self::pepper());
    }

    public static function allowsScope(object $tokenRow, string $resource): bool
    {
        $raw = strtolower(trim((string) ($tokenRow->scopes ?? 'read')));
        if ($raw === '' || $raw === 'read' || $raw === '*') {
            return true;
        }
        $parts = array_filter(array_map('trim', explode(',', $raw)));

        return in_array($resource, $parts, true);
    }

    public static function allowsWrite(object $tokenRow, string $resource): bool
    {
        $raw = strtolower(trim((string) ($tokenRow->scopes ?? 'read')));
        if ($raw === '*' || $raw === 'write') {
            return true;
        }
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        if (in_array('write', $parts, true)) {
            return true;
        }
        if (in_array($resource . ':write', $parts, true)) {
            return true;
        }

        return false;
    }

    public static function normalizeScopes(string $scopes): string
    {
        $scopes = strtolower(trim($scopes));
        if ($scopes === '' || $scopes === 'read' || $scopes === '*') {
            return 'read';
        }
        $allowed = ['patients', 'visits', 'plans', 'write', 'patients:write', 'visits:write', 'plans:write'];
        $parts = [];
        foreach (explode(',', $scopes) as $p) {
            $p = trim($p);
            if ($p !== '' && in_array($p, $allowed, true)) {
                $parts[] = $p;
            }
        }

        return $parts !== [] ? implode(',', array_unique($parts)) : 'read';
    }

    private static function pepper(): string
    {
        if (defined('ESH_API_TOKEN_PEPPER') && (string) ESH_API_TOKEN_PEPPER !== '') {
            return (string) ESH_API_TOKEN_PEPPER;
        }
        $local = $GLOBALS['__esh_config_local']['api_token_pepper'] ?? '';
        if (is_string($local) && $local !== '') {
            return $local;
        }

        return hash('sha256', (defined('DB_NAME') ? (string) DB_NAME : 'esh') . '|' . (defined('ROOT_PATH') ? ROOT_PATH : ''));
    }
}
