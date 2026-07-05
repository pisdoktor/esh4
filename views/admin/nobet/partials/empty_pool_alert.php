<?php if (!empty($nobetHavuzBos)): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-3 d-flex flex-wrap align-items-start gap-2" role="alert">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
        <div class="small mb-0">
            <strong>Personel havuzu boş.</strong>
            <?= htmlspecialchars((string) ($nobetHavuzUyari ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <span class="d-block mt-2">
                <a class="alert-link" href="<?= htmlspecialchars(esh_url('Settings', 'index', ['tab' => 'nobet']), ENT_QUOTES, 'UTF-8') ?>">Nöbet ayarları</a>
                ·
                <a class="alert-link" href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>">Kullanıcı yönetimi</a>
            </span>
        </div>
    </div>
<?php endif; ?>
