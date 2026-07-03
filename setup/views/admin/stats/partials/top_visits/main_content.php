<div class="card border-0 shadow-sm rounded-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('En yoğun hastalar listesi', 'main', 'h6', 'card-header bg-white py-2 border-bottom'); ?>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="align-middle">
                            <th width="72" class="text-center text-primary ps-2">
                                <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
                            </th>
                            <th width="88" class="text-center small">Durum</th>
                            <th>Hasta adı</th>
                            <th>TC</th>
                            <th>Mahalle / ilçe</th>
                            <th class="text-muted">Anne/Baba</th>
                            <th>D.Tarihi</th>
                            <th class="pe-3 text-end">Son izlem</th>
                            <th class="text-center text-nowrap">Tamamlanan</th>
                            <th class="text-nowrap">Takip süresi</th>
                            <th class="text-nowrap">Yoğunluk</th>
                        </tr>
                    </thead>
                    <tbody id="esh-stats-top-visits-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($topVisitsRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted border-0">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Liste yükleniyor…
                            </td>
                        </tr>
                    </tbody>
                </table>