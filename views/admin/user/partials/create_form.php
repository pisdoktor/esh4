                    <form action="<?= htmlspecialchars(esh_url('User', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <input type="hidden" name="id" value="">

                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="esh-form-section">Kişisel Bilgiler</h6>
                            </div>
                            
                            <?= \App\Helpers\FormHelper::fieldInput('name', 'Ad Soyad', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'placeholder' => 'Örn: Ahmet Yılmaz',
                                'required' => true,
                            ]) ?>

                            <?= \App\Helpers\FormHelper::fieldInput('tckimlikno', 'TC Kimlik No', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'maxlength' => '11',
                                'placeholder' => '11 Haneli',
                            ]) ?>

                            <?php
                            $eshUserCreateUnvanOptions = [];
                            foreach (\App\Models\User::unvanChoices() as $val => $label) {
                                $eshUserCreateUnvanOptions[] = \App\Helpers\FormHelper::makeOption((string) $val, $label);
                            }
                            echo \App\Helpers\FormHelper::fieldSelect('unvan', 'Ünvan', $eshUserCreateUnvanOptions, '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'tomSelect' => false,
                            ]);
                            ?>
                            <?php $eshUnvanSelectId = 'unvan'; include ROOT_PATH . '/views/partials/admin/user_unvan_role_hint.php'; ?>

                            <?= \App\Helpers\FormHelper::fieldInput('email', 'E-Posta Adresi', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'type' => 'email',
                                'placeholder' => 'ahmet@sirket.com',
                                'required' => true,
                            ]) ?>

                            <div class="col-12 mt-5">
                                <h6 class="esh-form-section">Hesap ve Yetki Ayarları</h6>
                            </div>

                            <?= \App\Helpers\FormHelper::fieldInput('username', 'Kullanıcı Adı', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'required' => true,
                            ]) ?>

                            <?php
                            $userUiTheme = null;
                            include ROOT_PATH . '/views/partials/ui_theme_select_field.php';
                            ?>

                            <?= \App\Helpers\FormHelper::fieldInput('password', 'Giriş Şifresi', '', [
                                'col' => 'col-md-6',
                                'labelClass' => 'form-label fw-semibold',
                                'type' => 'password',
                                'required' => true,
                            ]) ?>

                            <?php include ROOT_PATH . '/views/partials/admin/user_isadmin_level_field.php'; ?>

                            <?php include ROOT_PATH . '/views/partials/admin/user_kurum_field.php'; ?>

                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2">
                                        <?= \App\Helpers\FormHelper::fieldSwitch('activated', 'Hesabı Aktifleştir', true, [
                                            'col' => '',
                                            'id' => 'activatedSwitch',
                                            'wrapClass' => 'form-check form-switch mt-1',
                                            'labelClass' => 'form-check-label fw-bold',
                                            'afterInput' => '<small class="text-muted">Kapatılırsa kullanıcı sisteme giriş yapamaz.</small>',
                                        ]) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-2">
                                        <?= \App\Helpers\FormHelper::fieldSwitch('eimza_enabled', 'E-imza Girişi Aktif', false, [
                                            'col' => '',
                                            'id' => 'eimzaSwitch',
                                            'wrapClass' => 'form-check form-switch mt-1',
                                            'labelClass' => 'form-check-label fw-bold',
                                            'afterInput' => '<small class="text-muted">Açılırsa kullanıcı challenge + sertifika + imza ile giriş yapabilir.</small>',
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between esh-form-actions">
                            <a href="<?= htmlspecialchars(esh_url('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light px-4 border">
                                <i class="fa-solid fa-arrow-left me-1"></i> Listeye Dön
                            </a>
                            <button type="submit" class="btn btn-success px-5 fw-bold">
                                <i class="fa-solid fa-save me-1"></i> Kullanıcıyı Kaydet
                            </button>
                        </div>
                    </form>
