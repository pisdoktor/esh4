<p class="small text-muted mb-3">Aktif hasta: <strong><?= $aktif ?></strong> —
        <a href="<?= htmlspecialchars(esh_url('Stats', 'guvenceDist'), ENT_QUOTES, 'UTF-8') ?>">Güvence dağılımı</a> ·
        <a href="<?= htmlspecialchars(esh_url('Stats', 'ageGenderBands'), ENT_QUOTES, 'UTF-8') ?>">Yaş × cinsiyet</a></p>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Güvence × yaş bandı tablosu', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-3">Güvence</th>
                        <?php foreach ($bandKeys as $bk): ?>
                            <th class="small"><?= htmlspecialchars((string) ($bandLabels[$bk] ?? $bk), ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                        <th>Σ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guvences as $guvence):
                        $row = $matrix[$guvence] ?? [];
                        $rowSum = (int) ($report['row_totals'][$guvence] ?? 0);
                    ?>
                        <tr>
                            <th class="text-start ps-3 bg-light small"><?= htmlspecialchars($guvence, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php foreach ($bandKeys as $bk):
                                $n = (int) ($row[$bk] ?? 0);
                                $intensity = $rowSum > 0 ? min(100, (int) round($n / $rowSum * 100)) : 0;
                            ?>
                                <td style="background: rgba(25, 135, 84, <?= $intensity / 200 ?>);"><?= $n ?: '—' ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $rowSum ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th class="text-start ps-3">Sütun toplamı</th>
                        <?php foreach ($bandKeys as $bk): ?>
                            <td class="fw-semibold"><?= (int) ($colTotals[$bk] ?? 0) ?></td>
                        <?php endforeach; ?>
                        <td class="fw-bold"><?= $aktif ?></td>
                    </tr>
                </tfoot>
            </table>