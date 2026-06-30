<div class="container-fluid mt-2">
    <div class="row mb-4">
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-database text-primary me-2"></i>Veritabanı bakımı ve yedek</h3>
            <p class="text-muted mb-0 small">
                Veritabanı: <code><?= htmlspecialchars($dbLabel, ENT_QUOTES, 'UTF-8') ?></code>
                · Yedek klasörü: <code><?= htmlspecialchars($backupPathLabel, ENT_QUOTES, 'UTF-8') ?></code>
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
            <span class="badge <?= !empty($tools['mysqldump']) ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-3 py-2">
                <?= !empty($tools['mysqldump']) ? 'mysqldump bulundu (isteğe bağlı)' : 'mysqldump yok — varsayılan PHP yedeği' ?>
            </span>
        </div>
    </div>
