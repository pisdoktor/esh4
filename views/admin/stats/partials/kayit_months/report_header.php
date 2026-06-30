<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-plus text-primary me-2"></i>Kayıt ayları (aktif hasta)</h4>
            <?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center">
            <?php foreach ($limitOptions as $limVal => $limLabel): ?>
                <?php
                $q = $baseQ;
                if ($limVal > 0) {
                    $q['limit'] = $limVal;
                }
                $isActive = ($limit === $limVal);
                ?>
                <a href="<?= htmlspecialchars(\App\Helpers\UrlHelper::fromRequestParams($q), ENT_QUOTES, 'UTF-8') ?>"
                   class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($limLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    </div>