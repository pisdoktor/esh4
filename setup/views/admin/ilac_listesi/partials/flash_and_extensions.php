    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <div class="mb-3">
        <?php if (!($zipArchiveAvailable ?? false)): ?>
            <div class="alert alert-danger border-0 shadow-sm small mb-2">
                <i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>
                <strong>zip kapalı</strong> — Excel (.xlsx) yüklemesi çalışmaz; PHP <code>ZipArchive</code> gerekli.
                XAMPP: <code>php.ini</code> içinde <code>;extension=zip</code> satırındaki noktalı virgülü kaldırın
                (<code>extension=zip</code>), Apache’yi yeniden başlatın.
            </div>
        <?php endif; ?>
        <?php if (!($intlCollatorAvailable ?? true)): ?>
            <div class="alert alert-warning border-0 shadow-sm small mb-2">
                <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
                <strong>intl kapalı</strong> — Yükleme çalışır; ilaç adları tam Türkçe sıralama yerine yedek sıralama kullanır.
                XAMPP: <code>php.ini</code> içinde <code>extension=intl</code> (php_intl), ardından Apache yeniden başlatma önerilir.
            </div>
        <?php endif; ?>
        <?php if (($zipArchiveAvailable ?? false) || ($intlCollatorAvailable ?? false)): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 small">
                <?php if ($zipArchiveAvailable ?? false): ?>
                    <span class="badge bg-success-subtle text-success border fw-normal">zip: açık</span>
                <?php endif; ?>
                <?php if ($intlCollatorAvailable ?? false): ?>
                    <span class="badge bg-success-subtle text-success border fw-normal">intl: açık</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>