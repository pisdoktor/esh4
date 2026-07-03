# AGENTS.md

## Cursor Cloud specific instructions

This is **ESH / "SONEV" v4.0.0**, a Turkish home-health-care management web app. It is plain **PHP 8** using a custom MVC framework — **Composer and npm are optional** (not required at runtime); classes load via `spl_autoload_register` in `public/index.php`. Optional `composer.json` adds dev tooling; `vendor/autoload.php` loads when present. Database access is PDO; the default driver is **MySQL/MariaDB** (other drivers exist under `database/schemas/`).

System dependencies (PHP CLI + extensions and MariaDB server) are baked into the VM snapshot. The startup update script is intentionally minimal (no repo package manager to run); it only ensures the app's runtime-writable directories exist. Services are **not** auto-started — start them yourself as below.

### Start the database (required, each session)
MariaDB is installed but not auto-started:
```
sudo mkdir -p /var/run/mysqld && sudo chown mysql:mysql /var/run/mysqld
sudo mariadbd-safe &
sleep 8 && sudo mysqladmin ping
```
- DB name `esh4`, table prefix `esh_`, user `root` with **empty password** over TCP `127.0.0.1:3306` (matches `config/config.local.php`). The `root@localhost` account is configured for `mysql_native_password` with an empty password so the PDO TCP connection works; this persists on disk.
- If `esh4` is missing/empty, recreate it: import `database/schemas/schema.sql`, then the seed files listed in `database/seed/install_seeds.php` **in order**. Seed files use the `#__` table-prefix placeholder, so substitute it before importing, e.g. `sed 's/#__/esh_/g' database/seed/<file> | mysql --protocol=TCP -h 127.0.0.1 -u root esh4`. (`schema.sql` already uses the literal `esh_` prefix.)
- The platform owner account (`admin` / `Admin123`, `esh_users.id=1`, `isadmin=3`) is created by the web installer's finalize step; if missing, insert it with `password_hash('Admin123', PASSWORD_DEFAULT)` and `isadmin=3`. Existing installs: run `database/migrate_esh_platform_owner_level.sql` to promote the oldest `isadmin=2` user.

### Run the app (development)
There is no Composer/npm dev server. Serve with PHP's built-in server. The app expects its docroot to be the **project root** and is reached under `/public/` (assets resolve as `SITEURL/public/assets`). Because `php -S` has no `mod_rewrite`, use the committed dev router `tools/dev_server_router.php`, which forwards `/public/*` to `public/index.php` (mirroring `public/.htaccess`). Set `ESH_SITEURL` to match host/port, e.g. (from the project root):
```
ESH_SITEURL=http://localhost:8000 php -S 0.0.0.0:8000 -t . tools/dev_server_router.php
```
Then open `http://localhost:8000/public/` (root `/` 302-redirects to `/public/`). Routing also accepts `?controller=X&action=Y` in addition to SEF paths like `/public/Patient/ilkkayit`.

### Lint / test / build
- Lint: `php -l <file>` (per `GELISTIRME_KURALLARI.md`). Note `views/partials/site/list_page_close.php` is a template fragment that does not pass standalone `php -l` (pre-existing; it is only ever `include`d).
- Smoke tests (PHPUnit, Composer gerekmez): `php tools/run_smoke_tests.php --download` (ilk seferde PHAR indirir), sonra `php tools/run_smoke_tests.php`. `composer install` sonrası vendor PHPUnit otomatik kullanılır. Kapsam: `tests/Smoke/`.
- **Tam sistem QA:** `php tools/verify_full_system.php` — lint, modül CRUD wiring, read-only SQL probu, migration/rota smoke, PHPUnit ve `logs/db_errors.log` delta kapısı; sonunda tarayıcı CRUD checklist basar. Baseline: `php tools/verify_db_errors_gate.php --save-baseline`. Ayrı: `verify_module_crud.php`, `verify_module_queries.php`, `verify_phase_migrations.php`, `verify_phase_routes.php`.
- **CI önerisi:** Her push/PR öncesi `php tools/run_smoke_tests.php` çalıştırın; başarısızsa merge etmeyin. İsteğe bağlı: `php -l` ile değiştirilen PHP dosyalarını doğrulayın.
- REST API OpenAPI: `docs/api-v1-openapi.yaml`
- Modern frontend (isteğe bağlı): Ayarlar → Güvenlik → Modern frontend; kaynak `frontend/modern/` (Vite). CDN pilot: `public/assets/modern/*.mjs`. Derleme: `php tools/build_modern_frontend.php` (npm gerekir).
- REST API v1 (Bearer token, modül kapalı varsayılan): `public/api/v1/patients|visits|plans` — token: sistem sahibi «REST API tokenları» veya `php tools/create_api_token.php --user=ID`. Modülü ayarlardan açın (`rest_api`). Doğrulama: `php tools/test_api_v1.php TOKEN`.
- Build: none; it is interpreted PHP. The disabled installer ships as `public/install.php__`; if you re-enable `public/install.php` while `config/install.lock` exists, the app returns a 503 maintenance page unless `ESH_ALLOW_INSTALL_PHP=1`.

### Useful env overrides
`ESH_SITEURL`, `TOMTOM_KEY`, `ESH_DB_DEBUG`, `ESH_ALLOW_INSTALL_PHP`, `ESH_MYSQL_BIN`. TomTom maps, SMS sending, and the e-imza smart-card bridge are optional and not needed for core patient/visit workflows.
