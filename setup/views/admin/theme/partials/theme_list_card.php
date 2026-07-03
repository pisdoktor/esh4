    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-palette me-2 text-primary"></i>Tema Yönetimi</h5>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(\App\Helpers\ThemeViewHelper::editorPageUrl(), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" title="Yeni pencerede açılır">
                    <i class="fa-solid fa-sliders me-1"></i>Tema editörü
                </a>
                <div class="text-end lh-sm">
                <span class="badge bg-primary-subtle text-primary">Site varsayılanı: <?= htmlspecialchars((string) ($siteThemeSlug ?? 'default'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <br><span class="badge bg-secondary-subtle text-secondary border mt-1">Bu oturumda görünen: <?= htmlspecialchars((string) ($effectiveThemeSlug ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Kurulu temaların listesi. <strong>Aktif yap</strong> site geneli varsayılan temayı belirler.
                Kullanıcılar kişisel tercihlerini <strong>Profil → düzenle</strong> ekranından seçebilir; boş bırakırlarsa site varsayılanı geçerlidir.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Görünen ad</th>
                            <th>Kod (slug)</th>
                            <th>Sürüm</th>
                            <th>Oluşturulma</th>
                            <th>Güncellenme</th>
                            <th>Oluşturan</th>
                            <th class="text-center">Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="esh-theme-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-theme-list-loading-row">
                            <td colspan="8" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Liste yükleniyor…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>