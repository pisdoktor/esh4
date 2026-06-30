<?php
/**
 * Yara fotoğrafı yükleme formu (wounds sayfası alt bölüm).
 *
 * @var object $hasta
 * @var bool $pasifDosyaKapali
 */
use App\Helpers\FormHelper;

$hastaId = (int) ($hasta->id ?? 0);
?>
<div id="wound-upload" class="esh-wound-upload mt-4 pt-4 border-top">
    <?php if (!empty($pasifDosyaKapali)): ?>
        <div class="alert alert-warning mb-0">
            <i class="fa-solid fa-lock me-2"></i>Pasif dosyada yeni fotoğraf yüklenemez; mevcut kayıtlar görüntülenebilir.
        </div>
    <?php else: ?>
        <h6 class="text-danger fw-bold mb-3"><i class="fa-solid fa-upload me-2"></i>Fotoğraf yükle</h6>
        <form action="<?= htmlspecialchars(esh_url('Patient', 'uploadWoundPhoto'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" class="row g-3 p-3 border rounded bg-white shadow-sm" data-esh-required-legend="off" data-esh-required-markers="off">
            <input type="hidden" name="id" value="<?= $hastaId ?>">
            <div class="col-12 col-lg-6">
                <label class="form-label small text-muted mb-1">Fotoğraflar</label>
                <input type="file" name="wound_photo[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
                <small class="text-muted">JPG, PNG, WEBP — her dosya en fazla 8 MB</small>
            </div>
            <?= FormHelper::fieldInput('yara_bolgesi', 'Yara bölgesi', '', [
                'col' => 'col-6 col-lg-3',
                'labelClass' => 'form-label small text-muted mb-1',
                'maxlength' => '100',
                'placeholder' => 'Örn: Sol topuk',
            ]) ?>
            <div class="col-6 col-lg-3">
                <label class="form-label small text-muted mb-1">Yara evresi</label>
                <select name="yara_evresi" class="form-select">
                    <option value="">Belirtilmedi</option>
                    <option value="Evre 1">Evre 1</option>
                    <option value="Evre 2">Evre 2</option>
                    <option value="Evre 3">Evre 3</option>
                    <option value="Evre 4">Evre 4</option>
                    <option value="Diğer">Diğer</option>
                </select>
            </div>
            <?= FormHelper::fieldDate('cekim_tarihi', 'Çekim tarihi', '', [
                'col' => 'col-12 col-lg-6',
                'labelClass' => 'form-label small text-muted mb-1',
            ]) ?>
            <?= FormHelper::fieldInput('aciklama', 'Açıklama', '', [
                'col' => 'col-12 col-lg-6',
                'labelClass' => 'form-label small text-muted mb-1',
                'maxlength' => '255',
                'placeholder' => 'Açıklama (opsiyonel)',
            ]) ?>
            <div class="col-12">
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-upload me-1"></i>Fotoğrafları yükle
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
