            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check me-2 text-primary"></i>Atanmış adresler</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Seviye</th>
                                <th>Ad</th>
                                <th>Yol</th>
                                <th width="80" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="esh-kurum-adres-tbody" data-kurum-id="<?= $kurumId ?>">
                            <?php include __DIR__ . '/assignment_rows.php'; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="esh-kurum-adres-config"
                 data-kurum-id="<?= $kurumId ?>"
                 data-csrf="<?= htmlspecialchars(esh_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
                 hidden></div>