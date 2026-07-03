<div class="esh-page esh-page--form esh-page-brans container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Branş Düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Brans', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <input type="hidden" name="id" value="<?= $item->id ?>">

                        <?= \App\Helpers\FormHelper::fieldInput('bransadi', 'Branş Adı', $item->bransadi, [
                            'col' => 'mb-3',
                            'id' => 'bransadi',
                            'labelClass' => 'form-label fw-bold',
                            'required' => true,
                        ]) ?>
                        <p class="small text-muted">Günlük hasta kotası kurum yöneticisi tarafından kurum branş seçimi ekranından belirlenir.</p>

                        <div class="d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('Brans', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-1"></i>Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>