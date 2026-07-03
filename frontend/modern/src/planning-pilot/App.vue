<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  apiUrl: { type: String, default: '' },
});

const loading = ref(true);
const error = ref('');
const summary = ref(null);

onMounted(async () => {
  if (!props.apiUrl) {
    error.value = 'API yok';
    loading.value = false;
    return;
  }
  try {
    const res = await fetch(props.apiUrl, { credentials: 'same-origin' });
    const json = await res.json();
    if (!json?.ok) {
      error.value = json?.error || 'Hata';
      return;
    }
    summary.value = json.summary;
  } catch {
    error.value = 'Baglanti hatasi';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="esh-modern-pilot__inner">
    <div class="d-flex align-items-center gap-2 mb-2">
      <span class="badge text-bg-success rounded-pill">Plan pilot</span>
      <span class="small text-muted">Modern ozet</span>
    </div>
    <div v-if="loading" class="small text-muted">Yukleniyor...</div>
    <div v-else-if="error" class="small text-danger">{{ error }}</div>
    <div v-else-if="summary" class="d-flex flex-wrap gap-3">
      <div><strong>{{ summary.pending }}</strong> <span class="text-muted small">bekleyen</span></div>
      <div><strong>{{ summary.today }}</strong> <span class="text-muted small">bugun</span></div>
      <div><strong>{{ summary.next_7_days }}</strong> <span class="text-muted small">7 gun</span></div>
    </div>
  </div>
</template>
