<?php
/** @var list<object> $messages */
/** @var int $viewerId */

if (empty($messages)): ?>
    <?php if (empty($sinceId) && (($GLOBALS['actionName'] ?? '') === 'thread')): ?>
    <div class="text-center text-muted py-5 esh-mesaj-empty-hint">
        <i class="fa-regular fa-comments fa-2x mb-2 d-block opacity-50"></i>
        Henüz mesaj yok. İlk mesajı siz gönderin.
    </div>
    <?php endif; ?>
<?php else: ?>
    <?php foreach ($messages as $m):
        $mid = (int) ($m->id ?? 0);
        $isSystem = (string) ($m->gonderen_tip ?? '') === 'system';
        $senderId = (int) ($m->gonderen_id ?? 0);
        $isMine = !$isSystem && $senderId === (int) $viewerId;
        $senderName = $isSystem
            ? 'Sistem'
            : (trim((string) ($m->gonderen_adi ?? '')) !== '' ? (string) $m->gonderen_adi : 'Kullanıcı');
        $body = (string) ($m->govde ?? '');
        $created = !empty($m->created_at)
            ? date('d-m-Y H:i', strtotime((string) $m->created_at))
            : '';
        $bubbleClass = $isSystem ? 'esh-mesaj-bubble--system' : ($isMine ? 'esh-mesaj-bubble--mine' : 'esh-mesaj-bubble--other');
    ?>
    <div class="esh-mesaj-bubble-wrap <?= $isMine ? 'esh-mesaj-bubble-wrap--mine' : '' ?>" data-message-id="<?= $mid ?>">
        <div class="esh-mesaj-bubble <?= htmlspecialchars($bubbleClass, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!$isMine): ?>
            <div class="esh-mesaj-bubble-sender small fw-semibold mb-1"><?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="esh-mesaj-bubble-body"><?= nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) ?></div>
            <?php if ($created !== ''): ?>
            <div class="esh-mesaj-bubble-time small opacity-75 mt-1"><?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
