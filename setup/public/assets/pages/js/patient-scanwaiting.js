/**
 * MERNIS toplu vefat taraması (Patient scan / scanWaiting).
 * ESH_PAGE: totalRecords, scanScope, scanAction, scanConfirm
 */
(function () {
    'use strict';

    let currentOffset = 0;
    let totalFound = 0;
    let scanning = false;
    let stopRequested = false;
    let pendingTimer = null;
    let activeRequest = null;

    function readPageConfig() {
        const p = window.ESH_PAGE || {};
        return {
            totalRecords: Number(p.totalRecords || 0),
            scanScope: String(p.scanScope || 'active'),
            scanAction: String(p.scanAction || 'bulkDiedScan'),
            scanConfirm: String(p.scanConfirm || 'Tarama baslatilsin mi?'),
        };
    }

    function genderNameClass(genderRaw) {
        const g = String(genderRaw || '').trim().toUpperCase();
        if (g === '1' || g === 'E' || g === 'ERKEK' || g === 'M') return 'text-primary';
        if (g === '2' || g === 'K' || g === 'KADIN' || g === 'F') return 'text-danger';
        return 'text-dark';
    }

    function $(sel) {
        return typeof jQuery !== 'undefined' ? jQuery(sel) : null;
    }

    function setScanningUi(isOn) {
        scanning = isOn;
        const $start = $('#startBtn');
        const $stop = $('#stopBtn');
        if (!$start || !$stop) {
            return;
        }
        if (isOn) {
            $start.prop('disabled', true).addClass('d-none');
            $stop.removeClass('d-none').prop('disabled', false);
        } else {
            $start.prop('disabled', false).removeClass('d-none')
                .html('<i class="fa fa-refresh me-1"></i> Yeniden Başlat');
            $stop.addClass('d-none').prop('disabled', true);
        }
    }

    function clearPending() {
        if (pendingTimer) {
            clearTimeout(pendingTimer);
            pendingTimer = null;
        }
        if (activeRequest && typeof activeRequest.abort === 'function') {
            try {
                activeRequest.abort();
            } catch (e) {
                /* yoksay */
            }
            activeRequest = null;
        }
    }

    function appendDeathRow(item) {
        const $log = $('#logBody');
        if (!$log) {
            return;
        }
        const statusIcon = '<i class="fa-solid fa-skull me-2 small"></i>VEFAT (' + item.tarih + ')';
        $log.prepend(
            '<tr class="table-danger"><td>' + item.tc + '</td><td><span class="fw-bold ' + genderNameClass(item.cinsiyet) + '">' + item.ad + '</span></td><td>' + (item.anneAdi || '-') + ' / ' + (item.babaAdi || '-') + '</td><td>' + (item.ilce || '-') + ' / ' + (item.mahalle || '-') + '</td><td>' + (item.kayittarihi || '—') + '</td><td>' + (item.sonizlem || '—') + '</td><td>' + statusIcon + '</td></tr>'
        );
        if (typeof toastr !== 'undefined') {
            toastr.error(item.ad + ' vefat etmiş!', 'Sistem Tespiti', { timeOut: 2000 });
        }
    }

    function finishScan(message, isSuccess, fromUserStop) {
        stopRequested = fromUserStop === true;
        clearPending();
        setScanningUi(false);
        const msg = message || ('Toplam ' + totalFound + ' vefat tespit edildi.');
        const $status = $('#statusText');
        if ($status) {
            $status.html('<span class="' + (isSuccess ? 'text-success' : 'text-warning') + ' fw-bold">' + msg + '</span>');
        }
        if (typeof toastr !== 'undefined') {
            if (fromUserStop) {
                toastr.info(msg);
            } else if (isSuccess) {
                toastr.success(msg);
            } else {
                toastr.warning(msg);
            }
        }
    }

    function scheduleNextBatch() {
        if (stopRequested) {
            return;
        }
        pendingTimer = setTimeout(function () {
            pendingTimer = null;
            runBatch();
        }, 1000);
    }

    function scanAjaxBaseUrl(cfg) {
        if (window.ESH_PAGE && window.ESH_PAGE.bulkDiedScanUrl) {
            return String(window.ESH_PAGE.bulkDiedScanUrl);
        }
        if (typeof eshUrl === 'function') {
            return eshUrl('Patient', cfg.scanAction || 'bulkDiedScan');
        }
        const base = String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/');
        return base + 'index.php?controller=Patient&action=' + encodeURIComponent(cfg.scanAction || 'bulkDiedScan');
    }

    function runBatch() {
        if (stopRequested || !scanning) {
            return;
        }
        if (typeof jQuery === 'undefined') {
            finishScan('jQuery yüklenemedi; tarama durduruldu.', false);
            return;
        }

        const cfg = readPageConfig();
        activeRequest = jQuery.getJSON(
            scanAjaxBaseUrl(cfg),
            { scope: cfg.scanScope, offset: currentOffset }
        );

        activeRequest.done(function (data) {
            if (stopRequested) {
                return;
            }
            const processed = data && typeof data.processed === 'number' ? data.processed : 0;
            const results = data && Array.isArray(data.results) ? data.results : [];

            if (processed > 0) {
                results.forEach(function (item) {
                    if (item && (item.oldu == 1 || item.oldu === '1')) {
                        totalFound++;
                        appendDeathRow(item);
                    }
                });
                currentOffset = data.nextOffset != null ? Number(data.nextOffset) : (currentOffset + processed);
                const displayProcessed = Math.min(currentOffset, cfg.totalRecords);
                const $status = $('#statusText');
                if ($status) {
                    $status.text(displayProcessed + ' hasta kontrol edildi…');
                }
                const percent = cfg.totalRecords > 0 ? Math.round((displayProcessed / cfg.totalRecords) * 100) : 0;
                const $bar = $('#progressBar');
                const $pct = $('#percentText');
                if ($bar) {
                    $bar.css('width', percent + '%');
                }
                if ($pct) {
                    $pct.text('%' + percent);
                }

                if (stopRequested) {
                    finishScan('Tarama durduruldu. ' + displayProcessed + ' hasta kontrol edildi, ' + totalFound + ' vefat tespit edildi.', false, true);
                    return;
                }
                scheduleNextBatch();
            } else {
                finishScan('BİTTİ! Toplam ' + totalFound + ' vefat tespit edildi.', true);
            }
        }).fail(function (jqXHR, textStatus) {
            if (stopRequested || textStatus === 'abort') {
                return;
            }
            if (typeof toastr !== 'undefined') {
                toastr.warning('Bir hata oluştu, 3 saniye sonra tekrar denenecek…');
            }
            pendingTimer = setTimeout(function () {
                pendingTimer = null;
                runBatch();
            }, 3000);
        }).always(function () {
            activeRequest = null;
        });
    }

    function startScan() {
        if (scanning) {
            return;
        }
        const cfg = readPageConfig();
        if (!window.confirm(cfg.scanConfirm)) {
            return;
        }
        stopRequested = false;
        currentOffset = 0;
        totalFound = 0;
        const $log = $('#logBody');
        if ($log) {
            $log.empty();
        }
        const $progress = $('#progressArea');
        if ($progress) {
            $progress.removeClass('d-none');
        }
        setScanningUi(true);
        const $status = $('#statusText');
        if ($status) {
            $status.text('Başlatılıyor…');
        }
        runBatch();
    }

    function stopScan() {
        if (!scanning) {
            return;
        }
        stopRequested = true;
        clearPending();
        const cfg = readPageConfig();
        const displayProcessed = Math.min(currentOffset, cfg.totalRecords);
        finishScan(
            'Tarama durduruldu. ' + displayProcessed + ' hasta kontrol edildi, ' + totalFound + ' vefat tespit edildi.',
            false,
            true
        );
    }

    function bindUi() {
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        if (startBtn) {
            startBtn.addEventListener('click', function (e) {
                e.preventDefault();
                startScan();
            });
        }
        if (stopBtn) {
            stopBtn.addEventListener('click', function (e) {
                e.preventDefault();
                stopScan();
            });
        }
    }

    window.startScan = startScan;
    window.stopScan = stopScan;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindUi);
    } else {
        bindUi();
    }
})();
