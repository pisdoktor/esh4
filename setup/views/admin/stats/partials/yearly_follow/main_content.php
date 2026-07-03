<div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg h-100 text-center text-white overflow-hidden bg-primary bg-gradient">
                <div class="card-header border-0 border-bottom border-light border-opacity-50 py-3 fw-bold bg-dark bg-opacity-25">Yıllık kapsama (12 ay)</div>
                <div class="card-body py-5 px-3">
                    <div class="display-4 fw-bold text-white">%<?= htmlspecialchars((string) ($yearly->yillik_skor ?? '0'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="progress mt-4 rounded-pill bg-dark bg-opacity-25 esh-stats-progress-md">
                        <div class="progress-bar rounded-pill bg-warning" style="width: <?= min(100, (float) ($yearly->yillik_skor ?? 0)) ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader(
                    '<i class="fa-solid fa-chart-line me-2 text-success"></i>Yıllık izlem hedef gerçekleşme (hedef: ' . (int) ($yearly->max_beklenti ?? 0) . ' = hasta × ' . (int) ($yearly->hedef ?? 4) . ')',
                    'main',
                    'h5'
                ); ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Yıl</th>
                                    <th>Toplam izlem</th>
                                    <th class="pe-4">Hedef %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $maxB = (int) ($yearly->max_beklenti ?? 0);
                                foreach (($yearly->by_year ?? []) as $yil => $sayi):
                                    $oran = $maxB > 0 ? round(((int) $sayi / $maxB) * 100, 1) : 0;
                                    $bar = 'bg-danger';
                                    if ($oran >= 100) {
                                        $bar = 'bg-success';
                                    } elseif ($oran >= 70) {
                                        $bar = 'bg-info';
                                    } elseif ($oran >= 40) {
                                        $bar = 'bg-warning';
                                    }
                                    $w = min(100, $oran);
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= (int) $yil ?></td>
                                        <td><?= (int) $sayi ?> adet</td>
                                        <td class="pe-4" style="min-width: 200px;">
                                            <div class="progress esh-stats-progress-lg">
                                                <div class="progress-bar <?= $bar ?>" style="width: <?= $w ?>%;">%<?= $oran ?></div>
                                            </div>
                                            <?php if ($oran > 100): ?><small class="text-success">Hedef aşıldı</small><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>