<div class="esh-page esh-page--list esh-page-theme container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>">Yönetim paneli</a></li>
            <li class="breadcrumb-item active">Tema görünümü</li>
        </ol>
    </nav>
<?php include __DIR__ . '/partials/theme_list_card.php'; ?>
</div>
