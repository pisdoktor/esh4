<div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small opacity-75">Toplam kayıt</h6>
                    <h2 class="fw-bold mb-0"><?= (int) ($data['summary']->toplam ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small opacity-75">Aktif hasta</h6>
                    <h2 class="fw-bold mb-0"><?= (int) ($data['summary']->aktif ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small opacity-75">Erkek hasta</h6>
                    <h2 class="fw-bold mb-0"><?= (int) ($data['summary']->erkek ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small opacity-75">Kadın hasta</h6>
                    <h2 class="fw-bold mb-0"><?= (int) ($data['summary']->kadin ?? 0) ?></h2>
                </div>
            </div>
        </div>
    </div>

    

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <?php
                $eshStatsCardTitle = '<i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Mahalle dağılımı';
                $eshStatsPdfBlock = 'mahalle';
                $eshStatsCardHeadingTag = 'h5';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light sticky-top">
                                <tr class="small text-muted">
                                    <th class="ps-4">İlçe / mahalle</th>
                                    <th class="text-center">Erkek</th>
                                    <th class="text-center">Kadın</th>
                                    <th class="text-end pe-4">Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($data['mahalleler'] ?? []) as $m): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= $m->mahalle_adi ?></div>
                                        <div class="small text-muted"><?= $m->ilce_adi ?></div>
                                    </td>
                                    <td class="text-center text-primary fw-bold"><?= $m->erkek_sayisi ?></td>
                                    <td class="text-center text-danger fw-bold"><?= $m->kadin_sayisi ?></td>
                                    <td class="text-end pe-4 fw-bold"><?= $m->toplam_hasta ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <?php
                $eshStatsCardTitle = '<i class="fa-solid fa-chart-line me-2 text-success"></i>Yıllara göre kayıt';
                $eshStatsPdfBlock = 'kayit_yili';
                $eshStatsCardHeadingTag = 'h5';
                require dirname(__DIR__) . '/stats_card_header.php';
                ?>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr class="small text-muted">
                                <th>Yıl</th>
                                <th class="text-center">E</th>
                                <th class="text-center">K</th>
                                <th class="text-end">Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($data['yillar'] ?? []) as $y): ?>
                            <tr>
                                <td class="fw-bold"><?= $y->kayityili ?></td>
                                <td class="text-center small"><?= $y->erkek_sayisi ?></td>
                                <td class="text-center small"><?= $y->kadin_sayisi ?></td>
                                <td class="text-end fw-bold"><?= $y->toplam_sayi ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>