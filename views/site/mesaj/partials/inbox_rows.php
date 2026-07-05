<?php
/** @var list<object> $items */
/** @var MesajService $service */
use App\Helpers\AuthHelper;
use App\Services\MesajService;

$viewerId = AuthHelper::sessionUserId() ?? '';
$service = $service ?? new MesajService();
$mailbox = $mailbox ?? ($mailboxType ?? 'inbox');

$emptyMessages = [
    'inbox' => 'Henüz mesajınız yok.',
    'sent' => 'Giden kutusunda konuşma yok.',
    'trash' => 'Çöp kutusu boş.',
];
$emptyMessage = $emptyMessages[$mailbox] ?? $emptyMessages['inbox'];

if (empty($items)): ?>
    <div class="list-group-item text-center text-muted py-5">
        <i class="fa-regular fa-envelope-open fa-2x mb-2 d-block opacity-50"></i>
        <?= htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php else: ?>
    <?php foreach ($items as $row):
        $kid = (string) ($row->id ?? '');
        $unread = $mailbox === 'inbox' ? (int) ($row->unread_count ?? 0) : 0;
        $title = $service->conversationDisplayTitle($row, $viewerId);
        $tip = (string) ($row->tip ?? '');
        $tipIcon = match ($tip) {
            'patient' => 'fa-hospital-user text-info',
            'system' => 'fa-bullhorn text-warning',
            default => 'fa-user text-primary',
        };
        $ozet = trim((string) ($row->son_mesaj_ozet ?? ''));
        if (mb_strlen($ozet) > 120) {
            $ozet = mb_substr($ozet, 0, 117) . '…';
        }
        $tarih = !empty($row->son_mesaj_created)
            ? date('d-m-Y H:i', strtotime((string) $row->son_mesaj_created))
            : (!empty($row->son_mesaj_at) ? date('d-m-Y H:i', strtotime((string) $row->son_mesaj_at)) : '');
        if ($mailbox === 'trash' && !empty($row->silindi_at)) {
            $tarih = date('d-m-Y H:i', strtotime((string) $row->silindi_at));
        }
        $href = esh_url('Mesaj', 'thread', ['id' => $kid]);
    ?>
    <div class="list-group-item py-3<?= $unread > 0 ? ' esh-mesaj-unread' : '' ?>">
        <div class="d-flex w-100 justify-content-between align-items-start gap-2">
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="flex-grow-1 min-w-0 text-body text-decoration-none">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="fa-solid <?= htmlspecialchars($tipIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span class="fw-semibold text-truncate"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($unread > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= (int) $unread ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($ozet !== ''): ?>
                <p class="mb-0 small text-muted text-truncate"><?= htmlspecialchars($ozet, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </a>
            <div class="d-flex flex-column align-items-end gap-2">
                <?php if ($tarih !== ''): ?>
                <small class="text-muted text-nowrap"><?= htmlspecialchars($tarih, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
                <?php if ($mailbox === 'trash'): ?>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-success js-mesaj-restore" data-konusma-id="<?= htmlspecialchars($kid, ENT_QUOTES, 'UTF-8') ?>" title="Geri yükle">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger js-mesaj-purge" data-konusma-id="<?= htmlspecialchars($kid, ENT_QUOTES, 'UTF-8') ?>" title="Kalıcı sil">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <?php elseif ($mailbox !== 'sent'): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary js-mesaj-trash" data-konusma-id="<?= htmlspecialchars($kid, ENT_QUOTES, 'UTF-8') ?>" title="Çöp kutusuna taşı">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
