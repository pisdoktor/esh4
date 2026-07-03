<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fa-solid fa-map me-2"></i>Yeni federasyon bölgesi</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="<?= htmlspecialchars(esh_url('FederationRegion', 'store'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <?= FormHelper::fieldInput('ad', 'Bölge adı', '', ['col' => 'col-md-8', 'labelClass' => 'form-label fw-bold', 'required' => true]) ?>
                        <?= FormHelper::fieldInput('kod', 'Kod', '', ['col' => 'col-md-4', 'labelClass' => 'form-label fw-bold', 'required' => true, 'placeholder' => 'denizli-merkez']) ?>
                        <?= FormHelper::fieldInput('il_adi', 'İl', '', ['col' => 'col-md-4', 'labelClass' => 'form-label']) ?>
                        <?= FormHelper::fieldInput('hub_node_ref', 'Hub düğüm ref', '', ['col' => 'col-md-8', 'labelClass' => 'form-label', 'class' => 'form-control-sm font-monospace']) ?>
                        <?= FormHelper::fieldTextarea('aciklama', 'Açıklama', '', ['col' => 'col-12', 'rows' => 2]) ?>
                        <?= FormHelper::fieldCheckbox('aktif', 'Aktif', true, ['col' => 'col-12', 'id' => 'bolgeAktif']) ?>
                        <div class="col-12 d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">Vazgeç</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
