ESH 4.0.0 — temiz kurulum paketi (setup/)
==========================================
Oluşturma: 2026-06-17
Kaynak betik: php tools/build_setup_mirror.php

Bu klasör, sıfır sunucu kurulumu için gerekli uygulama dosyalarının aynasıdır.
Geliştirme ortamı artıkları, dist/, .git, yerel config ve log yedekleri dahil edilmez.

Kurulum (özet)
--------------
1. Bu klasörün İÇERİĞİNİ (setup/ değil, altındaki app, public, config, …) web köküne kopyalayın.
2. Tarayıcıdan `public/install.php` sihirbazını açın (şema + seed + config.local.php + install.lock).
3. Veritabanı türü: mysql, sqlsrv, pgsql, sqlite veya oci (sihirbazda seçilir).
   İlgili şema: database/schemas/ altında schema.sql, schema.mssql.sql, schema.pgsql.sql, schema.sqlite.sql, schema.oci.sql
4. Kurulum sonrası güvenlik için `public/install.php` dosyasını sunucudan kaldırın veya kilitleyin.

Önemli
------
• Desteklenen sürücüler: `mysql`, `sqlsrv`, `pgsql`, `sqlite`, `oci` (ilgili PDO eklentisi gerekir).
• `config/config.local.php` ve `config/install.lock` pakette YOK — sihirbaz oluşturur.
• `config/config.local.example.php` şablon olarak dahildir.
• `logs/` boş bırakıldı; uygulama çalışırken dolar.
• `storage/backups/` yalnızca .htaccess / .gitignore ile gelir (--full değilse).

Dosya listesi: DOSYALAR.txt
