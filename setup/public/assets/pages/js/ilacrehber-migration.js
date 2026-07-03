(function () {
    'use strict';

    const cfg = (window.ESH_PAGE && window.ESH_PAGE.ilacRehberMigration) ? window.ESH_PAGE.ilacRehberMigration : {};
    const csrfToken = String(cfg.csrfToken || '');
    const startUrl = String(cfg.startUrl || '');
    const statusUrl = String(cfg.statusUrl || '');
    const cancelUrl = String(cfg.cancelUrl || '');
    const initialActive = cfg.initialActive || null;
    const initialIdleStatus = cfg.initialStatus || {};

    const durumEtiketleri = { queued: 'Sırada', running: 'Çalışıyor', success: 'Tamamlandı', error: 'Hata' };
    let activeJob = null;
    let pollTimer = null;

    function $(id) {
        return document.getElementById(id);
    }

    async function parseJsonResponse(response) {
        const text = await response.text();
        if (!text || !text.trim()) {
            throw new Error('Sunucu boş yanıt döndü (HTTP ' + response.status + ')');
        }
        try {
            return JSON.parse(text);
        } catch (parseErr) {
            const snippet = text.replace(/\s+/g, ' ').trim().slice(0, 180);
            throw new Error('JSON bekleniyordu (HTTP ' + response.status + '): ' + snippet);
        }
    }

    function collectOptions() {
        return {
            site: $('site').value,
            sleep_ms: $('sleep_ms').value,
            discovery: $('discovery').value,
            id_max: $('id_max').value,
            id_empty_stop: $('id_empty_stop').value,
            limit_etken: $('limit_etken').value,
            limit_ilac: $('limit_ilac').value,
            progress_every: $('progress_every').value,
            truncate: $('truncate').checked ? '1' : '0',
            fresh: $('fresh').checked ? '1' : '0',
            resume: $('resume').checked ? '1' : '0',
            insecure_ssl: $('insecure_ssl').checked ? '1' : '0'
        };
    }

    function formatCheckpoint(cp) {
        if (!cp) {
            return 'Checkpoint: yok';
        }
        const parts = [];
        if (cp.seed_total > 0) {
            parts.push('seed ' + (cp.seed_index || 0) + '/' + cp.seed_total);
        }
        if (cp.discovery !== 'seeds' && cp.id_max > 0) {
            parts.push('Id ' + (cp.id_cursor || 1) + '/' + cp.id_max);
        }
        if (cp.etken_processed > 0) {
            parts.push('etken işlenen: ' + cp.etken_processed);
        }
        if (cp.updated_at) {
            parts.push('güncelleme: ' + cp.updated_at);
        }
        return 'Checkpoint: ' + (parts.length ? parts.join(' · ') : 'kayıtlı');
    }

    function applyCheckpoint(cp) {
        $('checkpointLine').textContent = formatCheckpoint(cp);
    }

    function setPhaseBar(barId, pct, animate) {
        const bar = $(barId);
        if (!bar) {
            return;
        }
        const val = Math.max(0, Math.min(100, Number(pct || 0)));
        bar.style.width = val + '%';
        bar.textContent = val + '%';
        if (animate && val < 100) {
            bar.classList.add('progress-bar-animated');
        } else {
            bar.classList.remove('progress-bar-animated');
        }
    }

    function discoveryFromStatus(d) {
        const job = d && d.job ? d.job : null;
        const jo = (job && job.config) ? job.config : (d && d.job_options ? d.job_options : {});
        const cp = d && d.checkpoint ? d.checkpoint : {};
        const mode = String(cp.discovery || jo.discovery || 'both').toLowerCase();
        return ['seeds', 'ids', 'both'].includes(mode) ? mode : 'both';
    }

    function applyProgress(d) {
        const wrap = $('progressWrap');
        const resumeLine = $('checkpointResumeLine');
        const etkenTxt = $('etkenProgressText');
        if (!wrap) {
            return;
        }

        const phase = String(d.phase || 'idle');
        const running = !!(d && d.running);
        const discovery = discoveryFromStatus(d);
        const seedTotal = Number(d.progress_seed_total || 0);
        const seedIndex = Number(d.progress_seed_index || 0);
        const idMax = Number(d.progress_id_max || 0);
        const idCursor = Number(d.progress_id_cursor || 1);
        const seedPct = Number(d.progress_seed_percent || 0);
        const idPct = Number(d.progress_id_percent || 0);
        const overallPct = Number(d.progress_overall_percent || 0);
        const etkenDone = Number(d.etken_processed || 0);

        const showSeeds = discovery === 'seeds' || discovery === 'both';
        const showIds = discovery === 'ids' || discovery === 'both';
        const hasCheckpoint = !!(d && d.checkpoint);
        const showBars = running || phase === 'done' || hasCheckpoint;

        wrap.classList.toggle('d-none', !showBars);
        $('seedProgressSection').classList.toggle('d-none', !showSeeds);
        $('idProgressSection').classList.toggle('d-none', !showIds);

        if (phase === 'done' && !running) {
            setPhaseBar('overallBar', 100, false);
            $('overallProgressTxt').textContent = 'Tamamlandı';
            if (showSeeds) {
                setPhaseBar('seedBar', 100, false);
                $('seedProgressTxt').textContent = seedTotal > 0 ? (seedTotal + ' / ' + seedTotal + ' seed') : '—';
            }
            if (showIds) {
                setPhaseBar('idBar', 100, false);
                $('idProgressTxt').textContent = idMax > 0 ? (idMax + ' / ' + idMax + ' id') : '—';
            }
            etkenTxt.textContent = etkenDone > 0 ? ('Etken upsert: ' + etkenDone) : '';
            resumeLine.classList.add('d-none');
            return;
        }

        if (!showBars) {
            etkenTxt.textContent = '';
            resumeLine.classList.add('d-none');
            return;
        }

        const animate = running && phase !== 'idle';
        setPhaseBar('overallBar', overallPct, animate);
        let overallLabel = overallPct + '%';
        if (phase === 'seeds') {
            overallLabel += ' · Seed taraması';
        } else if (phase === 'ids') {
            overallLabel += ' · Id taraması';
        }
        if (!running && hasCheckpoint) {
            overallLabel += ' · Durduruldu';
        }
        $('overallProgressTxt').textContent = overallLabel;

        if (showSeeds) {
            setPhaseBar('seedBar', seedPct, animate && phase === 'seeds');
            $('seedProgressTxt').textContent = seedTotal > 0
                ? (seedIndex + ' / ' + seedTotal + ' seed')
                : '—';
        }
        if (showIds) {
            setPhaseBar('idBar', idPct, animate && phase === 'ids');
            const idShown = Math.max(1, Math.min(idCursor, idMax || idCursor));
            $('idProgressTxt').textContent = idMax > 0
                ? (idShown + ' / ' + idMax + ' id')
                : '—';
        }

        etkenTxt.textContent = etkenDone > 0
            ? ('Etken upsert (işlenen): ' + etkenDone + ' — keşif toplamı çalışma bitene kadar bilinmez')
            : '';

        const hint = d.checkpoint_resume || '';
        if (hint) {
            resumeLine.textContent = hint;
            resumeLine.classList.remove('d-none');
        } else {
            resumeLine.classList.add('d-none');
        }
    }

    function setLog(lines) {
        const box = $('logBox');
        if (!box) {
            return;
        }
        const arr = Array.isArray(lines) ? lines : [];
        box.innerHTML = '';
        if (arr.length === 0) {
            const placeholder = document.createElement('div');
            placeholder.className = 'text-muted opacity-75';
            placeholder.textContent = 'Scrape başlayınca adımlar burada listelenir.';
            box.appendChild(placeholder);
            return;
        }
        arr.forEach(function (line) {
            const row = document.createElement('div');
            row.className = 'ir-migration-log-line';
            row.textContent = String(line);
            box.appendChild(row);
        });
        box.scrollTop = box.scrollHeight;
    }

    function applyStats(stats, statusData) {
        if (!stats || !stats.ok) {
            return;
        }
        $('statEtken').textContent = String(stats.etken ?? '—');
        $('statIlac').textContent = String(stats.ilac ?? '—');
        $('statWithout').textContent = String(stats.etken_without_ilac ?? '—');
        const running = !!(statusData && statusData.running);
        const stopped = !!(statusData && statusData.worker_state === 'stopped');
        const meta = [];
        if (running && stats.import_in_progress) {
            meta.push('Import devam ediyor');
            if (stats.import_started_at) {
                meta.push('başlangıç: ' + stats.import_started_at);
            }
        } else if (stopped) {
            meta.push('Durduruldu — kaldığı yerden devam edebilirsiniz');
            if (stats.import_started_at) {
                meta.push('son çalışma: ' + stats.import_started_at);
            }
        } else if (stats.import_finished_at) {
            meta.push('Son import: ' + stats.import_finished_at);
        }
        $('statsMeta').textContent = meta.length ? meta.join(' · ') : 'Import kaydı yok veya tamamlandı';
    }

    function statusLineText(data, job) {
        if (job && (job.status === 'queued' || job.status === 'running')) {
            const dl = durumEtiketleri[job.status] || job.status;
            return dl + ' · ' + (job.step || '');
        }
        const msg = data && data.status_message ? String(data.status_message).trim() : '';
        if (msg) {
            return msg;
        }
        if (data && data.worker_state === 'stopped') {
            return 'Durduruldu — kaldığı yerden devam edebilirsiniz';
        }
        if (data && data.worker_state === 'completed') {
            return 'Tamamlandı';
        }
        return 'Hazır — scrape başlatılabilir';
    }

    function updateActiveJobBanner(job) {
        const ban = $('activeBanner');
        const stopBtn = $('stopBtn');
        if (!ban) {
            return;
        }
        const st = job && job.status ? String(job.status) : '';
        const isActive = activeJob && (st === 'queued' || st === 'running');
        if (!isActive) {
            ban.classList.add('d-none');
            if (stopBtn) {
                stopBtn.classList.add('d-none');
            }
            return;
        }
        ban.classList.remove('d-none');
        if (stopBtn) {
            stopBtn.classList.remove('d-none');
        }
        const sid = $('activeJobIdShort');
        if (sid && activeJob) {
            sid.textContent = activeJob.length > 14 ? (activeJob.slice(0, 14) + '…') : activeJob;
        }
        const echo = $('activeJobStepEcho');
        if (echo) {
            const dl = durumEtiketleri[st] || st;
            echo.textContent = dl + ' · ' + (job.step || '');
        }
    }

    function applyStatusPayload(d) {
        const job = d.job || null;
        const running = !!(d && d.running);
        const stopped = !!(d && d.worker_state === 'stopped');
        updateActiveJobBanner(job);
        $('startBtn').disabled = running;
        $('statusLine').textContent = statusLineText(d, job);
        if (d.stats) {
            applyStats(d.stats, d);
        }
        if (d.checkpoint !== undefined) {
            applyCheckpoint(d.checkpoint);
        }
        applyProgress(d);
        const jobLogs = job && job.logs ? job.logs : null;
        if (jobLogs && jobLogs.length) {
            setLog(jobLogs);
        } else if (d.log_tail) {
            setLog(d.log_tail);
        }
        const resumeChk = $('resume');
        if (resumeChk && stopped && !$('fresh').checked) {
            resumeChk.checked = true;
        }
    }

    function attachResumeFromInitial(payload) {
        if (!payload || !payload.job_id || !payload.job) {
            return;
        }
        const st = payload.job.status;
        if (st !== 'queued' && st !== 'running') {
            return;
        }
        activeJob = payload.job_id;
        $('startBtn').disabled = true;
        updateActiveJobBanner(payload.job);
        applyStatusPayload({
            ok: true,
            running: true,
            job: payload.job,
            checkpoint: null,
            stats: null,
            worker_state: 'running',
            status_message: ''
        });
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        pollTimer = setInterval(poll, 1200);
        poll();
    }

    async function poll() {
        if (!activeJob) {
            return;
        }
        try {
            const url = statusUrl + (statusUrl.indexOf('?') >= 0 ? '&' : '?') + 'job_id=' + encodeURIComponent(activeJob);
            const r = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const d = await parseJsonResponse(r);
            if (!d.ok) {
                throw new Error(d.error || 'Durum okunamadı');
            }
            applyStatusPayload(d);
            const st = d.job && d.job.status ? String(d.job.status) : '';
            if (st === 'success' || st === 'error') {
                clearInterval(pollTimer);
                pollTimer = null;
                $('startBtn').disabled = false;
                activeJob = null;
                updateActiveJobBanner(d.job);
            }
        } catch (e) {
            $('statusLine').textContent = 'Hata: ' + e.message;
        }
    }

    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        poll();
        pollTimer = setInterval(poll, 1200);
    }

    function bindEvents() {
        $('fresh').addEventListener('change', function () {
            if ($('fresh').checked) {
                $('resume').checked = false;
            }
        });
        $('resume').addEventListener('change', function () {
            if ($('resume').checked) {
                $('fresh').checked = false;
            }
        });
        $('truncate').addEventListener('change', function () {
            if ($('truncate').checked) {
                $('fresh').checked = true;
                $('resume').checked = false;
            }
        });

        $('startBtn').addEventListener('click', async function () {
            const statusEl = $('statusLine');
            if ($('truncate').checked
                && !confirm('esh_rehber_* tabloları TRUNCATE edilecek; mevcut rehber verileri silinir. Devam edilsin mi?')) {
                return;
            }

            const body = new URLSearchParams({ csrf_token: csrfToken, ...collectOptions() });
            $('startBtn').disabled = true;
            statusEl.textContent = 'Başlatılıyor…';
            try {
                const r = await fetch(startUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString(),
                    credentials: 'same-origin'
                });
                const d = await parseJsonResponse(r);
                if (!d.ok) {
                    const extra = d.active_job_id ? (' (aktif iş: ' + d.active_job_id + ')') : '';
                    throw new Error((d.error || 'Başlatılamadı') + extra);
                }
                activeJob = d.job_id;
                statusEl.textContent = 'Çalışıyor…';
                if (pollTimer) {
                    clearInterval(pollTimer);
                }
                await poll();
                startPolling();
            } catch (e) {
                $('startBtn').disabled = false;
                statusEl.textContent = 'Hata: ' + e.message;
                alert(e.message);
            }
        });

        $('stopBtn').addEventListener('click', async function () {
            if (!activeJob) {
                return;
            }
            if (!confirm('Scrape işçisi sonlandırılsın mı? Veritabanı yarım kalmış olabilir.')) {
                return;
            }
            try {
                const r = await fetch(cancelUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        csrf_token: csrfToken,
                        job_id: activeJob,
                        force: '1'
                    }),
                    credentials: 'same-origin'
                });
                const d = await parseJsonResponse(r);
                if (!d.ok) {
                    throw new Error(d.error || 'Durdurulamadı');
                }
                $('statusLine').textContent = d.killed
                    ? 'İşçi sonlandırıldı.'
                    : 'Durdurma isteği gönderildi; günlük güncellenecek…';
                await poll();
            } catch (e) {
                alert(e.message);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindEvents();
        attachResumeFromInitial(initialActive);
        if (!activeJob) {
            applyStatusPayload(initialIdleStatus);
        }
    });
})();
