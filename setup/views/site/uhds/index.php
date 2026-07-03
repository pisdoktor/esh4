<?php
/**
 * Uhds randevu takvimi (site).
 *
 * @var int $y
 * @var int $m
 * @var string $monthTitle
 * @var array<string,int> $countsByDay
 * @var string $selectedDate Y-m-d veya ''
 * @var array<int,object> $dayRows
 * @var array<int,object> $branslar
 * @var array{y:int,m:int} $prev
 * @var array{y:int,m:int} $next
 * @var string $prefillTc Hasta kartı kısayolu (11 hane) veya boş
 * @var string $prefillHastaLabel Görünen hasta özeti (boş olabilir)
 * @var array<int,object> $istekler esh_istekler listesi
 */
$istekler = $istekler ?? [];

$eshAppointmentCalController = 'Uhds';
$eshAppointmentCalAction = 'index';
include ROOT_PATH . '/views/site/partials/appointment_calendar_bootstrap.php';
?>
<div class="esh-page esh-page-randevu container-fluid px-2 px-lg-3 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-video text-primary me-2"></i>Uhds</h1>
            <p class="text-muted small mb-0">Güne tıklayın; aynı günde farklı branş ve farklı hastalar için randevu ekleyebilirsiniz.</p>
        </div>
    </div>

    <?php if ($prefillTc !== ''): ?>
        <div class="alert alert-info small py-2 mb-3" role="status">
            Hasta kartından geldiniz; branş ve zamanı seçip kaydedin. Farklı hasta için arama kutusunu kullanmak üzere
            <a href="<?= htmlspecialchars($linkCalNoTc($y, $m, $selectedDate !== '' ? $selectedDate : null), ENT_QUOTES, 'UTF-8') ?>" class="alert-link">bu bağlantı</a>
            ile TC kilidini kaldırın.
        </div>
    <?php endif; ?>

    <div class="row g-3 esh-page-randevu__layout">
        <div class="col-lg-6">
            <?php include ROOT_PATH . '/views/site/partials/appointment_calendar_month.php'; ?>
        </div>
        <div class="col-lg-6">
            <?php include __DIR__ . '/partials/day_panel.php'; ?>
        </div>
    </div>
</div>
