<?php
/**
 * Profil düzenleme formu (ortak gövde).
 *
 * @var \App\Models\User $user
 * @var string $eshUiThemePartial ui_theme_select_field.php yolu
 */
$eshUser = $user ?? null;
if (!$eshUser instanceof \App\Models\User) {
    return;
}

use App\Models\User;
use App\Helpers\FormHelper;

$eshUiThemePartial = $eshUiThemePartial ?? (ROOT_PATH . '/views/partials/ui_theme_select_field.php');
$isAdmin = \App\Helpers\AuthHelper::sessionIsAdmin();
$curUnvan = (string) ($eshUser->unvan ?? '');
?>
<form action="<?= htmlspecialchars(esh_url('User', 'update'), ENT_QUOTES, 'UTF-8') ?>" method="POST" class="esh-profile-edit-form" novalidate>
    <input type="hidden" name="id" value="<?= (int) $eshUser->id ?>">

    <section class="card border-0 shadow-sm rounded-4 mb-4 esh-profile-edit-section" aria-labelledby="esh-profile-edit-general-heading">
        <div class="card-header bg-white border-bottom py-3 px-4 esh-profile-edit-section__head">
            <h2 id="esh-profile-edit-general-heading" class="h6 mb-1 fw-bold text-primary">
                <i class="fa-solid fa-address-card me-2" aria-hidden="true"></i>Genel bilgiler
            </h2>
            <p class="small text-muted mb-0">Kimlik ve iletişim bilgileriniz hasta kayıtlarında görünen ünvandır.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 g-md-4">
                <?= FormHelper::fieldInputGroup('name', 'Ad soyad', (string) $eshUser->name, [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-name',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-name',
                    'inputGroupSm' => true,
                    'prefixIcon' => 'fa-solid fa-user text-secondary',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0',
                    'required' => true,
                    'autocomplete' => 'name',
                ]) ?>
                <?= FormHelper::fieldInputGroup('email', 'E-posta', (string) ($eshUser->email ?? ''), [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-email',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-email',
                    'inputGroupSm' => true,
                    'type' => 'email',
                    'prefixIcon' => 'fa-solid fa-envelope text-secondary',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0',
                    'required' => true,
                    'autocomplete' => 'email',
                ]) ?>
                <?= FormHelper::fieldInputGroup('tckimlikno', 'TC kimlik no', (string) ($eshUser->tckimlikno ?? ''), [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-tc',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-tc',
                    'inputGroupSm' => true,
                    'prefixIcon' => 'fa-solid fa-id-card text-secondary',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0',
                    'maxlength' => '11',
                    'inputmode' => 'numeric',
                    'autocomplete' => 'off',
                    'afterInput' => '<div class="form-text small">Kimlik doğrulama işlemleri için kullanılır.</div>',
                ]) ?>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-unvan">Ünvan</label>
                    <?php if ($isAdmin): ?>
                        <?php
                        $eshProfileUnvanOptions = [];
                        foreach (User::unvanChoices() as $val => $label) {
                            $eshProfileUnvanOptions[] = FormHelper::makeOption((string) $val, $label);
                        }
                        echo FormHelper::selectList(
                            $eshProfileUnvanOptions,
                            'unvan',
                            'class="form-select form-select-sm"',
                            'value',
                            'text',
                            $curUnvan,
                            'esh-profile-unvan',
                            false
                        );
                        ?>
                    <?php else: ?>
                        <div id="esh-profile-unvan" class="form-control form-control-sm bg-light text-muted"><?= htmlspecialchars(User::unvanLabel($curUnvan !== '' ? $curUnvan : null), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="form-text small text-muted">Ünvanınızı yalnızca yönetici değiştirebilir.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-4 mb-4 esh-profile-edit-section" aria-labelledby="esh-profile-edit-account-heading">
        <div class="card-header bg-white border-bottom py-3 px-4 esh-profile-edit-section__head">
            <h2 id="esh-profile-edit-account-heading" class="h6 mb-1 fw-bold text-primary">
                <i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>Hesap ve arayüz
            </h2>
            <p class="small text-muted mb-0">Oturum adı ve kişisel tema tercihiniz.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 g-md-4">
                <?= FormHelper::fieldInputGroup('username', 'Kullanıcı adı', (string) $eshUser->username, [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-username',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-username',
                    'inputGroupSm' => true,
                    'prefixText' => '@',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0' . ($isAdmin ? '' : ' bg-light'),
                    'autocomplete' => 'username',
                    'title' => $isAdmin ? 'Admin kullanıcı adı değiştirebilir' : 'Kullanıcı adı değiştirilemez',
                    'extraAttrs' => $isAdmin ? [] : ['readonly' => 'readonly'],
                    'afterInput' => '<div class="form-text small text-muted">' . ($isAdmin ? 'Admin yetkisi ile kullanıcı adını değiştirebilirsiniz.' : 'Kullanıcı adınızı değiştiremezsiniz.') . '</div>',
                ]) ?>
                <div class="col-12">
                    <?php
                    $userUiTheme = $eshUser->ui_theme ?? null;
                    if (is_file($eshUiThemePartial)) {
                        include $eshUiThemePartial;
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-4 mb-4 esh-profile-edit-section esh-profile-edit-section--security" aria-labelledby="esh-profile-edit-security-heading">
        <div class="card-header bg-white border-bottom py-3 px-4 esh-profile-edit-section__head">
            <h2 id="esh-profile-edit-security-heading" class="h6 mb-1 fw-bold text-danger">
                <i class="fa-solid fa-shield-halved me-2" aria-hidden="true"></i>Güvenlik
            </h2>
            <p class="small text-muted mb-0">Şifrenizi değiştirmek istemiyorsanız alanları boş bırakın.</p>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-light border small py-2 mb-3 mb-md-4" role="status">
                <i class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
                Güçlü bir şifre en az 8 karakter; harf ve rakam içermelidir.
            </div>
            <div class="row g-3 g-md-4">
                <?= FormHelper::fieldInputGroup('new_password', 'Yeni şifre', '', [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-new-password',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-new-password',
                    'inputGroupSm' => true,
                    'type' => 'password',
                    'prefixIcon' => 'fa-solid fa-lock text-secondary',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0',
                    'placeholder' => '••••••••',
                    'autocomplete' => 'new-password',
                ]) ?>
                <?= FormHelper::fieldInputGroup('confirm_password', 'Yeni şifre (tekrar)', '', [
                    'col' => 'col-md-6',
                    'id' => 'esh-profile-confirm-password',
                    'labelClass' => 'form-label small fw-semibold',
                    'labelFor' => 'esh-profile-confirm-password',
                    'inputGroupSm' => true,
                    'type' => 'password',
                    'prefixIcon' => 'fa-solid fa-lock text-secondary',
                    'prefixIconClass' => 'bg-light border-end-0',
                    'class' => 'border-start-0',
                    'placeholder' => '••••••••',
                    'autocomplete' => 'new-password',
                ]) ?>
            </div>
            <div id="esh-profile-password-mismatch" class="alert alert-warning border small py-2 mt-3 mb-0 d-none" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                Şifreler uyuşmuyor. Lütfen her iki alanı da aynı şekilde doldurun.
            </div>
        </div>
    </section>

    <div class="card border-0 shadow-sm rounded-4 esh-profile-edit-actions">
        <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row justify-content-end align-items-stretch align-items-sm-center gap-2">
            <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light px-4 order-sm-1">İptal</a>
            <button type="submit" class="btn btn-primary px-4 px-md-5 fw-semibold order-sm-2">
                <i class="fa-solid fa-check me-2" aria-hidden="true"></i>Değişiklikleri kaydet
            </button>
        </div>
    </div>
</form>
