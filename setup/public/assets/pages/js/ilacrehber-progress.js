/**
 * İlaç rehberi — canlı tablo sayıları (statsAjax).
 */
jQuery(function ($) {
    var root = document.getElementById('esh-ilac-rehber-live-stats');
    if (!root) {
        return;
    }
    var ajaxUrl = root.getAttribute('data-stats-ajax-url') || '';
    var $etken = $('#esh-rehber-stat-etken');
    var $ilac = $('#esh-rehber-stat-ilac');
    var $without = $('#esh-rehber-stat-without');
    var $badge = $('#esh-rehber-import-badge');
    var $updated = $('#esh-rehber-stats-updated');
    var $logWrap = $('#esh-rehber-log-line-wrap');
    var $logLine = $('#esh-rehber-log-line');
    var REFRESH_MS = 8000;
    var timerId = -1;

    function formatTime() {
        var d = new Date();
        var pad = function (n) {
            return n < 10 ? '0' + n : String(n);
        };
        return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function applyStats(res) {
        if (!res || !res.ok) {
            return;
        }
        $etken.text(String(res.etken != null ? res.etken : '—'));
        $ilac.text(String(res.ilac != null ? res.ilac : '—'));
        $without.text(String(res.etken_without_ilac != null ? res.etken_without_ilac : '—'));
        if (res.import_in_progress && res.scrape_worker_running) {
            $badge.removeClass('d-none').attr('aria-hidden', 'false');
        } else {
            $badge.addClass('d-none').attr('aria-hidden', 'true');
        }
        $updated.text('Güncellendi ' + formatTime());
        var line = res.last_log_line;
        if (line) {
            $logLine.text(line);
            $logWrap.removeClass('d-none');
        }
    }

    function fetchStats() {
        if (!ajaxUrl) {
            return;
        }
        $.getJSON(ajaxUrl)
            .done(applyStats)
            .fail(function () {
                $updated.text('Güncellenemedi ' + formatTime());
            });
    }

    fetchStats();
    timerId = window.setInterval(fetchStats, REFRESH_MS);

    $(window).on('beforeunload', function () {
        if (timerId !== -1) {
            window.clearInterval(timerId);
        }
    });
});
