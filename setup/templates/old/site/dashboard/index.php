<?php
/*
 * Old tema — site/dashboard/index
 * Düzen: Günün planı SOLDA, hasta arama + takvim SAĞDA.
 * Canonical script: views/site/dashboard/partials/esh_page_config.php
 */
?>
<div class="container-fluid py-3 esh-page-dashboard">
    <div class="row g-3">
        <div class="col-lg-4">
            <?php include __DIR__ . '/partials/gunun_plani.php'; ?>
        </div>
        <div class="col-lg-8">
            <?php include __DIR__ . '/partials/hasta_ara.php'; ?>
            <?php include __DIR__ . '/partials/takvim.php'; ?>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/views/site/dashboard/partials/esh_page_config.php'; ?>
