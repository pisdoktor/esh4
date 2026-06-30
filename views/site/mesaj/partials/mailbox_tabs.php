<?php
/** @var string $mailboxType */
$mailboxType = $mailboxType ?? 'inbox';
$tabs = [
    'inbox' => ['label' => 'Gelen Kutusu', 'icon' => 'fa-inbox', 'url' => esh_url('Mesaj', 'index')],
    'sent' => ['label' => 'Giden Kutusu', 'icon' => 'fa-paper-plane', 'url' => esh_url('Mesaj', 'sent')],
    'trash' => ['label' => 'Çöp Kutusu', 'icon' => 'fa-trash-can', 'url' => esh_url('Mesaj', 'trash')],
];
?>
<ul class="nav nav-pills mb-3 esh-mesaj-mailbox-tabs flex-wrap gap-1">
    <?php foreach ($tabs as $key => $tab): ?>
    <li class="nav-item">
        <a class="nav-link<?= $mailboxType === $key ? ' active' : '' ?>"
           href="<?= htmlspecialchars($tab['url'], ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid <?= htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
