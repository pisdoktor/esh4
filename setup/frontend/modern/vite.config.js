import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: path.resolve(__dirname, '../../public/assets/modern/dist'),
    emptyOutDir: true,
    rollupOptions: {
      input: {
        'dashboard-pilot': path.resolve(__dirname, 'src/dashboard-pilot/main.js'),
        'planning-pilot': path.resolve(__dirname, 'src/planning-pilot/main.js'),
      },
      output: {
        entryFileNames: '[name].built.mjs',
        format: 'es',
      },
    },
  },
});
