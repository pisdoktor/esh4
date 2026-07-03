    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Mevcut JSON</h6>
                </div>
                <div class="card-body">
                    <?php if (!$jsonStats['exists']): ?>
                        <p class="text-warning mb-0 small">Dosya henüz yok. Aşağıdan Excel yükleyerek oluşturun.</p>
                    <?php else: ?>
                        <dl class="row small mb-0">
                            <dt class="col-sm-5">Son güncelleme</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars((string) ($jsonStats['mtimeFormatted'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt class="col-sm-5">Kayıt sayısı</dt>
                            <dd class="col-sm-7">
                                <?= $jsonStats['count'] !== null ? (int) $jsonStats['count'] : '—' ?>
                                <span class="text-muted">(benzersiz ilaç adı)</span>
                            </dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Kaynak</h6>
                </div>
                <div class="card-body small">
                    <p class="mb-2">
                        <a href="<?= htmlspecialchars($titckUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                            TİTCK Dinamik Modül 43 — E-Reçete İlaç Listesi
                            <i class="fa-solid fa-arrow-up-right-from-square ms-1 opacity-75" aria-hidden="true"></i>
                        </a>
                    </p>
                    <p class="text-muted mb-0">Güncel <strong>.xlsx</strong> dosyasını indirip buradan yükleyin. Liste genelde Salı günleri yenilenir.</p>
                </div>
            </div>
        </div>
    </div>