(function () {
    var challengeBtn = document.getElementById('esh-eimza-challenge-btn');
    var loginBtn = document.getElementById('esh-eimza-login-btn');
    var challengeEl = document.getElementById('esh-eimza-challenge');
    var challengeIdEl = document.getElementById('esh-eimza-challenge-id');
    var tcEl = document.getElementById('esh-eimza-tc');
    var pinEl = document.getElementById('esh-eimza-pin');
    var tokenCheckBtn = document.getElementById('esh-eimza-token-check-btn');
    var sigEl = document.getElementById('esh-eimza-signature');
    var certEl = document.getElementById('esh-eimza-certificate');
    var msgEl = document.getElementById('esh-eimza-login-message');
    if (!challengeBtn || !loginBtn || !challengeEl || !challengeIdEl || !tcEl || !sigEl || !certEl || !msgEl) {
        return;
    }
    var bridgeCfg = (window.ESH_EIMZA && typeof window.ESH_EIMZA === 'object') ? window.ESH_EIMZA : {};
    var bridgeEnabled = !!bridgeCfg.localBridgeEnabled;
    var bridgeBase = String(bridgeCfg.localBridgeBaseUrl || '').replace(/\/+$/, '');

    function showMessage(text, ok) {
        msgEl.classList.remove('d-none', 'text-danger', 'text-success');
        msgEl.classList.add(ok ? 'text-success' : 'text-danger');
        msgEl.textContent = text || '';
    }

    challengeBtn.addEventListener('click', function () {
        challengeBtn.disabled = true;
        showMessage('', true);
        fetch(eshUrl('Auth', 'eimzaChallenge'), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().then(function (data) { return { res: res, data: data }; });
        }).then(function (payload) {
            if (!payload.res.ok || !payload.data || !payload.data.ok) {
                showMessage((payload.data && payload.data.error) ? payload.data.error : 'Challenge üretilemedi.', false);
                return;
            }
            challengeIdEl.value = String(payload.data.challenge_id || '');
            challengeEl.value = payload.data.challenge || '';
            showMessage('Challenge oluşturuldu. İmzalayıp devam edin.', true);
        }).catch(function () {
            showMessage('Challenge üretimi sırasında ağ hatası oluştu.', false);
        }).finally(function () {
            challengeBtn.disabled = false;
        });
    });

    function checkTokenStatus() {
        if (!bridgeEnabled || !bridgeBase) {
            showMessage('Yerel e-imza köprüsü kapalı.', false);
            return Promise.resolve(null);
        }
        return fetch(bridgeBase + '/health', {
            method: 'GET',
            credentials: 'omit'
        }).then(function (res) {
            return res.json().then(function (data) { return { res: res, data: data }; });
        }).then(function (payload) {
            if (!payload.res.ok || !payload.data || !payload.data.ok || payload.data.token_present !== true) {
                showMessage('E-imza flash bulunamadı veya bridge hazır değil.', false);
                return null;
            }
            if (!tcEl.value && payload.data.tc_kimlikno) {
                tcEl.value = String(payload.data.tc_kimlikno);
            }
            showMessage('E-imza flash algılandı.', true);
            return payload.data;
        }).catch(function () {
            showMessage('Yerel e-imza servisine bağlanılamadı (bridge).', false);
            return null;
        });
    }

    if (tokenCheckBtn) {
        tokenCheckBtn.addEventListener('click', function () {
            tokenCheckBtn.disabled = true;
            checkTokenStatus().finally(function () {
                tokenCheckBtn.disabled = false;
            });
        });
    }

    loginBtn.addEventListener('click', function () {
        loginBtn.disabled = true;
        var tc = String(tcEl.value || '').replace(/\D/g, '');
        var challengeId = String(challengeIdEl.value || '').trim();
        var signature = String(sigEl.value || '').trim();
        var cert = String(certEl.value || '').trim();
        if (challengeId === '') {
            showMessage('Önce challenge üretin.', false);
            loginBtn.disabled = false;
            return;
        }

        var useBridge = bridgeEnabled && bridgeBase && pinEl;
        var signPromise = Promise.resolve({
            signature_b64: signature,
            certificate_pem: cert,
            tc_kimlikno: tc
        });

        if (useBridge) {
            var pin = String(pinEl.value || '');
            if (pin === '') {
                showMessage('PIN alanı zorunlu.', false);
                loginBtn.disabled = false;
                return;
            }
            signPromise = checkTokenStatus().then(function (statusData) {
                if (!statusData) {
                    return null;
                }
                return fetch(bridgeBase + '/sign', {
                    method: 'POST',
                    credentials: 'omit',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        challenge: String(challengeEl.value || ''),
                        pin: pin
                    })
                }).then(function (res) {
                    return res.json().then(function (data) { return { res: res, data: data }; });
                }).then(function (payload) {
                    if (!payload.res.ok || !payload.data || !payload.data.ok) {
                        showMessage((payload.data && payload.data.error) ? payload.data.error : 'Token imzalama başarısız.', false);
                        return null;
                    }
                    return {
                        signature_b64: String(payload.data.signature_b64 || ''),
                        certificate_pem: String(payload.data.certificate_pem || ''),
                        tc_kimlikno: String(payload.data.tc_kimlikno || tc)
                    };
                }).catch(function () {
                    showMessage('Yerel imzalama sırasında ağ hatası oluştu.', false);
                    return null;
                });
            });
        }

        signPromise.then(function (signedPayload) {
            if (!signedPayload) {
                return;
            }
            signature = String(signedPayload.signature_b64 || '').trim();
            cert = String(signedPayload.certificate_pem || '').trim();
            tc = String(signedPayload.tc_kimlikno || tc).replace(/\D/g, '');
            if (signature === '' || cert === '') {
                showMessage('İmza veya sertifika boş döndü.', false);
                return;
            }
            if (tc !== '' && tc.length !== 11) {
                showMessage('TC bilgisi geçersiz.', false);
                return;
            }
            var fd = new FormData();
            fd.append('challenge_id', challengeId);
            fd.append('tc_kimlikno', tc);
            fd.append('signature_b64', signature);
            fd.append('certificate_pem', cert);
            return fetch(eshUrl('Auth', 'eimzaLogin'), {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                return res.json().then(function (data) { return { res: res, data: data }; });
            }).then(function (payload) {
                if (!payload.res.ok || !payload.data || !payload.data.ok) {
                    showMessage((payload.data && payload.data.error) ? payload.data.error : 'E-imza ile giriş başarısız.', false);
                    return;
                }
                showMessage('Giriş başarılı, yönlendiriliyor...', true);
                window.location.href = payload.data.redirect || eshUrl('Dashboard', 'index');
            }).catch(function () {
                showMessage('E-imza giriş isteğinde ağ hatası oluştu.', false);
            });
        }).catch(function () {
            showMessage('İmzalama akışı beklenmedik hatayla kesildi.', false);
        }).finally(function () {
            loginBtn.disabled = false;
        });
    });
})();
