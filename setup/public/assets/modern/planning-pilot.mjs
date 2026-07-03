/**
 * Plan listesi modern pilot — Vue 3 ESM özet şeridi.
 * render() kullanır; template derleyici gerekmez.
 */
export function bootPlanningPilot(root, vueModule) {
    if (!root || !vueModule) {
        return;
    }
    const { createApp, ref, onMounted, h } = vueModule;
    const apiUrl = root.dataset.apiUrl || '';
    if (!apiUrl) {
        return;
    }

    function metric(value, label) {
        return h('div', {}, [
            h('strong', {}, String(value ?? '')),
            ' ',
            h('span', { class: 'text-muted small' }, label),
        ]);
    }

    createApp({
        setup() {
            const loading = ref(true);
            const error = ref('');
            const summary = ref(null);

            onMounted(async () => {
                try {
                    const res = await fetch(apiUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    if (!json || !json.ok) {
                        error.value = (json && json.error) ? json.error : 'Veri alınamadı';
                        return;
                    }
                    summary.value = json.summary || null;
                } catch (e) {
                    error.value = 'Bağlantı hatası';
                } finally {
                    loading.value = false;
                }
            });

            return () => {
                const header = h('div', { class: 'd-flex align-items-center gap-2 mb-2' }, [
                    h('span', { class: 'badge text-bg-success rounded-pill' }, 'Plan pilot'),
                    h('span', { class: 'small text-muted' }, 'Modern özet'),
                ]);

                let body;
                if (loading.value) {
                    body = h('div', { class: 'small text-muted' }, 'Yükleniyor…');
                } else if (error.value) {
                    body = h('div', { class: 'small text-danger' }, error.value);
                } else if (summary.value) {
                    const s = summary.value;
                    body = h('div', { class: 'd-flex flex-wrap gap-3' }, [
                        metric(s.pending, 'bekleyen'),
                        metric(s.today, 'bugün'),
                        metric(s.next_7_days, '7 gün'),
                    ]);
                }

                return h('div', { class: 'esh-modern-pilot__inner' }, [header, body].filter(Boolean));
            };
        },
    }).mount(root);
}
