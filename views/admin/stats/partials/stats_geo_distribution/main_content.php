<p class="small text-muted mb-3">Aktif hasta: <strong><?= $aktif ?></strong> — adres kayıtlı ilçe / mahalle kırılımı.</p>
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('İlçe (tümü)', 'grafik'); ?>
                <div class="card-body" style="height: min(420px, <?= max(200, count($ilceLabels) * 28) ?>px);">
                    <?php
                    $chartId = 'geoIlceChart';
                    $chartLabels = $ilceLabels;
                    $chartValues = $ilceValues;
                    $barColor = '#dc3545';
                    require dirname(__DIR__) . '/chart_hbar.php';
                    ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('En yoğun mahalleler (ilk ' . count($mahalle) . ')', 'mahalle_grafik'); ?>
                <div class="card-body" style="height: min(420px, <?= max(200, count($mahLabels) * 28) ?>px);">
                    <?php
                    $chartId = 'geoMahalleChart';
                    $chartLabels = $mahLabels;
                    $chartValues = $mahValues;
                    $barColor = '#fd7e14';
                    require dirname(__DIR__) . '/chart_hbar.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Mahalle tablosu', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th class="ps-3">İlçe</th><th>Mahalle</th><th class="text-end pe-3">Hasta</th></tr></thead>
                <tbody>
                    <?php foreach ($mahalle as $r): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars((string) ($r->ilce_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($r->mahalle_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-3 fw-bold"><?= (int) ($r->adet ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>