<?php
/**
 * @var object $item
 * @var array<string, string> $kategoriChoices
 * @var array<string, string> $izinSablonuChoices
 * @var int $userCount
 */
use App\Helpers\FormHelper;

$isSystem = (int) ($item->is_system ?? 0) === 1;
$kategoriOptions = [];
foreach ($kategoriChoices as $val => $label) {
    $kategoriOptions[] = FormHelper::makeOption((string) $val, $label);
}
$izinOptions = [];
foreach ($izinSablonuChoices as $val => $label) {
    $izinOptions[] = FormHelper::makeOption((string) $val, $label);
}
?>
<div class="esh-page esh-page--form esh-page-unvan container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Ünvan Düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($userCount > 0): ?>
                        <div class="alert alert-info py-2 small">
                            Bu ünvanda <?= (int) $userCount ?> kullanıcı kayıtlı.
                        </div>
                    <?php endif; ?>
                    <form action="<?= htmlspecialchars(esh_url('Unvan', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= \App\Helpers\CsrfHelper::hiddenField() ?>
                        <input type="hidden" name="id" value="<?= (int) $item->id ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('kod', 'Kod', (string) ($item->kod ?? ''), [
                                    'required' => true,
                                    'extraAttrs' => $isSystem ? ['readonly' => 'readonly'] : [],
                                    'afterInput' => $isSystem ? '<div class="form-text">Sistem ünvanında kod değiştirilemez.</div>' : '',
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('ad', 'Görünen ad', (string) ($item->ad ?? ''), ['required' => true]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect('kategori', 'Kategori', $kategoriOptions, (string) ($item->kategori ?? 'diger')) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect('izin_sablonu', 'İzin şablonu', $izinOptions, (string) ($item->izin_sablonu ?? 'personel'), [
                                    'extraAttrs' => $isSystem ? ['disabled' => 'disabled'] : [],
                                    'afterInput' => $isSystem
                                        ? '<div class="form-text">Sistem ünvanında izin şablonu değiştirilemez; izinleri Rol yönetiminden düzenleyin.</div>'
                                        : '<div class="form-text">Yalnızca yeni oluşturulan ünvanlarda uygulanır.</div>',
                                ]) ?>
                                <?php if ($isSystem): ?>
                                    <input type="hidden" name="izin_sablonu" value="<?= htmlspecialchars((string) ($item->izin_sablonu ?? 'personel'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <?= FormHelper::fieldInput('sort_order', 'Sıra', (string) ($item->sort_order ?? 100), ['type' => 'number']) ?>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <?= FormHelper::fieldSwitch('aktif', 'Aktif', (int) ($item->aktif ?? 1) === 1) ?>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::textarea('mevzuat_notu', 'Mevzuat / not', (string) ($item->mevzuat_notu ?? ''), ['rows' => 2]) ?>
                            </div>
                            <div class="col-12">
                                <a href="<?= htmlspecialchars(esh_url('Role', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none">
                                    Bağlı rol izinlerini düzenle → Rol yönetimi
                                </a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-3">
                            <a href="<?= htmlspecialchars(esh_url('Unvan', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-4">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-sync-alt me-2"></i>Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
