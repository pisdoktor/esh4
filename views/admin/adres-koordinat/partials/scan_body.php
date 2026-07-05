            <?php $providerLabel = (string) ($quota['provider_label'] ?? ($activeMapProvider['label'] ?? 'Harita')); ?>
            <div class="alert alert-info py-2 small mb-3">
                Koordinatsız kapı kayıtları <strong>tek tek</strong> işlenir: ilçe → mahalle → sokak → kapı hiyerarşisinden adres metni oluşturulur, <?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?> Geocode API ile sorgulanır, sonuç <code>esh_adrestablosu.coords</code> alanına yazılır.
                Günlük <strong><?= (int) ($quota['limit'] ?? 2500) ?></strong> sorgu kotası <strong>tüm sistemde ortaktır</strong> (bu tarama, hasta kartı adres değişikliği, yeni kapı no vb.); pratikte her gün tam 2500 kapı taranamayabilir.
                Kota dolunca veya <strong>Taramayı durdur</strong> ile işlem kesilir; alttaki listede yalnızca <strong>başarısız</strong> kayıtlar gösterilir (~<?= number_format((int) ($missingCount ?? 0), 0, ',', '.') ?> koordinatsız kapı).
            </div>

            <div id="progressArea" class="mb-3 d-none">
                <div class="d-flex justify-content-between mb-1 small fw-bold">
                    <span>İşlem: <span id="statusText">Hazır</span></span>
                    <span id="percentText">%0</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="small text-muted mt-1">
                    Bu oturum: <span id="sessionProcessed">0</span> sorgu —
                    Başarılı: <span id="sessionOk" class="text-success">0</span> —
                    Başarısız: <span id="sessionFail" class="text-danger">0</span>
                </div>
            </div>

            <p class="small fw-semibold text-muted mb-2">Bu oturumda başarısız kayıtlar</p>
            <div class="table-responsive" style="max-height: 450px;">
                <table class="table table-sm table-hover border">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th>İlçe</th>
                            <th>Mahalle</th>
                            <th>Sokak</th>
                            <th>Kapı no</th>
                            <th>Hata / not</th>
                        </tr>
                    </thead>
                    <tbody id="logBody">
                        <tr><td colspan="5" class="text-center text-muted">Tarama başlayınca yalnızca bulunamayan adresler listelenir.</td></tr>
                    </tbody>
                </table>
            </div>