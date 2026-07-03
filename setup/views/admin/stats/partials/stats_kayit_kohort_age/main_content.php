<div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Kayıt kohortu × yaş bandı', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-3">Kayıt yılı</th>
                        <?php foreach ($groups as $label): ?>
                            <th><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                        <th>Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($years as $y):
                        $row = $matrix[$y] ?? [];
                        $rowSum = (int) ($rowTotals[$y] ?? 0);
                    ?>
                        <tr>
                            <th class="text-start ps-3 bg-light"><?= (int) $y ?></th>
                            <?php foreach ($groupKeys as $gk):
                                $n = (int) ($row[$gk] ?? 0);
                                $intensity = $rowSum > 0 ? min(100, (int) round($n / $rowSum * 100)) : 0;
                            ?>
                                <td style="background: rgba(13, 110, 253, <?= $intensity / 200 ?>);"><?= $n ?: '—' ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $rowSum ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Yaş bantları «Yaş × cinsiyet» raporu ile aynıdır (0–1 ay, 2 ay–2 yaş, … 86+); her hücre kayıt tarihindeki yaşa göre hesaplanır.
            <a href="<?= htmlspecialchars(esh_url('Stats', 'ageGenderBands'), ENT_QUOTES, 'UTF-8') ?>">Yaş × cinsiyet</a>