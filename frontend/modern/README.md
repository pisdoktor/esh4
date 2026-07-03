# Modern frontend (kademeli)

İsteğe bağlı Vue 3 + Vite kaynağı. **Sunucuda npm zorunlu değil** — `public/assets/modern/` altındaki `.mjs` dosyaları CDN Vue ile doğrudan çalışır.

## Hızlı pilot (npm yok)

1. Ayarlar → Güvenlik → **Modern frontend** → etkin + dashboard/plan pilot
2. Dashboard veya plan listesini açın

## Vite ile derleme (geliştirici)

```bash
cd frontend/modern
npm install
npm run build
```

Çıktı: `public/assets/modern/dist/dashboard-pilot.built.mjs`

Ardından ayarlardan «Derlenmiş bundle kullan» seçeneğini açın.

## Composer (isteğe bağlı)

```bash
composer install --no-dev   # yalnızca prod — vendor gerekmez
composer install            # geliştirici: PHPUnit vendor üzerinden
php tools/run_smoke_tests.php
```
