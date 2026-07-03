<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  apiUrl: { type: String, default: '' },
});

const loading = ref(true);
const error = ref('');
const summary = ref(null);
const dateLabel = ref('');

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
    dateLabel.value = json.date_label || '';
  } catch {
    error.value = 'Bağlantı hatası';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="esh-modern-pilot__inner">
    <div class="d-flex align-items-center gap-2 mb-2">
      <span class="badge text-bg-primary rounded-pill">Vue SFC</span>
      <span v-if="dateLabel" class="small text-muted">{{ dateLabel }}</span>
    </div>
    <div v-if="loading" class="small text-muted">Yükleniyor…</div>
    <div v-else-if="error" class="small text-danger">{{ error }}</div>
    <div v-else-if="summary" class="row g-2">
      <div class="col-4">
        <div class="esh-modern-pilot__stat">
          <div class="esh-modern-pilot__stat-val">{{ summary.section_count }}</div>
          <div class="esh-modern-pilot__stat-lbl">Bölüm</div>
        </div>
      </div>
      <div class="col-4">
        <div class="esh-modern-pilot__stat">
          <div class="esh-modern-pilot__stat-val">{{ summary.task_count }}</div>
          <div class="esh-modern-pilot__stat-lbl">Görev</div>
        </div>
      </div>
      <div class="col-4">
        <div class="esh-modern-pilot__stat">
          <div class="esh-modern-pilot__stat-val">{{ summary.mernis_patient_count }}</div>
          <div class="esh-modern-pilot__stat-lbl">Hasta</div>
        </div>
      </div>
    </div>
  </div>
</template>
