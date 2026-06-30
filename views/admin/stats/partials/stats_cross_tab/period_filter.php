    <?php if ($periodType !== ''): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <div class="row g-3 align-items-center">
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary mb-2">Dönem (ay)</label>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <?php foreach ($periodMonthChoices as $choice):
                                $q = $periodFilterBaseQ;
                                $q['months'] = $choice;
                                $isActive = $months === $choice;
                                ?>
                                <a href="<?= htmlspecialchars(\App\Helpers\UrlHelper::fromRequestParams($q), ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm <?= $isActive ? 'btn-success' : 'btn-outline-secondary' ?>">
                                    Son <?= (int) $choice ?> ay
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>