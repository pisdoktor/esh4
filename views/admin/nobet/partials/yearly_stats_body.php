<div class="p-2">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h6 class="mb-0">Yıllık Nöbet İstatistiği</h6>
        <form class="d-flex align-items-center gap-2 js-nobet-modal-form" method="get" action="<?= htmlspecialchars(esh_form_action('Nobet', 'yearlyStats'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Nobet', 'yearlyStats') ?>
            <input type="number" class="form-control form-control-sm" name="istatistik_yil" min="2000" max="2100" value="<?= (int) ($istatistikYil ?? date('Y')) ?>" style="width:120px;">
            <button class="btn btn-sm btn-outline-primary">Getir</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ad</th>
                    <th>Unvan</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?><th class="text-center"><?= $m ?></th><?php endfor; ?>
                    <th class="text-center">Toplam</th>
                    <th class="text-center">Hafta içi</th>
                    <th class="text-center">Hafta sonu</th>
                    <th class="text-center">Bayram/Tatil</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($yillikIstatistik)): ?>
                <?php foreach ($yillikIstatistik as $s): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string) ($s['ad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($s['unvan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?><td class="text-center"><?= (int) (($s['aylar'][$m] ?? 0)) ?></td><?php endfor; ?>
                        <td class="text-center fw-bold"><?= (int) ($s['toplam_nobet'] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($s['haftaici'] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($s['haftasonu'] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($s['bayram_nobet'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="19" class="text-center text-muted">Seçilen yıl için nöbet istatistiği bulunamadı.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>