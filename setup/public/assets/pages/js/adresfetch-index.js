(function () {

    const cfg = (window.ESH_PAGE && window.ESH_PAGE.adresFetch) ? window.ESH_PAGE.adresFetch : {};

    let jobId = String(cfg.initialJobId || '');

    let resumableJobId = String(cfg.resumableJobId || '');

    let scanning = false;

    let stopRequested = false;

    let pendingTimer = null;

    let activeRequest = null;

    let stepCounter = 0;

    let renderedLogCount = 0;

    const LOOP_DELAY_MS = 60;

    const MAX_LOG_DOM_LINES = 4000;

    const STATUS_LABELS = {
        running: 'Çalışıyor',
        queued: 'Kuyrukta',
        completed: 'Tamamlandı',
        cancelled: 'Durduruldu',
        error: 'Hata'
    };



    function shortJobId(id) {

        const s = String(id || '');

        return s.length > 14 ? (s.slice(0, 14) + '…') : (s || '—');

    }



    function updateActiveJobBanner(data) {

        const $ban = $('#activeBanner');

        if (!$ban.length) {

            return;

        }

        const status = data && data.status ? String(data.status) : '';

        const show = scanning && jobId !== '' && status === 'running';

        if (!show) {

            $ban.addClass('d-none');

            return;

        }

        $ban.removeClass('d-none');

        $('#activeJobIdShort').text(shortJobId(jobId));

        const phase = (data && data.phase_label) ? String(data.phase_label).trim() : '';

        const label = STATUS_LABELS[status] || status;

        $('#activeJobStepEcho').text(phase !== '' ? (label + ' · ' + phase) : label);

    }



    function fmtNum(n) {

        try {

            return Number(n).toLocaleString('tr-TR');

        } catch (e) {

            return String(n);

        }

    }



    function updateCounts(counts) {

        if (!counts) {

            return;

        }

        if (counts.ilce !== undefined) {

            $('#statIlce').text(fmtNum(counts.ilce));

        }

        if (counts.mahalle !== undefined) {

            $('#statMahalle').text(fmtNum(counts.mahalle));

        }

        if (counts.sokak !== undefined) {

            $('#statSokak').text(fmtNum(counts.sokak));

        }

        if (counts.kapino !== undefined) {

            $('#statKapino').text(fmtNum(counts.kapino));

        }

    }



    function updateProgress(data) {

        const pct = Math.min(100, Math.max(0, Number(data.progress || 0)));

        $('#progressBar').css('width', pct + '%');

        $('#percentText').text('%' + pct);

        if (data.phase_label) {

            $('#phaseDetail').text(data.phase_label);

        }

        if (data.mesaj) {

            $('#statusText').text(data.mesaj);

        } else if (data.phase_label) {

            $('#statusText').text(data.phase_label);

        }

        updateActiveJobBanner(data);

    }



    function appendLogLines(lines, reset) {

        if (!Array.isArray(lines) || lines.length === 0) {

            return;

        }

        const $box = $('#logBox');

        if (reset) {

            $box.empty();

            renderedLogCount = 0;

        }

        lines.forEach(function (line) {

            $box.append($('<div>').text(String(line)));

        });

        renderedLogCount += lines.length;

        const children = $box.children();

        if (children.length > MAX_LOG_DOM_LINES) {

            children.slice(0, children.length - MAX_LOG_DOM_LINES).remove();

        }

        const el = document.getElementById('logBox');

        if (el) {

            el.scrollTop = el.scrollHeight;

        }

    }



    function ingestLogResponse(data, reset) {

        if (!data) {

            return;

        }

        if (Array.isArray(data.log_new) && data.log_new.length > 0) {

            appendLogLines(data.log_new, !!reset);

            return;

        }

        if (!Array.isArray(data.log) || data.log.length === 0) {

            return;

        }

        if (reset || data.log.length < renderedLogCount) {

            appendLogLines(data.log, true);

            return;

        }

        if (data.log.length > renderedLogCount) {

            appendLogLines(data.log.slice(renderedLogCount), false);

        }

    }



    function updateIdleButtons() {

        const hasResumable = resumableJobId !== '';

        if (hasResumable) {

            $('#resumeBtn').removeClass('d-none');

            $('#resumeHint').removeClass('d-none');

            const label = String(cfg.resumablePhaseLabel || '').trim();

            $('#resumeHintText').text(

                label !== ''

                    ? ('Tamamlanmamış senkron: ' + label)

                    : 'Tamamlanmamış bir senkron bulundu; kaldığı yerden devam edebilirsiniz.'

            );

            $('#startBtn').html('<i class="fa fa-play me-1"></i> Sıfırdan başlat');

        } else {

            $('#resumeBtn').addClass('d-none');

            $('#resumeHint').addClass('d-none');

            $('#startBtn').html('<i class="fa fa-play me-1"></i> Senkronu başlat');

        }

    }



    function setScanningUi(isOn) {

        scanning = isOn;

        if (isOn) {

            $('#startBtn').prop('disabled', true).addClass('d-none');

            $('#resumeBtn').addClass('d-none');

            $('#stopBtn').removeClass('d-none').prop('disabled', false);

            $('#truncateCheck').prop('disabled', true);

            $('#progressArea').removeClass('d-none');

            $('#resumeHint').addClass('d-none');

            updateActiveJobBanner({ status: 'running', phase_label: $('#statusText').text() });

        } else {

            $('#startBtn').prop('disabled', false).removeClass('d-none');

            $('#stopBtn').addClass('d-none').prop('disabled', true);

            $('#truncateCheck').prop('disabled', false);

            updateActiveJobBanner(null);

            updateIdleButtons();

        }

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



    function finishScan(message, isSuccess, fromUserStop) {

        stopRequested = fromUserStop === true;

        clearPending();

        setScanningUi(false);

        $('#statusText').html('<span class="' + (isSuccess ? 'text-success' : 'text-warning') + ' fw-bold">' + $('<span>').text(message).html() + '</span>');

        if (fromUserStop && jobId) {

            resumableJobId = jobId;

            updateIdleButtons();

        }

        if (typeof toastr !== 'undefined') {

            if (fromUserStop) {

                toastr.info(message);

            } else if (isSuccess) {

                toastr.success(message);

                resumableJobId = '';

                updateIdleButtons();

            } else {

                toastr.warning(message);

            }

        }

    }



    function scheduleNext(delayMs) {

        if (stopRequested) {

            return;

        }

        pendingTimer = setTimeout(function () {

            pendingTimer = null;

            runNext();

        }, typeof delayMs === 'number' ? delayMs : LOOP_DELAY_MS);

    }



    function runNext() {

        if (stopRequested || !scanning || !jobId) {

            return;

        }

        const url = String(cfg.processNextUrl || '');

        activeRequest = $.getJSON(url, { job_id: jobId });

        activeRequest.done(function (data) {

            if (stopRequested) {

                return;

            }

            if (!data || data.ok === false) {

                finishScan((data && data.mesaj) ? data.mesaj : 'İşlem başarısız.', false);

                return;

            }

            stepCounter += Number(data.steps_done || 1);

            updateCounts(data.counts);

            updateProgress(data);

            ingestLogResponse(data, false);

            if (data.done) {

                const ok = data.status === 'completed';

                finishScan(data.mesaj || (ok ? 'Senkron tamamlandı.' : 'Senkron durdu.'), ok);

                return;

            }

            scheduleNext(data.phase === 'kapi' ? 0 : LOOP_DELAY_MS);

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



    function beginScanning(message) {

        stopRequested = false;

        stepCounter = 0;

        renderedLogCount = 0;

        $('#logBox').html('<div class="text-muted">' + $('<span>').text(message || 'Başlatılıyor…').html() + '</div>');

        setScanningUi(true);

        $('#statusText').text(message || 'Başlatılıyor…');

    }



    function startSync() {

        if (scanning) {

            return;

        }

        const truncate = $('#truncateCheck').is(':checked');

        if (truncate) {

            if (!confirm(String(cfg.confirmTruncate || 'TRUNCATE onay?'))) {

                return;

            }

        } else if (resumableJobId !== '') {

            if (!confirm(String(cfg.confirmFreshStart || 'Sıfırdan başlat?'))) {

                return;

            }

        } else if (!confirm(String(cfg.confirmStart || 'Başlat?'))) {

            return;

        }

        beginScanning('Yeni senkron başlatılıyor…');



        $.post(String(cfg.startUrl || ''), {

            truncate: truncate ? '1' : '0'

        }).done(function (data) {

            if (!data || !data.ok) {

                finishScan((data && data.mesaj) ? data.mesaj : 'Başlatılamadı.', false);

                return;

            }

            jobId = String(data.job_id || '');

            resumableJobId = '';

            if (typeof toastr !== 'undefined' && data.mesaj) {

                toastr.info(data.mesaj);

            }

            runNext();

        }).fail(function () {

            finishScan('Başlatma isteği başarısız.', false);

        });

    }



    function resumeSync() {

        if (scanning) {

            return;

        }

        const targetId = resumableJobId || jobId;

        if (!targetId) {

            if (typeof toastr !== 'undefined') {

                toastr.warning('Devam ettirilecek iş bulunamadı.');

            }

            return;

        }

        if (!confirm(String(cfg.confirmResume || 'Kaldığı yerden devam?'))) {

            return;

        }

        beginScanning('Kaldığı yerden devam ediliyor…');



        $.post(String(cfg.resumeUrl || ''), { job_id: targetId }).done(function (data) {

            if (!data || !data.ok) {

                finishScan((data && data.mesaj) ? data.mesaj : 'Devam ettirilemedi.', false);

                return;

            }

            jobId = String(data.job_id || targetId);

            resumableJobId = '';

            updateProgress(data);

            if (typeof toastr !== 'undefined' && data.mesaj) {

                toastr.info(data.mesaj);

            }

            runNext();

        }).fail(function () {

            finishScan('Devam isteği başarısız.', false);

        });

    }



    function stopSync() {

        if (!scanning || !jobId) {

            return;

        }

        stopRequested = true;

        clearPending();

        $.post(String(cfg.cancelUrl || ''), { job_id: jobId });

        finishScan('Durduruldu. İlerleme kaydedildi; «Kaldığı yerden devam et» ile sürdürebilirsiniz.', false, true);

    }



    $(document).ready(function () {

        updateIdleButtons();

        $('#startBtn').on('click', startSync);

        $('#resumeBtn').on('click', resumeSync);

        $('#stopBtn').on('click', stopSync);



        if (jobId) {

            setScanningUi(true);

            $('#statusText').text('Devam eden iş bulundu…');

            $.getJSON(String(cfg.statusUrl || ''), { job_id: jobId }).done(function (data) {

                if (data && data.has_job) {

                    updateCounts(data.counts);

                    updateProgress(data);

                    if (data.log) {

                        ingestLogResponse(data, true);

                    }

                    if (!data.done) {

                        stopRequested = false;

                        runNext();

                    } else {

                        finishScan(data.phase_label || 'İş tamamlandı.', data.status === 'completed');

                    }

                }

            });

        }

    });

})();


