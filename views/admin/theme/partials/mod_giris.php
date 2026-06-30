    <section class="esh-page__panel mb-3 esh-preview-mod-target" id="esh-preview-mod-giris" aria-label="Giriş sayfası">

        <div class="esh-page__panel-head">

            <div>

                <span class="esh-page__panel-title d-block">Giriş sayfası</span>

                <span class="esh-page__panel-subtitle"><code>body.page-login</code> · <code>--esh-ui-login-*</code> · iki modlu kart</span>

            </div>

        </div>

        <div class="esh-page__panel-body p-0">

            <div class="esh-theme-preview-login-sandbox page-login <?= htmlspecialchars($previewLoginThemeClass, ENT_QUOTES, 'UTF-8') ?>">

                <div class="card login-card login-card-split mx-auto mb-0">

                    <div class="login-header">

                        <div class="login-brand-badge"><i class="fa-solid fa-house-medical-flag"></i></div>

                        <h4 class="mb-1 fw-bold">SON <span class="text-white">EV</span></h4>

                        <div class="login-brand-subtitle">Evde Sağlık Hizmetleri</div>

                        <small class="opacity-75">Sürüm <?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8') ?></small>

                    </div>

                    <div class="card-body p-4">

                        <div class="row g-4 login-split-panels">

                            <div class="col-12 col-lg-6">

                                <div class="border rounded-3 p-3 p-md-4 h-100 login-mode-card login-mode-card--normal">

                                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-key me-2"></i>Normal giriş</h6>

                                    <div class="mb-3">

                                        <label class="form-label small fw-bold text-muted">Kullanıcı Adı</label>

                                        <div class="input-group">

                                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>

                                            <input type="text" class="form-control border-start-0 shadow-none" placeholder="Kullanıcı adınız" disabled value="ornek.kullanici" autocomplete="off">

                                        </div>

                                    </div>

                                    <div class="mb-3">

                                        <label class="form-label small fw-bold text-muted">Şifre</label>

                                        <div class="input-group">

                                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>

                                            <input type="password" class="form-control border-start-0 shadow-none" placeholder="••••••••" disabled autocomplete="off">

                                        </div>

                                    </div>

                                    <button type="button" class="btn btn-primary w-100 btn-login mt-3 shadow-sm" disabled>

                                        Giriş Yap <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>

                                    </button>

                                </div>

                            </div>

                            <div class="col-12 col-lg-6">

                                <div class="border rounded-3 p-3 p-md-4 h-100 bg-light-subtle login-mode-card login-mode-card--eimza">

                                    <div class="d-flex align-items-center justify-content-between mb-2">

                                        <h6 class="mb-0 fw-bold text-secondary"><i class="fa-solid fa-certificate me-2 text-primary"></i>E-imza ile giriş</h6>

                                        <span class="badge text-bg-secondary">Yakında</span>

                                    </div>

                                    <p class="small text-muted mb-3">Bu giriş tipi geçici olarak devre dışı. Aktif edildiğinde flash kontrolü ve PIN ile giriş yapılacak.</p>

                                    <div class="mb-2">

                                        <label class="form-label small fw-bold text-muted mb-1">TC Kimlik No</label>

                                        <input type="text" class="form-control form-control-sm" placeholder="11 haneli TC" disabled autocomplete="off">

                                    </div>

                                    <div class="mb-2">

                                        <label class="form-label small fw-bold text-muted mb-1">E-imza PIN</label>

                                        <input type="password" class="form-control form-control-sm" placeholder="Token PIN" disabled autocomplete="off">

                                    </div>

                                    <button type="button" class="btn btn-outline-secondary w-100 btn-sm mt-2" disabled>

                                        E-imza ile giriş yap

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card-footer bg-white border-0 pb-4 text-center">

                        <small class="text-muted">Yardım için sistem yöneticisine başvurun.</small>

                        <p class="esh-login-hastaarama small text-center mt-3 mb-0">

                            <span class="text-muted">Kayıtlı hasta sorgulama (TC)</span>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>



