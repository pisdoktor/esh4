<div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                </div>
            </div>
            <button
                id="stats-visit-personnel-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#stats-visit-personnel-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="stats-visit-personnel-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="stats-visit-personnel-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'visitPersonnel'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'visitPersonnel') ?>
                <?= \App\Helpers\FormHelper::fieldDateRangeFilter('date_from', 'date_to', $date_from, $date_to, [
                    'col' => 'col-12 col-md-6',
                    'prefixIconClass' => 'bg-white text-primary border-end-0',
                ]) ?>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="fa-solid fa-filter me-1"></i>Uygula</button>
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'visitPersonnel'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('İzlem yapan personel', 'main'); ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th class="ps-3">Personel</th><th>Unvan</th><th class="text-end pe-3">İzlem adedi</th></tr></thead>
            <tbody>
                <?php $sonUnvan = null; ?>
                <?php foreach ($rows as $r): ?>
                    <?php if ((int) ($r->adet ?? 0) <= 0) { continue; } ?>
                    <?php $unvan = (string) ($r->unvan ?? 'Belirtilmemiş'); ?>
                    <?php if ($unvan !== $sonUnvan): ?>
                        <?php $sonUnvan = $unvan; ?>
                        <tr class="table-info">
                            <td colspan="3" class="ps-3">
                                <i class="fa-solid fa-layer-group me-1"></i>
                                <strong><?= htmlspecialchars(mb_strtoupper($unvan, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="ps-4"><?= htmlspecialchars((string) ($r->name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($unvan, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-end pe-3 fw-bold"><span class="badge bg-primary"><?= (int) ($r->adet ?? 0) ?> İşlem</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows) || $sonUnvan === null): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Bu aralıkta kayıt yok</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>