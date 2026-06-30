<?php use App\Helpers\FormHelper; ?>
<div class="esh-page esh-page-mesaj-thread container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars($mailboxBackUrl ?? esh_url('Mesaj', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i><?= !empty($isTrashed) ? 'Çöp kutusu' : 'Gelen kutusu' ?>
        </a>
        <?php if (!empty($hastaId) && $hastaId > 0): ?>
        <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => (int) $hastaId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info btn-sm">
            <i class="fa-solid fa-hospital-user me-1"></i>Hasta dosyası
        </a>
        <?php endif; ?>
        <h1 class="h5 mb-0 ms-1 flex-grow-1"><?= htmlspecialchars((string) ($threadTitle ?? 'Konuşma'), ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($isTrashed)): ?>
        <button type="button" class="btn btn-sm btn-outline-success" id="esh-mesaj-thread-restore" data-konusma-id="<?= (int) ($konusmaId ?? 0) ?>">
            <i class="fa-solid fa-rotate-left me-1"></i>Geri yükle
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="esh-mesaj-thread-purge" data-konusma-id="<?= (int) ($konusmaId ?? 0) ?>">
            <i class="fa-solid fa-trash me-1"></i>Kalıcı sil
        </button>
        <?php else: ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="esh-mesaj-thread-trash" data-konusma-id="<?= (int) ($konusmaId ?? 0) ?>">
            <i class="fa-solid fa-trash-can me-1"></i>Sil
        </button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm border-0 esh-mesaj-thread-card">
        <div class="card-body p-0 d-flex flex-column" style="min-height: 420px; max-height: 70vh;">
            <div id="esh-mesaj-thread-scroll" class="esh-mesaj-thread-messages flex-grow-1 overflow-auto p-3"
                 data-since-id="<?= (int) ($lastId ?? 0) ?>">
                <?php
                $viewerId = (int) ($_SESSION['user_id'] ?? 0);
                include ROOT_PATH . '/views/site/mesaj/partials/thread_messages.php';
                ?>
            </div>
            <?php if (empty($isTrashed)): ?>
            <div class="border-top p-3 bg-light">
                <form id="esh-mesaj-send-form" class="d-flex gap-2 align-items-end" autocomplete="off">
                    <input type="hidden" name="konusma_id" value="<?= (int) ($konusmaId ?? 0) ?>">
                    <div class="flex-grow-1">
                        <?= FormHelper::fieldTextarea('govde', 'Mesaj', '', [
                            'col' => '',
                            'id' => 'esh-mesaj-govde',
                            'labelClass' => 'visually-hidden',
                            'labelFor' => 'esh-mesaj-govde',
                            'rows' => 2,
                            'maxlength' => '4000',
                            'placeholder' => 'Mesajınızı yazın…',
                            'required' => true,
                        ]) ?>
                    </div>
                    <button type="submit" class="btn btn-primary" id="esh-mesaj-send-btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span class="d-none d-sm-inline ms-1">Gönder</span>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="border-top p-3 bg-light text-muted small text-center">
                Bu konuşma çöp kutusunda. Yanıt yazmak için önce geri yükleyin.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.mesajThread = {
    konusmaId: <?= (int) ($konusmaId ?? 0) ?>,
    sendUrl: <?= json_encode($sendUrl ?? esh_url('Mesaj', 'send'), JSON_UNESCAPED_UNICODE) ?>,
    pollThreadUrl: <?= json_encode($pollThreadUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
    markReadUrl: <?= json_encode($markReadUrl ?? esh_url('Mesaj', 'markRead'), JSON_UNESCAPED_UNICODE) ?>,
    moveToTrashUrl: <?= json_encode($moveToTrashUrl ?? esh_url('Mesaj', 'moveToTrash'), JSON_UNESCAPED_UNICODE) ?>,
    restoreUrl: <?= json_encode($restoreUrl ?? esh_url('Mesaj', 'restore'), JSON_UNESCAPED_UNICODE) ?>,
    purgeUrl: <?= json_encode($purgeUrl ?? esh_url('Mesaj', 'purge'), JSON_UNESCAPED_UNICODE) ?>,
    trashListUrl: <?= json_encode(esh_url('Mesaj', 'trash'), JSON_UNESCAPED_UNICODE) ?>,
    inboxUrl: <?= json_encode(esh_url('Mesaj', 'index'), JSON_UNESCAPED_UNICODE) ?>,
    isTrashed: <?= !empty($isTrashed) ? 'true' : 'false' ?>,
    sinceId: <?= (int) ($lastId ?? 0) ?>
};
</script>
