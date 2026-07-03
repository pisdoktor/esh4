import { createApp, ref, onMounted } from 'vue';
import App from './App.vue';

const root = document.getElementById('esh-modern-pilot-root');
if (root) {
  createApp(App, { apiUrl: root.dataset.apiUrl || '' }).mount(root);
}
