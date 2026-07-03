<div class="esh-page esh-page--list esh-page-hasta container-fluid py-4">
    <?php include __DIR__ . '/partials/list_passive_filter.php'; ?>

    <div class="card border-0 esh-list-table-card">
        <div class="card-body p-0">
            <div class="esh-page__table-wrap esh-patient-list-table">
                <table class="table table-hover align-middle mb-0 esh-ui-table">
                    <thead class="esh-page__table-head">
                        <?php include __DIR__ . '/partials/list_passive_table_head.php'; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <?php include __DIR__ . '/partials/list_table_row_passive.php'; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include __DIR__ . '/partials/list_pagination_footer.php'; ?>
    </div>
</div>
