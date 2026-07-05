<div class="esh-page esh-page--admin container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-bullhorn me-2 text-warning"></i>Sistem Duyurusu</h1>
            <p class="text-muted small mb-0">Tüm kurumlardaki yöneticilere toplu veya seçili duyuru gönderin</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Mesaj', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Gelen kutusu</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($broadcastSendUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_csrf_field() ?>
                <div class="mb-3">
                    <label for="mesaj-baslik" class="form-label">Başlık</label>
                    <input type="text" class="form-control" id="mesaj-baslik" name="baslik" maxlength="255" required>
                </div>
                <div class="mb-3">
                    <label for="mesaj-govde" class="form-label">Mesaj</label>
                    <textarea class="form-control" id="mesaj-govde" name="govde" rows="5" maxlength="4000" required></textarea>
                </div>
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="tum_kullanicilar" value="1" id="mesaj-tum-kullanicilar">
                        <label class="form-check-label" for="mesaj-tum-kullanicilar">Tüm aktif yöneticilere gönder</label>
                    </div>
                    <label class="form-label" for="mesaj-alicilar">veya alıcı seçin</label>
                    <div class="esh-tomselect-field">
                        <select name="user_ids[]" id="mesaj-alicilar" class="form-select esh-tomselect" multiple
                                data-placeholder="Alıcı kullanıcıları seçin…">
                        <?php foreach (($users ?? []) as $u):
                            $uid = \App\Helpers\IdHelper::normalizeRequestId($u->id ?? null);
                            if ($uid === null || \App\Helpers\IdHelper::idsMatch($uid, $_SESSION['user_id'] ?? null)) {
                                continue;
                            }
                            $unvan = \App\Models\User::unvanLabel((string) ($u->unvan ?? ''));
                            $label = (string) ($u->name ?? '');
                            $kurumAdi = trim((string) ($u->kurum_adi ?? ''));
                            if ($kurumAdi === '' && (int) ($u->isadmin ?? 0) >= \App\Helpers\AuthHelper::ROLE_SUPERADMIN) {
                                $kurumAdi = 'Platform';
                            }
                            if ($kurumAdi !== '') {
                                $label .= ' — ' . $kurumAdi;
                            }
                            if ($unvan !== '') {
                                $label .= ' (' . $unvan . ')';
                            }
                        ?>
                        <option value="<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-text">Birden fazla yönetici seçebilirsiniz. «Tüm aktif yöneticiler» işaretliyken bu alan devre dışı kalır.</div>
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-paper-plane me-1"></i>Duyuruyu gönder
                </button>
            </form>
        </div>
    </div>
</div>
