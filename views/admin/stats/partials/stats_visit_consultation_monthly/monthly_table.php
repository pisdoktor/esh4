    <div class="card border-0 shadow-sm">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Aylık özet', 'main', 'h6', 'card-header bg-white border-bottom'); ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ay</th>
                            <th class="text-end">Toplam izlem</th>
                            <th class="text-end">Branş girilen izlem</th>
                            <th class="text-end">İstek girilen izlem</th>
                            <th class="text-end">Branş–istek çifti</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($monthRows): foreach ($monthRows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($monthLabels[$r->ym] ?? $r->ym), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= (int) ($r->izlem_adet ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($r->bransli_izlem ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($r->istekli_izlem ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($r->ciftli_izlem ?? 0) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Seçilen aralıkta kayıt bulunamadı.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>