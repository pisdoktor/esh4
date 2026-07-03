            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Toplam etken</div>
                        <div class="fs-4 fw-bold" id="statEtken"><?= number_format($etkenCount, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Toplam ilaç</div>
                        <div class="fs-4 fw-bold" id="statIlac"><?= number_format($ilacCount, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">İlaçsız etken</div>
                        <div class="fs-4 fw-bold" id="statWithout"><?= number_format($withoutCount, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-3" id="statsMeta" aria-live="polite">—</p>