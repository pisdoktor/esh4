<div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Kayıt tarihinden önce ilk izlem', 'main'); ?>
        <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th class="ps-3">ID</th><th class="text-center">İzlem</th><th>Hasta</th><th>Kayıt</th><th>İlk izlem</th><th class="text-center">Gün farkı</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3"><?= (int) ($r->id ?? 0) ?></td>
                        <td class="text-center" style="width: 76px;">
                            <?= \App\Helpers\UIHelper::patientSummaryButtons(
                                (string) ($r->tckimlik ?? ''),
                                (int) ($r->izlemsayisi ?? 0),
                                (int) ($r->yizlemsayisi ?? 0),
                                (int) ($r->totalplanli ?? 0)
                            ) ?>
                        </td>
                        <td><?= \App\Helpers\UIHelper::patientStatsCardLink($r) ?></td>
                        <td><?= htmlspecialchars((string) ($r->kayit_tr ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-danger fw-semibold"><?= htmlspecialchars((string) ($r->ilk_izlem_tr ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center"><span class="badge bg-danger"><?= abs((int) ($r->gun_fark ?? 0)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-success py-4"><i class="fa-solid fa-check-circle me-1"></i>Uyumsuzluk bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>