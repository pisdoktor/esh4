<?php
declare(strict_types=1);
/** @var object $patient */
use App\Helpers\FormHelper;
?>
<div class="mb-4" style="max-height: 300px; overflow-y: auto;">
    <label class="small fw-bold mb-2 text-muted text-uppercase">Kayıtlı Notlar</label>
    <?php include __DIR__ . '/../patient_notes_edit_list.php'; ?>
</div>
<div class="mt-3 pt-3 border-top">
    <?= FormHelper::fieldTextarea('new_note', 'Yeni Not Ekle', '', [
        'labelClass' => 'small fw-bold mb-2 text-success',
        'labelHtml' => '<i class="fa-solid fa-plus-circle me-1"></i>Yeni Not Ekle',
        'class' => 'shadow-none',
        'rows' => 3,
        'maxlength' => '2000',
        'placeholder' => 'Yeni bir not yazın (Tarih otomatik eklenecektir)...',
        'helpText' => '* Kaydet butonuna bastığınızda bu not listenin en başına eklenecektir.',
        'helpClass' => 'text-muted mt-2 d-block',
        'helpStyle' => 'font-size: 0.75rem;',
    ]) ?>
</div>
