<div class="esh-page esh-page--form esh-page-arac container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>Araç düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Arac', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">
                        <?= \App\Helpers\FormHelper::fieldInput('plaka', 'Plaka', $item->plaka ?? '', [
                            'col' => 'mb-3',
                            'id' => 'plaka',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'class' => 'form-control-lg font-monospace',
                            'required' => true,
                            'maxlength' => '32',
                        ]) ?>
                        <?= \App\Helpers\FormHelper::fieldInput('arac_bilgisi', 'Araç bilgisi', $item->arac_bilgisi ?? '', [
                            'col' => 'mb-4',
                            'id' => 'arac_bilgisi',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'required' => true,
                            'maxlength' => '255',
                        ]) ?>
                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Arac', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Geri</a>
                            <button type="submit" class="btn btn-primary px-5">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>