<div class="modal fade" id="modalAdresEditor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-adres-baslik">Kayıt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="f-adres-id" value="">
                <input type="hidden" id="f-adres-tip" value="">
                <input type="hidden" id="f-adres-ustid" value="">
                <p class="small fw-semibold text-primary mb-2" id="f-adres-bilgi"></p>
                <label class="form-label fw-bold" for="f-adres-adi" id="label-adres-adi">Ad</label>
                <input type="text" class="form-control" id="f-adres-adi" maxlength="255" autocomplete="off">
                <div id="wrap-kapino-coords" class="d-none mt-3">
                    <label class="form-label fw-bold" for="f-adres-coords">Koordinat (enlem,boylam)</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="f-adres-coords" maxlength="255" placeholder="38.123456,29.123456" autocomplete="off">
                        <button type="button" class="btn btn-outline-primary" id="btn-adres-geocode" title="TomTom ile bul">
                            <i class="fa-solid fa-location-crosshairs"></i>
                        </button>
                    </div>
                    <p class="form-text small mb-0">Harita ve rota bu kapı no kaydındaki koordinatı kullanır; aynı kapıdaki tüm hastalar paylaşır.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="btn-adres-kaydet">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>