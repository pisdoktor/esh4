<?php
/**
 * Hasta kartı — doluluk detay modalı.
 *
 * @var object $hasta
 */
?>
<div class="modal fade" id="patientDolulukModal" tabindex="-1" aria-labelledby="patientDolulukModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="patientDolulukModalLabel">
                    <i class="fa-solid fa-chart-pie me-2 text-primary" aria-hidden="true"></i>Kart doluluk detayı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <?php include __DIR__ . '/kart_doluluk_modal_body.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
