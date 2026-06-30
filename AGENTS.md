# AGENTS.md

## Cursor Cloud specific instructions

This is **ESH / "SONEV" v4.0.0**, a Turkish home-health-care management web app. It is plain **PHP 8** using a custom MVC framework — there is **no Composer and no npm**; classes load via `spl_autoload_register` in `public/index.php`. Database access is PDO; the default driver is **MySQL/MariaDB** (other drivers exist under `database/schemas/`).

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
- The superadmin (`admin` / `Admin123`, `esh_users.id=1`, `isadmin=2`) is created by the web installer's finalize step; if missing, insert it with `password_hash('Admin123', PASSWORD_DEFAULT)`.

### Run the app (development)
There is no Composer/npm dev server. Serve with PHP's built-in server. The app expects its docroot to be the **project root** and is reached under `/public/` (assets resolve as `SITEURL/public/assets`). Because `php -S` has no `mod_rewrite`, use the committed dev router `tools/dev_server_router.php`, which forwards `/public/*` to `public/index.php` (mirroring `public/.htaccess`). Set `ESH_SITEURL` to match host/port, e.g. (from the project root):
```
ESH_SITEURL=http://localhost:8000 php -S 0.0.0.0:8000 -t . tools/dev_server_router.php
```
Then open `http://localhost:8000/public/` (root `/` 302-redirects to `/public/`). Routing also accepts `?controller=X&action=Y` in addition to SEF paths like `/public/Patient/ilkkayit`.

### Lint / test / build
- Lint: `php -l <file>` (per `GELISTIRME_KURALLARI.md`). Note `views/partials/site/list_page_close.php` is a template fragment that does not pass standalone `php -l` (pre-existing; it is only ever `include`d).
- Tests: there is **no automated test suite** (no PHPUnit/Composer). The `tools/` directory holds standalone PHP verification/maintenance scripts, not a unit-test harness.
- Build: none; it is interpreted PHP. The disabled installer ships as `public/install.php__`; if you re-enable `public/install.php` while `config/install.lock` exists, the app returns a 503 maintenance page unless `ESH_ALLOW_INSTALL_PHP=1`.

### Useful env overrides
`ESH_SITEURL`, `TOMTOM_KEY`, `ESH_DB_DEBUG`, `ESH_ALLOW_INSTALL_PHP`, `ESH_MYSQL_BIN`. TomTom maps, SMS sending, and the e-imza smart-card bridge are optional and not needed for core patient/visit workflows.
