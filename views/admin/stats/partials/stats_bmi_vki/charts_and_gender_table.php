    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('VKİ sınıfı dağılımı', 'main', 'h6', 'card-header bg-white'); ?>
                <div class="card-body">
                    <div class="esh-chart-wrap esh-chart-wrap--md"><canvas id="chartBmiVki"></canvas></div>
                    <table class="table table-sm mt-3 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sınıf</th>
                                <th class="text-end">Hasta</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td>
                                        <span class="d-inline-block rounded-circle me-1 align-middle" style="width:10px;height:10px;background:<?= htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8') ?>"></span>
                                        <?= htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8') ?>
                                        <span class="text-muted small">(<?= htmlspecialchars($c['short'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                    </td>
                                    <td class="text-end fw-bold"><?= (int) $c['count'] ?></td>
                                    <td class="text-end text-muted"><?= htmlspecialchars((string) $c['pct'], ENT_QUOTES, 'UTF-8') ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Cinsiyete göre VKİ sınıfı', 'tablo', 'h6', 'card-header bg-white'); ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Cinsiyet</th>
                                    <?php foreach ($catKeys as $ck): ?>
                                        <th class="text-center small"><?= htmlspecialchars($catMeta[$ck]['label'] ?? $ck, ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php endforeach; ?>
                                    <th class="text-center pe-3">Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ([\App\Helpers\CinsiyetHelper::KADIN, \App\Helpers\CinsiyetHelper::ERKEK, '?'] as $gk):
                                    $row = $byGender[$gk] ?? [];
                                    $rowTot = 0;
                                    foreach ($catKeys as $ck) {
                                        $rowTot += (int) ($row[$ck] ?? 0);
                                    }
                                    if ($rowTot === 0 && $gk === '?') {
                                        continue;
                                    }
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-medium <?= \App\Helpers\CinsiyetHelper::isKadin($gk) ? 'text-danger' : (\App\Helpers\CinsiyetHelper::isErkek($gk) ? 'text-primary' : 'text-muted') ?>">
                                            <?= htmlspecialchars(BmiHelper::genderLabel($gk), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <?php foreach ($catKeys as $ck): ?>
                                            <td class="text-center"><?= (int) ($row[$ck] ?? 0) ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-center fw-bold pe-3"><?= $rowTot ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($r['avg_by_gender'])): ?>
                        <div class="px-3 py-2 border-top small text-muted">
                            Ortalama VKİ —
                            <?php
                            $parts = [];
                            foreach ($r['avg_by_gender'] as $ag) {
                                if (($ag['count'] ?? 0) > 0 && $ag['avg'] !== null) {
                                    $parts[] = htmlspecialchars($ag['label'], ENT_QUOTES, 'UTF-8') . ': <strong>' . htmlspecialchars((string) $ag['avg'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                }
                            }
                            echo implode(' · ', $parts);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>