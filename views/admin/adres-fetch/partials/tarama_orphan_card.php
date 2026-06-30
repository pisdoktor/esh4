    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0"><i class="fa-solid fa-magnifying-glass me-2"></i>Eksik ilçe taraması</h5>
            <button type="button" class="btn btn-dark btn-sm" id="orphanScanBtn">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Eksik ilçe tara
            </button>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary py-2 small mb-3">
                <strong>Eksik ilçe tara</strong> ile üst zinciri kırık mahalle, sokak ve kapı kayıtları sayılır ve listelenir.
                Alt kaydı olan üst seviye satırlar silinemez; önce <strong>Kapı → Sokak → Mahalle</strong> sekmelerinden alt seviyeleri temizleyin.
            </div>

            <div id="orphanSummary" class="d-none mb-3">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" id="orphanCountMahalle">Mahalle: —</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" id="orphanCountSokak">Sokak: —</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" id="orphanCountKapino">Kapı: —</span>
                    <span class="small text-muted" id="orphanScanStatus"></span>
                </div>
                <ul class="nav nav-tabs nav-tabs-sm mb-2" id="orphanTipTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" data-orphan-tip="mahalle" id="orphanTabMahalle">Mahalle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-orphan-tip="sokak" id="orphanTabSokak">Sokak</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-orphan-tip="kapino" id="orphanTabKapino">Kapı</button>
                    </li>
                </ul>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="orphanDeleteSelectedBtn" disabled>
                        <i class="fa-solid fa-trash-can me-1"></i> Seçilenleri sil (0)
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="orphanDeleteAllBtn" disabled title="Aktif sekmedeki tüm kayıtları siler (kapı: tek tek, diğer: sunucu partisi)">
                        <i class="fa-solid fa-trash-can me-1"></i> Tüm kayıtları sil
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="orphanBulkDeleteAbortBtn">
                        <i class="fa-solid fa-stop me-1"></i> Durdur
                    </button>
                    <span class="small text-muted" id="orphanDeleteStatus"></span>
                </div>
                <div id="orphanBulkDeleteProgressWrap" class="d-none mb-2">
                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1 gap-2">
                        <span id="orphanBulkDeleteProgressLabel">0 / 0</span>
                        <span id="orphanBulkDeleteProgressHint"></span>
                    </div>
                    <div class="progress" style="height: 8px">
                        <div id="orphanBulkDeleteProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                    </div>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-striped mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width:2rem">
                                    <input type="checkbox" class="form-check-input" id="orphanCheckAll" title="Sayfadaki tümünü seç" aria-label="Sayfadaki tümünü seç">
                                </th>
                                <th>Ad</th>
                                <th>ID</th>
                                <th>Üst id</th>
                                <th>Neden</th>
                                <th style="width:4.5rem">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="orphanTableBody">
                            <tr><td colspan="6" class="text-muted">Tarama yapılmadı.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="orphanPagerWrap" class="d-none mt-2 border-top pt-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="small text-muted" id="orphanPageInfo"></div>
                            <div id="orphanLimitWrap"></div>
                        </div>
                        <div id="orphanNavWrap"></div>
                    </div>
                </div>
            </div>
            <details class="small text-muted mt-3">
                <summary class="user-select-none">CLI ile toplu temizlik (süper yönetici)</summary>
                <p class="mb-1 mt-2">Binlerce kayıt için tarayıcı yerine sunucuda:</p>
                <pre class="small bg-light border rounded p-2 mb-0"><code>php tools/purge_orphan_addresses.php --dry-run --tip=all
php tools/purge_orphan_addresses.php --tip=kapino
php tools/purge_orphan_addresses.php --tip=sokak
php tools/purge_orphan_addresses.php --tip=mahalle</code></pre>
            </details>
        </div>
    </div>