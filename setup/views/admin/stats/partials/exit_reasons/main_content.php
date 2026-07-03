<?php
    $statsDateFilterAction = 'exitReasons';
    $statsDateFilterIdPrefix = 'stats-exit-reasons';
    $statsDateFilterAccent = 'danger';
    require dirname(__DIR__, 4) . '/partials/admin/stats_date_range_filters.php';
    ?>

    <div class="card shadow-sm border-0">
        <?php
        \App\Helpers\StatsViewPdfHelper::renderCardHeader(
            '<span class="text-danger"><i class="fa-solid fa-chart-pie me-2"></i>Çıkış nedeni dağılımı</span>',
            'main',
            'h5'
        );
        ?>
        <div class="card-body">
            <?php if ($toplam > 0): ?>
                <div class="mb-4 esh-chart-wrap--md">
                    <canvas id="statsExitReasonChart"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:12px;"></th>
                                <th>Neden</th>
                                <th class="text-end">Hasta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableRows as $tr): ?>
                                <tr>
                                    <td><span class="badge rounded-circle p-2" style="background-color:<?= htmlspecialchars($tr['renk'], ENT_QUOTES, 'UTF-8') ?>">&nbsp;</span></td>
                                    <td><?= htmlspecialchars($tr['isim'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end fw-bold"><?= (int) $tr['sayi'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">Dönem toplamı</td>
                                <td class="text-end text-danger"><?= $toplam ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-5 mb-0">Seçilen dönemde pasife alınan hasta kaydı yok veya neden kodu eşleşmedi.</p>
            <?php endif; ?>
        </div>
    </div>