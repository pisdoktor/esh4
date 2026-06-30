    <div class="card shadow-sm border-0 mt-4">
        <?php
        $eshStatsCardTitle = 'e-Rapor branş dağılımı (üst kayıtlar)';
        $eshStatsPdfBlock = 'brans_erapor';
        require dirname(__DIR__) . '/stats_card_header.php';
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th class="ps-3">Branş</th><th class="text-end pe-3">Adet</th></tr></thead>
                <tbody>
                    <?php if (empty($brans)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Veri yok</td></tr>
                    <?php else: ?>
                        <?php foreach ($brans as $b): ?>
                            <tr>
                                <td class="ps-3"><?= htmlspecialchars((string) ($b->brans ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end pe-3 fw-bold"><?= (int) ($b->count ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>