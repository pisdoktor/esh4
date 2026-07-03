(function () {
    function trUpper(el) {
        if (!el || typeof el.value !== 'string') return;
        var v = el.value;
        var u = v.toLocaleUpperCase('tr-TR');
        if (u !== v) {
            var start = el.selectionStart, end = el.selectionEnd;
            el.value = u;
            if (start != null && end != null) {
                try { el.setSelectionRange(start, end); } catch (e) {}
            }
        }
    }

    function sanitizeDigits(v) {
        return (v || '').replace(/\D+/g, '').slice(0, 11);
    }

    function formatTcDisplay(tc) {
        var d = sanitizeDigits(tc);
        if (d.length !== 11) return d;
        return d.slice(0, 3) + ' ' + d.slice(3, 8) + ' ' + d.slice(8);
    }

    function setTcState(el, state) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        if (state === 'valid') el.classList.add('is-valid');
        if (state === 'invalid') el.classList.add('is-invalid');
    }

    function getEraporPatientFields() {
        return {
            isimEl: document.getElementById('erapor-field-isim'),
            soyisimEl: document.getElementById('erapor-field-soyisim'),
            kayitliEl: document.getElementById('erapor-field-kayitlimi'),
            hintEl: document.querySelector('.erapor-patient-locked-hint'),
            formEl: document.getElementById('erapor-create-form')
        };
    }

    function getOrCreateHavuzAlertBox() {
        var box = document.getElementById('esh-erapor-havuz-tc-alert');
        var textEl = document.getElementById('esh-erapor-havuz-tc-alert-text');
        if (box && textEl) {
            return { box: box, text: textEl };
        }

        var formEl = document.getElementById('erapor-create-form');
        if (!formEl) return null;

        var host = formEl.closest('.card') || formEl.closest('.au-panel') || formEl.parentElement;
        if (!host || !host.parentNode) return null;

        box = document.createElement('div');
        box.id = 'esh-erapor-havuz-tc-alert';
        box.className = 'alert alert-warning border-warning shadow-sm col-md-8 mx-auto mb-3 d-none';
        box.setAttribute('role', 'alert');
        box.setAttribute('aria-live', 'polite');
        box.innerHTML = '<div class="d-flex align-items-start gap-2">'
            + '<i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>'
            + '<div id="esh-erapor-havuz-tc-alert-text" class="small mb-0"></div>'
            + '</div>';
        host.parentNode.insertBefore(box, host);
        textEl = document.getElementById('esh-erapor-havuz-tc-alert-text');
        return box && textEl ? { box: box, text: textEl } : null;
    }

    function renderHavuzTcAlert(count, tc) {
        var ui = getOrCreateHavuzAlertBox();
        if (!ui) return;

        var n = parseInt(count, 10) || 0;
        if (n <= 0) {
            ui.box.classList.add('d-none');
            ui.text.textContent = '';
            return;
        }

        var tcFmt = formatTcDisplay(tc);
        var kayitLabel = n === 1 ? '1 kayıt' : (n + ' kayıt');
        var listUrl = eshUrl('Erapor', 'index', { search: sanitizeDigits(tc) });
        ui.text.innerHTML = '<strong>' + tcFmt + '</strong> TC Kimlik No ile e-Rapor havuzunda '
            + '<strong>' + kayitLabel + '</strong> bulunmaktadır. '
            + 'Yine de yeni kayıt ekleyebilirsiniz. '
            + '<a href="' + listUrl + '" class="alert-link">Havuzda listele</a>';
        ui.box.classList.remove('d-none');
    }

    function hideHavuzTcAlert() {
        renderHavuzTcAlert(0, '');
    }

    function setEraporKayitliHidden(locked) {
        var kayitliEl = document.getElementById('erapor-field-kayitlimi');
        var hidden = document.getElementById('erapor-kayitlimi-hidden');
        if (!kayitliEl) return;
        if (locked) {
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'kayitlimi';
                hidden.id = 'erapor-kayitlimi-hidden';
                hidden.value = kayitliEl.value || '1';
                kayitliEl.parentNode.appendChild(hidden);
            } else {
                hidden.value = kayitliEl.value || '1';
            }
            kayitliEl.disabled = true;
            return;
        }
        if (hidden) {
            hidden.remove();
        }
        kayitliEl.disabled = false;
    }

    function setEraporPatientFieldsLocked(locked) {
        var f = getEraporPatientFields();
        if (!f.isimEl || !f.soyisimEl || !f.kayitliEl) return;

        [f.isimEl, f.soyisimEl, f.kayitliEl].forEach(function (el) {
            el.classList.toggle('erapor-patient-locked', locked);
            if (el === f.kayitliEl) {
                return;
            }
            el.readOnly = locked;
            if (locked) {
                el.setAttribute('aria-readonly', 'true');
            } else {
                el.removeAttribute('aria-readonly');
            }
        });

        setEraporKayitliHidden(locked);
        if (f.hintEl) {
            f.hintEl.classList.toggle('d-none', !locked);
        }
        if (f.formEl) {
            f.formEl.setAttribute('data-erapor-patient-locked', locked ? '1' : '0');
        }
    }

    function renderTcLookupStatus(data) {
        var badgeEl = document.getElementById('tcLookupStatusBadge');
        if (!badgeEl) return;
        if (data && data.exists && data.status_text) {
            var cls = String(data.status_badge || 'secondary').trim();
            badgeEl.className = 'badge bg-' + cls;
            badgeEl.textContent = String(data.status_text);
            badgeEl.title = data.view_url ? 'Hasta kartı durumu' : '';
            badgeEl.classList.remove('d-none');
            return;
        }
        badgeEl.className = 'badge d-none';
        badgeEl.textContent = '';
        badgeEl.removeAttribute('title');
    }

    function handleTcLookupResponse(resp, tcInput) {
        if (!resp || !resp.ok) return;

        if (!resp.valid) {
            setTcState(tcInput, 'invalid');
            setEraporPatientFieldsLocked(false);
            renderTcLookupStatus(null);
            hideHavuzTcAlert();
            var helpInvalid = document.getElementById('tcLookupHelp');
            if (helpInvalid) helpInvalid.textContent = 'Geçersiz TC Kimlik No.';
            if (window.toastr) toastr.warning('Geçersiz TC Kimlik No.');
            return;
        }

        setTcState(tcInput, 'valid');
        autoFillFromPatient(resp);
        renderHavuzTcAlert(resp.havuz_count, tcInput.value);
    }

    function autoFillFromPatient(data) {
        var f = getEraporPatientFields();
        var helpEl = document.getElementById('tcLookupHelp');
        if (!f.isimEl || !f.soyisimEl || !f.kayitliEl) return;

        if (data && data.exists) {
            f.isimEl.value = String(data.isim || '').toLocaleUpperCase('tr-TR');
            f.soyisimEl.value = String(data.soyisim || '').toLocaleUpperCase('tr-TR');
            f.kayitliEl.value = '1';
            setEraporPatientFieldsLocked(true);
            renderTcLookupStatus(data);
            var statusLine = data.status_text ? (' Dosya durumu: ' + data.status_text + '.') : '';
            if (helpEl) {
                helpEl.textContent = 'Hasta kartında kayıt bulundu;' + statusLine + ' ad, soyad ve kayıt durumu kilitlendi.';
            }
            if (window.toastr) {
                var toastMsg = 'Hasta bulundu; bilgiler otomatik dolduruldu.';
                if (data.status_text) {
                    toastMsg += ' Durum: ' + data.status_text + '.';
                }
                toastr.success(toastMsg);
            }
            return;
        }

        f.kayitliEl.value = '0';
        setEraporPatientFieldsLocked(false);
        renderTcLookupStatus(null);
        if (helpEl) helpEl.textContent = 'TC hastalar tablosunda bulunamadı. Ad-soyadı manuel girebilirsiniz.';
    }

    function runTcLookup($, tcInput, formEl) {
        var excludeId = formEl ? (formEl.getAttribute('data-erapor-edit-id') || '') : '';
        return $.ajax({
            url: eshUrl('Erapor', 'tcLookupAjax', {
                tc: tcInput.value,
                exclude_id: excludeId
            }),
            method: 'GET',
            dataType: 'json',
        }).done(function (resp) {
            handleTcLookupResponse(resp, tcInput);
        }).fail(function () {
            setTcState(tcInput, null);
        });
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(function ($) {
            var formEl = document.getElementById('erapor-create-form');
            if (formEl && formEl.getAttribute('data-erapor-patient-locked') === '1') {
                setEraporPatientFieldsLocked(true);
            }

            $('.erapor-upper').on('input blur', function () {
                if (this.readOnly) return;
                trUpper(this);
            });
            $('form[action*="controller=Erapor"][action*="action=store"]').on('submit', function () {
                var tcEl = document.querySelector('input[name="hastatckimlik"]');
                if (tcEl) {
                    tcEl.value = sanitizeDigits(tcEl.value);
                    if (tcEl.value.length !== 11 || tcEl.classList.contains('is-invalid')) {
                        if (window.toastr) toastr.error('Lütfen geçerli bir TC Kimlik No giriniz.');
                        tcEl.focus();
                        return false;
                    }
                }
                $('.erapor-upper').each(function () { trUpper(this); });
            });

            var tcInput = document.querySelector('input[name="hastatckimlik"]');
            var pendingReq = null;
            if (tcInput) {
                tcInput.addEventListener('input', function () {
                    tcInput.value = sanitizeDigits(tcInput.value);
                    setTcState(tcInput, null);
                    if (pendingReq && typeof pendingReq.abort === 'function') {
                        pendingReq.abort();
                    }
                    if (tcInput.value.length < 11) {
                        setEraporPatientFieldsLocked(false);
                        renderTcLookupStatus(null);
                        hideHavuzTcAlert();
                        var helpShort = document.getElementById('tcLookupHelp');
                        if (helpShort) helpShort.textContent = 'TC girildiğinde otomatik kontrol yapılır.';
                        return;
                    }
                    pendingReq = runTcLookup($, tcInput, formEl);
                });

                if (sanitizeDigits(tcInput.value).length === 11) {
                    pendingReq = runTcLookup($, tcInput, formEl);
                }
            }
        });
    }
})();
