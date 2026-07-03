    <div class="card shadow-sm border-0 mt-4">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Dönem hareket özeti', 'liste', 'h6', 'card-header bg-white'); ?>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= htmlspecialchars($period_label ?? '', ENT_QUOTES, 'UTF-8') ?> — takibe başlayan</span>
                    <span><strong><?= $newT ?></strong> <small class="text-muted">(E <?= (int) ($new->new_male ?? 0) ?> / K <?= (int) ($new->new_female ?? 0) ?>)</small></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= htmlspecialchars($period_label ?? '', ENT_QUOTES, 'UTF-8') ?> — takipten çıkan</span>
                    <span><strong><?= $exT ?></strong> <small class="text-muted">(E <?= (int) ($ex->exit_male ?? 0) ?> / K <?= (int) ($ex->exit_female ?? 0) ?>)</small></span>
                </li>
            </ul>
        </div>
    </div>