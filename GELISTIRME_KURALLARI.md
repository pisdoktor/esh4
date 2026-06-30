# Geliştirme kuralları

Bu dosya depo kökündeki geliştirme standartlarını özetler. Cursor agent kuralları: `.cursor/rules/` (ör. `yedek-ve-lint.mdc`, `formhelper-view-forms.mdc`).

---

## Yedek ve lint

Bir dosyada **içerik değiştirmeden hemen önce** (kullanıcıya sormadan):

1. Yedek al: aynı klasörde `dosyaadi.ext.pre-edit.bak` (bit‑bütün kopya).
2. Düzenlemeyi yap.
3. Lint / sözdizimi kontrolü çalıştır (PHP: `php -l <dosya>`; diğer dillerde uygun denetim).
4. Kontroller sorunsuzsa `.pre-edit.bak` yedeğini **sil**. Hata varsa yedeği silme; düzelt veya yedekten dön.

Yedekleme veya lint imkânsızsa kısaca bildir.

---

## FormHelper ve view formları

**Helper:** `app/Helpers/FormHelper.php`  
**Hasta canonical partial’lar:** `views/site/hasta/partials/edit_sections/`

### FormHelper kullan

- Standart CRUD alanları: metin, textarea, select, checkbox, switch, tarih, telefon, tarih aralığı filtreleri
- Admin/site **liste filtreleri** (arama, durum, limit, kurum, tarih aralığı)
- Yeni eklenen **basit** form alanları — mevcut sayfada FormHelper varsa aynı pattern’i sürdür
- Hasta düzenleme: mümkünse `edit_sections/` partial include (ör. `kimlik_iletisim.php`, `bekleyen_kimlik.php`); `edit.php`, `bedit.php`, `ilkkayit.php`, `hasta_edit_modals.php` ile hizalı kalsın
- Tema dosyaları (`templates/**`) canonical view’u include etmeli; form HTML’ini kopyalama

### Ham HTML / özel partial bırak (FormHelper’a zorlama)

- Adres **cascade** select’leri (ilçe→mahalle→sokak→kapı) + Tom Select (`.esh-tomselect`) + `patient-edit.js` / Address AJAX
- Stats/planning **cascade** (ilçe→bölge, adres hasta filtresi `[sayı]` option + AJAX)
- Randevu/UHDS **hasta arama widget’ı**, tablo içi **mini-formlar** (`hasta_geldi` vb.)
- Controller’dan gelen hazır HTML (`$lists['ilce']`, `$hast` Tom Select çoklu tanı)
- `edit_dosya_durumu` dinamik pasif/nakil seçenekleri
- Çok adımlı / dinamik formlar: `hastailacrapor`, `admin/ekip`, `mesaj/broadcast`, `settings` — parça parça taşınır; tüm sayfa bir seferde FormHelper’a çevrilmez

### Yeni geliştirme

1. Alan basit mi? → `FormHelper` veya uygun `edit_sections` partial
2. Özel JS / cascade / autocomplete var mı? → partial + mevcut JS sınıfları (`js-address-cascade`, `esh-tomselect`, `esh-filter-control`); gereksiz helper opsiyonu ekleme
3. Legacy `templates/**` içine form kopyalama — canonical `views/` include kullan

### Migrasyon notu

Karmaşık formların dışarıda kalması **çalışma zamanı hatası** üretmez; risk bakım ve tutarlılıktır. Basit alanları taşırken yukarıdaki istisna listesine dokunma.
