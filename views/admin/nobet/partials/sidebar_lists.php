    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">İzinler</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <tbody>
                        <?php foreach ($izinlerAktif as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $r->personel_ad) ?></td>
                                <td>
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->baslangic_tarihi) ?>
                                    -
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->bitis_tarihi) ?>
                                </td>
                                <td><form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteIzin'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Silinsin mi?"><input type="hidden" name="id" value="<?= (int) $r->id ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Muafiyet İstekleri</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <tbody>
                        <?php foreach ($istekler as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $r->personel_ad) ?></td>
                                <td>
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->baslangic_tarihi) ?>
                                    -
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->bitis_tarihi) ?>
                                </td>
                                <td><form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteIstek'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Silinsin mi?"><input type="hidden" name="id" value="<?= (int) $r->id ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Resmi Tatiller</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <tbody>
                        <?php foreach ($tatiller as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $r->aciklama) ?></td>
                                <td>
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->baslangic_tarihi) ?>
                                    -
                                    <?= \App\Helpers\DateHelper::toTr((string) $r->bitis_tarihi) ?>
                                </td>
                                <td><form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteTatil'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Silinsin mi?"><input type="hidden" name="id" value="<?= (int) $r->id ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
