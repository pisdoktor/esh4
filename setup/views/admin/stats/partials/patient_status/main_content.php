<div class="card shadow-sm border-0">
        <?php
        $eshStatsCardTitle = '<i class="fa-solid fa-layer-group me-2 text-primary"></i>Hasta durum detayları';
        $eshStatsPdfBlock = 'main';
        $eshStatsCardHeadingTag = 'h5';
        require dirname(__DIR__) . '/stats_card_header.php';
        ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Durum</th>
                        <th>Adet</th>
                        <th class="pe-4">Oran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tanimlar as $kod => $detay):
                        $adet = $veriler[$kod] ?? 0;
                        $yuzde = $genelToplam > 0 ? round(($adet / $genelToplam) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge <?= htmlspecialchars($detay['badge'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($detay['label'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($kod, ENT_QUOTES, 'UTF-8') ?>)
                                </span>
                            </td>
                            <td class="fw-bold"><?= (int) $adet ?></td>
                            <td class="pe-4" style="min-width: 160px;">
                                <div class="progress esh-stats-progress-sm">
                                    <div class="progress-bar" style="width: <?= min(100, $yuzde) ?>%;"></div>
                                </div>
                                <small class="text-muted">%<?= $yuzde ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($veriler as $kod => $adet):
                        if (isset($tanimlar[$kod])) {
                            continue;
                        }
                        $yuzde = $genelToplam > 0 ? round(($adet / $genelToplam) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="ps-4"><span class="badge bg-light text-dark border">Diğer (<?= htmlspecialchars((string) $kod, ENT_QUOTES, 'UTF-8') ?>)</span></td>
                            <td class="fw-bold"><?= (int) $adet ?></td>
                            <td class="pe-4"><small>%<?= $yuzde ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-4">Genel toplam</td>
                        <td colspan="2"><?= (int) $genelToplam ?> hasta</td>
                    </tr>
                </tfoot>
            </table>