<div class="esh-page esh-page--list esh-page-adres-fetch esh-page-adres-fetch-tree container-fluid py-4">
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="<?= htmlspecialchars(esh_url('AdresFetch', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none">
            <i class="fa-solid fa-arrow-left me-1"></i> Denizli adres senkronu
        </a>
        <a href="<?= htmlspecialchars(esh_url('AdresFetch', 'tarama'), ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Eksik ilçe taraması →
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">İlçe</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['ilce'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Mahalle</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['mahalle'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Sokak</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['sokak'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Kapı no</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int) ($counts['kapino'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-sitemap me-2"></i>Adres ağacı</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary py-2 small mb-3">
                İlçe → mahalle → sokak → kapı hiyerarşisi <strong>+ / −</strong> ile açılır; alt kayıtlar istekle yüklenir.
            </div>

            <div class="mb-2">
                <input type="search" class="form-control form-control-sm" id="adresTreeSearch"
                       placeholder="Ağaçta ara (görünen düğümler)…" autocomplete="off">
            </div>
            <div class="esh-adres-tree-panel border rounded bg-light p-2">
                <ul class="esh-adres-tree list-unstyled mb-0" id="adresTreeRoot">
                    <li class="text-muted small py-2 px-1">İlçeler yükleniyor…</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.adresFetchTree = {
    treeChildrenUrl: <?= json_encode(esh_url('AdresFetch', 'ajaxTreeChildren'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
</script>
