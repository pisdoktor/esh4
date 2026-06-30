<ul class="nav nav-tabs nav-justified bg-light border-0 esh-detail-tabs" id="hastaTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active py-3 border-0 text-dark" data-bs-toggle="tab" data-bs-target="#geneltab" type="button">
            <i class="fa-solid fa-circle-info me-1"></i><span class="small fw-bold">Genel</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-3 border-0 text-primary" data-bs-toggle="tab" data-bs-target="#pansumantab" type="button">
            <i class="fa-solid fa-band-aid me-1"></i><span class="small fw-bold">Pansuman</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-3 border-0 text-danger" data-bs-toggle="tab" data-bs-target="#sondatab" type="button">
            <i class="fa-solid fa-syringe me-1"></i><span class="small fw-bold">Sonda</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-3 border-0 text-warning" data-bs-toggle="tab" data-bs-target="#mamatab" type="button">
            <i class="fa-solid fa-bottle-droplet me-1"></i><span class="small fw-bold">Mama</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-3 border-0 text-info" data-bs-toggle="tab" data-bs-target="#beztab" type="button">
            <i class="fa-solid fa-baby-carriage me-1"></i><span class="small fw-bold">Bez</span>
        </button>
    </li>
</ul>

<div class="tab-content p-4 esh-patient-detail-tab-content" id="hastaTabContent">
    <div class="tab-pane fade show active" id="geneltab" role="tabpanel">
        <?php include __DIR__ . '/detail_tab_genel.php'; ?>
    </div>
    <div class="tab-pane fade" id="pansumantab" role="tabpanel">
        <?php include __DIR__ . '/detail_tab_pansuman.php'; ?>
    </div>
    <div class="tab-pane fade" id="sondatab" role="tabpanel">
        <?php include __DIR__ . '/detail_tab_sonda.php'; ?>
    </div>
    <div class="tab-pane fade" id="mamatab" role="tabpanel">
        <?php include __DIR__ . '/detail_tab_mama.php'; ?>
    </div>
    <div class="tab-pane fade" id="beztab" role="tabpanel">
        <?php include __DIR__ . '/detail_tab_bez.php'; ?>
    </div>
</div>
