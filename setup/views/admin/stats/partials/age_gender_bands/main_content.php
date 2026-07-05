<div class="card shadow-sm border-0 mb-4">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-chart-column me-2 text-primary"></i>Aktif hastalar — yaş bandı ve cinsiyet', 'main', 'h5'); ?>
        <div class="card-body">
            <div class="esh-chart-wrap--lg">
                <canvas id="statsAgeGenderChart"></canvas>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-sm table-hover border-top">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-2">Yaş aralığı</th>
                            <th class="text-center text-danger">Kadın</th>
                            <th class="text-center text-primary">Erkek</th>
                            <th class="text-center">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $keys = array_keys($template);
                        $tK = $tE = 0;
                        foreach ($keys as $idx => $key):
                            $vK = $stats[\App\Helpers\CinsiyetHelper::KADIN][$key];
                            $vE = $stats[\App\Helpers\CinsiyetHelper::ERKEK][$key];
                            $tK += $vK;
                            $tE += $vE;
                            $lab = $labels[$idx] ?? $key;
                        ?>
                            <tr>
                                <td class="ps-2 fw-medium"><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center"><?= $vK ?></td>
                                <td class="text-center"><?= $vE ?></td>
                                <td class="text-center fw-bold"><?= $vK + $vE ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-2">Genel toplam</td>
                            <td class="text-center"><?= $tK ?></td>
                            <td class="text-center"><?= $tE ?></td>
                            <td class="text-center bg-primary text-white"><?= $tK + $tE ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>