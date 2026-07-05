<?php use App\Helpers\AuthHelper; ?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fa-solid fa-building-user me-2"></i>Personel kuruma nakil
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 small mb-4">
                        <strong><?= htmlspecialchars((string) ($user->name ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        — Kullanıcı adı: <code><?= htmlspecialchars((string) ($user->username ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                        <?php if ($currentKurum): ?>
                            <br>Mevcut kurum: <strong><?= htmlspecialchars((string) ($currentKurum->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= htmlspecialchars(esh_url('User', 'storeKurum'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (string) ($user->id ?? '') ?>">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="eshUserChangeKurumSelect">Hedef kurum</label>
                            <select name="kurum_id" id="eshUserChangeKurumSelect" class="form-select" required>
                                <?php foreach ($kurumlar as $k): ?>
                                    <?php if ((int) ($k->id ?? 0) === (int) ($user->kurum_id ?? 0)) {
                                        continue;
                                    } ?>
                                    <option value="<?= (int) ($k->id ?? 0) ?>">
                                        <?= htmlspecialchars((string) ($k->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($k->kod)): ?>
                                            (<?= htmlspecialchars((string) $k->kod, ENT_QUOTES, 'UTF-8') ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Kaynak kurumda hesap pasifleştirilir; hedef kurumda aynı kullanıcı adı ve şifre ile yeni personel kaydı açılır.
                                Nöbet, izin ve ekip kayıtları kaynak hesapta kalır.
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="copy_role" id="eshUserCopyRole" value="1" checked>
                                <label class="form-check-label" for="eshUserCopyRole">Mevcut yetkiyi koru (<?= htmlspecialchars(AuthHelper::adminLevelLabel($targetLevel), ENT_QUOTES, 'UTF-8') ?>)</label>
                            </div>
                        </div>
                        <div class="col-12" id="eshUserNakilRoleWrap" style="display:none;">
                            <label class="form-label fw-semibold" for="eshUserNakilRole">Hedef kurumdaki yetki</label>
                            <select name="isadmin_level" id="eshUserNakilRole" class="form-select">
                                <?php foreach ($assignableLevels as $level): ?>
                                    <?php if ($level === AuthHelper::ROLE_SUPERADMIN) {
                                        continue;
                                    } ?>
                                    <option value="<?= $level ?>"<?= $targetLevel === $level ? ' selected' : '' ?>>
                                        <?= htmlspecialchars(AuthHelper::adminLevelLabel($level), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Nakil et
                            </button>
                            <a href="<?= htmlspecialchars(esh_url('User', 'adminEdit', ['id' => (string) ($user->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Personel düzenleme</a>
                            <a href="<?= htmlspecialchars(esh_url('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Listeye dön</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
