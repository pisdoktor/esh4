</div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted text-uppercase">Benzersiz hasta</div>
                    <div class="display-6 fw-bold text-success"><?= $th ?></div>
                    <div class="small text-muted mt-1">Bu ay</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted text-uppercase">Tamamlanmış izlem</div>
                    <div class="display-6 fw-bold text-primary"><?= $ti ?></div>
                    <div class="small text-muted mt-1">Bu ay</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted text-uppercase">Sıklık (izlem / hasta)</div>
                    <div class="display-6 fw-bold text-warning"><?= htmlspecialchars((string) $sik, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted mt-1">Bu ay</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-table me-2 text-secondary"></i>Son 6 ay — sıklık (izlem / hasta)', 'main', 'h5'); ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Dönem</th>
                        <th class="text-center">Benzersiz hasta</th>
                        <th class="text-center">Tamamlanmış izlem</th>
                        <th class="text-end pe-4">Sıklık</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Kayıt yok</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <?php
                            $ayNo = (int) ($row->ay ?? 0);
                            $ayAdi = $turkceAylar[$ayNo] ?? '—';
                            $donem = $ayAdi . ' ' . (int) ($row->yil ?? 0);
                            $isCurrent = ($row->ym ?? '') === date('Y-m');
                            ?>
                            <tr<?= $isCurrent ? ' class="table-success-subtle"' : '' ?>>
                                <td class="ps-4">
                                    <?= htmlspecialchars($donem, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isCurrent): ?>
                                        <span class="badge bg-success-subtle text-success border ms-1">Bu ay</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= (int) ($row->toplamhasta ?? 0) ?></td>
                                <td class="text-center"><?= (int) ($row->toplamizlem ?? 0) ?></td>
                                <td class="text-end pe-4 fw-bold"><?= htmlspecialchars((string) ($row->siklik ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>