<div class="esh-page esh-page--form esh-page-brans container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Yeni Branş Tanımla</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Brans', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= \App\Helpers\FormHelper::fieldInput('bransadi', 'Branş Adı', '', [
                            'col' => 'mb-3',
                            'id' => 'bransadi',
                            'labelClass' => 'form-label fw-bold',
                            'placeholder' => 'Örn: Nöroloji',
                            'required' => true,
                            'extraAttrs' => ['autofocus' => 'autofocus'],
                            'afterInput' => '<div class="form-text">Hastanelerdeki resmi tıbbi birim adını yazınız.</div>',
                        ]) ?>

                        <p class="small text-muted">Günlük hasta kotası kurum yöneticisi tarafından kurum branş seçimi ekranından belirlenir.</p>

                        <div class="d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('Brans', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">Vazgeç</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>