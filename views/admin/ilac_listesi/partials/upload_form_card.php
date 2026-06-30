    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold">Excel yükle ve JSON üret</h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars(esh_url('IlacListesi', 'upload'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="row g-3">
                <div class="col-12">
                    <label for="ilacListesiXlsx" class="form-label fw-semibold">E-Reçete İlaç Listesi (.xlsx)</label>
                    <input type="file" class="form-control" name="xlsx" id="ilacListesiXlsx" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    <div class="form-text">En fazla <?= (int) (25) ?> MB · yalnızca .xlsx</div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="include_pasif" value="1" id="includePasif">
                        <label class="form-check-label" for="includePasif">Pasif ürünleri de dahil et</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-file-arrow-up me-1"></i>Listeyi güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>