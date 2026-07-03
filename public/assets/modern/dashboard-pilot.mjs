/**
 * Dashboard modern pilot — Vue 3 ESM (CDN), oturum tabanlı özet kartı.
 * render() kullanır; template derleyici gerekmez.
 */
export function bootDashboardPilot(root, vueModule) {
    if (!root || !vueModule) {
        return;
    }
    const { createApp, ref, onMounted, h } = vueModule;
    const apiUrl = root.dataset.apiUrl || '';
    if (!apiUrl) {
        return;
    }

    function statCol(value, label) {
        return h('div', { class: 'col-4' }, [
            h('div', { class: 'esh-modern-pilot__stat' }, [
                h('div', { class: 'esh-modern-pilot__stat-val' }, String(value ?? '')),
                h('div', { class: 'esh-modern-pilot__stat-lbl' }, label),
            ]),
        ]);
    }

    createApp({
        setup() {
            const loading = ref(true);
            const error = ref('');
            const summary = ref(null);
            const dateLabel = ref('');

            onMounted(async () => {
                try {
                    const res = await fetch(apiUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    if (!json || !json.ok) {
                        error.value = (json && json.error) ? json.error : 'Veri alınamadı';
                        return;
                    }
                    summary.value = json.summary || null;
                    dateLabel.value = json.date_label || '';
                } catch (e) {
                    error.value = 'Bağlantı hatası';
                } finally {
                    loading.value = false;
                }
            });

            return () => {
                const header = h('div', { class: 'd-flex align-items-center gap-2 mb-2' }, [
                    h('span', { class: 'badge text-bg-primary rounded-pill' }, 'Vue pilot'),
                    dateLabel.value ? h('span', { class: 'small text-muted' }, dateLabel.value) : null,
                ].filter(Boolean));

                let body;
                if (loading.value) {
                    body = h('div', { class: 'small text-muted' }, 'Yükleniyor…');
                } else if (error.value) {
                    body = h('div', { class: 'small text-danger' }, error.value);
                } else if (summary.value) {
                    const s = summary.value;
                    body = h('div', { class: 'row g-2' }, [
                        statCol(s.section_count, 'Bölüm'),
                        statCol(s.task_count, 'Görev'),
                        statCol(s.mernis_patient_count, 'Hasta'),
                    ]);
                }

                return h('div', { class: 'esh-modern-pilot__inner' }, [header, body].filter(Boolean));
            };
        },
    }).mount(root);
}
