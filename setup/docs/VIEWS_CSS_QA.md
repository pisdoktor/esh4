# Views CSS — Kalite Kontrol Listesi

Bu belge, views CSS profesyonelleştirme çalışması sonrası doğrulama adımlarını özetler.

## Tema smoke testi (11 tema)

Her temada en az birer sayfa açın:

- `default`, `meridian`, `aurora`, `winui`, `winui-dark`, `evcare`, `nord`, `catppuccin-latte`, `catppuccin-mocha`, `meridian-soft`, `old`

Kontrol: tablo başlığı kontrastı, panel gölgesi, form focus halkası, navbar okunabilirliği.

## Sayfa türü checklist

| Tür | Beklenen sınıflar |
|-----|-------------------|
| Liste | `esh-page esh-page--list`, `esh-page__panel`, `esh-page__table-head` |
| Form | `esh-page esh-page--form`, `esh-form-section`, `form-pages.css` |
| Detay | `esh-page esh-page--detail` |
| İstatistik | `stats-hub.css` + `stats-common.css` (head) |

## CSS yükleme

Sayfa özel CSS dosyaları `PageAssetHelper` ile `<head>` içinde yüklenir (`views/partials/header.php`).

Manuel `<link>` yalnızca bağımsız kabuklarda kalmalı: `theme/preview_shell.php`, `guest/hastaarama_shell.php`.

## Inline style hedefi

Dinamik değerler (progress genişliği, `min()` ile hesaplanan grafik yüksekliği, ekip rengi) hariç tutulur.

Statik yükseklikler `esh-chart-wrap--*` sınıflarına taşındı.

## Tema override senkronizasyonu

`templates/<slug>/views/` altındaki override dosyaları canonical `views/` ile senkron tutulmalıdır. Override yoksa `ThemeViewHelper` otomatik fallback kullanır.

## Lint

PHP: `php -l <dosya>`

Yeni yardımcılar: `app/Helpers/PageAssetHelper.php`, `app/Helpers/PageShellHelper.php`
