    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <?php
                $eshStatsCardTitle = 'İlçe bazlı aktif hasta (üst ' . count($ilce) . ')';
                $eshStatsPdfBlock = 'ilce_ranking';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <div class="table-responsive" style="max-height: 320px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top"><tr><th class="ps-3">İlçe</th><th class="text-end pe-3">Adet</th></tr></thead>
                        <tbody>
                            <?php foreach ($ilce as $r): ?>
                                <tr>
                                    <td class="ps-3"><?= htmlspecialchars((string) ($r->ilce_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3 fw-bold"><?= (int) ($r->adet ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <?php
                $eshStatsCardTitle = 'Bağımlılık (aktif)';
                $eshStatsPdfBlock = 'bagimlilik';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <div class="table-responsive" style="max-height: 320px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top"><tr><th class="ps-3">Durum</th><th class="text-end pe-3">Adet</th></tr></thead>
                        <tbody>
                            <?php foreach ($bagimlilik as $b): ?>
                                <?php
                                $bkod = trim((string) ($b->kod ?? ''));
                                $betik = $bagimlilikEtiket[$bkod] ?? ($bkod === '—' || $bkod === '' ? 'Belirtilmemiş' : $bkod);
                                ?>
                                <tr>
                                    <td class="ps-3"><?= htmlspecialchars($betik, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3"><?= (int) ($b->adet ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>