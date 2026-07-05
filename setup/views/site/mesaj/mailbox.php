<?php
/** @var string $mailboxType */
$mailboxType = $mailboxType ?? 'inbox';
$icons = [
    'inbox' => 'fa-inbox text-primary',
    'sent' => 'fa-paper-plane text-success',
    'trash' => 'fa-trash-can text-secondary',
];
$iconClass = $icons[$mailboxType] ?? $icons['inbox'];
?>
<div class="esh-page esh-page--list esh-page-mesaj container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid <?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars((string) ($mailboxTitle ?? 'Gelen Kutusu'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted small mb-0"><?= htmlspecialchars((string) ($mailboxDescription ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars(esh_url('Mesaj', 'compose'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pen me-1"></i>Yeni Mesaj
            </a>
            <?php if (\App\Helpers\AuthHelper::sessionIsPlatformOwner()): ?>
            <a href="<?= htmlspecialchars(esh_url('Mesaj', 'broadcast'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning btn-sm">
                <i class="fa-solid fa-bullhorn me-1"></i>Duyuru Gönder
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php include ROOT_PATH . '/views/site/mesaj/partials/mailbox_tabs.php'; ?>

    <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush rounded" id="esh-mesaj-inbox"
             data-esh-fetch-url="<?= htmlspecialchars($inboxRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
             data-mailbox="<?= htmlspecialchars($mailboxType, ENT_QUOTES, 'UTF-8') ?>"
             data-move-trash-url="<?= htmlspecialchars($moveToTrashUrl ?? esh_url('Mesaj', 'moveToTrash'), ENT_QUOTES, 'UTF-8') ?>"
             data-restore-url="<?= htmlspecialchars($restoreUrl ?? esh_url('Mesaj', 'restore'), ENT_QUOTES, 'UTF-8') ?>"
             data-purge-url="<?= htmlspecialchars($purgeUrl ?? esh_url('Mesaj', 'purge'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="list-group-item text-center text-muted py-5">
                <span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>
                Yükleniyor…
            </div>
        </div>
    </div>
</div>
