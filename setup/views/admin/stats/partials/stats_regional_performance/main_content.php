<div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-layer-group me-2 text-success"></i>Bölgesel izlem performansı (ilçe &gt; mahalle)', 'main', 'h5', 'card-header bg-white border-0'); ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Bölge (ilçe / mahalle)</th>
                            <th class="text-end" style="width:12%">Hasta sayısı</th>
                            <th class="text-end" style="width:12%">İzlenen (<?= (int) ($rolling_months ?? 3) ?> ay)</th>
                            <th style="width:34%">Verimlilik skoru</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $currentIlce = '';
                        if (!empty($rows)):
                            foreach ($rows as $row):
                                $ilceAd = trim((string) ($row->ilce_adi ?? ''));
                                if ($currentIlce !== $ilceAd):
                                    $currentIlce = $ilceAd;
                                    ?>
                                    <tr class="table-secondary">
                                        <td colspan="4" class="fw-bold">
                                            <i class="fa-solid fa-city me-2 text-muted" aria-hidden="true"></i><?= htmlspecialchars($ilceAd !== '' ? $ilceAd : 'İlçe belirtilmemiş', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                    <?php
                                endif;
                                $th = (int) ($row->toplam_hasta ?? 0);
                                $iz = (int) ($row->izlenen_hasta ?? 0);
                                $skor = $th > 0 ? round(($iz / $th) * 100, 1) : 0.0;
                                $barClass = 'bg-danger';
                                if ($skor >= 75) {
                                    $barClass = 'bg-success';
                                } elseif ($skor >= 45) {
                                    $barClass = 'bg-warning';
                                }
                                $mahalleAd = trim((string) ($row->mahalle_adi ?? ''));
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <i class="fa-solid fa-caret-right text-muted me-1" aria-hidden="true"></i>
                                        <?= htmlspecialchars($mahalleAd !== '' ? $mahalleAd : 'Mahalle belirtilmemiş', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-end"><span class="badge bg-secondary"><?= $th ?></span></td>
                                    <td class="text-end"><span class="badge bg-dark bg-opacity-50"><?= $iz ?></span></td>
                                    <td>
                                        <div class="progress esh-stats-progress-xl">
                                            <div class="progress-bar <?= htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8') ?> progress-bar-striped"
                                                 role="progressbar"
                                                 style="width: <?= min(100, $skor) ?>%;"
                                                 aria-valuenow="<?= htmlspecialchars((string) $skor, ENT_QUOTES, 'UTF-8') ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                %<?= htmlspecialchars((string) $skor, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr><td colspan="4" class="text-muted ps-3 py-4">Kayıtlı aktif hasta verisi bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>