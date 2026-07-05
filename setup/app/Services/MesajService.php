<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\RateLimitHelper;
use App\Helpers\TenantContext;
use App\Models\Mesaj;
use App\Models\MesajKonusma;
use App\Models\User;
use RuntimeException;

/**
 * Mesajlaşma iş mantığı — yetki, DM, hasta konuşması, duyuru.
 */
final class MesajService
{
    private MesajKonusma $konusmaModel;
    private Mesaj $mesajModel;

    public function __construct()
    {
        $this->konusmaModel = new MesajKonusma();
        $this->mesajModel = new Mesaj();
    }

    public static function moduleReady(): bool
    {
        return MesajKonusma::tableReady();
    }

    public static function isActiveUser(string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($userId)) {
            return false;
        }
        $u = new User();

        return $u->load($userId) && (int) ($u->activated ?? 0) === 1;
    }

    public static function canUseMessaging(string $userId): bool
    {
        if (!AppSettings::isModuleEnabled('mesajlasma')
            || !self::moduleReady()
            || !self::isActiveUser($userId)) {
            return false;
        }
        $u = new User();
        if (!$u->load($userId)) {
            return false;
        }

        return (int) ($u->isadmin ?? 0) >= AuthHelper::ROLE_ADMIN;
    }

    /**
     * @throws RuntimeException
     */
    public function assertActiveUser(string $userId): void
    {
        if (!self::isActiveUser($userId)) {
            throw new RuntimeException('Pasif hesaplar mesajlaşmayı kullanamaz.');
        }
    }

    /**
     * @throws RuntimeException
     */
    public function assertCanAccessConversation(string $userId, string $konusmaId): object
    {
        if (IdHelper::isEmptyEntityId($userId) || IdHelper::isEmptyEntityId($konusmaId)) {
            throw new RuntimeException('Geçersiz konuşma.');
        }
        $this->assertActiveUser($userId);
        $row = $this->konusmaModel->findById($konusmaId);
        if (!$row) {
            throw new RuntimeException('Konuşma bulunamadı.');
        }

        if ($row->tip === 'patient') {
            $hastaId = (string) ($row->hasta_id ?? '');
            if (!PatientAccessHelper::canAccessPatient($hastaId)) {
                throw new RuntimeException('Bu hasta konuşmasına erişim yetkiniz yok.');
            }
            $this->konusmaModel->ensureImplicitMember($row, $userId);
            $this->konusmaModel->addMember($konusmaId, $userId);

            return $row;
        }

        if (!$this->konusmaModel->isMember($konusmaId, $userId)) {
            if ($this->konusmaModel->ensureImplicitMember($row, $userId)) {
                return $row;
            }
            throw new RuntimeException('Bu konuşmaya erişim yetkiniz yok.');
        }

        return $row;
    }

    public function moveToTrash(string $konusmaId, string $userId): array
    {
        try {
            $this->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }
        if (!$this->konusmaModel->moveToTrash($konusmaId, $userId)) {
            return ['ok' => false, 'mesaj' => 'Konuşma çöp kutusuna taşınamadı.'];
        }

        return ['ok' => true, 'unread_total' => $this->countUnread($userId)];
    }

    public function restoreFromTrash(string $konusmaId, string $userId): array
    {
        try {
            $this->assertActiveUser($userId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }
        if (!$this->konusmaModel->isMember($konusmaId, $userId)) {
            return ['ok' => false, 'mesaj' => 'Konuşma bulunamadı.'];
        }
        if (!$this->konusmaModel->restoreFromTrash($konusmaId, $userId)) {
            return ['ok' => false, 'mesaj' => 'Konuşma geri yüklenemedi.'];
        }

        return ['ok' => true];
    }

    public function purgeFromTrash(string $konusmaId, string $userId): array
    {
        try {
            $this->assertActiveUser($userId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }
        if (!$this->konusmaModel->isTrashedForUser($konusmaId, $userId)) {
            return ['ok' => false, 'mesaj' => 'Yalnızca çöp kutusundaki konuşmalar kalıcı silinebilir.'];
        }
        if (!$this->konusmaModel->purgeFromTrash($konusmaId, $userId)) {
            return ['ok' => false, 'mesaj' => 'Konuşma silinemedi.'];
        }

        return ['ok' => true, 'unread_total' => $this->countUnread($userId)];
    }

    public function isTrashedForUser(string $konusmaId, string $userId): bool
    {
        return $this->konusmaModel->isTrashedForUser($konusmaId, $userId);
    }

    public function countUnread(string $userId): int
    {
        return Mesaj::countUnreadForUser($userId);
    }

    public function markRead(string $konusmaId, string $userId, string $lastMessageId): void
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || IdHelper::isEmptyEntityId($lastMessageId)) {
            return;
        }
        $this->assertCanAccessConversation($userId, $konusmaId);
        $member = $this->konusmaModel->getMemberRow($konusmaId, $userId);
        if (!$member) {
            return;
        }
        $current = IdHelper::normalizeRequestId($member->son_okunan_mesaj_id ?? null);
        if ($current !== null && !IdHelper::isEmptyEntityId($lastMessageId) && strcmp($lastMessageId, $current) <= 0) {
            return;
        }
        $this->konusmaModel->db->executePrepared(
            'UPDATE #__mesaj_konusma_uyeler SET son_okunan_mesaj_id = ? WHERE konusma_id = ? AND user_id = ?',
            [$lastMessageId, $konusmaId, $userId]
        );
    }

    /**
     * @return array{ok:bool, mesaj_id?:int, mesaj?:string}
     */
    public function sendMessage(string $konusmaId, string $userId, string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return ['ok' => false, 'mesaj' => 'Mesaj boş olamaz.'];
        }
        if (mb_strlen($body) > Mesaj::MAX_BODY_LEN) {
            return ['ok' => false, 'mesaj' => 'Mesaj çok uzun (en fazla ' . Mesaj::MAX_BODY_LEN . ' karakter).'];
        }

        $rateKey = 'user_' . $userId;
        if (RateLimitHelper::tooManyAttempts('mesaj_send', $rateKey, 10, 60)) {
            return ['ok' => false, 'mesaj' => 'Çok hızlı mesaj gönderiyorsunuz. Lütfen bir dakika bekleyin.'];
        }

        try {
            $konusma = $this->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }

        if ($konusma->tip === 'dm') {
            $otherId = $this->dmOtherUserId($konusma, $userId);
            if ($otherId !== '' && $otherId !== null && !self::isActiveUser($otherId)) {
                return ['ok' => false, 'mesaj' => 'Pasif kullanıcıya mesaj gönderilemez.'];
            }
        }

        RateLimitHelper::hit('mesaj_send', $rateKey, 60);

        $mesajId = $this->mesajModel->insertMessage($konusmaId, $userId, 'user', $body);
        if ($mesajId === null) {
            return ['ok' => false, 'mesaj' => 'Mesaj kaydedilemedi.'];
        }

        $this->konusmaModel->touchLastMessage($konusmaId);
        $this->konusmaModel->restoreIfTrashed($konusmaId, $userId);

        if ($konusma->tip === 'dm') {
            $otherId = $this->dmOtherUserId($konusma, $userId);
            if ($otherId !== '' && $otherId !== null && self::isActiveUser($otherId)) {
                $this->konusmaModel->addMember($konusmaId, $otherId);
            }
        }

        return ['ok' => true, 'mesaj_id' => $mesajId];
    }

    /**
     * @throws RuntimeException
     */
    public function getOrCreateDm(string $userA, string $userB): string
    {
        if (IdHelper::isEmptyEntityId($userA) || IdHelper::isEmptyEntityId($userB)) {
            throw new RuntimeException('Geçersiz kullanıcı.');
        }
        if (IdHelper::idsMatch($userA, $userB)) {
            throw new RuntimeException('Kendinize mesaj gönderemezsiniz.');
        }

        $this->assertUsersCanDm($userA, $userB);

        $existing = $this->konusmaModel->findDmPair($userA, $userB);
        if ($existing) {
            $kid = (string) $existing->id;
            $this->konusmaModel->addMember($kid, $userA);
            $this->konusmaModel->addMember($kid, $userB);

            return $kid;
        }

        $low = strcmp($userA, $userB) <= 0 ? $userA : $userB;
        $high = strcmp($userA, $userB) <= 0 ? $userB : $userA;
        $kurumId = $this->resolveSharedKurumId($userA, $userB);

        $uLow = new User();
        $uHigh = new User();
        $title = 'Özel mesaj';
        if ($uLow->load($low) && $uHigh->load($high)) {
            $title = trim((string) $uLow->name) . ' — ' . trim((string) $uHigh->name);
        }

        $id = $this->konusmaModel->db->insertPrepared('#__mesaj_konusmalar', [
            'id' => IdHelper::generateUuidV4(),
            'tip' => 'dm',
            'kurum_id' => $kurumId,
            'dm_kucuk_id' => $low,
            'dm_buyuk_id' => $high,
            'baslik' => mb_substr($title, 0, 255),
            'olusturan_id' => $userA,
        ]);
        if ($id === false) {
            throw new RuntimeException('Konuşma oluşturulamadı.');
        }
        $kid = (string) $id;
        $this->konusmaModel->addMember($kid, $userA);
        $this->konusmaModel->addMember($kid, $userB);

        return $kid;
    }

    /**
     * @throws RuntimeException
     */
    public function getOrCreatePatientThread(string $hastaId, string $userId): string
    {
        if (IdHelper::isEmptyEntityId($hastaId) || IdHelper::isEmptyEntityId($userId)) {
            throw new RuntimeException('Geçersiz hasta.');
        }
        $this->assertActiveUser($userId);
        if (!self::canUseMessaging($userId)) {
            throw new RuntimeException('Mesajlaşma yalnızca yöneticiler içindir.');
        }
        $patient = PatientAccessHelper::requirePatientAccess($hastaId);
        $kurumId = isset($patient->kurum_id) ? (int) $patient->kurum_id : 0;
        if ($kurumId <= 0) {
            $kurumId = (int) (TenantContext::sessionKurumId() ?? 1);
        }

        $existing = $this->konusmaModel->findByHastaId($hastaId);
        if ($existing) {
            $kid = (string) $existing->id;
            $this->konusmaModel->addMember($kid, $userId);

            return $kid;
        }

        $ad = trim((string) ($patient->ad ?? '') . ' ' . (string) ($patient->soyad ?? ''));
        $title = $ad !== '' ? 'Hasta: ' . $ad : 'Hasta #' . $hastaId;

        $id = $this->konusmaModel->db->insertPrepared('#__mesaj_konusmalar', [
            'id' => IdHelper::generateUuidV4(),
            'tip' => 'patient',
            'kurum_id' => $kurumId,
            'hasta_id' => $hastaId,
            'baslik' => mb_substr($title, 0, 255),
            'olusturan_id' => $userId,
        ]);
        if ($id === false) {
            throw new RuntimeException('Hasta konuşması oluşturulamadı.');
        }
        $kid = (string) $id;
        $this->konusmaModel->addMember($kid, $userId);

        return $kid;
    }

    /**
     * @param list<string> $recipientUserIds
     * @return array{ok:bool, konusma_id?:string, mesaj?:string}
     */
    public function sendSystemBroadcast(string $adminId, string $title, string $body, array $recipientUserIds): array
    {
        if (!AuthHelper::sessionIsPlatformOwner()) {
            return ['ok' => false, 'mesaj' => 'Yalnızca ' . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8') . ' sistem duyurusu gönderebilir.'];
        }
        if (!self::isActiveUser($adminId)) {
            return ['ok' => false, 'mesaj' => 'Pasif hesaplar mesajlaşmayı kullanamaz.'];
        }
        $title = trim($title);
        $body = trim($body);
        if ($title === '' || $body === '') {
            return ['ok' => false, 'mesaj' => 'Başlık ve mesaj zorunludur.'];
        }

        $recipients = array_values(array_unique(array_filter(array_map(
            static fn ($raw) => IdHelper::normalizeRequestId($raw),
            $recipientUserIds
        ))));
        $recipients = array_filter($recipients, static fn (?string $id): bool => $id !== null && !IdHelper::idsMatch($id, $adminId));
        if ($recipients === []) {
            return ['ok' => false, 'mesaj' => 'En az bir alıcı seçin.'];
        }

        try {
            $kurumId = TenantContext::requireKurumScope();
        } catch (\Throwable) {
            $kurumId = (int) (TenantContext::sessionKurumFilter() ?? TenantContext::sessionKurumId() ?? 1);
        }
        foreach ($recipients as $rid) {
            if (!self::isActiveUser($rid)) {
                return ['ok' => false, 'mesaj' => 'Pasif kullanıcılar duyuru alamaz.'];
            }
            if (!self::userIsAdminOrAbove($rid)) {
                return ['ok' => false, 'mesaj' => 'Duyurular yalnızca yöneticilere gönderilebilir.'];
            }
        }

        $id = $this->konusmaModel->db->insertPrepared('#__mesaj_konusmalar', [
            'id' => IdHelper::generateUuidV4(),
            'tip' => 'system',
            'kurum_id' => $kurumId,
            'baslik' => mb_substr($title, 0, 255),
            'olusturan_id' => $adminId,
        ]);
        if ($id === false) {
            return ['ok' => false, 'mesaj' => 'Duyuru oluşturulamadı.'];
        }
        $konusmaId = (string) $id;

        $this->konusmaModel->addMember($konusmaId, $adminId);
        foreach ($recipients as $rid) {
            $this->konusmaModel->addMember($konusmaId, $rid);
        }

        $mesajId = $this->mesajModel->insertMessage($konusmaId, null, 'system', $body);
        if ($mesajId === null) {
            return ['ok' => false, 'mesaj' => 'Duyuru mesajı kaydedilemedi.'];
        }
        $this->konusmaModel->touchLastMessage($konusmaId);
        $this->markRead($konusmaId, $adminId, $mesajId);

        return ['ok' => true, 'konusma_id' => $konusmaId];
    }

    /**
     * @return list<object>
     */
    public function getDmCandidates(string $currentUserId): array
    {
        if (IdHelper::isEmptyEntityId($currentUserId) || !self::canUseMessaging($currentUserId)) {
            return [];
        }
        $userModel = new User();
        $list = $userModel->getMessagingAdminList();
        $out = [];
        foreach ($list as $u) {
            $uid = (string) ($u->id ?? '');
            if ($uid === '' || IdHelper::idsMatch($uid, $currentUserId)) {
                continue;
            }
            try {
                $this->assertUsersCanDm($currentUserId, $uid);
                $out[] = $u;
            } catch (RuntimeException) {
                continue;
            }
        }

        return $out;
    }

    public function conversationDisplayTitle(object $konusma, string $viewerId): string
    {
        $tip = (string) ($konusma->tip ?? '');
        if ($tip === 'patient' || $tip === 'system') {
            return (string) ($konusma->baslik ?? 'Konuşma');
        }
        if ($tip === 'dm') {
            $low = (string) ($konusma->dm_kucuk_id ?? '');
            $high = (string) ($konusma->dm_buyuk_id ?? '');
            $otherId = $viewerId === $low ? $high : ($viewerId === $high ? $low : '');
            if ($otherId !== '') {
                $u = new User();
                if ($u->load($otherId)) {
                    return (string) ($u->name ?? 'Kullanıcı');
                }
            }
        }

        return (string) ($konusma->baslik ?? 'Konuşma');
    }

    private function dmOtherUserId(object $konusma, string $viewerId): ?string
    {
        $low = (string) ($konusma->dm_kucuk_id ?? '');
        $high = (string) ($konusma->dm_buyuk_id ?? '');
        if ($viewerId === $low) {
            return $high !== '' ? $high : null;
        }
        if ($viewerId === $high) {
            return $low !== '' ? $low : null;
        }

        return null;
    }

    /**
     * @throws RuntimeException
     */
    private function assertUsersCanDm(string $userA, string $userB): void
    {
        $ua = new User();
        $ub = new User();
        if (!$ua->load($userA) || !$ub->load($userB)) {
            throw new RuntimeException('Kullanıcı bulunamadı.');
        }
        if ((int) ($ua->activated ?? 0) !== 1 || (int) ($ub->activated ?? 0) !== 1) {
            throw new RuntimeException('Pasif kullanıcılar mesaj alamaz veya gönderemez.');
        }
        if ((int) ($ua->isadmin ?? 0) < AuthHelper::ROLE_ADMIN
            || (int) ($ub->isadmin ?? 0) < AuthHelper::ROLE_ADMIN) {
            throw new RuntimeException('Mesajlaşma yalnızca yöneticiler arasında yapılabilir.');
        }
    }

    private function resolveSharedKurumId(string $userA, string $userB): int
    {
        $ua = new User();
        $ub = new User();
        $ua->load($userA);
        $ub->load($userB);
        $kurumA = (int) ($ua->kurum_id ?? 0);
        $kurumB = (int) ($ub->kurum_id ?? 0);
        if ($kurumA > 0) {
            return $kurumA;
        }
        if ($kurumB > 0) {
            return $kurumB;
        }

        return (int) (TenantContext::sessionKurumId() ?? 1);
    }

    private static function userIsAdminOrAbove(string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($userId) || !self::isActiveUser($userId)) {
            return false;
        }
        $u = new User();

        return $u->load($userId) && (int) ($u->isadmin ?? 0) >= AuthHelper::ROLE_ADMIN;
    }

    /**
     * Saha ekibine koordinasyon mesajı — mevcut DM altyapısı ile.
     *
     * @param list<int> $recipientUserIds
     * @return array{ok:bool,sent:int,error?:string}
     */
    public function notifyFieldTeam(int|string $senderUserId, array $recipientUserIds, string $subject, string $body): array
    {
        $senderUserId = IdHelper::normalizeRequestId($senderUserId);
        if ($senderUserId === null || !self::canUseMessaging($senderUserId)) {
            return ['ok' => false, 'sent' => 0, 'error' => 'Mesajlaşma kullanılamıyor.'];
        }
        $subject = trim($subject);
        $body = trim($body);
        if ($body === '') {
            return ['ok' => false, 'sent' => 0, 'error' => 'Mesaj gövdesi boş.'];
        }
        if (strlen($subject) > 120) {
            $subject = substr($subject, 0, 120);
        }
        $sent = 0;
        foreach ($recipientUserIds as $rid) {
            $rid = IdHelper::normalizeRequestId($rid);
            if ($rid === null || $rid === $senderUserId || !self::canUseMessaging($rid)) {
                continue;
            }
            try {
                $konusmaId = $this->getOrCreateDm($senderUserId, $rid);
                $text = ($subject !== '' ? '[' . $subject . '] ' : '') . $body;
                $this->sendMessage($konusmaId, $senderUserId, $text);
                $sent++;
            } catch (\Throwable) {
                continue;
            }
        }

        return ['ok' => $sent > 0, 'sent' => $sent];
    }
}
