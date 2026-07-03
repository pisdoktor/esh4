        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Tatil Ekle</div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'saveTatil'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
                        <div class="col-12"><input type="text" name="aciklama" class="form-control form-control-sm" placeholder="Tatil adı" required></div>
                        <div class="col-6"><input type="text" name="baslangic_tarihi" class="form-control form-control-sm datepicker" placeholder="Başlangıç" required></div>
                        <div class="col-6"><input type="text" name="bitis_tarihi" class="form-control form-control-sm datepicker" placeholder="Bitiş" required></div>
                        <div class="col-12">
                            <select name="tatil_tipi" class="form-select form-select-sm">
                                <option value="resmi_tatil" selected>Resmi Tatil</option>
                                <option value="bayram">Bayram</option>
                                <option value="dini_bayram">Dini Bayram</option>
                                <option value="milli_bayram">Milli Bayram</option>
                                <option value="idari_izin">İdari İzin</option>
                                <option value="yerel_tatil">Yerel Tatil</option>
                            </select>
                        </div>
                        <div class="col-12"><button class="btn btn-sm btn-dark">Kaydet</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
