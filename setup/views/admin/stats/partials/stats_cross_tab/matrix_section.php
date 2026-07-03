    <?php if ($grand > 0 && $cellUnit !== ''): ?>
        <p class="small text-muted mb-3">Tablodaki toplam: <strong><?= $grand ?></strong> <?= htmlspecialchars($cellUnit, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($rowKeys === [] || $colKeys === []): ?>
        <div class="alert alert-secondary">Bu kırılım için gösterilecek veri yok.</div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <?php
            $eshStatsCardTitle = 'Çapraz tablo';
            $eshStatsPdfBlock = 'matrix';
            $eshStatsCardHeadingTag = 'h6';
            require dirname(__DIR__) . '/stats_card_header.php';
            ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3"></th>
                            <?php foreach ($colKeys as $ck): ?>
                                <th class="small"><?= htmlspecialchars((string) ($colLabels[$ck] ?? $ck), ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                            <th>Σ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $prevUnvan = null; ?>
                        <?php foreach ($rowKeys as $rk):
                            if ($groupByUnvan) {
                                $uv = (string) ($rowUnvan[$rk] ?? '_bos');
                                if ($uv !== $prevUnvan) {
                                    $prevUnvan = $uv;
                                    $sectionLabel = \App\Helpers\StatsCrossTabBuilder::personnelUnvanGroupLabel($uv);
                                    ?>
                                    <tr class="table-info">
                                        <th colspan="<?= (int) $colSpan ?>" class="text-start ps-3 small fw-bold">
                                            <i class="fa-solid fa-layer-group me-1" aria-hidden="true"></i>
                                            <?= htmlspecialchars(mb_strtoupper($sectionLabel, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </th>
                                    </tr>
                                    <?php
                                }
                            }
                            $row = $matrix[$rk] ?? [];
                            $rowSum = (int) ($rowTotals[$rk] ?? 0);
                        ?>
                            <tr>
                                <th class="text-start ps-3 bg-light small<?= $groupByUnvan ? ' ps-4' : '' ?>"><?= htmlspecialchars((string) ($rowLabels[$rk] ?? $rk), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php foreach ($colKeys as $ck):
                                    $n = (int) ($row[$ck] ?? 0);
                                    $intensity = $rowSum > 0 ? min(100, (int) round($n / $rowSum * 100)) : 0;
                                ?>
                                    <td style="background: rgba(25, 135, 84, <?= $intensity / 200 ?>);"><?= $n ?: '—' ?></td>
                                <?php endforeach; ?>
                                <td class="fw-bold"><?= $rowSum ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($colKeys !== []): ?>
                        <tfoot class="table-light">
                            <tr>
                                <th class="text-start ps-3">Sütun toplamı</th>
                                <?php foreach ($colKeys as $ck): ?>
                                    <td class="fw-semibold"><?= (int) ($colTotals[$ck] ?? 0) ?: '—' ?></td>
                                <?php endforeach; ?>
                                <td class="fw-bold"><?= $grand ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>