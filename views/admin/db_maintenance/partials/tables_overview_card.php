        <div class="col-xl-6" id="tablolar">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Tablo özeti</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 360px;">
                        <table class="table table-sm table-hover mb-0 small">
                            <thead class="table-light sticky-top"><tr><th class="ps-3">Tablo</th><th>Motor</th><th class="text-end">Tahm. satır</th><th class="text-end pe-3">MB</th></tr></thead>
                            <tbody>
                                <?php foreach ($tables as $t): ?>
                                    <tr>
                                        <td class="ps-3"><code><?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td><?= htmlspecialchars((string) ($t['eng'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end"><?= number_format((int) ($t['est_rows'] ?? 0), 0, ',', '.') ?></td>
                                        <td class="text-end pe-3"><?= htmlspecialchars((string) ($t['mb'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
