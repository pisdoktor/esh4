<?php
?>
<div class="modal fade" id="woundPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <h6 class="modal-title text-white fw-bold">
                    <i class="fa-solid fa-image me-2"></i>Yara Fotoğrafı
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="d-flex justify-content-between mb-2">
                    <button type="button" id="woundPrevBtn" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-chevron-left me-1"></i> Önceki
                    </button>
                    <button type="button" id="woundNextBtn" class="btn btn-outline-light btn-sm">
                        Sonraki <i class="fa-solid fa-chevron-right ms-1"></i>
                    </button>
                </div>
                <img id="woundModalImage" src="" alt="Yara fotoğrafı büyük görünüm" class="w-100 rounded" style="max-height: 70vh; object-fit: contain;" loading="lazy" decoding="async">
                <div class="mt-2 small text-light">
                    <div id="woundModalMeta" class="text-warning"></div>
                    <div id="woundModalCaption" class="mt-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>
