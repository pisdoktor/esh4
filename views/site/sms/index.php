<div class="esh-page esh-page--sms container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-comment-sms me-2 text-primary"></i>SMS Bildirimleri</h1>
            <p class="text-muted small mb-0">Hasta, yakın ve aile hekimine bilgilendirme SMS geçmişi</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($composeUrl ?? esh_url('Sms', 'compose'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-paper-plane me-1"></i>Yeni SMS
            </a>
            <a href="<?= htmlspecialchars(esh_url('Sms', 'templates'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Şablonlar</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tarih</th>
                        <th>Segment</th>
                        <th>Durum</th>
                        <th class="text-end">Başarılı</th>
                        <th class="text-end">Toplam</th>
                        <th>Gönderen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Henüz SMS gönderimi yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $g):
                        $gid = (string) ($g->id ?? '');
                        ?>
                    <tr>
                        <td class="small font-monospace"><?= htmlspecialchars(substr($gid, 0, 8), ENT_QUOTES, 'UTF-8') ?>…</td>
                        <td class="small"><?= htmlspecialchars((string) ($g->created_at ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\App\Helpers\SmsSettings::segmentLabel((string) ($g->segment_tipi ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string) ($g->durum ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-end"><?= (int) ($g->basarili ?? 0) ?></td>
                        <td class="text-end"><?= (int) ($g->toplam ?? 0) ?></td>
                        <td class="small"><?= htmlspecialchars((string) ($g->olusturan_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a href="<?= htmlspecialchars(esh_url('Sms', 'historyDetail', ['id' => $gid]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Detay</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
