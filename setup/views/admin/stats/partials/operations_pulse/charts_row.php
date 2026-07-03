    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <?php
                $eshStatsCardTitle = 'Son 12 ay tamamlanmış izlem (adet)';
                $eshStatsPdfBlock = 'visit_months';
                $eshStatsPdfTitle = 'Aylık izlem tablosu (grafik verisi) — PDF';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <div class="card-body esh-chart-wrap--280">
                    <canvas id="pulseVisitMonths"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <?php
                $eshStatsCardTitle = 'e-Rapor havuzu';
                $eshStatsPdfBlock = 'erapor_pool';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">Toplam <strong><?= (int) ($erapor->toplam ?? 0) ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between">Sistemde eşleşen <span class="text-success fw-bold"><?= (int) ($erapor->sistemde ?? 0) ?></span></li>
                    <li class="list-group-item d-flex justify-content-between">Eşleşmeyen <span class="text-warning fw-bold"><?= (int) ($erapor->disaridan ?? 0) ?></span></li>
                    <li class="list-group-item d-flex justify-content-between">Pansumanlı aktif hasta <strong><?= (int) $pansuman ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between">Aktif hasta planlı izlem kaydı <strong><?= (int) $planned ?></strong></li>
                </ul>
            </div>
        </div>
    </div>