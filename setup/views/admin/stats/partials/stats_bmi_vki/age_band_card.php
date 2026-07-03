    <div class="card border-0 shadow-sm mb-4">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Yaş bandına göre VKİ sınıfı', 'yas_band', 'h6', 'card-header bg-white'); ?>
        <div class="card-body">
            <div class="esh-chart-wrap--300">
                <canvas id="chartBmiVkiAge"></canvas>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-sm table-hover border-top mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-2">Yaş bandı</th>
                            <?php foreach ($catKeys as $ck): ?>
                                <th class="text-center small"><?= htmlspecialchars($catMeta[$ck]['label'] ?? $ck, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                            <th class="text-center">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ageBandKeys as $band):
                            $row = $byAge[$band] ?? BmiHelper::emptyCategoryCounts();
                            $bandTot = 0;
                            foreach ($catKeys as $ck) {
                                $bandTot += (int) ($row[$ck] ?? 0);
                            }
                        ?>
                            <tr>
                                <td class="ps-2 fw-medium"><?= htmlspecialchars(AgeBandHelper::label($band), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php foreach ($catKeys as $ck): ?>
                                    <td class="text-center"><?= (int) ($row[$ck] ?? 0) ?></td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold"><?= $bandTot ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>