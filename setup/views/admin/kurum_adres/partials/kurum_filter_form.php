            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="<?= htmlspecialchars(esh_url('KurumAdres', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end" id="esh-kurum-adres-filter">
                        <?= esh_form_route_hiddens('KurumAdres', 'index') ?>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold" for="eshKurumAdresKurum">Kurum</label>
                            <select name="kurum_id" id="eshKurumAdresKurum" class="form-select" data-esh-auto-submit>
                                <?php foreach ($kurumlar as $k): ?>
                                    <option value="<?= (int) ($k->id ?? 0) ?>"<?= (int) ($k->id ?? 0) === $kurumId ? ' selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($k->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($selectedKurum): ?>
                        <div class="col-md-6 col-lg-8">
                            <p class="small text-muted mb-0">
                                <strong><?= htmlspecialchars((string) ($selectedKurum->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                — kod: <code><?= htmlspecialchars((string) ($selectedKurum->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></code>.
                                Atama yoksa kurum tüm adresleri görür; atama yapıldığında yalnızca yetkili bölgeler listelenir.
                            </p>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>