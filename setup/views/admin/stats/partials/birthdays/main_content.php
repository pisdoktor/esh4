<div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-cake-candles me-2 text-primary"></i>Bugün doğum günü olan aktif hastalar', 'main', 'h5'); ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="text-center">İzlem</th>
                        <th class="ps-4">Hasta</th>
                        <th>TC</th>
                        <th>Bölge</th>
                        <th>Doğum</th>
                        <th class="text-center">Yaş</th>
                    </tr>
                </thead>
                <tbody id="esh-stats-birthdays-tbody"
                       data-esh-fetch-url="<?= htmlspecialchars($birthdaysRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Liste yükleniyor…
                        </td>
                    </tr>
                </tbody>
            </table>