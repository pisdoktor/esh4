                    <div class="d-flex justify-content-end mb-3">
                        <span class="badge bg-light text-dark border">ID: #<?= $user->id ?></span>
                    </div>
                    <?php if (\App\Helpers\UserKurumTransfer::isArchivedAtSource($user)): ?>
                    <div class="alert alert-secondary border-0 small mb-4">
                        Bu hesap başka kuruma nakil edilmiş arşiv kaydıdır; giriş yapılamaz.
                    </div>
                    <?php endif; ?>
