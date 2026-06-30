<a href="<?= htmlspecialchars(esh_url('Stats', 'monthlyFollowFreq'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success btn-sm">
            <i class="fa-solid fa-list-check me-1"></i>Aylık izlem sıklığı (kartlar + son 6 ay)
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-users-viewfinder me-2 text-danger"></i>Bu ay izlenen hastaların yaş grupları', 'main', 'h5'); ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Yaş aralığı</th>
                        <th class="text-end pe-4">Hasta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $top = 0;
                    foreach ($labels as $k => $lab):
                        $v = (int) ($age->$k ?? 0);
                        $top += $v;
                    ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-4 fw-bold"><?= $v ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-danger fw-bold">
                    <tr>
                        <td class="ps-4">Toplam</td>
                        <td class="text-end pe-4"><?= $top ?></td>
                    </tr>
                </tfoot>
            </table>