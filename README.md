# ESH / SONEV v4.0.0

Türkiye'de evde sağlık hizmetleri yönetimi için geliştirilmiş web uygulaması. PHP 8 tabanlı özel MVC çatısı; varsayılan veritabanı **MySQL/MariaDB** (PostgreSQL, SQL Server, SQLite ve Oracle şemaları da mevcuttur).

## Depo yapısı

| Dizin | Açıklama |
|-------|----------|
| `app/` | Controller, Model, Service ve Helper sınıfları |
| `config/` | Uygulama yapılandırması (`config.local.php` git dışıdır) |
| `database/` | Şema, seed ve migrasyon dosyaları |
| `public/` | Web kökü (`index.php`, statik varlıklar) |
| `views/` | PHP view şablonları |
| `tools/` | CLI yardımcı betikleri (test, kurulum aynası, export) |
| **`setup/`** | **Sıfır sunucu kurulum paketi** (temiz ayna; aşağıya bakın) |

## Hızlı kurulum (üretim)

`setup/` klasörü, geliştirme artıkları olmadan dağıtıma hazır dosya aynasıdır.

1. `setup/` **içeriğini** (klasörün kendisini değil) web sunucusu köküne kopyalayın.
2. Tarayıcıdan `public/install.php` kurulum sihirbazını açın.
3. Veritabanı türünü seçin; şema ve seed otomatik uygulanır, `config/config.local.php` oluşturulur.
4. Kurulum sonrası `public/install.php` dosyasını kaldırın veya erişimi kilitleyin.

Ayrıntılar: [`setup/README.txt`](setup/README.txt)

### Kurulum paketini yenileme (geliştiriciler)

Ana projeden `setup/` aynasını güncellemek için:

```bash
php tools/build_setup_mirror.php
```

Tam paket (ek depolama içeriği dahil): `php tools/build_setup_mirror.php --full`

## Geliştirme ortamı

### Gereksinimler

- PHP 8.x (PDO, mbstring, json, session, …)
- MariaDB / MySQL (veya desteklenen diğer sürücülerden biri)
- Composer ve npm **isteğe bağlı** (çalışma zamanında gerekmez)

### Veritabanı

```bash
# MariaDB örneği — şema + seed
mysql -u root esh4 < database/schemas/schema.sql
# Seed dosyaları sırası: database/seed/install_seeds.php
```

Yerel yapılandırma şablonu: `config/config.local.example.php` → `config/config.local.php`

### Yerel sunucu

```bash
ESH_SITEURL=http://localhost:8000 php -S 0.0.0.0:8000 -t . tools/dev_server_router.php
```

Tarayıcı: `http://localhost:8000/public/`

### Test

```bash
php tools/run_smoke_tests.php --download   # ilk seferde PHPUnit PHAR indirir
php tools/run_smoke_tests.php
php tools/verify_full_system.php           # tam sistem QA
```

## Özellikler (özet)

- Hasta kayıt, izlem, planlama ve ekip yönetimi
- RBAC yetkilendirme, çok kurum / bölge kapsamı
- ESYS / USBS dosya köprüsü, REST API v1 (Bearer token)
- SMS, mesajlaşma, stok, istatistik modülleri
- Klinik ölçekler (Barthel, Braden, Harizmi, Itaki, MNA)

## Dokümantasyon

- [`AGENTS.md`](AGENTS.md) — geliştirici / CI notları
- [`GELISTIRME_KURALLARI.md`](GELISTIRME_KURALLARI.md) — kodlama kuralları
- [`docs/api-v1-openapi.yaml`](docs/api-v1-openapi.yaml) — REST API OpenAPI

## Lisans

Bu depo özel / kurumsal kullanım içindir. Dağıtım koşulları proje sahibi tarafından belirlenir.
