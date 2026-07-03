                    <form action="<?= htmlspecialchars(esh_url('User', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <input type="hidden" name="id" value="<?= $user->id ?>">

                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="esh-form-section">Kişisel Bilgiler</h6>
                            </div>
                            
                            <?= \App\Helpers\FormHelper::fieldInput('name', 'Ad Soyad', $user->name ?? '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small',
                                'required' => true,
                            ]) ?>

                            <?= \App\Helpers\FormHelper::fieldInput('tckimlikno', 'TC Kimlik No', $user->tckimlikno ?? '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small',
                                'maxlength' => '11',
                            ]) ?>

                            <?php
                            $eshUserUnvanOptions = [];
                            foreach (\App\Models\User::unvanChoices() as $val => $label) {
                                $eshUserUnvanOptions[] = \App\Helpers\FormHelper::makeOption((string) $val, $label);
                            }
                            echo \App\Helpers\FormHelper::fieldSelect('unvan', 'Ünvan', $eshUserUnvanOptions, (string) ($user->unvan ?? ''), [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small',
                                'tomSelect' => false,
                            ]);
                            ?>
                            <?php $eshUnvanSelectId = 'unvan'; include ROOT_PATH . '/views/partials/admin/user_unvan_role_hint.php'; ?>

                            <?= \App\Helpers\FormHelper::fieldInput('email', 'E-Posta Adresi', $user->email ?? '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small',
                                'type' => 'email',
                                'required' => true,
                            ]) ?>

                            <?= \App\Helpers\FormHelper::fieldInput('username', 'Kullanıcı Adı', $user->username ?? '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small',
                                'required' => true,
                            ]) ?>

                            <?php
                            $userUiTheme = $user->ui_theme ?? null;
                            include ROOT_PATH . '/views/partials/ui_theme_select_field.php';
                            ?>

                            <div class="col-12 mt-5">
                                <h6 class="esh-form-section">Hesap Yönetimi ve Yetkiler</h6>
                            </div>

                            <?= \App\Helpers\FormHelper::fieldInput('password', 'Şifreyi Güncelle', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold small text-danger',
                                'type' => 'password',
                                'placeholder' => 'Değiştirmek istemiyorsanız boş bırakın',
                                'afterInput' => '<div class="form-text text-muted small">Şifre alanı boş bırakılırsa mevcut şifre korunur.</div>',
                            ]) ?>

                            <?php include ROOT_PATH . '/views/partials/admin/user_isadmin_level_field.php'; ?>

                            <?php include ROOT_PATH . '/views/partials/admin/user_kurum_field.php'; ?>
                            <?php include ROOT_PATH . '/views/partials/admin/user_bolge_scope_field.php'; ?>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Hesap Durumu</label>
                                <?= \App\Helpers\FormHelper::fieldSwitch('activated', 'Aktif', !empty($user->activated), [
                                    'col' => '',
                                    'id' => 'activatedSwitch',
                                    'wrapClass' => 'form-check form-switch p-2 ps-5 border rounded bg-light',
                                    'labelClass' => 'form-check-label fw-bold text-success',
                                ]) ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Elektronik İmza</label>
                                <?= \App\Helpers\FormHelper::fieldSwitch('eimza_enabled', 'E-imza ile girişe izin ver', !empty($user->eimza_enabled), [
                                    'col' => '',
                                    'id' => 'eimzaSwitch',
                                    'wrapClass' => 'form-check form-switch p-2 ps-5 border rounded bg-light',
                                    'labelClass' => 'form-check-label fw-bold',
                                    'afterInput' => !empty($user->eimza_last_login_at)
                                        ? '<div class="form-text small">Son e-imza girişi: ' . htmlspecialchars((string) $user->eimza_last_login_at, ENT_QUOTES, 'UTF-8') . '</div>'
                                        : '',
                                ]) ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between esh-form-actions">
                            <div class="d-flex flex-wrap gap-2">
                            <a href="<?= htmlspecialchars(esh_url('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light px-4 border">
                                <i class="fa-solid fa-arrow-left me-1"></i> Listeye Dön
                            </a>
                            <?php if (\App\Helpers\AuthHelper::sessionIsSuperAdmin()
                                && (int) ($user->isadmin ?? 0) < \App\Helpers\AuthHelper::ROLE_SUPERADMIN
                                && !\App\Helpers\UserKurumTransfer::isArchivedAtSource($user)): ?>
                            <a href="<?= htmlspecialchars(esh_url('User', 'changeKurum', ['id' => (int) ($user->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info">
                                <i class="fa-solid fa-building-user me-1"></i> Kuruma nakil
                            </a>
                            <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fa-solid fa-save me-1"></i> Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
