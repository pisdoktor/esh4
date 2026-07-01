<div class="row g-4 align-items-stretch">

    <div class="col-md-4">

        <div class="h-100 p-2 rounded-4 border border-3 border-<?= htmlspecialchars((string) ($meta['color'] ?? 'primary'), ENT_QUOTES, 'UTF-8') ?> shadow-lg bg-<?= htmlspecialchars((string) ($meta['color'] ?? 'primary'), ENT_QUOTES, 'UTF-8') ?>-subtle">

            <div class="card esh-stats-metric-card border-0 shadow-none h-100 rounded-3 overflow-hidden">

                <div class="card-body text-center d-flex flex-column justify-content-center py-4 px-3">

                    <span class="esh-stats-metric-card__icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto">

                        <i class="fa-solid fa-gauge-high fa-lg" aria-hidden="true"></i>

                    </span>

                    <div class="esh-stats-metric-card__label small text-uppercase fw-semibold mb-2"><?= htmlspecialchars((string) ($meta['scoreLabel'] ?? 'Ortalama skor'), ENT_QUOTES, 'UTF-8') ?></div>

                    <div class="display-3 fw-bold lh-1 mb-2"><?= htmlspecialchars((string) ($report->ortalama_skor ?? 0), ENT_QUOTES, 'UTF-8') ?></div>

                    <div class="esh-stats-metric-card__pill small rounded-pill py-2 px-3 d-inline-block mx-auto mb-2">

                        Kapsam: <strong class="fw-bold"><?= htmlspecialchars((string) ($report->kapsam_yuzde ?? 0), ENT_QUOTES, 'UTF-8') ?>%</strong>

                    </div>

                    <div class="small text-muted">

                        Uygun: <strong><?= (int) ($report->uygun_hasta ?? 0) ?></strong>

                        · Değerlendirilen: <strong><?= (int) ($report->degerlendirilen_hasta ?? 0) ?></strong>

                        · Eksik: <strong><?= (int) ($report->eksik ?? 0) ?></strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white py-3">

                <h6 class="mb-0 fw-bold">Grafik</h6>

            </div>

            <div class="card-body">

                <div class="esh-chart-wrap--200"><canvas id="<?= htmlspecialchars((string) ($meta['chartId'] ?? 'chartClinicalScale'), ENT_QUOTES, 'UTF-8') ?>"></canvas></div>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-sm h-100">

            <?php

            $eshStatsCardTitle = 'Risk / durum grupları';

            $eshStatsPdfBlock = 'dagilim';

            require dirname(__DIR__) . '/stats_card_header.php';

            ?>

            <table class="table table-sm mb-0">

                <?php foreach ($grup as $label => $n):

                    $pct = round($n / $tot * 100, 1);

                ?>

                    <tr><td><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></td><td class="text-end fw-bold"><?= (int) $n ?></td><td class="text-end text-muted small"><?= $pct ?>%</td></tr>

                <?php endforeach; ?>

            </table>

        </div>

    </div>

</div>
