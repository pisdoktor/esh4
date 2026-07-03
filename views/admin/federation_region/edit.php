<?php
declare(strict_types=1);

use App\Helpers\FormHelper;

/** @var \App\Models\FederationRegion $bolge */
$bolge = $bolge ?? new \App\Models\FederationRegion();
$readonlyKod = (int) ($bolge->id ?? 0) === 1;
?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fa-solid fa-map me-2"></i>Bölge düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="<?= htmlspecialchars(esh_url('FederationRegion', 'store'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) ($bolge->id ?? 0) ?>">
                        <?= FormHelper::fieldInput('ad', 'Bölge adı', (string) ($bolge->ad ?? ''), ['col' => 'col-md-8', 'labelClass' => 'form-label fw-bold', 'required' => true]) ?>
                        <?= FormHelper::fieldInput('kod', 'Kod', (string) ($bolge->kod ?? ''), [
                            'col' => 'col-md-4',
                            'labelClass' => 'form-label fw-bold',
                            'required' => true,
                            'extraAttrs' => $readonlyKod ? ['readonly' => 'readonly'] : [],
                        ]) ?>
                        <?= FormHelper::fieldInput('il_adi', 'İl', (string) ($bolge->il_adi ?? ''), ['col' => 'col-md-4', 'labelClass' => 'form-label']) ?>
                        <?= FormHelper::fieldInput('hub_node_ref', 'Hub düğüm ref', (string) ($bolge->hub_node_ref ?? ''), ['col' => 'col-md-8', 'labelClass' => 'form-label', 'class' => 'form-control-sm font-monospace']) ?>
                        <?= FormHelper::fieldTextarea('aciklama', 'Açıklama', (string) ($bolge->aciklama ?? ''), ['col' => 'col-12', 'rows' => 2]) ?>
                        <?= FormHelper::fieldCheckbox('aktif', 'Aktif', !empty($bolge->aktif), ['col' => 'col-12', 'id' => 'bolgeAktifEdit']) ?>
                        <div class="col-12 d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">Vazgeç</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
