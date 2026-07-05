-- =============================================================================
-- ESH Panel — MySQL / MariaDB referans şeması (esh20)
-- =============================================================================
-- Karakter seti: utf8mb4 + utf8mb4_turkish_ci (Türkçe sıralama / büyük-küçük harf).
--
-- Tablolar (ön ek esh_):
--   esh_adrestablosu, esh_hastalikcat, esh_branslar, esh_guvence, esh_islemler,
--   esh_hastaliklar, esh_users, esh_hastalar, esh_araclar, esh_istekler, esh_izlemler, esh_pizlemler,
--   esh_erapor, esh_hastailacrapor, esh_hasta_ilaclar, esh_ekipler, esh_personel_izin, esh_personel_istek, esh_resmi_tatiller, esh_personel_nobet,
--   esh_hasta_yara_fotolar, esh_hasta_braden, esh_hasta_itaki, esh_hasta_harizmi, esh_hasta_mna, esh_hasta_barthel, esh_rota_cache, esh_rehber_etken, esh_rehber_ilac, esh_rehber_import_log,
--   esh_mesaj_konusmalar, esh_mesaj_konusma_uyeler, esh_mesajlar,
--   esh_sms_sablonlari, esh_sms_gonderim, esh_sms_alici, esh_sms_optout,
--   esh_stok_malzeme, esh_stok_mevcut, esh_stok_hareket, esh_stok_uyari_log, esh_stok_parti,
--   esh_hasta_nakil, esh_eimza_challenges, esh_eimza_login_logs,
--   esh_audit_log, esh_esys_sync_log, esh_api_tokens, esh_federation_regions, esh_federation_sync_log,
--   esh_usbs_sync_log, esh_portal_appointment_requests, esh_cds_ack
--
-- Kurulum:
--   • Tarayıcı: public/install.php (config/install.lock yokken index buraya yönlendirir)
--   • Elle: mysql -u KULLANICI -p VERİTABANI_ADI < database/schemas/schema.sql
--   • Seed (RBAC, ünvan rolleri, güvence, branş, işlem, istek, adres): database/seed/*.sql — Installer otomatik yükler
--   • Ünvan kataloğu: schema içinde #__unvanlar; RBAC verisi seed_esh_rbac.sql + seed_esh_unvan_roles.sql
--
-- UYARI: Bu dosya DROP TABLE IF EXISTS içerir; mevcut ESH tablolarını siler.
--
-- Not: Güncel şema tek kaynaktır; migrate dosyaları mevcut kurulumlar içindir.
-- Son senkron: uygulama modelleri + migrate_esh_* (2026-07); domain entity PK: CHAR(36) UUID.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `esh_cds_ack`;
DROP TABLE IF EXISTS `esh_portal_appointment_requests`;
DROP TABLE IF EXISTS `esh_api_tokens`;
DROP TABLE IF EXISTS `esh_usbs_sync_log`;
DROP TABLE IF EXISTS `esh_federation_sync_log`;
DROP TABLE IF EXISTS `esh_esys_sync_log`;
DROP TABLE IF EXISTS `esh_audit_log`;
DROP TABLE IF EXISTS `esh_rehber_ilac`;
DROP TABLE IF EXISTS `esh_rehber_etken`;
DROP TABLE IF EXISTS `esh_rehber_import_log`;
DROP TABLE IF EXISTS `esh_eimza_login_logs`;
DROP TABLE IF EXISTS `esh_eimza_challenges`;
DROP TABLE IF EXISTS `esh_stok_parti`;
DROP TABLE IF EXISTS `esh_stok_uyari_log`;
DROP TABLE IF EXISTS `esh_stok_hareket`;
DROP TABLE IF EXISTS `esh_stok_mevcut`;
DROP TABLE IF EXISTS `esh_stok_malzeme`;
DROP TABLE IF EXISTS `esh_sms_alici`;
DROP TABLE IF EXISTS `esh_sms_gonderim`;
DROP TABLE IF EXISTS `esh_sms_optout`;
DROP TABLE IF EXISTS `esh_sms_sablonlari`;
DROP TABLE IF EXISTS `esh_mesajlar`;
DROP TABLE IF EXISTS `esh_mesaj_konusma_uyeler`;
DROP TABLE IF EXISTS `esh_mesaj_konusmalar`;
DROP TABLE IF EXISTS `esh_hasta_nakil`;
DROP TABLE IF EXISTS `esh_rota_cache`;
DROP TABLE IF EXISTS `esh_ekipler`;
DROP TABLE IF EXISTS `esh_hasta_ilaclar`;
DROP TABLE IF EXISTS `esh_hasta_barthel`;
DROP TABLE IF EXISTS `esh_hasta_mna`;
DROP TABLE IF EXISTS `esh_hasta_harizmi`;
DROP TABLE IF EXISTS `esh_hasta_itaki`;
DROP TABLE IF EXISTS `esh_hasta_braden`;
DROP TABLE IF EXISTS `esh_hasta_yara_fotolar`;
DROP TABLE IF EXISTS `esh_erapor`;
DROP TABLE IF EXISTS `esh_pizlemler`;
DROP TABLE IF EXISTS `esh_izlemler`;
DROP TABLE IF EXISTS `esh_istekler`;
DROP TABLE IF EXISTS `esh_araclar`;
DROP TABLE IF EXISTS `esh_hastalar`;
DROP TABLE IF EXISTS `esh_personel_nobet`;
DROP TABLE IF EXISTS `esh_personel_istek`;
DROP TABLE IF EXISTS `esh_personel_izin`;
DROP TABLE IF EXISTS `esh_resmi_tatiller`;
DROP TABLE IF EXISTS `esh_users`;
DROP TABLE IF EXISTS `esh_hastailacrapor`;
DROP TABLE IF EXISTS `esh_hastaliklar`;
DROP TABLE IF EXISTS `esh_islemler`;
DROP TABLE IF EXISTS `esh_branslar`;
DROP TABLE IF EXISTS `esh_guvence`;
DROP TABLE IF EXISTS `esh_hastalikcat`;
DROP TABLE IF EXISTS `esh_kurum_hastalik`;
DROP TABLE IF EXISTS `esh_kurum_islem`;
DROP TABLE IF EXISTS `esh_kurum_istek`;
DROP TABLE IF EXISTS `esh_kurum_brans`;
DROP TABLE IF EXISTS `esh_kurum_adres`;
DROP TABLE IF EXISTS `esh_mahalle_plan`;
DROP TABLE IF EXISTS `esh_federation_regions`;
DROP TABLE IF EXISTS `esh_kurumlar`;
DROP TABLE IF EXISTS `esh_adrestablosu`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Federasyon bölgeleri (çok bölgeli SaaS)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_federation_regions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kod` VARCHAR(64) NOT NULL,
  `ad` VARCHAR(255) NOT NULL,
  `il_adi` VARCHAR(64) NULL DEFAULT NULL,
  `hub_node_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Merkez hub düğüm referansı',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `aciklama` TEXT NULL DEFAULT NULL,
  `ayarlar_json` LONGTEXT NULL DEFAULT NULL COMMENT 'Bölge varsayılan ayarları (modules, islem_ids, operational)',
  `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fed_region_kod` (`kod`),
  KEY `idx_fed_region_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `esh_federation_regions` (`id`, `kod`, `ad`, `aktif`, `aciklama`) VALUES (1, 'varsayilan', 'Varsayılan bölge', 1, 'Kurulum sonrası otomatik oluşturulur');

-- ---------------------------------------------------------------------------
-- Kurumlar (multi-tenant kök)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_kurumlar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad` VARCHAR(255) NOT NULL,
  `kod` VARCHAR(64) NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Federasyon bölgesi',
  `federation_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Uzak düğüm kurum referansı',
  `logo` VARCHAR(255) DEFAULT NULL,
  `adres` TEXT DEFAULT NULL,
  `telefon` VARCHAR(64) DEFAULT NULL,
  `ayarlar_json` LONGTEXT DEFAULT NULL,
  `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kurum_kod` (`kod`),
  KEY `idx_kurum_aktif` (`aktif`),
  KEY `idx_kurum_bolge` (`bolge_id`),
  KEY `idx_kurum_federation_ref` (`federation_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `esh_kurumlar` (`id`, `ad`, `kod`, `aktif`, `bolge_id`) VALUES (1, 'Varsayılan Kurum', 'varsayilan', 1, 1);

-- ---------------------------------------------------------------------------
-- Adres ağacı (bölge → ilçe → mahalle → sokak → kapı); platform geneli — tüm kurumlar ortak
-- Mahalle bölge/gün planlaması: esh_mahalle_plan (kurum bazlı)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_adrestablosu` (
  `id` VARCHAR(64) NOT NULL,
  `adi` VARCHAR(255) NOT NULL DEFAULT '',
  `ust_id` VARCHAR(64) DEFAULT NULL,
  `tip` VARCHAR(20) NOT NULL DEFAULT 'bolge' COMMENT 'bolge, ilce, mahalle, sokak, kapino',
  `federation_bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'esh_federation_regions.id (tip=bolge)',
  `coords` VARCHAR(255) DEFAULT NULL COMMENT 'kapino: enlem,boylam (TomTom / harita)',
  `has_coords` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=coords dolu (kapino)',
  PRIMARY KEY (`ust_id`, `id`),
  KEY `idx_adres_id` (`id`),
  KEY `idx_tip_ust` (`tip`, `ust_id`),
  KEY `idx_tip_has_coords` (`tip`, `has_coords`),
  UNIQUE KEY `uk_adres_fed_bolge` (`federation_bolge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_mahalle_plan` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `mahalle_id` VARCHAR(64) NOT NULL,
  `bolge` INT NOT NULL DEFAULT 0,
  `gun` VARCHAR(64) DEFAULT NULL COMMENT 'mahalle planlama: haftanın günleri CSV',
  PRIMARY KEY (`kurum_id`, `mahalle_id`),
  KEY `idx_mp_mahalle` (`mahalle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_kurum_adres` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `adres_id` VARCHAR(64) NOT NULL,
  `tip` VARCHAR(20) NOT NULL COMMENT 'ilce, mahalle, sokak',
  PRIMARY KEY (`kurum_id`, `adres_id`),
  KEY `idx_ka_kurum_tip` (`kurum_id`, `tip`),
  KEY `idx_ka_adres` (`adres_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_kurum_brans` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `brans_id` INT UNSIGNED NOT NULL,
  `hasta_kotasi` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Günlük maks. hasta; NULL/0=sınırsız',
  PRIMARY KEY (`kurum_id`, `brans_id`),
  KEY `idx_kb_brans` (`brans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_kurum_istek` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `istek_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`kurum_id`, `istek_id`),
  KEY `idx_ki_istek` (`istek_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_kurum_islem` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `islem_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`kurum_id`, `islem_id`),
  KEY `idx_km_islem` (`islem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_kurum_hastalik` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `hastalik_id` INT UNSIGNED NOT NULL,
  `icd` VARCHAR(32) DEFAULT NULL COMMENT 'Atama anındaki ICD-10',
  PRIMARY KEY (`kurum_id`, `hastalik_id`),
  KEY `idx_kh_hastalik` (`hastalik_id`),
  KEY `idx_kh_kurum_icd` (`kurum_id`, `icd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hastalikcat` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `icd_range` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_branslar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=platform kataloğu',
  `bransadi` VARCHAR(255) NOT NULL,
  `hasta_kotasi` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Kullanılmıyor; kota esh_kurum_brans',
  PRIMARY KEY (`id`),
  KEY `idx_branslar_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_guvence` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guvenceadi` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_islemler` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=platform kataloğu',
  `islemadi` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_islemler_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_araclar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `plaka` VARCHAR(32) NOT NULL DEFAULT '',
  `arac_bilgisi` VARCHAR(255) NOT NULL DEFAULT '',
  `kapasite` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Eşzamanlı hasta/ziyaret kapasitesi',
  PRIMARY KEY (`id`),
  KEY `idx_plaka` (`plaka`),
  KEY `idx_araclar_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_istekler` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=platform kataloğu',
  `istek_adi` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_istekler_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_hastaliklar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=platform kataloğu',
  `cat` INT UNSIGNED NOT NULL DEFAULT 0,
  `hastalikadi` VARCHAR(255) NOT NULL,
  `icd` VARCHAR(32) DEFAULT NULL,
  `parent_icd` VARCHAR(32) DEFAULT NULL COMMENT 'SKRS üst kod (ICD-10 ağacı)',
  `seviye` TINYINT UNSIGNED DEFAULT NULL COMMENT 'SKRS seviye (2=grup, 3+=alt kod)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hastaliklar_kurum_icd` (`kurum_id`, `icd`),
  KEY `idx_cat` (`cat`),
  KEY `idx_hastaliklar_kurum` (`kurum_id`),
  KEY `idx_hastaliklar_icd` (`icd`),
  KEY `idx_hastaliklar_parent` (`parent_icd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Hasta tanı bazlı ilaç/rapor satırları (TC + esh_hastaliklar.id); `HastaIlacRapor` modeli
CREATE TABLE IF NOT EXISTS `esh_hastailacrapor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hastatckimlik` VARCHAR(11) NOT NULL,
  `hastalikicd` VARCHAR(32) NOT NULL COMMENT 'ICD-10 tanı kodu',
  `rapor` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 raporlu',
  `bitistarihi` DATE DEFAULT NULL COMMENT 'raporlu iken bitiş (YYYY-MM-DD)',
  `brans` VARCHAR(512) NOT NULL DEFAULT '' COMMENT 'virgülle esh_branslar.id',
  `raporyeri` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `uk_tc_hastalik_icd` (`hastatckimlik`, `hastalikicd`),
  KEY `idx_tc` (`hastatckimlik`),
  KEY `idx_hastalik_icd` (`hastalikicd`),
  KEY `idx_hastailacrapor_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Hasta ilaç listesi (hasta_id); `HastaIlac` modeli
CREATE TABLE IF NOT EXISTS `esh_hasta_ilaclar` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `ilac_adi` VARCHAR(255) NOT NULL,
  `etken_madde` VARCHAR(512) NULL,
  `recete_turu` VARCHAR(128) NULL,
  `not` TEXT NULL,
  `hastalikicd` VARCHAR(32) DEFAULT NULL COMMENT 'İlgili ICD-10 tanı',
  `sira` SMALLINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hasta` (`hasta_id`),
  KEY `idx_hasta_ilac_hastalik_icd` (`hastalikicd`),
  KEY `idx_hasta_ilaclar_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_users` (
  `id` CHAR(36) NOT NULL,
  `username` VARCHAR(64) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(128) NOT NULL DEFAULT '',
  `tckimlikno` VARCHAR(11) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `nowvisit` DATETIME DEFAULT NULL,
  `lastvisit` DATETIME DEFAULT NULL,
  `registerDate` DATETIME DEFAULT NULL,
  `activated` TINYINT(1) NOT NULL DEFAULT 0,
  `activation` VARCHAR(64) DEFAULT NULL,
  `isadmin` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=personel, 1=kurum yöneticisi, 2=bölge yöneticisi, 3=sistem yöneticisi',
  `kurum_id` INT UNSIGNED NULL DEFAULT 1 COMMENT 'NULL=platform hesabı',
  `bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Bölge yöneticisi federasyon bölge kapsamı',
  `unvan` VARCHAR(64) DEFAULT NULL COMMENT 'örn. hemsire, tekniker, eczaci, diger',
  `ui_theme` VARCHAR(64) DEFAULT NULL COMMENT 'templates/<slug>; NULL = site default (config)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_users_activated_name` (`activated`, `name`),
  KEY `idx_users_kurum` (`kurum_id`),
  KEY `idx_users_bolge` (`bolge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hastalar` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `tckimlik` VARCHAR(11) NOT NULL,
  `isim` VARCHAR(128) DEFAULT NULL,
  `soyisim` VARCHAR(128) DEFAULT NULL,
  `anneAdi` VARCHAR(128) DEFAULT NULL,
  `babaAdi` VARCHAR(128) DEFAULT NULL,
  `dogumtarihi` DATE DEFAULT NULL,
  `cinsiyet` VARCHAR(2) DEFAULT NULL COMMENT '1=erkek, 2=kadın',
  `kilo` DECIMAL(6,2) DEFAULT NULL,
  `boy` DECIMAL(6,2) DEFAULT NULL,
  `kayittarihi` DATE DEFAULT NULL,
  `ceptel1` VARCHAR(32) DEFAULT NULL,
  `ceptel2` VARCHAR(32) DEFAULT NULL,
  `bakimveren_ad` VARCHAR(128) DEFAULT NULL,
  `bakimveren_tel` VARCHAR(32) DEFAULT NULL,
  `bakimveren_yakinlik` VARCHAR(64) DEFAULT NULL,
  `alerji` TEXT,
  `acil_not` TEXT,
  `guvence` INT DEFAULT NULL,
  `yupasno` VARCHAR(64) DEFAULT NULL,
  `ailehekimi` VARCHAR(128) DEFAULT NULL,
  `ailehekimitel` VARCHAR(32) DEFAULT NULL,
  `sms_bilgilendirme_onay` TINYINT(1) NOT NULL DEFAULT 1,
  `kangrubu` VARCHAR(8) DEFAULT NULL,
  `ilce` VARCHAR(64) DEFAULT NULL,
  `mahalle` VARCHAR(64) DEFAULT NULL,
  `sokak` VARCHAR(64) DEFAULT NULL,
  `kapino` VARCHAR(64) DEFAULT NULL,
  `adres_aciklama` TEXT,
  `diger_adres` LONGTEXT COMMENT 'JSON veya serileştirilmiş ek adresler',
  `bagimlilik` VARCHAR(10) DEFAULT NULL,
  `pasif` VARCHAR(10) NOT NULL DEFAULT '0' COMMENT '0 aktif, 1 pasif, -3 bekleyen, -1 vefat, 4 araf, 5 silinen',
  `pasiftarihi` DATE DEFAULT NULL,
  `pasifnedeni` VARCHAR(16) DEFAULT NULL,
  `gecici` TINYINT(1) NOT NULL DEFAULT 0,
  `ng` TINYINT(1) NOT NULL DEFAULT 0,
  `peg` TINYINT(1) NOT NULL DEFAULT 0,
  `port` TINYINT(1) NOT NULL DEFAULT 0,
  `o2bagimli` TINYINT(1) NOT NULL DEFAULT 0,
  `ventilator` TINYINT(1) NOT NULL DEFAULT 0,
  `kolostomi` TINYINT(1) NOT NULL DEFAULT 0,
  `trakeostomi` TINYINT(1) NOT NULL DEFAULT 0,
  `cpap` TINYINT(1) NOT NULL DEFAULT 0,
  `aspirasyon` TINYINT(1) NOT NULL DEFAULT 0,
  `ileostomi` TINYINT(1) NOT NULL DEFAULT 0,
  `urostomi` TINYINT(1) NOT NULL DEFAULT 0,
  `picc` TINYINT(1) NOT NULL DEFAULT 0,
  `dren` TINYINT(1) NOT NULL DEFAULT 0,
  `diyaliz` TINYINT(1) NOT NULL DEFAULT 0,
  `basiyarasi` TINYINT(1) NOT NULL DEFAULT 0,
  `ivtedavi` TINYINT(1) NOT NULL DEFAULT 0,
  `izolasyon` TINYINT(1) NOT NULL DEFAULT 0,
  `sonda` TINYINT(1) NOT NULL DEFAULT 0,
  `sondatarihi` DATE DEFAULT NULL,
  `pansuman` TINYINT(1) NOT NULL DEFAULT 0,
  `pgunleri` VARCHAR(64) DEFAULT NULL,
  `pzaman` VARCHAR(32) DEFAULT NULL,
  `mama` TINYINT(1) NOT NULL DEFAULT 0,
  `mamacesit` VARCHAR(128) DEFAULT NULL,
  `mamaraporbitis` DATE DEFAULT NULL,
  `mamaraporyeri` VARCHAR(255) DEFAULT NULL,
  `bez` TINYINT(1) NOT NULL DEFAULT 0,
  `bezrapor` TINYINT(1) NOT NULL DEFAULT 0,
  `bezraporbitis` DATE DEFAULT NULL,
  `yatak` TINYINT(1) NOT NULL DEFAULT 0,
  `hastaliklar` TEXT COMMENT 'virgülle ICD-10 kodu',
  `erapor` VARCHAR(255) DEFAULT NULL,
  `esys_hasta_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS hasta kayıt no',
  `esys_basvuru_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS başvuru / dosya no',
  `enabiz_hasta_ref` VARCHAR(64) DEFAULT NULL COMMENT 'e-Nabız hasta kayıt no',
  `usbs_hasta_ref` VARCHAR(64) DEFAULT NULL COMMENT 'USBS hasta / dosya no',
  `randevutarihi` DATE DEFAULT NULL,
  `zaman` TINYINT DEFAULT NULL COMMENT '0 sabah 1 öğle 2 akşam',
  `notes` LONGTEXT,
  `profil_foto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tckimlik` (`tckimlik`),
  KEY `idx_pasif` (`pasif`(10)),
  KEY `idx_mahalle` (`mahalle`),
  KEY `idx_ilce` (`ilce`),
  KEY `idx_randevu_pasif_zaman` (`randevutarihi`, `pasif`, `zaman`),
  KEY `idx_pansuman_slot` (`pasif`, `pansuman`, `pzaman`),
  KEY `idx_pasif_isim` (`pasif`, `isim`),
  KEY `idx_hastalar_kurum` (`kurum_id`),
  KEY `idx_hastalar_esys_hasta_ref` (`esys_hasta_ref`),
  KEY `idx_hastalar_esys_basvuru_ref` (`esys_basvuru_ref`),
  KEY `idx_hastalar_enabiz_ref` (`enabiz_hasta_ref`),
  KEY `idx_hastalar_usbs_hasta_ref` (`usbs_hasta_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_izlemler` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hastatckimlik` VARCHAR(11) NOT NULL,
  `izlemtarihi` DATE NOT NULL,
  `izlemtarihi_dt` DATE DEFAULT NULL COMMENT 'Parse edilmiş izlem tarihi (Stats filtreleri)',
  `yapilan` TEXT COMMENT 'tek id veya virgüllü çoklu işlem',
  `yapildimi` TINYINT(1) NOT NULL DEFAULT 0,
  `neden` VARCHAR(255) DEFAULT NULL,
  `izlemiyapan` VARCHAR(255) DEFAULT NULL COMMENT 'virgüllü esh_users.id',
  `zaman` VARCHAR(32) DEFAULT NULL,
  `aciklama` TEXT,
  `checkin_lat` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'Saha ziyaret enlem (isteğe bağlı)',
  `checkin_lon` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'Saha ziyaret boylam (isteğe bağlı)',
  `checkin_at` DATETIME NULL DEFAULT NULL COMMENT 'Konum alındığı an',
  `checkin_accuracy` DECIMAL(8,1) NULL DEFAULT NULL COMMENT 'GPS doğruluk (metre)',
  `arac` INT UNSIGNED DEFAULT NULL COMMENT 'esh_araclar.id',
  `brans` VARCHAR(255) DEFAULT NULL COMMENT 'konsültasyon: virgülle esh_branslar.id',
  `kons_istekler` VARCHAR(512) DEFAULT NULL COMMENT 'konsültasyon EK-3: virgülle esh_istekler.id',
  `kons_brans_istek` TEXT DEFAULT NULL COMMENT 'konsültasyon EK-3: JSON brans_id => [istek_id]',
  `esys_izlem_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS izlem kayıt no',
  `esys_konsultasyon_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS konsültasyon kayıt no',
  `usbs_bildirim_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'USBS izlem bildirim no',
  `usbs_bildirim_durum` VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'pending,sent,failed,skipped',
  `usbs_bildirim_at` DATETIME NULL DEFAULT NULL COMMENT 'Bildirim gönderim / onay zamanı',
  `erecete_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'e-Reçete / Medula reçete no',
  PRIMARY KEY (`id`),
  KEY `idx_izlem_tc_yapildi_tarih` (`hastatckimlik`, `yapildimi`, `izlemtarihi`),
  KEY `idx_izlem_tarih_yapildi` (`izlemtarihi`, `yapildimi`),
  KEY `idx_izlem_tarih_tc` (`izlemtarihi`, `hastatckimlik`),
  KEY `idx_izlem_yapildi_tarih_dt` (`yapildimi`, `izlemtarihi_dt`),
  KEY `idx_arac` (`arac`),
  KEY `idx_izlemler_kurum` (`kurum_id`),
  KEY `idx_izlemler_esys_izlem_ref` (`esys_izlem_ref`),
  KEY `idx_izlemler_usbs_bildirim_ref` (`usbs_bildirim_ref`),
  KEY `idx_izlemler_usbs_bildirim_durum` (`usbs_bildirim_durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_pizlemler` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hastatckimlik` VARCHAR(11) NOT NULL,
  `planlanantarih` DATE NOT NULL,
  `yapilacak` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'işlem id veya virgüllü',
  `zaman` TINYINT NOT NULL DEFAULT 0,
  `planiyapan` CHAR(36) DEFAULT NULL,
  `plantarihi` DATETIME DEFAULT NULL,
  `oncelik` TINYINT NOT NULL DEFAULT 1,
  `aciklama` TEXT,
  `notlar` TEXT COMMENT 'pindex.php notlar alanı ile uyum',
  `durum` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 bekliyor, 1 tamamlandı',
  `esys_plan_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS planlı ziyaret no',
  PRIMARY KEY (`id`),
  KEY `idx_plan_zaman_durum` (`planlanantarih`, `zaman`, `durum`),
  KEY `idx_tc_durum` (`hastatckimlik`, `durum`),
  KEY `idx_pizlemler_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_erapor` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hastatckimlik` VARCHAR(11) DEFAULT NULL,
  `isim` VARCHAR(128) DEFAULT NULL,
  `soyisim` VARCHAR(128) DEFAULT NULL,
  `ceptel1` VARCHAR(32) DEFAULT NULL,
  `basvurutarihi` DATE DEFAULT NULL,
  `brans` INT UNSIGNED DEFAULT NULL,
  `kayitlimi` TINYINT(1) NOT NULL DEFAULT 0,
  `yenilendimi` TINYINT(1) NOT NULL DEFAULT 0,
  `neden` VARCHAR(255) DEFAULT NULL,
  `esys_erapor_ref` VARCHAR(64) DEFAULT NULL COMMENT 'ESYS e-rapor / başvuru eşleşme no',
  PRIMARY KEY (`id`),
  KEY `idx_tc` (`hastatckimlik`),
  KEY `idx_erapor_basvuru_id` (`basvurutarihi`, `id`),
  KEY `idx_erapor_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_ekipler` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `tarih` DATE NOT NULL,
  `vardiya` VARCHAR(32) DEFAULT NULL,
  `ekip_no` INT DEFAULT NULL,
  `user_ids` VARCHAR(512) DEFAULT NULL COMMENT 'virgülle kullanıcı id',
  `arac_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'esh_araclar.id',
  `baslangic_saati` VARCHAR(32) DEFAULT NULL,
  `kayit_tarihi` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tarih` (`tarih`),
  KEY `idx_ekipler_kurum` (`kurum_id`),
  KEY `idx_ekipler_arac` (`arac_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Nöbet planı (NobetPlan ile uyumlu; personel_id -> esh_users.id)
CREATE TABLE IF NOT EXISTS `esh_personel_izin` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `personel_id` CHAR(36) NOT NULL,
  `baslangic_tarihi` DATE NOT NULL,
  `bitis_tarihi` DATE NOT NULL,
  `sebep` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_izin_personel` (`personel_id`),
  KEY `idx_izin_tarih` (`baslangic_tarihi`, `bitis_tarihi`),
  KEY `idx_personel_izin_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_personel_istek` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `personel_id` CHAR(36) NOT NULL,
  `baslangic_tarihi` DATE NOT NULL,
  `bitis_tarihi` DATE NOT NULL,
  `aciklama` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_istek_personel` (`personel_id`),
  KEY `idx_istek_tarih` (`baslangic_tarihi`, `bitis_tarihi`),
  KEY `idx_personel_istek_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_resmi_tatiller` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `aciklama` VARCHAR(255) NOT NULL,
  `baslangic_tarihi` DATE NOT NULL,
  `bitis_tarihi` DATE NOT NULL,
  `tatil_tipi` VARCHAR(50) NOT NULL DEFAULT 'resmi_tatil',
  PRIMARY KEY (`id`),
  KEY `idx_tatil_tarih` (`baslangic_tarihi`, `bitis_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_personel_nobet` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `personel_id` CHAR(36) NOT NULL,
  `nobet_tarihi` DATE NOT NULL,
  `nobet_tipi` VARCHAR(50) NOT NULL DEFAULT 'normal',
  `durum` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tekil_nobet` (`personel_id`, `nobet_tarihi`),
  KEY `idx_nobet_tarih` (`nobet_tarihi`),
  KEY `idx_nobet_personel` (`personel_id`),
  KEY `idx_personel_nobet_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_yara_fotolar` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `dosya_adi` VARCHAR(255) NOT NULL,
  `orijinal_ad` VARCHAR(255) DEFAULT NULL,
  `mime` VARCHAR(100) DEFAULT NULL,
  `boyut` INT UNSIGNED DEFAULT NULL,
  `aciklama` VARCHAR(255) DEFAULT NULL,
  `yara_bolgesi` VARCHAR(100) DEFAULT NULL,
  `yara_evresi` VARCHAR(50) DEFAULT NULL,
  `cekim_tarihi` DATETIME DEFAULT NULL,
  `yukleyen_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hasta` (`hasta_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_yara_fotolar_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_braden` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `duyusal` TINYINT UNSIGNED NOT NULL,
  `nem` TINYINT UNSIGNED NOT NULL,
  `aktivite` TINYINT UNSIGNED NOT NULL,
  `hareket` TINYINT UNSIGNED NOT NULL,
  `beslenme` TINYINT UNSIGNED NOT NULL,
  `surtunme` TINYINT UNSIGNED NOT NULL,
  `toplam_skor` TINYINT UNSIGNED NOT NULL,
  `risk_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_braden_hasta` (`hasta_id`),
  KEY `idx_braden_tarih` (`degerlendirme_tarihi`),
  KEY `idx_braden_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_itaki` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `degerlendirme_gerekcesi` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `secimler_json` TEXT NOT NULL,
  `toplam_skor` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `risk_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_itaki_hasta` (`hasta_id`),
  KEY `idx_itaki_tarih` (`degerlendirme_tarihi`),
  KEY `idx_itaki_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_harizmi` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `degerlendirme_gerekcesi` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `secimler_json` TEXT NOT NULL,
  `toplam_skor` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `risk_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_harizmi_hasta` (`hasta_id`),
  KEY `idx_harizmi_tarih` (`degerlendirme_tarihi`),
  KEY `idx_harizmi_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_mna` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `besin_alimi` TINYINT UNSIGNED NOT NULL,
  `kilo_kaybi` TINYINT UNSIGNED NOT NULL,
  `mobilite` TINYINT UNSIGNED NOT NULL,
  `stres_hastalik` TINYINT UNSIGNED NOT NULL,
  `noropsikolojik` TINYINT UNSIGNED NOT NULL,
  `bmi_olcum_tipi` VARCHAR(20) NOT NULL DEFAULT 'bmi',
  `bmi_skor` TINYINT UNSIGNED NOT NULL,
  `toplam_skor` TINYINT UNSIGNED NOT NULL,
  `durum_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mna_hasta` (`hasta_id`),
  KEY `idx_mna_tarih` (`degerlendirme_tarihi`),
  KEY `idx_mna_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_barthel` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `hasta_id` CHAR(36) NOT NULL,
  `degerlendirme_tarihi` DATE NOT NULL,
  `barbeslenme` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barbanyo` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barbakim` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `bargiyinme` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barbarsak` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barmesane` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `bartuvalet` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `bartransfer` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barmobilite` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `barmerdiven` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `toplam_skor` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `bagimlilik_duzeyi` VARCHAR(32) NOT NULL DEFAULT '',
  `notlar` TEXT DEFAULT NULL,
  `kaydeden_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_barthel_hasta` (`hasta_id`),
  KEY `idx_barthel_tarih` (`degerlendirme_tarihi`),
  KEY `idx_barthel_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_rota_cache` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash` VARCHAR(128) NOT NULL,
  `origin` VARCHAR(512) NOT NULL,
  `destination` VARCHAR(512) NOT NULL,
  `sure` INT NOT NULL DEFAULT 0,
  `mesafe` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Branş / poliklinik randevu takvimi (takvim üzerinden; gün + branş + hasta TC)
CREATE TABLE IF NOT EXISTS `esh_kons_randevu` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `randevu_tarihi` DATE NOT NULL,
  `zaman` TINYINT NOT NULL DEFAULT 0 COMMENT '0 sabah 1 öğle 2 akşam',
  `kons_istekler` VARCHAR(512) NOT NULL DEFAULT '' COMMENT 'konsültasyon: virgülle esh_istekler.id',
  `brans_id` INT UNSIGNED NOT NULL,
  `hastatckimlik` VARCHAR(11) NOT NULL,
  `notlar` VARCHAR(512) DEFAULT NULL,
  `hasta_geldi` TINYINT NULL DEFAULT NULL COMMENT 'NULL=belirtilmedi, 1=geldi, 0=gelmedi',
  `olusturan_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tarih_brans_hasta` (`randevu_tarihi`, `brans_id`, `hastatckimlik`),
  KEY `idx_kons_randevu_tarih` (`randevu_tarihi`),
  KEY `idx_kons_randevu_hasta` (`hastatckimlik`),
  KEY `idx_kons_randevu_brans_tarih` (`brans_id`, `randevu_tarihi`),
  KEY `idx_kons_randevu_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_goruntulu_randevu` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `randevu_tarihi` DATE NOT NULL,
  `zaman` TINYINT NOT NULL DEFAULT 0 COMMENT '0 sabah 1 öğle 2 akşam',
  `kons_istekler` VARCHAR(512) NOT NULL DEFAULT '' COMMENT 'konsültasyon: virgülle esh_istekler.id',
  `brans_id` INT UNSIGNED NOT NULL,
  `hastatckimlik` VARCHAR(11) NOT NULL,
  `notlar` VARCHAR(512) DEFAULT NULL,
  `hasta_geldi` TINYINT NULL DEFAULT NULL COMMENT 'NULL=belirtilmedi, 1=geldi, 0=gelmedi',
  `video_room_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Jitsi oda adı',
  `video_started_at` DATETIME NULL DEFAULT NULL,
  `video_ended_at` DATETIME NULL DEFAULT NULL,
  `visit_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Görüşme sonrası bağlı izlem',
  `telehealth_summary` TEXT NULL DEFAULT NULL COMMENT 'Görüşme notu / özet',
  `olusturan_id` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gor_tarih_brans_hasta` (`randevu_tarihi`, `brans_id`, `hastatckimlik`),
  KEY `idx_gor_randevu_tarih` (`randevu_tarihi`),
  KEY `idx_gor_randevu_hasta` (`hastatckimlik`),
  KEY `idx_gor_randevu_brans_tarih` (`brans_id`, `randevu_tarihi`),
  KEY `idx_gor_randevu_kurum` (`kurum_id`),
  KEY `idx_gor_video_room` (`video_room_id`),
  KEY `idx_gor_visit` (`visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Kurumlar arası hasta nakil talep / onay logu
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_hasta_nakil` (
  `id` CHAR(36) NOT NULL,
  `kaynak_hasta_id` CHAR(36) NOT NULL,
  `kaynak_kurum_id` INT UNSIGNED NOT NULL,
  `hedef_kurum_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = il disi nakil (onay oncesi)',
  `hedef_bolge_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Il disi hedef federasyon bolgesi',
  `hedef_hasta_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Onay sonrasi acilan klon id',
  `onceki_nakil_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Geri nakilde bagli giden nakil',
  `orijinal_kaynak_hasta_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Onayda aktiflestirilecek kaynak kurum kaydi',
  `tip` ENUM('kurum_ici','il_disi','geri_nakil') NOT NULL DEFAULT 'kurum_ici',
  `durum` ENUM('beklemede','onaylandi','reddedildi','iptal') NOT NULL DEFAULT 'beklemede',
  `talep_eden_user_id` CHAR(36) NOT NULL,
  `talep_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `onaylayan_user_id` CHAR(36) NULL DEFAULT NULL,
  `onay_tarihi` DATETIME NULL DEFAULT NULL,
  `red_nedeni` VARCHAR(500) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hedef_durum` (`hedef_kurum_id`, `durum`),
  KEY `idx_hedef_bolge_durum` (`hedef_bolge_id`, `durum`),
  KEY `idx_kaynak_hasta` (`kaynak_hasta_id`),
  KEY `idx_kaynak_kurum_durum` (`kaynak_kurum_id`, `durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- SMS bildirimleri (hasta / yakın / aile hekimi bilgilendirme)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_sms_sablonlari` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `kod` VARCHAR(64) NOT NULL DEFAULT '',
  `baslik` VARCHAR(255) NOT NULL DEFAULT '',
  `govde` VARCHAR(1600) NOT NULL DEFAULT '',
  `degiskenler_json` TEXT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_sablon_kurum` (`kurum_id`, `aktif`),
  KEY `idx_sms_sablon_kod` (`kod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_gonderim` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `olusturan_id` CHAR(36) NOT NULL,
  `segment_tipi` VARCHAR(32) NOT NULL DEFAULT 'tek_hasta',
  `segment_param_json` TEXT NULL,
  `sablon_id` INT UNSIGNED NULL DEFAULT NULL,
  `govde_ozet` VARCHAR(500) NOT NULL DEFAULT '',
  `mesaj_turu` ENUM('bilgilendirme', 'ticari') NOT NULL DEFAULT 'bilgilendirme',
  `durum` ENUM('taslak', 'beklemede', 'gonderiliyor', 'tamamlandi', 'hata') NOT NULL DEFAULT 'beklemede',
  `toplam` INT UNSIGNED NOT NULL DEFAULT 0,
  `basarili` INT UNSIGNED NOT NULL DEFAULT 0,
  `basarisiz` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_gonderim_kurum` (`kurum_id`, `created_at`),
  KEY `idx_sms_gonderim_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_alici` (
  `id` CHAR(36) NOT NULL,
  `gonderim_id` CHAR(36) NOT NULL,
  `hasta_id` CHAR(36) NULL DEFAULT NULL,
  `rol` ENUM('hasta', 'hasta2', 'bakimveren', 'ailehekimi') NOT NULL DEFAULT 'hasta',
  `telefon_norm` VARCHAR(16) NOT NULL DEFAULT '',
  `govde` VARCHAR(1600) NOT NULL DEFAULT '',
  `provider_msg_id` VARCHAR(64) NULL DEFAULT NULL,
  `durum` ENUM('beklemede', 'gonderildi', 'teslim', 'hata', 'atlandi') NOT NULL DEFAULT 'beklemede',
  `hata_kodu` VARCHAR(32) NULL DEFAULT NULL,
  `hata_mesaj` VARCHAR(255) NULL DEFAULT NULL,
  `gonderim_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sms_alici_gonderim` (`gonderim_id`),
  KEY `idx_sms_alici_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_sms_optout` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telefon_norm` VARCHAR(16) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `kaynak` ENUM('manuel', 'sms_stop') NOT NULL DEFAULT 'manuel',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_optout_tel_kurum` (`telefon_norm`, `kurum_id`),
  KEY `idx_sms_optout_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Stok takip modülü
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_stok_malzeme` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `kod` VARCHAR(64) NULL DEFAULT NULL,
  `ad` VARCHAR(255) NOT NULL DEFAULT '',
  `kategori` ENUM('mama', 'bez', 'pansuman', 'yatak', 'sarf', 'cihaz', 'diger') NOT NULL DEFAULT 'sarf',
  `birim` ENUM('adet', 'kutu', 'paket', 'litre', 'kg') NOT NULL DEFAULT 'adet',
  `min_stok` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `aciklama` VARCHAR(512) NULL DEFAULT NULL,
  `tedarikci_adi` VARCHAR(255) NULL DEFAULT NULL,
  `tedarikci_tel` VARCHAR(32) NULL DEFAULT NULL,
  `birim_fiyat` DECIMAL(12,2) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_malzeme_kurum` (`kurum_id`, `aktif`),
  KEY `idx_stok_malzeme_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_mevcut` (
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `miktar` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kurum_id`, `malzeme_id`),
  KEY `idx_stok_mevcut_malzeme` (`malzeme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_hareket` (
  `id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `hareket_tipi` ENUM('giris', 'cikis', 'iade') NOT NULL,
  `miktar` DECIMAL(12,3) NOT NULL,
  `hareket_tarihi` DATE NOT NULL,
  `hasta_id` CHAR(36) NULL DEFAULT NULL,
  `ekip_id` INT UNSIGNED NULL DEFAULT NULL,
  `kullanici_id` CHAR(36) NOT NULL,
  `aciklama` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_hareket_kurum_tarih` (`kurum_id`, `hareket_tarihi`),
  KEY `idx_stok_hareket_malzeme` (`malzeme_id`),
  KEY `idx_stok_hareket_hasta` (`hasta_id`),
  KEY `idx_stok_hareket_ekip` (`ekip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_uyari_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `uyari_tarihi` DATE NOT NULL,
  `sms_gonderildi` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stok_uyari_gun` (`kurum_id`, `malzeme_id`, `uyari_tarihi`),
  KEY `idx_stok_uyari_kurum` (`kurum_id`, `uyari_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_stok_parti` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NOT NULL,
  `malzeme_id` INT UNSIGNED NOT NULL,
  `lot_no` VARCHAR(64) NULL DEFAULT NULL,
  `skt` DATE NULL DEFAULT NULL,
  `miktar` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stok_parti_malzeme` (`kurum_id`, `malzeme_id`, `skt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Mesajlaşma (DM, hasta konuşması, sistem duyurusu)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_mesaj_konusmalar` (
  `id` CHAR(36) NOT NULL,
  `tip` ENUM('dm', 'patient', 'system') NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `hasta_id` CHAR(36) NULL DEFAULT NULL,
  `dm_kucuk_id` CHAR(36) NULL DEFAULT NULL,
  `dm_buyuk_id` CHAR(36) NULL DEFAULT NULL,
  `baslik` VARCHAR(255) NOT NULL DEFAULT '',
  `olusturan_id` CHAR(36) NULL DEFAULT NULL,
  `son_mesaj_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dm_pair` (`dm_kucuk_id`, `dm_buyuk_id`),
  UNIQUE KEY `uq_hasta_thread` (`hasta_id`),
  KEY `idx_kurum_son` (`kurum_id`, `son_mesaj_at`),
  KEY `idx_tip` (`tip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_mesaj_konusma_uyeler` (
  `id` CHAR(36) NOT NULL,
  `konusma_id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `son_okunan_mesaj_id` CHAR(36) NULL DEFAULT NULL,
  `katilim_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `silindi_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_konusma_user` (`konusma_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_silindi` (`user_id`, `silindi_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_mesajlar` (
  `id` CHAR(36) NOT NULL,
  `konusma_id` CHAR(36) NOT NULL,
  `gonderen_id` CHAR(36) NULL DEFAULT NULL,
  `gonderen_tip` ENUM('user', 'system') NOT NULL DEFAULT 'user',
  `govde` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_konusma_id` (`konusma_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- E-imza giriş (challenge + audit log)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_eimza_challenges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `nonce_hash` CHAR(64) NOT NULL,
  `issued_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME NULL DEFAULT NULL,
  `ip_address` VARCHAR(64) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eimza_challenge_expires` (`expires_at`),
  KEY `idx_eimza_challenge_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_eimza_login_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `tc_kimlikno` VARCHAR(16) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `reason` VARCHAR(128) NOT NULL,
  `cert_serial` VARCHAR(128) NULL DEFAULT NULL,
  `cert_fingerprint` VARCHAR(128) NULL DEFAULT NULL,
  `ip_address` VARCHAR(64) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eimza_logs_user_created` (`user_id`, `created_at`),
  KEY `idx_eimza_logs_tc_created` (`tc_kimlikno`, `created_at`),
  KEY `idx_eimza_logs_success_created` (`success`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- KVKK / iç denetim — işlem günlüğü
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL COMMENT 'patient.view, visit.create, stats.export, ...',
  `entity_type` VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'patient, visit, stats, auth, ...',
  `entity_id` CHAR(36) NULL DEFAULT NULL,
  `entity_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'TC, rapor anahtarı vb.',
  `ip_address` VARCHAR(64) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `request_uri` VARCHAR(512) NULL DEFAULT NULL,
  `context_json` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user_created` (`user_id`, `created_at`),
  KEY `idx_audit_action_created` (`action`, `created_at`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_kurum_created` (`kurum_id`, `created_at`),
  KEY `idx_audit_entity_ref` (`entity_ref`, `created_at`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- ESYS / AHBS dosya senkron günlüğü
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_esys_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `direction` VARCHAR(24) NOT NULL COMMENT 'esh_to_esys, esys_to_esh, api_push',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `stats_json` TEXT NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_esys_sync_created` (`created_at`),
  KEY `idx_esys_sync_kurum_created` (`kurum_id`, `created_at`),
  KEY `idx_esys_sync_direction` (`direction`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- REST API v1 — bearer token
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `label` VARCHAR(128) NOT NULL DEFAULT '',
  `token_prefix` VARCHAR(16) NOT NULL COMMENT 'esh_live_ + ilk 8 hex — arama',
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256(token + pepper)',
  `scopes` VARCHAR(255) NOT NULL DEFAULT 'read' COMMENT 'read veya patients,visits,plans',
  `expires_at` DATETIME NULL DEFAULT NULL,
  `last_used_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_token_prefix` (`token_prefix`),
  KEY `idx_api_token_user` (`user_id`),
  KEY `idx_api_token_kurum` (`kurum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Federasyon dosya köprüsü — senkron günlüğü
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_federation_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `direction` VARCHAR(32) NOT NULL COMMENT 'node_snapshot, hub_to_node, node_to_hub',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `stats_json` TEXT NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fed_sync_created` (`created_at`),
  KEY `idx_fed_sync_direction` (`direction`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- e-Nabız / USBS entegrasyon köprüsü — senkron günlüğü
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_usbs_sync_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kurum_id` INT UNSIGNED NULL DEFAULT NULL,
  `user_id` CHAR(36) NULL DEFAULT NULL,
  `direction` VARCHAR(24) NOT NULL COMMENT 'esh_to_usbs, usbs_to_esh, api_push',
  `status` VARCHAR(16) NOT NULL COMMENT 'success, partial, failed',
  `file_name` VARCHAR(255) NULL DEFAULT NULL,
  `stats_json` TEXT NULL DEFAULT NULL,
  `error_message` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usbs_sync_created` (`created_at`),
  KEY `idx_usbs_sync_kurum_created` (`kurum_id`, `created_at`),
  KEY `idx_usbs_sync_direction` (`direction`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Hasta portalı — randevu değişiklik talepleri
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_portal_appointment_requests` (
  `id` CHAR(36) NOT NULL,
  `hasta_id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `uhds_id` CHAR(36) NOT NULL,
  `talep_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mevcut_tarih` DATE NOT NULL,
  `talep_tarih` DATE NOT NULL,
  `talep_zaman` TINYINT NULL DEFAULT NULL COMMENT '0 sabah 1 ogle 2 aksam',
  `neden` VARCHAR(500) NOT NULL,
  `durum` VARCHAR(20) NOT NULL DEFAULT 'queued',
  `durum_notu` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portal_req_hasta` (`hasta_id`),
  KEY `idx_portal_req_kurum` (`kurum_id`),
  KEY `idx_portal_req_durum` (`durum`),
  KEY `idx_portal_req_uhds` (`uhds_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- Klinik karar desteği — uyarı onay kayıtları
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_cds_ack` (
  `id` CHAR(36) NOT NULL,
  `hasta_id` CHAR(36) NOT NULL,
  `kurum_id` INT UNSIGNED NOT NULL,
  `alert_code` VARCHAR(80) NOT NULL,
  `ack_by_user_id` CHAR(36) NULL DEFAULT NULL,
  `ack_note` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cds_ack_hasta` (`hasta_id`),
  KEY `idx_cds_ack_kurum` (`kurum_id`),
  KEY `idx_cds_ack_code` (`alert_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------------
-- İlaç rehberi snapshot (üçüncü taraf scrape; TİTCK / hasta ilaçlarından ayrı)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esh_rehber_import_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_site` VARCHAR(32) NOT NULL,
  `started_at` DATETIME NOT NULL,
  `finished_at` DATETIME NULL DEFAULT NULL,
  `etken_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `ilac_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `error_summary` TEXT NULL,
  `options_json` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rehber_import_finished` (`finished_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_rehber_etken` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad` VARCHAR(512) NOT NULL,
  `ad_normalized` VARCHAR(512) NOT NULL,
  `source_site` VARCHAR(32) NOT NULL,
  `source_key` VARCHAR(128) NOT NULL,
  `scraped_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rehber_etken_site_key` (`source_site`, `source_key`),
  KEY `idx_rehber_etken_norm` (`ad_normalized`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_rehber_ilac` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `etken_id` INT UNSIGNED NOT NULL,
  `ad` VARCHAR(512) NOT NULL,
  `firma` VARCHAR(255) NULL,
  `recete_turu` VARCHAR(128) NULL,
  `source_site` VARCHAR(32) NOT NULL,
  `source_url` VARCHAR(1024) NULL,
  `source_key` VARCHAR(256) NOT NULL,
  `scraped_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rehber_ilac_site_key` (`source_site`, `source_key`),
  KEY `idx_rehber_ilac_etken` (`etken_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- =============================================================================
-- Personel ünvanları (evde sağlık hizmetleri — mevzuat + platform kataloğu)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `esh_unvanlar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kod` VARCHAR(64) NOT NULL COMMENT 'esh_users.unvan ile eşleşir (snake_case)',
  `ad` VARCHAR(128) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL DEFAULT 'diger' COMMENT 'hekim, hemsirelik, teknik, multidisipliner, idari, diger',
  `izin_sablonu` VARCHAR(64) NOT NULL DEFAULT 'personel' COMMENT 'Yeni rol izinleri için şablon rol slug',
  `sort_order` INT NOT NULL DEFAULT 100,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `mevzuat_notu` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unvan_kod` (`kod`),
  KEY `idx_unvan_aktif_sort` (`aktif`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO `esh_unvanlar` (`kod`, `ad`, `kategori`, `izin_sablonu`, `sort_order`, `is_system`, `mevzuat_notu`) VALUES
('uzman_doktor', 'Uzman Doktor', 'hekim', 'doktor', 10, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği — uzman hekim'),
('doktor', 'Doktor', 'hekim', 'doktor', 15, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği — hekim'),
('dis_hekimi', 'Diş Hekimi', 'hekim', 'doktor', 18, 1, 'D tipi evde sağlık birimi'),
('hemsire', 'Hemşire', 'hemsirelik', 'hemsire', 20, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('ebe', 'Ebe', 'hemsirelik', 'hemsire', 25, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('saglik_memuru', 'Sağlık Memuru', 'hemsirelik', 'hemsire', 30, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('toplum_sagligi_teknisyeni', 'Toplum Sağlığı Teknisyeni', 'hemsirelik', 'hemsire', 35, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('tekniker', 'Tekniker', 'teknik', 'tekniker', 40, 1, 'Evde bakım / yaşlı bakım teknikeri (genel)'),
('evde_hasta_bakim_teknikeri', 'Evde Hasta Bakım Teknikeri', 'teknik', 'tekniker', 45, 1, 'T/H tip birim çekirdek kadro'),
('yasli_bakim_teknikeri', 'Yaşlı Bakım Teknikeri', 'teknik', 'tekniker', 50, 1, 'T/H tip birim çekirdek kadro'),
('agiz_dis_sagligi_teknikeri', 'Ağız ve Diş Sağlığı Teknikeri', 'teknik', 'tekniker', 55, 1, 'D tip birim'),
('dis_protez_teknikeri', 'Diş Protez Teknikeri', 'teknik', 'tekniker', 58, 1, 'D tip birim'),
('yardimci_saglik_personeli', 'Yardımcı Sağlık Personeli', 'teknik', 'tekniker', 60, 1, 'T/H tip birim'),
('eczaci', 'Eczacı', 'multidisipliner', 'eczaci', 70, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('gerontolog', 'Gerontolog', 'multidisipliner', 'personel', 80, 1, 'Multidisipliner ekip (kurumsal)'),
('psikolog', 'Psikolog', 'multidisipliner', 'personel', 85, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('sosyal_calismaci', 'Sosyal Çalışmacı', 'multidisipliner', 'personel', 90, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('fizyoterapist', 'Fizyoterapist', 'multidisipliner', 'personel', 95, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('diyetisyen', 'Diyetisyen', 'multidisipliner', 'personel', 100, 1, 'Evde Sağlık Hizmeti Sunumu Yönetmeliği'),
('tibbi_sekreter', 'Tıbbi Sekreter', 'idari', 'personel', 110, 1, 'Birim kayıt ve iletişim'),
('saglik_yoneticisi', 'Sağlık Yöneticisi', 'idari', 'personel', 115, 1, 'İdari koordinasyon'),
('sofor', 'Şoför', 'idari', 'personel', 120, 1, 'Evde sağlık aracı (sürücü yetkisi)'),
('diger', 'Diğer', 'diger', 'personel', 200, 1, 'Tanımsız / diğer personel');

-- =============================================================================
-- Şema sonu (yeni sütunlar: önce buraya ekleyin; ardından migrate dosyası veya yedek üretin)
-- =============================================================================

-- RBAC: roller ve izinler
CREATE TABLE IF NOT EXISTS `esh_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `unvan_code` VARCHAR(64) NULL DEFAULT NULL COMMENT 'esh_users.unvan ile otomatik eşleme',
  `name` VARCHAR(128) NOT NULL,
  `description` VARCHAR(512) NULL DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`),
  UNIQUE KEY `uk_roles_unvan_code` (`unvan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_key` VARCHAR(64) NOT NULL,
  `crud` VARCHAR(32) NOT NULL,
  `slug` VARCHAR(128) NOT NULL,
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(512) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_slug` (`slug`),
  KEY `idx_permissions_module` (`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `esh_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `esh_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `esh_user_roles` (
  `user_id` CHAR(36) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `esh_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `esh_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
