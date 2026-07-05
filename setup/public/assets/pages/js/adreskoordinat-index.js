(function () {
    const cfg = (window.ESH_PAGE && window.ESH_PAGE.coordsScan) ? window.ESH_PAGE.coordsScan : {};
    let afterId = '';
    let sessionProcessed = 0;
    let sessionOk = 0;
    let sessionFail = 0;
    let missingStart = Number(cfg.missingStart || 0);
    let totalKapino = Number(cfg.totalKapino || 0);
    let scanning = false;
    let stopRequested = false;
    let pendingTimer = null;
    let activeRequest = null;

    function fmtNum(n) {
        try {
            return Number(n).toLocaleString('tr-TR');
        } catch (e) {
            return String(n);
        }
    }

    function updateStats(data) {
        if (data.missing !== undefined) {
            $('#statMissing').text(fmtNum(data.missing));
            const has = Math.max(0, totalKapino - Number(data.missing));
            $('#statHasCoords').text(fmtNum(has));
        }
        if (data.quota) {
            $('#statQuotaUsed').text(data.quota.used);
            $('#statQuotaRemaining').text(data.quota.remaining);
        }
        $('#sessionProcessed').text(sessionProcessed);
        $('#sessionOk').text(sessionOk);
        $('#sessionFail').text(sessionFail);
    }

    function updateProgress() {
        const missingNow = parseInt(String($('#statMissing').text()).replace(/\./g, ''), 10);
        const resolved = Math.max(0, missingStart - (isNaN(missingNow) ? missingStart : missingNow));
        const base = missingStart > 0 ? missingStart : 1;
        const pct = Math.min(100, Math.round((resolved / base) * 100));
        $('#progressBar').css('width', pct + '%');
        $('#percentText').text('%' + pct);
    }

    function setScanningUi(isOn) {
        scanning = isOn;
        if (isOn) {
            $('#startBtn').prop('disabled', true).addClass('d-none');
            $('#stopBtn').removeClass('d-none').prop('disabled', false);
        } else {
            $('#startBtn').prop('disabled', false).removeClass('d-none')
                .html('<i class="fa fa-refresh me-1"></i> Yeniden başlat');
            $('#stopBtn').addClass('d-none').prop('disabled', true);
        }
    }

    function appendFailLogRow(item) {
        const note = item.mesaj || 'Koordinat bulunamadı';
        const adres = item.adres ? (' <small class="text-muted d-block">' + $('<span>').text(item.adres).html() + '</small>') : '';
        $('#logBody').prepend(
            '<tr class="table-warning">'
            + '<td>' + $('<span>').text(item.ilce || '—').html() + '</td>'
            + '<td>' + $('<span>').text(item.mahalle || '—').html() + '</td>'
            + '<td>' + $('<span>').text(item.sokak || '—').html() + '</td>'
            + '<td>' + $('<span>').text(item.kapino || '—').html() + '</td>'
            + '<td><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>'
            + $('<span>').text(note).html() + adres + '</td>'
            + '</tr>'
        );
    }

    function clearPending() {
        if (pendingTimer) {
            clearTimeout(pendingTimer);
            pendingTimer = null;
        }
        if (activeRequest && activeRequest.abort) {
            try {
                activeRequest.abort();
            } catch (e) {
                /* yoksay */
            }
            activeRequest = null;
        }
    }

    function startCoordsScan() {
        if (scanning) {
            return;
        }
        if (!confirm(String(cfg.confirm || 'Tarama baslatilsin mi?'))) {
            return;
        }
        stopRequested = false;
        afterId = '';
        sessionProcessed = 0;
        sessionOk = 0;
        sessionFail = 0;
        missingStart = parseInt(String($('#statMissing').text()).replace(/\./g, ''), 10) || missingStart;
        $('#logBody').html('<tr id="logEmptyRow"><td colspan="5" class="text-center text-muted">Başarısız kayıt olursa burada listelenir.</td></tr>');
        $('#progressArea').removeClass('d-none');
        setScanningUi(true);
        $('#statusText').text('Başlatılıyor…');
        runNext();
    }

    function stopCoordsScan() {
        if (!scanning) {
            return;
        }
        stopRequested = true;
        clearPending();
        finishScan('Tarama durduruldu. Kaldığınız yerden yeniden başlatabilirsiniz.', false, true);
    }

    function finishScan(message, isSuccess, fromUserStop) {
        stopRequested = fromUserStop === true;
        clearPending();
        setScanningUi(false);
        $('#statusText').html('<span class="' + (isSuccess ? 'text-success' : 'text-warning') + ' fw-bold">' + message + '</span>');
        if (typeof toastr !== 'undefined' && !fromUserStop) {
            if (isSuccess) {
                toastr.success(message);
            } else {
                toastr.warning(message);
            }
        } else if (typeof toastr !== 'undefined' && fromUserStop) {
            toastr.info(message);
        }
        if ($('#logBody tr').length === 0) {
            $('#logBody').html('<tr><td colspan="5" class="text-center text-muted">Başarısız kayıt yok.</td></tr>');
        }
    }

    function scheduleNext() {
        if (stopRequested) {
            return;
        }
        pendingTimer = setTimeout(function () {
            pendingTimer = null;
            runNext();
        }, 180);
    }

    function runNext() {
        if (stopRequested || !scanning) {
            return;
        }

        const processUrl = (cfg.processNextUrl && String(cfg.processNextUrl) !== '')
            ? String(cfg.processNextUrl)
            : (typeof eshUrl === 'function' ? eshUrl('AdresKoordinat', 'processNext') : (String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/') + 'index.php?controller=AdresKoordinat&action=processNext'));

        activeRequest = $.getJSON(processUrl, {
            after_id: afterId
        });

        activeRequest.done(function (data) {
            if (stopRequested) {
                return;
            }
            if (!data || data.ok === false) {
                finishScan((data && data.mesaj) ? data.mesaj : 'İşlem başarısız.', false);
                return;
            }

            if (data.quota) {
                updateStats(data);
            }
            if (data.missing !== undefined) {
                updateStats(data);
            }

            if (data.item) {
                sessionProcessed++;
                if (data.item.geocode_ok) {
                    sessionOk++;
                } else {
                    sessionFail++;
                    $('#logEmptyRow').remove();
                    appendFailLogRow(data.item);
                }
                updateStats({ missing: data.missing, quota: data.quota });
                updateProgress();
            }

            if (data.next_after_id) {
                afterId = String(data.next_after_id);
            }

            $('#statusText').text(sessionProcessed + ' kapı işlendi (başarılı: ' + sessionOk + ', başarısız: ' + sessionFail + ')…');

            if (stopRequested) {
                finishScan('Tarama durduruldu.', false, true);
                return;
            }

            if (data.done) {
                let msg = 'Tarama durdu.';
                if (data.stop_reason === 'complete') {
                    msg = 'Tüm koordinatsız kapılar işlendi.';
                } else if (data.stop_reason === 'quota') {
                    const providerLabel = String(cfg.providerLabel || 'Harita');
                    msg = 'Günlük ' + providerLabel + ' kotası doldu. Yarın veya kota boşalınca devam edebilirsiniz.';
                } else if (data.stop_reason === 'round_complete') {
                    msg = data.mesaj || 'Bu tur tamamlandı.';
                } else if (data.mesaj) {
                    msg = data.mesaj;
                }
                finishScan(msg, data.stop_reason === 'complete');
                return;
            }

            scheduleNext();
        }).fail(function (jqXHR, textStatus) {
            if (stopRequested || textStatus === 'abort') {
                return;
            }
            if (typeof toastr !== 'undefined') {
                toastr.warning('Bağlantı hatası; 3 saniye sonra yeniden denenecek…');
            }
            pendingTimer = setTimeout(function () {
                pendingTimer = null;
                runNext();
            }, 3000);
        }).always(function () {
            activeRequest = null;
        });
    }

    window.startCoordsScan = startCoordsScan;
    window.stopCoordsScan = stopCoordsScan;

    $(document).ready(function () {
        $('#startBtn').on('click', startCoordsScan);
        $('#stopBtn').on('click', stopCoordsScan);
    });
})();
