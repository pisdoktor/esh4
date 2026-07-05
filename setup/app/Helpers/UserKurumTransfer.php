<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\Kurum;
use App\Models\User;

/**
 * Süper yönetici: personeli başka kuruma nakil.
 * Kaynak kurumda hesap pasifleştirilir ve kullanıcı adı arşivlenir (uk_username).
 * Hedef kurumda aynı kullanıcı adı, şifre ve profil bilgileriyle klon açılır; yetki isteğe bağlı değiştirilebilir.
 * Nöbet, izin, ekip vb. kayıtlar kaynak kullanıcı id'sinde kalır.
 */
final class UserKurumTransfer
{
    private const USERNAME_MAX_LEN = 64;

    public static function isArchivedAtSource(object $user): bool
    {
        if (!empty($user->activated)) {
            return false;
        }
        $username = trim((string) ($user->username ?? ''));

        return str_starts_with($username, '__nakil_');
    }

    /**
     * @param int|null $newIsadmin null = kaynak yetkisini kopyala
     */
    public static function validate(object $user, int $newKurumId, ?int $newIsadmin = null): ?string
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return 'Bu işlem yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), 'UTF-8')
                . ' tarafından yapılabilir.';
        }

        $level = AuthHelper::clampLevel((int) ($user->isadmin ?? 0));
        if (AuthHelper::isPlatformLevel($level)) {
            return 'Platform hesapları kuruma nakil edilemez.';
        }

        if (self::isArchivedAtSource($user)) {
            return 'Bu hesap zaten başka kuruma nakil edilmiş (arşiv).';
        }

        $newKurumId = (int) $newKurumId;
        if ($newKurumId <= 0) {
            return 'Geçerli bir kurum seçin.';
        }

        $kurum = new Kurum();
        if (!$kurum->load($newKurumId)) {
            return 'Seçilen kurum bulunamadı.';
        }
        if (empty($kurum->aktif)) {
            return 'Pasif kuruma personel taşınamaz.';
        }

        $currentKid = isset($user->kurum_id) ? (int) $user->kurum_id : 0;
        if ($currentKid <= 0) {
            return 'Kurumsuz hesap nakil edilemez.';
        }
        if ($currentKid === $newKurumId) {
            return null;
        }

        $originalUsername = trim((string) ($user->username ?? ''));
        if ($originalUsername === '') {
            return 'Kullanıcı adı olmayan hesap nakil edilemez.';
        }

        $userId = IdHelper::normalizeRequestId($user->id ?? null);
        if ($userId === null) {
            return 'Geçersiz kullanıcı kaydı.';
        }

        $db = Database::getInstance();
        $exists = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__users WHERE username = ? AND id <> ?',
            [$originalUsername, $userId]
        );
        if ($exists > 0) {
            return 'Hedef kurumda veya sistemde aynı kullanıcı adı zaten kullanılıyor.';
        }

        if ($newIsadmin !== null) {
            $newIsadmin = AuthHelper::clampLevel($newIsadmin);
            if ($newIsadmin >= AuthHelper::ROLE_SUPERADMIN && !AuthHelper::canAssignSuperAdminRole()) {
                return 'Platform yetkisi atama izniniz yok.';
            }
            if ($newIsadmin >= AuthHelper::ROLE_SUPERADMIN) {
                return 'Kurum naklinde platform yetkisi atanamaz.';
            }
        }

        return null;
    }

    /**
     * @param int|null $newIsadmin null = kaynak yetkisini kopyala
     * @return string|true|false string = hedef kurumdaki yeni kullanıcı id
     */
    public static function apply(object $user, int $newKurumId, ?int $newIsadmin = null): string|bool
    {
        $err = self::validate($user, $newKurumId, $newIsadmin);
        if ($err !== null) {
            return false;
        }

        $oldKurumId = (int) ($user->kurum_id ?? 0);
        $newKurumId = (int) $newKurumId;
        if ($oldKurumId === $newKurumId) {
            return true;
        }

        $userId = IdHelper::normalizeRequestId($user->id ?? null);
        $originalUsername = trim((string) ($user->username ?? ''));
        if ($userId === null || $originalUsername === '') {
            return false;
        }

        $targetLevel = $newIsadmin !== null
            ? AuthHelper::clampLevel($newIsadmin)
            : AuthHelper::clampLevel((int) ($user->isadmin ?? 0));

        $db = Database::getInstance();
        $newUserId = $db->transaction(static function (Database $db) use ($user, $userId, $newKurumId, $originalUsername, $targetLevel): string|false {
            $archivedUsername = self::archivedUsername($userId, $originalUsername);
            if (!$db->updatePrepared(
                '#__users',
                ['activated' => 0, 'username' => $archivedUsername],
                'id = ?',
                [$userId]
            )) {
                return false;
            }

            $cloneId = self::cloneUserAtKurum($user, $newKurumId, $originalUsername, $targetLevel);
            if ($cloneId === null) {
                return false;
            }

            return $cloneId;
        });

        return is_string($newUserId) && $newUserId !== '' ? $newUserId : false;
    }

    private static function cloneUserAtKurum(object $source, int $newKurumId, string $username, int $isadmin): ?string
    {
        $clone = new User();
        $data = get_object_vars($source);
        unset($data['id'], $data['db'], $data['_dirty']);
        $data['kurum_id'] = $newKurumId;
        $data['username'] = $username;
        $data['isadmin'] = $isadmin;
        $data['activated'] = 1;
        $data['nowvisit'] = null;
        $data['lastvisit'] = null;
        $clone->bind($data);
        if (!$clone->store()) {
            return null;
        }

        return IdHelper::normalizeRequestId($clone->id ?? null);
    }

    private static function archivedUsername(string $userId, string $original): string
    {
        $prefix = '__nakil_' . $userId . '__';
        $maxOrig = self::USERNAME_MAX_LEN - strlen($prefix);
        if ($maxOrig < 1) {
            return substr($prefix, 0, self::USERNAME_MAX_LEN);
        }
        if (strlen($prefix . $original) <= self::USERNAME_MAX_LEN) {
            return $prefix . $original;
        }

        return $prefix . substr($original, 0, $maxOrig);
    }
}
