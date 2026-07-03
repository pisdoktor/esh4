<?php

declare(strict_types=1);

namespace App\Controllers;

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
        if ($userId > 0 && !MesajService::canUseMessaging($userId)) {
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

    private function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function jsonOut(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
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
        if ($this->currentUserId() <= 0) {
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
        $konusmaId = (int) ($_GET['id'] ?? 0);
        if ($konusmaId <= 0) {
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
        if ($lastId > 0 && !$isTrashed) {
            $this->service->markRead($konusmaId, $userId, $lastId);
        }

        $pageTitle = $this->service->conversationDisplayTitle($konusma, $userId);
        $threadTitle = $pageTitle;
        $konusmaId = $konusmaId;
        $hastaId = (int) ($konusma->hasta_id ?? 0);
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
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = (int) ($_GET['id'] ?? 0);
        $sinceId = (int) ($_GET['since_id'] ?? 0);

        try {
            $this->service->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            $this->jsonOut(['ok' => false, 'mesaj' => $e->getMessage()], 403);
        }

        $messages = $this->mesajModel->getMessages($konusmaId, $sinceId);
        $viewerId = $userId;

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        $lastId = 0;
        foreach ($messages as $m) {
            $mid = (int) ($m->id ?? 0);
            if ($mid > $lastId) {
                $lastId = $mid;
            }
        }

        $this->jsonOut([
            'ok' => true,
            'html' => $html,
            'last_id' => $lastId,
            'append' => $sinceId > 0,
        ]);
    }

    public function send(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = (int) ($_POST['konusma_id'] ?? 0);
        $body = (string) ($_POST['govde'] ?? $_POST['body'] ?? '');

        $result = $this->service->sendMessage($konusmaId, $userId, $body);
        if (!$result['ok']) {
            $this->jsonOut($result, 400);
        }

        $msg = $this->mesajModel->db->fetchObjectPrepared(
            'SELECT m.*, u.name AS gonderen_adi FROM #__mesajlar m
             LEFT JOIN #__users u ON u.id = m.gonderen_id
             WHERE m.id = ? LIMIT 1',
            [(int) $result['mesaj_id']]
        );
        $messages = $msg ? [$msg] : [];
        $viewerId = $userId;

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        $this->jsonOut([
            'ok' => true,
            'mesaj_id' => (int) $result['mesaj_id'],
            'html' => $html,
        ]);
    }

    public function markRead(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = (int) ($_POST['konusma_id'] ?? 0);
        $lastMessageId = (int) ($_POST['last_message_id'] ?? 0);

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
        if ($userId <= 0) {
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
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }

        $konusmaId = (int) ($_GET['id'] ?? 0);
        $sinceId = (int) ($_GET['since_id'] ?? 0);

        try {
            $this->service->assertCanAccessConversation($userId, $konusmaId);
        } catch (RuntimeException $e) {
            $this->jsonOut(['ok' => false, 'mesaj' => $e->getMessage()], 403);
        }

        $messages = $this->mesajModel->getMessages($konusmaId, $sinceId);
        $viewerId = $userId;
        $lastId = $sinceId;

        ob_start();
        include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
        $html = ob_get_clean();

        foreach ($messages as $m) {
            $mid = (int) ($m->id ?? 0);
            if ($mid > $lastId) {
                $lastId = $mid;
            }
        }
        if ($lastId > $sinceId) {
            $this->service->markRead($konusmaId, $userId, $lastId);
        }

        $this->jsonOut([
            'ok' => true,
            'html' => $html,
            'last_id' => $lastId,
            'has_new' => $lastId > $sinceId,
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
        if ($userId <= 0) {
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
                'id' => (int) ($u->id ?? 0),
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
        if ($userId <= 0) {
            $_SESSION['error'] = 'Oturum gerekli.';
            header('Location: ' . esh_url('Mesaj', 'compose'));
            exit;
        }

        $targetId = (int) ($_POST['user_id'] ?? 0);
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
        $hastaId = (int) ($_GET['id'] ?? $_GET['hasta_id'] ?? 0);

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
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = (int) ($_POST['konusma_id'] ?? 0);
        $this->jsonOut($this->service->moveToTrash($konusmaId, $userId));
    }

    public function restore(): void
    {
        $this->ensureModule();
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = (int) ($_POST['konusma_id'] ?? 0);
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
        if ($userId <= 0) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Oturum gerekli'], 401);
        }
        $konusmaId = (int) ($_POST['konusma_id'] ?? 0);
        $this->jsonOut($this->service->purgeFromTrash($konusmaId, $userId));
    }

    public function broadcast(): void
    {
        $this->ensureModule();
        AuthHelper::requireSuperAdmin();

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
        AuthHelper::requireSuperAdmin();

        $adminId = $this->currentUserId();
        $title = (string) ($_POST['baslik'] ?? '');
        $body = (string) ($_POST['govde'] ?? '');
        $allUsers = !empty($_POST['tum_kullanicilar']);
        $selected = isset($_POST['user_ids']) && is_array($_POST['user_ids'])
            ? array_map('intval', $_POST['user_ids'])
            : [];

        if ($allUsers) {
            $userModel = new User();
            $list = $userModel->getMessagingAdminList();
            $selected = [];
            foreach ($list as $u) {
                $uid = (int) ($u->id ?? 0);
                if ($uid > 0 && $uid !== $adminId) {
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
