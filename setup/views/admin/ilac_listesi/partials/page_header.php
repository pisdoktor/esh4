<div class="esh-page esh-page--list container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>">Yönetim paneli</a></li>
            <li class="breadcrumb-item active">İlaç listesi (TİTCK)</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-pills text-primary me-2"></i>İlaç listesi (TİTCK)</h3>
            <p class="text-muted mb-0 small">
                TİTCK <strong>E-Reçete İlaç Listesi</strong> Excel dosyasını yükleyerek hasta ilaç raporundaki otomatik tamamlama listesini
                (<code><?= htmlspecialchars($jsonRel, ENT_QUOTES, 'UTF-8') ?></code>) güncelleyin.
                Her kayıt için ilaç adı, ATC adı (etken madde) ve reçete türü aktarılır; liste TİTCK sitesinden otomatik indirilmez.
            </p>
        </div>
    </div>