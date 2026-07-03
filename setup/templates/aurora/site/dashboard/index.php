<?php
/*
 * Aurora Light teması — site/dashboard/index
 * Düzen: Günün planı SOLDA, hasta arama + takvim SAĞDA (flex-lg-row-reverse).
 * Canonical script: views/site/dashboard/partials/esh_page_config.php
 */
?>
<div class="container-fluid py-4 esh-page-dashboard au-dashboard">
    <div class="row g-4 flex-lg-row-reverse">
        <div class="col-lg-8">
            <?php include __DIR__ . '/partials/hasta_ara.php'; ?>
            <?php include __DIR__ . '/partials/takvim.php'; ?>
        </div>
        <div class="col-lg-4">
            <?php include __DIR__ . '/partials/gunun_plani.php'; ?>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/views/site/dashboard/partials/esh_page_config.php'; ?>
