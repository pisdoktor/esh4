                    <div class="p-3 border-bottom bg-body-tertiary bg-opacity-10">
                        <form action="<?= htmlspecialchars(esh_url('HastaIlacRapor', 'storeIlac'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="row g-2 align-items-end hastailacrapor-ilac-quick-add">
                            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1" for="ilacAdiQuick">İlaç adı <span class="text-danger">*</span></label>
                                <div class="hastailacrapor-ilac-adi-wrap position-relative">
                                    <input type="text" class="form-control form-control-sm hastailacrapor-ilac-adi-input" id="ilacAdiQuick" name="ilac_adi" maxlength="255" required autocomplete="off" aria-autocomplete="list" aria-controls="ilacAdiQuickSuggest" aria-expanded="false">
                                    <div id="ilacAdiQuickSuggest" class="hastailacrapor-ilac-suggest list-group shadow-sm" role="listbox" hidden></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small fw-semibold mb-1" for="ilacReceteQuick">Reçete türü</label>
                                <input type="text" class="form-control form-control-sm" id="ilacReceteQuick" name="recete_turu" maxlength="128" autocomplete="off" placeholder="İsteğe bağlı">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small fw-semibold mb-1" for="ilacTaniQuick">İlgili tanı</label>
                                <select class="form-select form-select-sm" id="ilacTaniQuick" name="hastalik_icd">
                                    <option value="">— Seçilmedi —</option>
                                    <?php foreach ($hastalikOptions as $optIcd => $optAd): ?>
                                        <option value="<?= htmlspecialchars((string) $optIcd, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($optAd, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small fw-semibold mb-1" for="ilacNotQuick">Not (Doz, kullanım şekli vs)</label>
                                <input type="text" class="form-control form-control-sm" id="ilacNotQuick" name="not" maxlength="500" autocomplete="off">
                            </div>
                            <div class="col-12 col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-plus me-1"></i>Ekle</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>İlaç</th>
                                    <th>Not (Doz, kullanım şekli vs)</th>
                                    <th>İlgili tanı</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="esh-hir-ilac-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($ilacRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Liste yükleniyor…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
