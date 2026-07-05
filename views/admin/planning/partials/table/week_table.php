<div class="container-fluid py-4 esh-page-plan-week">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-calendar-week me-2 text-primary"></i>Haftalık plan tablosu</h4>
                <p class="small text-muted mb-0">Gün sütunları × bölge; mahalle ve aktif hasta sayısı (eski <code>planlama&amp;task=table</code>).</p>
            </div>
            <div class="no-print d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-dark" data-esh-action="window-print"><i class="fa-solid fa-print me-1"></i> Yazdır</button>
                <a href="<?= htmlspecialchars(esh_url('Planning', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">Planlama listesi</a>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive plan-week-scroll">
                <table class="table table-bordered mb-0 plan-week-table">
                    <thead>
                        <tr class="text-center small">
                            <?php foreach ($gunSirasi as $gk): ?>
                                <th class="text-white bg-dark py-2">
                                    <?= htmlspecialchars($gunUzun[$gk] ?? $gk, ENT_QUOTES, 'UTF-8') ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($gunSirasi as $gk): ?>
                                <td class="align-top p-1 bg-light">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <?php
                                        $byBolge = $grid[$gk] ?? [];
                                        if ($byBolge === []):
                                            echo '<span class="text-muted small p-2">—</span>';
                                        else:
                                            foreach ($byBolge as $bolgeNo => $mahalleler):
                                                $bTop = 0;
                                                ?>
                                                <div class="border rounded bg-white shadow-sm plan-bolge-card" style="min-width: 100px; max-width: 160px;">
                                                    <div class="text-center text-white fw-bold py-1 rounded-top" style="font-size: 11px; background: #c0392b;">
                                                        <?= (int) $bolgeNo ?>. bölge
                                                    </div>
                                                    <div class="p-1" style="font-size: 11px;">
                                                        <?php foreach ($mahalleler as $m):
                                                            $bTop += (int) ($m->hastasayisi ?? 0);
                                                            ?>
                                                            <div class="d-flex justify-content-between gap-1 border-bottom py-1 mb-0" title="<?= htmlspecialchars((string) ($m->adi ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                <span class="text-truncate"><?= htmlspecialchars((string) ($m->adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                                <span class="badge bg-danger-subtle text-danger flex-shrink-0"><?= (int) ($m->hastasayisi ?? 0) ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="text-end fw-bold pt-1" style="font-size: 10px; color: #666;">Σ <?= $bTop ?></div>
                                                    </div>
                                                </div>
                                            <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>