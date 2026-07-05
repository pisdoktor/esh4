<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Mesaj;
use App\Models\User;
use App\Services\MesajService;
use RuntimeException;

class MesajController
{
    private MesajService $service;
    private Mesaj $mesajModel;

    public function __construct()
    {
        $this->service = new MesajService();
        $this->mesajModel = new Mesaj();
    }

    private function ensureModule(): void
    {
        if (!AppSettings::isModuleEnabled('mesajlasma') || !MesajService::moduleReady()) {
            $_SESSION['error'] = 'Mesajlaşma modülü kapalı veya kurulumu tamamlanmamış.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }

        $userId = $this->currentUserId();
        if ($userId !== null && !MesajService::canUseMessaging($userId)) {
            $message = MesajService::isActiveUser($userId)
                ? 'Mesajlaşma yalnızca yöneticiler içindir.'
                : 'Pasif hesaplar mesajlaşmayı kullanamaz.';
            if (\App\Helpers\CsrfHelper::isJsonClientRequest()) {
                $this->jsonOut(['ok' => false, 'mesaj' => $message], 403);
            }
            $_SESSION['error'] = $message;
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    private function currentUserId(): ?string
    {
        return AuthHelper::sessionUserId();
    }

    private function jsonOut(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param list<object> $messages
     */
    private function maxMessageIdFromList(array $messages, ?string $floorId = null): ?string
    {
        $lastId = $floorId;
        foreach ($messages as $m) {
            $mid = isset($m->id) ? (string) $m->id : '';
            if ($mid === '') {
                continue;
            }
            if ($lastId === null || strcmp($mid, (string) $lastId) > 0) {
                $lastId = $mid;
            }
        }

        return $lastId;
    }

    private function normalizeMailbox(string $raw): string
    {
        $raw = strtolower(trim($raw));
        if (in_array($raw, ['sent', 'giden', 'outbox'], true)) {
            return 'sent';
        }
        if (in_array($raw, ['trash', 'cop', 'çöp'], true)) {
            return 'trash';
        }

        return 'inbox';
    }

    private function renderMailbox(string $mailbox): void
    {
        $this->ensureModule();
        $mailbox = $this->normalizeMailbox($mailbox);
        $titles = [
            'inbox' => 'Gelen Kutusu',
            'sent' => 'Giden Kutusu',
            'trash' => 'Çöp Kutusu',
        ];
        $descriptions = [
            'inbox' => 'Özel mesajlar, hasta konuşmaları ve sistem duyuruları',
            'sent' => 'Gönderdiğiniz mesajların bulunduğu konuşmalar',
            'trash' => 'Sildiğiniz konuşmalar — geri yükleyebilir veya kalıcı silebilirsiniz',
        ];
        $pageTitle = 'Mesajlar — ' . ($titles[$mailbox] ?? 'Gelen Kutusu');
        $mailboxType = $mailbox;
        $mailboxTitle = $titles[$mailbox] ?? 'Gelen Kutusu';
        $mailboxDescription = $descriptions[$mailbox] ?? '';
        $inboxRowsFetchUrl = esh_url('Mesaj', 'inboxRows', ['kutu' => $mailbox]);
        $moveToTrashUrl = esh_url('Mesaj', 'moveToTrash');
        $restoreUrl = esh_url('Mesaj', 'restore');
        $purgeUrl = esh_url('Mesaj', 'purge');
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'mesaj/mailbox');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function index(): void
    {
        $this->renderMailbox('inbox');
    }

    public function sent(): void
    {
        $this->renderMailbox('sent');
    }

    public function trash(): void
    {
        $this->renderMailbox('trash');
    }

    public function inboxRows(): void
    {
        $this->ensureModule();
        if ($this->currentUserId() === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $userId = $this->currentUserId();
        $mailbox = $this->normalizeMailbox((string) ($_GET['kutu'] ?? 'inbox'));
        $items = (new \App\Models\MesajKonusma())->getMailboxForUser($userId, $mailbox);
        $service = $this->service;

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/inbox_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html, 'unread_total' => $service->countUnread($userId), 'kutu' => $mailbox]);
    }

    public function thread(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        $konusmaId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($konusmaId === null) {
            $_SESSION['error'] = 'Konuşma seçilmedi.';
            header('Location: ' . esh_url('Mesaj', 'index'));
            exit;
        }

        try {
            if ($this->service->isTrashedForUser($konusmaId, $userId)) {
                $konusma = (new \App\Models\MesajKonusma())->findById($konusmaId);
                if (!$konusma || !(new \App\Models\MesajKonusma())->isMember($konusmaId, $userId)) {
                    throw new RuntimeException('Konuşma bulunamadı.');
                }
            } else {
                $konusma = $this->service->assertCanAccessConversation($userId, $konusmaId);
            }
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . esh_url('Mesaj', 'index'));
            exit;
        }

        $isTrashed = $this->service->isTrashedForUser($konusmaId, $userId);
        $messages = $this->mesajModel->getMessages($konusmaId);
        $lastId = $this->mesajModel->getLastMessageId($konusmaId);
        if ($lastId !== null && !$isTrashed) {
            $this->service->markRead($konusmaId, $userId, $lastId);
        }

        $pageTitle = $this->service->conversationDisplayTitle($konusma, $userId);
        $threadTitle = $pageTitle;
        $konusmaId = $konusmaId;
        $hastaId = IdHelper::normalizeRequestId($konusma->hasta_id ?? null) ?? '';
        $threadRowsFetchUrl = esh_url('Mesaj', 'threadRows', ['id' => $konusmaId]);
        $sendUrl = esh_url('Mesaj', 'send');
        $markReadUrl = esh_url('Mesaj', 'markRead');
        $pollThreadUrl = esh_url('Mesaj', 'pollThread', ['id' => $konusmaId]);
        $moveToTrashUrl = esh_url('Mesaj', 'moveToTrash');
        $restoreUrl = esh_url('Mesaj', 'restore');
        $purgeUrl = esh_url('Mesaj', 'purge');
        $mailboxBackUrl = $isTrashed ? esh_url('Mesaj', 'trash') : esh_url('Mesaj', 'index');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'mesaj/thread');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function threadRows(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $sinceId = IdHelper::normalizeRequestId($_GET['since_id'] ?? null);

        try {
            $this->service->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            $this->jsonOut(['ok' => false, 'mesaj' => $e->getMessage()], 403);
        }

        $messages = $this->mesajModel->getMessages($konusmaId, $sinceId);
        $viewerId = $userId;
        $lastId = $sinceId === null
            ? $this->mesajModel->getLastMessageId($konusmaId)
            : $this->maxMessageIdFromList($messages);

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        $this->jsonOut([
            'ok' => true,
            'html' => $html,
            'last_id' => $lastId,
            'append' => $sinceId !== null,
        ]);
    }

    public function send(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = IdHelper::normalizeRequestId($_POST['konusma_id'] ?? null);
        $body = (string) ($_POST['govde'] ?? $_POST['body'] ?? '');
        if ($konusmaId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Konuşma seçilmedi.'], 400);
        }

        $result = $this->service->sendMessage($konusmaId, $userId, $body);
        if (!$result['ok']) {
            $this->jsonOut($result, 400);
        }

        $mesajId = IdHelper::normalizeRequestId($result['mesaj_id'] ?? null);
        $msg = $mesajId !== null ? $this->mesajModel->db->fetchObjectPrepared(
            'SELECT m.*, u.name AS gonderen_adi FROM #__mesajlar m
             LEFT JOIN #__users u ON u.id = m.gonderen_id
             WHERE m.id = ? LIMIT 1',
            [$mesajId]
        ) : null;
        $messages = $msg ? [$msg] : [];
        $viewerId = $userId;

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        $this->jsonOut([
            'ok' => true,
            'mesaj_id' => $mesajId ?? '',
            'html' => $html,
        ]);
    }

    public function markRead(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = IdHelper::normalizeRequestId($_POST['konusma_id'] ?? null);
        $lastMessageId = IdHelper::normalizeRequestId($_POST['last_message_id'] ?? null);
        if ($konusmaId === null || $lastMessageId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 400);
        }

        try {
            $this->service->markRead($konusmaId, $userId, $lastMessageId);
            $this->jsonOut(['ok' => true, 'unread_total' => $this->service->countUnread($userId)]);
        } catch (RuntimeException $e) {
            $this->jsonOut(['ok' => false, 'mesaj' => $e->getMessage()], 403);
        }
    }

    public function poll(): void
    {
        if (!AppSettings::isModuleEnabled('mesajlasma') || !MesajService::moduleReady()) {
            $this->jsonOut(['ok' => true, 'unread_total' => 0]);
        }
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        if (!MesajService::canUseMessaging($userId)) {
            $this->jsonOut(['ok' => true, 'unread_total' => 0]);
        }

        $this->jsonOut(['ok' => true, 'unread_total' => $this->service->countUnread($userId)]);
    }

    public function pollThread(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $sinceId = IdHelper::normalizeRequestId($_GET['since_id'] ?? null);

        try {
            $this->service->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            $this->jsonOut(['ok' => false, 'mesaj' => $e->getMessage()], 403);
        }

        $messages = $this->mesajModel->getMessages($konusmaId, $sinceId);
        $viewerId = $userId;
        $lastId = $this->maxMessageIdFromList($messages, $sinceId);

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        if ($lastId !== null && ($sinceId === null || strcmp((string) $lastId, (string) $sinceId) > 0)) {
            $this->service->markRead($konusmaId, $userId, $lastId);
        }

        $this->jsonOut([
            'ok' => true,
            'html' => $html,
            'last_id' => $lastId,
            'has_new' => $lastId !== null && ($sinceId === null || strcmp((string) $lastId, (string) $sinceId) > 0),
            'unread_total' => $this->service->countUnread($userId),
        ]);
    }

    public function compose(): void
    {
        $this->ensureModule();
        $pageTitle = 'Yeni Mesaj';
        $usersForDmUrl = esh_url('Mesaj', 'usersForDm');
        $startDmUrl = esh_url('Mesaj', 'startDm');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'mesaj/compose');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function usersForDm(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $users = $this->service->getDmCandidates($userId);
        $items = [];
        foreach ($users as $u) {
            $kurumAdi = trim((string) ($u->kurum_adi ?? ''));
            if ($kurumAdi === '' && (int) ($u->isadmin ?? 0) >= AuthHelper::ROLE_SUPERADMIN) {
                $kurumAdi = 'Platform';
            }
            $items[] = [
                'id' => (string) ($u->id ?? ''),
                'name' => (string) ($u->name ?? ''),
                'unvan' => User::unvanLabel((string) ($u->unvan ?? '')),
                'kurum_adi' => $kurumAdi,
            ];
        }

        $this->jsonOut(['ok' => true, 'items' => $items]);
    }

    public function startDm(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $_SESSION['error'] = 'Oturum gerekli.';
            header('Location: ' . esh_url('Mesaj', 'compose'));
            exit;
        }

        $targetId = IdHelper::normalizeRequestId($_POST['user_id'] ?? null);
        if ($targetId === null) {
            $_SESSION['error'] = 'Alıcı seçilmedi.';
            header('Location: ' . esh_url('Mesaj', 'compose'));
            exit;
        }
        try {
            $konusmaId = $this->service->getOrCreateDm($userId, $targetId);
            header('Location: ' . esh_url('Mesaj', 'thread', ['id' => $konusmaId]));
            exit;
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . esh_url('Mesaj', 'compose'));
            exit;
        }
    }

    public function patientThread(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        $hastaId = IdHelper::normalizeRequestId($_GET['id'] ?? $_GET['hasta_id'] ?? null);
        if ($hastaId === null) {
            $_SESSION['error'] = 'Hasta seçilmedi.';
            header('Location: ' . esh_url('Mesaj', 'index'));
            exit;
        }

        try {
            $konusmaId = $this->service->getOrCreatePatientThread($hastaId, $userId);
            header('Location: ' . esh_url('Mesaj', 'thread', ['id' => $konusmaId]));
            exit;
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . esh_url('Patient', 'view', ['id' => $hastaId]));
            exit;
        }
    }

    public function moveToTrash(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = IdHelper::normalizeRequestId($_POST['konusma_id'] ?? null);
        if ($konusmaId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Konuşma seçilmedi.'], 400);
        }
        $this->jsonOut($this->service->moveToTrash($konusmaId, $userId));
    }

    public function restore(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = IdHelper::normalizeRequestId($_POST['konusma_id'] ?? null);
        if ($konusmaId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Konuşma seçilmedi.'], 400);
        }
        $result = $this->service->restoreFromTrash($konusmaId, $userId);
        if ($result['ok']) {
            $result['redirect'] = esh_url('Mesaj', 'thread', ['id' => $konusmaId]);
        }
        $this->jsonOut($result);
    }

    public function purge(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = IdHelper::normalizeRequestId($_POST['konusma_id'] ?? null);
        if ($konusmaId === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Konuşma seçilmedi.'], 400);
        }
        $this->jsonOut($this->service->purgeFromTrash($konusmaId, $userId));
    }

    public function broadcast(): void
    {
        $this->ensureModule();
        AuthHelper::requirePlatformOwner();

        $userModel = new User();
        $users = $userModel->getMessagingAdminList();
        $pageTitle = 'Sistem Duyurusu';
        $broadcastSendUrl = esh_url('Mesaj', 'broadcastSend');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'mesaj/broadcast');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function broadcastSend(): void
    {
        $this->ensureModule();
        AuthHelper::requirePlatformOwner();

        $adminId = $this->currentUserId();
        $title = (string) ($_POST['baslik'] ?? '');
        $body = (string) ($_POST['govde'] ?? '');
        $allUsers = !empty($_POST['tum_kullanicilar']);
        $selected = isset($_POST['user_ids']) && is_array($_POST['user_ids'])
            ? array_values(array_filter(array_map(static fn($x) => IdHelper::normalizeRequestId($x), $_POST['user_ids'])))
            : [];

        if ($allUsers) {
            $userModel = new User();
            $list = $userModel->getMessagingAdminList();
            $selected = [];
            foreach ($list as $u) {
                $uid = (string) ($u->id ?? '');
                if ($uid !== '' && $uid !== $adminId) {
                    $selected[] = $uid;
                }
            }
        }

        $result = $this->service->sendSystemBroadcast($adminId, $title, $body, $selected);
        if ($result['ok']) {
            $_SESSION['success'] = 'Duyuru gönderildi.';
            header('Location: ' . esh_url('Mesaj', 'index'));
            exit;
        }

        $_SESSION['error'] = $result['mesaj'] ?? 'Duyuru gönderilemedi.';
        header('Location: ' . esh_url('Mesaj', 'broadcast'));
        exit;
    }
}
