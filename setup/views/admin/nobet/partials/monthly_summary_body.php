<div class="p-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Aylık Mesai Dengesi Özeti (<?= sprintf('%02d', (int) $ay) ?>/<?= (int) $yil ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ad</th>
                    <th>Unvan</th>
                    <th class="text-center">Haftaiçi / Haftasonu</th>
                    <th class="text-center">Zorunlu mesai</th>
                    <th class="text-center">Toplam çalışma</th>
                    <th class="text-center">Net mesai</th>
                    <th>Mazeret günleri</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($personelOzet)): ?>
                <?php $curUnvan = ''; ?>
                <?php foreach ($personelOzet as $o): ?>
                    <?php if ($curUnvan !== (string) ($o['unvan'] ?? '')): ?>
                        <?php $curUnvan = (string) ($o['unvan'] ?? ''); ?>
                        <tr class="table-secondary">
                            <td colspan="7" class="fw-bold"><?= strtoupper(htmlspecialchars(\App\Models\User::unvanLabel($curUnvan !== '' ? $curUnvan : null), ENT_QUOTES, 'UTF-8')) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php $net = (int) ($o['toplam_calisma'] ?? 0) - (int) ($o['zorunlu_mesai'] ?? 0); ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string) ($o['ad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($o['unvan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center"><?= (int) ($o['haftaici'] ?? 0) ?> / <span class="text-danger"><?= (int) ($o['haftasonu'] ?? 0) ?></span></td>
                        <td class="text-center"><?= (int) ($o['zorunlu_mesai'] ?? 0) ?> sa</td>
                        <td class="text-center"><?= (int) ($o['toplam_calisma'] ?? 0) ?> sa</td>
                        <td class="text-center fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= $net > 0 ? '+' : '' ?><?= $net ?> sa</td>
                        <td class="small text-muted"><?= !empty($o['mazeretler']) ? htmlspecialchars(implode(', ', (array) $o['mazeretler']), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">Bu ay için özet hesaplanacak nöbet verisi yok.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>