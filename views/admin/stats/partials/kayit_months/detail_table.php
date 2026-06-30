    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-table me-2 text-secondary"></i>Detay tablosu', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Yıl</th>
                        <th>Ay</th>
                        <th class="text-center">E</th>
                        <th class="text-center">K</th>
                        <th class="text-end pe-3">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Kayıt yok</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $y): ?>
                            <?php
                            $ayNo = (int) ($y->kayitay ?? 0);
                            $ayAdi = $turkceAylar[$ayNo] ?? '—';
                            ?>
                            <tr>
                                <td class="ps-3"><?= (int) ($y->kayityili ?? 0) ?></td>
                                <td><?= htmlspecialchars($ayAdi, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center"><?= (int) ($y->erkek_sayisi ?? 0) ?></td>
                                <td class="text-center"><?= (int) ($y->kadin_sayisi ?? 0) ?></td>
                                <td class="text-end pe-3 fw-bold"><?= (int) ($y->toplam_sayi ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td class="ps-3" colspan="2">Toplam</td>
                        <td class="text-center"><?= (int) $sumErkek ?></td>
                        <td class="text-center"><?= (int) $sumKadin ?></td>
                        <td class="text-end pe-3"><?= (int) $sumToplam ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>