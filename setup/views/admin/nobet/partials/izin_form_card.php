        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">İzin Ekle</div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'saveIzin'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
                        <div class="col-12">
                            <select class="form-select form-select-sm" name="personel_id" required>
                                <option value="">Personel seçiniz</option>
                                <?php foreach ($personeller as $p): ?>
                                    <option value="<?= htmlspecialchars((string) $p->id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $p->name) ?> (<?= htmlspecialchars((string) $p->unvan) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><input type="text" name="baslangic_tarihi" class="form-control form-control-sm datepicker" placeholder="Başlangıç" required></div>
                        <div class="col-6"><input type="text" name="bitis_tarihi" class="form-control form-control-sm datepicker" placeholder="Bitiş" required></div>
                        <div class="col-12"><input type="text" name="sebep" class="form-control form-control-sm" placeholder="Açıklama"></div>
                        <div class="col-12"><button class="btn btn-sm btn-primary">Kaydet</button></div>
                    </form>
                </div>
            </div>
        </div>
