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

$eshUiThemePartial = $eshUiThemePartial ?? (\App\Helpers\ThemeViewHelper::resolvePartial('ui_theme_select_field'));
$isAdmin = \App\Helpers\AuthHelper::sessionIsAdmin();
$curUnvan = (string) ($eshUser->unvan ?? '');
?>
<form action="<?= htmlspecialchars(esh_url('User', 'update'), ENT_QUOTES, 'UTF-8') ?>" method="POST" class="esh-profile-edit-form" novalidate>
    <input type="hidden" name="id" value="<?= (int) $eshUser->id ?>">

    <section class="au-panel mb-4 esh-profile-edit-section" aria-labelledby="esh-profile-edit-general-heading">
        <div class="au-panel__head esh-profile-edit-section__head">
            <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-address-card" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 id="esh-profile-edit-general-heading" class="au-panel__title">Genel bilgiler</h2>
                <p class="au-panel__sub mb-0">Kimlik ve iletişim bilgileriniz hasta kayıtlarında görünen ünvandır.</p>
            </div>
        </div>
        <div class="au-panel__body p-4">
            <div class="row g-3 g-md-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-name">Ad soyad</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-secondary" aria-hidden="true"></i></span>
                        <input type="text" id="esh-profile-name" name="name" class="form-control border-start-0" value="<?= htmlspecialchars((string) $eshUser->name, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="name">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-email">E-posta</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-secondary" aria-hidden="true"></i></span>
                        <input type="email" id="esh-profile-email" name="email" class="form-control border-start-0" value="<?= htmlspecialchars((string) ($eshUser->email ?? ''), ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-tc">TC kimlik no</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-secondary" aria-hidden="true"></i></span>
                        <input type="text" id="esh-profile-tc" name="tckimlikno" class="form-control border-start-0" value="<?= htmlspecialchars((string) ($eshUser->tckimlikno ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="11" inputmode="numeric" autocomplete="off">
                    </div>
                    <div class="form-text small">Kimlik doğrulama işlemleri için kullanılır.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-unvan">Ünvan</label>
                    <?php if ($isAdmin): ?>
                        <select id="esh-profile-unvan" name="unvan" class="form-select form-select-sm">
                            <?php foreach (User::unvanChoices() as $val => $label) :
                                $sel = ($curUnvan === (string) $val) ? ' selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') ?>"<?= $sel ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div id="esh-profile-unvan" class="form-control form-control-sm bg-light text-muted"><?= htmlspecialchars(User::unvanLabel($curUnvan !== '' ? $curUnvan : null), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="form-text small text-muted">Ünvanınızı yalnızca yönetici değiştirebilir.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="au-panel mb-4 esh-profile-edit-section" aria-labelledby="esh-profile-edit-account-heading">
        <div class="au-panel__head esh-profile-edit-section__head">
            <span class="au-icon-chip au-icon-chip--soft"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 id="esh-profile-edit-account-heading" class="au-panel__title">Hesap ve arayüz</h2>
                <p class="au-panel__sub mb-0">Oturum adı ve kişisel tema tercihiniz.</p>
            </div>
        </div>
        <div class="au-panel__body p-4">
            <div class="row g-3 g-md-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-username">Kullanıcı adı</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">@</span>
                        <input type="text" id="esh-profile-username" name="username" class="form-control border-start-0<?= $isAdmin ? '' : ' bg-light' ?>"
                               value="<?= htmlspecialchars((string) $eshUser->username, ENT_QUOTES, 'UTF-8') ?>"
                               <?= $isAdmin ? '' : 'readonly' ?>
                               title="<?= $isAdmin ? 'Admin kullanıcı adı değiştirebilir' : 'Kullanıcı adı değiştirilemez' ?>"
                               autocomplete="username">
                    </div>
                    <div class="form-text small text-muted"><?= $isAdmin ? 'Admin yetkisi ile kullanıcı adını değiştirebilirsiniz.' : 'Kullanıcı adınızı değiştiremezsiniz.' ?></div>
                </div>
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

    <section class="au-panel mb-4 esh-profile-edit-section esh-profile-edit-section--security" aria-labelledby="esh-profile-edit-security-heading">
        <div class="au-panel__head esh-profile-edit-section__head">
            <span class="au-icon-chip au-icon-chip--amber"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 id="esh-profile-edit-security-heading" class="au-panel__title text-danger">Güvenlik</h2>
                <p class="au-panel__sub mb-0">Şifrenizi değiştirmek istemiyorsanız alanları boş bırakın.</p>
            </div>
        </div>
        <div class="au-panel__body p-4">
            <div class="alert alert-light border small py-2 mb-3 mb-md-4" role="status">
                <i class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
                Güçlü bir şifre en az 8 karakter; harf ve rakam içermelidir.
            </div>
            <div class="row g-3 g-md-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-new-password">Yeni şifre</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-secondary" aria-hidden="true"></i></span>
                        <input type="password" id="esh-profile-new-password" name="new_password" class="form-control border-start-0" placeholder="••••••••" autocomplete="new-password">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="esh-profile-confirm-password">Yeni şifre (tekrar)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-secondary" aria-hidden="true"></i></span>
                        <input type="password" id="esh-profile-confirm-password" name="confirm_password" class="form-control border-start-0" placeholder="••••••••" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div id="esh-profile-password-mismatch" class="alert alert-warning border small py-2 mt-3 mb-0 d-none" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                Şifreler uyuşmuyor. Lütfen her iki alanı da aynı şekilde doldurun.
            </div>
        </div>
    </section>

    <div class="au-panel esh-profile-edit-actions">
        <div class="au-panel__body p-3 p-md-4 d-flex flex-column flex-sm-row justify-content-end align-items-stretch align-items-sm-center gap-2">
            <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light px-4 order-sm-1">İptal</a>
            <button type="submit" class="btn btn-primary px-4 px-md-5 fw-semibold order-sm-2">
                <i class="fa-solid fa-check me-2" aria-hidden="true"></i>Değişiklikleri kaydet
            </button>
        </div>
    </div>
</form>
