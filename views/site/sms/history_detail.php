<div class="esh-page container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">SMS Gönderim</h1>
            <p class="text-muted small mb-0">
                <span class="font-monospace"><?= htmlspecialchars((string) ($gonderim->id ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                · <?= htmlspecialchars(\App\Helpers\SmsSettings::segmentLabel((string) ($gonderim->segment_tipi ?? '')), ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars((string) ($gonderim->created_at ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <a href="<?= htmlspecialchars($backUrl ?? esh_url('Sms', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Geri</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Durum</div><strong><?= htmlspecialchars((string) ($gonderim->durum ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Başarılı</div><strong class="text-success"><?= (int) ($gonderim->basarili ?? 0) ?></strong></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Başarısız / atlanan</div><strong class="text-danger"><?= (int) ($gonderim->basarisiz ?? 0) ?></strong></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Toplam</div><strong><?= (int) ($gonderim->toplam ?? 0) ?></strong></div></div></div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Hasta</th><th>Rol</th><th>Telefon</th><th>Durum</th><th>Mesaj</th></tr>
                </thead>
                <tbody>
                <?php foreach (($alicilar ?? []) as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim((string) ($a->isim ?? '') . ' ' . (string) ($a->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\App\Helpers\SmsSettings::roleLabel((string) ($a->rol ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars(\App\Services\Sms\SmsPhoneNormalizer::mask((string) ($a->telefon_norm ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string) ($a->durum ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars(mb_substr((string) ($a->govde ?? ''), 0, 80), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
