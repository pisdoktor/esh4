            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-video text-primary me-2 opacity-75"></i>UHDS görüntülü görüşme</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Jitsi Meet tabanlı video oda, hasta davet bağlantısı ve görüşme sonrası izlem yönlendirmesi.
                    </p>
                    <div class="row g-3">
                        <?php $operationalFields = $uhdsTelehealthFields ?? [];
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="uhds-jitsi-test-btn" data-test-url="<?= htmlspecialchars(esh_url('Uhds', 'jitsiDomainTest'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-plug-circle-check me-1"></i>Jitsi domain testi
                        </button>
                        <span class="small ms-2 text-muted" id="uhds-jitsi-test-result"></span>
                    </div>
                </div>
            </div>
            <script<?= esh_csp_nonce_attr() ?>>
            (function () {
                var btn = document.getElementById('uhds-jitsi-test-btn');
                var result = document.getElementById('uhds-jitsi-test-result');
                if (!btn || !result) return;
                btn.addEventListener('click', function () {
                    var url = btn.getAttribute('data-test-url') || '';
                    if (!url) return;
                    result.textContent = 'Test ediliyor...';
                    fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (json && json.ok) {
                                result.textContent = 'Baglanti basarili: ' + (json.domain || '');
                                result.className = 'small ms-2 text-success';
                            } else {
                                result.textContent = (json && json.message) ? json.message : 'Test basarisiz.';
                                result.className = 'small ms-2 text-danger';
                            }
                        })
                        .catch(function () {
                            result.textContent = 'Test basarisiz.';
                            result.className = 'small ms-2 text-danger';
                        });
                });
            })();
            </script>
