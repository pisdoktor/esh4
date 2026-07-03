<div class="esh-page esh-page--list esh-page-nobet container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-plane-departure me-2"></i>İzin / Rapor Talebim</h6>
                </div>
                <div class="card-body">
                    <form action="<?= htmlspecialchars(esh_url('Nobet', 'saveMineIzin'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="row g-2">
                        <div class="col-md-6"><input type="text" name="baslangic_tarihi" class="form-control form-control-sm datepicker" placeholder="Başlangıç" required autocomplete="off"></div>
                        <div class="col-md-6"><input type="text" name="bitis_tarihi" class="form-control form-control-sm datepicker" placeholder="Bitiş" required autocomplete="off"></div>
                        <div class="col-12"><input type="text" name="sebep" class="form-control form-control-sm" placeholder="Açıklama"></div>
                        <div class="col-12"><button class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Kaydet</button></div>
                    </form>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Tarih</th><th>Açıklama</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach (($izinler ?? []) as $r): ?>
                                <tr>
                                    <td><?= \App\Helpers\DateHelper::toTr((string) ($r->baslangic_tarihi ?? '')) ?> - <?= \App\Helpers\DateHelper::toTr((string) ($r->bitis_tarihi ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($r->sebep ?? '')) ?></td>
                                    <td class="text-end"><form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteMineIzin'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="id" value="<?= (int) ($r->id ?? 0) ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold text-info"><i class="fa-solid fa-hand me-2"></i>Nöbet Muafiyet İsteğim</h6>
                </div>
                <div class="card-body">
                    <form action="<?= htmlspecialchars(esh_url('Nobet', 'saveMineIstek'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="row g-2">
                        <div class="col-md-6"><input type="text" name="baslangic_tarihi" class="form-control form-control-sm datepicker" placeholder="Başlangıç" required autocomplete="off"></div>
                        <div class="col-md-6"><input type="text" name="bitis_tarihi" class="form-control form-control-sm datepicker" placeholder="Bitiş" required autocomplete="off"></div>
                        <div class="col-12"><input type="text" name="aciklama" class="form-control form-control-sm" placeholder="Neden nöbet istemiyorsunuz?"></div>
                        <div class="col-12"><button class="btn btn-sm btn-info text-white"><i class="fa-solid fa-floppy-disk me-1"></i>Kaydet</button></div>
                    </form>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Tarih</th><th>Açıklama</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach (($istekler ?? []) as $r): ?>
                                <tr>
                                    <td><?= \App\Helpers\DateHelper::toTr((string) ($r->baslangic_tarihi ?? '')) ?> - <?= \App\Helpers\DateHelper::toTr((string) ($r->bitis_tarihi ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($r->aciklama ?? '')) ?></td>
                                    <td class="text-end"><form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteMineIstek'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="id" value="<?= (int) ($r->id ?? 0) ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

