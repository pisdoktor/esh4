<div class="esh-page esh-page--form esh-page-arac container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fa-solid fa-car me-2"></i>Yeni araç tanımı</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Arac', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <?= \App\Helpers\FormHelper::fieldInput('plaka', 'Plaka', '', [
                            'col' => 'mb-3',
                            'id' => 'plaka',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'class' => 'form-control-lg font-monospace',
                            'placeholder' => 'Örn: 20 ABC 123',
                            'required' => true,
                            'maxlength' => '32',
                            'extraAttrs' => ['autofocus' => 'autofocus'],
                        ]) ?>
                        <?= \App\Helpers\FormHelper::fieldInput('arac_bilgisi', 'Araç bilgisi (marka / model)', '', [
                            'col' => 'mb-3',
                            'id' => 'arac_bilgisi',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'placeholder' => 'Örn: Mercedes-Benz Sprinter',
                            'required' => true,
                            'maxlength' => '255',
                        ]) ?>
                        <?= \App\Helpers\FormHelper::fieldInput('kapasite', 'Kapasite (hasta)', '4', [
                            'col' => 'mb-4',
                            'id' => 'kapasite',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'type' => 'number',
                            'min' => '1',
                            'max' => '20',
                            'required' => true,
                        ]) ?>
                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Arac', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Geri</a>
                            <button type="submit" class="btn btn-success px-5">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>