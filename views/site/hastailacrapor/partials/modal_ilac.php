    <div class="modal fade" id="hastailacIlacModal" tabindex="-1" aria-labelledby="hastailacIlacModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= htmlspecialchars(esh_url('HastaIlacRapor', 'updateIlac'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                    <input type="hidden" name="ilac_id" id="hastailacIlacId" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hastailacIlacModalTitle">İlaç düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body vstack gap-3">
                        <div>
                            <label class="form-label fw-semibold" for="hastailacIlacAdi">İlaç adı <span class="text-danger">*</span></label>
                            <div class="hastailacrapor-ilac-adi-wrap position-relative">
                                <input type="text" class="form-control hastailacrapor-ilac-adi-input" id="hastailacIlacAdi" name="ilac_adi" maxlength="255" required autocomplete="off" aria-autocomplete="list" aria-controls="hastailacIlacAdiSuggest" aria-expanded="false">
                                <div id="hastailacIlacAdiSuggest" class="hastailacrapor-ilac-suggest list-group shadow-sm" role="listbox" hidden></div>
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold" for="hastailacIlacRecete">Reçete türü</label>
                            <input type="text" class="form-control" id="hastailacIlacRecete" name="recete_turu" maxlength="128" autocomplete="off" placeholder="İsteğe bağlı">
                        </div>
                        <div>
                            <label class="form-label fw-semibold" for="hastailacIlacTani">İlgili tanı</label>
                            <select class="form-select" id="hastailacIlacTani" name="hastalik_icd">
                                <option value="">— Seçilmedi —</option>
                                <?php foreach ($hastalikOptions as $optIcd => $optAd): ?>
                                    <option value="<?= htmlspecialchars((string) $optIcd, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($optAd, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold" for="hastailacIlacNot">Not (Doz, kullanım şekli vs)</label>
                            <textarea class="form-control" id="hastailacIlacNot" name="not" rows="2" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
