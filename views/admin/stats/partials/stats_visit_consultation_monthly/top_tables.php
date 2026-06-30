    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('En sık branşlar', 'brans', 'h6', 'card-header bg-white border-bottom'); ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Branş</th><th class="text-end">Adet</th></tr></thead>
                            <tbody>
                            <?php if ($bransTop): foreach ($bransTop as $label => $count): ?>
                                <tr><td><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></td><td class="text-end fw-bold"><?= (int) $count ?></td></tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Veri bulunamadı.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('En sık konsültasyon istekleri', 'istek', 'h6', 'card-header bg-white border-bottom'); ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>İstek</th><th class="text-end">Adet</th></tr></thead>
                            <tbody>
                            <?php if ($istekTop): foreach ($istekTop as $label => $count): ?>
                                <tr><td><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></td><td class="text-end fw-bold"><?= (int) $count ?></td></tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Veri bulunamadı.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>