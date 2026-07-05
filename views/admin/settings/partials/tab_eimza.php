            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-file-signature text-primary me-2 opacity-75"></i>E-imza yapılandırması (salt okunur)</h5></div>
                <div class="card-body p-0">
                    <p class="small text-muted px-3 pt-3 mb-0">
                        Sertifika zinciri, köprü URL ve hız sınırları yalnızca <code><?= htmlspecialchars($configLocalExample, ENT_QUOTES, 'UTF-8') ?></code> / <code>config.local.php</code> üzerinden yönetilir; JSON dosyasına yazılmaz.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>Alan</th><th>Değer</th><th>Not</th></tr></thead>
                            <tbody>
                                <?php foreach ($eimzaInfoRows as $infoRow): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars((string) ($infoRow['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><code class="small"><?= htmlspecialchars((string) ($infoRow['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="small text-muted"><?= htmlspecialchars((string) ($infoRow['hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
