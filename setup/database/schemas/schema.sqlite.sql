-- ESH Panel — SQLite şeması (schema.sql'den üretildi)
-- Üretim: php tools/build_schema_dialect.php sqlite
-- Kurulum: db_driver=sqlite
-- =============================================================================

PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS "esh_cds_ack";

DROP TABLE IF EXISTS "esh_portal_appointment_requests";

DROP TABLE IF EXISTS "esh_api_tokens";

DROP TABLE IF EXISTS "esh_usbs_sync_log";

DROP TABLE IF EXISTS "esh_federation_sync_log";

DROP TABLE IF EXISTS "esh_esys_sync_log";

DROP TABLE IF EXISTS "esh_audit_log";

DROP TABLE IF EXISTS "esh_rehber_ilac";

DROP TABLE IF EXISTS "esh_rehber_etken";

DROP TABLE IF EXISTS "esh_rehber_import_log";

DROP TABLE IF EXISTS "esh_eimza_login_logs";

DROP TABLE IF EXISTS "esh_eimza_challenges";

DROP TABLE IF EXISTS "esh_stok_parti";

DROP TABLE IF EXISTS "esh_stok_uyari_log";

DROP TABLE IF EXISTS "esh_stok_hareket";

DROP TABLE IF EXISTS "esh_stok_mevcut";

DROP TABLE IF EXISTS "esh_stok_malzeme";

DROP TABLE IF EXISTS "esh_sms_alici";

DROP TABLE IF EXISTS "esh_sms_gonderim";

DROP TABLE IF EXISTS "esh_sms_optout";

DROP TABLE IF EXISTS "esh_sms_sablonlari";

DROP TABLE IF EXISTS "esh_mesajlar";

DROP TABLE IF EXISTS "esh_mesaj_konusma_uyeler";

DROP TABLE IF EXISTS "esh_mesaj_konusmalar";

DROP TABLE IF EXISTS "esh_hasta_nakil";

DROP TABLE IF EXISTS "esh_rota_cache";

DROP TABLE IF EXISTS "esh_ekipler";

DROP TABLE IF EXISTS "esh_hasta_ilaclar";

DROP TABLE IF EXISTS "esh_hasta_barthel";

DROP TABLE IF EXISTS "esh_hasta_mna";

DROP TABLE IF EXISTS "esh_hasta_harizmi";

DROP TABLE IF EXISTS "esh_hasta_itaki";

DROP TABLE IF EXISTS "esh_hasta_braden";

DROP TABLE IF EXISTS "esh_hasta_yara_fotolar";

DROP TABLE IF EXISTS "esh_erapor";

DROP TABLE IF EXISTS "esh_pizlemler";

DROP TABLE IF EXISTS "esh_izlemler";

DROP TABLE IF EXISTS "esh_istekler";

DROP TABLE IF EXISTS "esh_araclar";

DROP TABLE IF EXISTS "esh_hastalar";

DROP TABLE IF EXISTS "esh_personel_nobet";

DROP TABLE IF EXISTS "esh_personel_istek";

DROP TABLE IF EXISTS "esh_personel_izin";

DROP TABLE IF EXISTS "esh_resmi_tatiller";

DROP TABLE IF EXISTS "esh_users";

DROP TABLE IF EXISTS "esh_hastailacrapor";

DROP TABLE IF EXISTS "esh_hastaliklar";

DROP TABLE IF EXISTS "esh_islemler";

DROP TABLE IF EXISTS "esh_branslar";

DROP TABLE IF EXISTS "esh_guvence";

DROP TABLE IF EXISTS "esh_hastalikcat";

DROP TABLE IF EXISTS "esh_kurum_hastalik";

DROP TABLE IF EXISTS "esh_kurum_islem";

DROP TABLE IF EXISTS "esh_kurum_istek";

DROP TABLE IF EXISTS "esh_kurum_brans";

DROP TABLE IF EXISTS "esh_kurum_adres";

DROP TABLE IF EXISTS "esh_mahalle_plan";

DROP TABLE IF EXISTS "esh_federation_regions";

DROP TABLE IF EXISTS "esh_kurumlar";

DROP TABLE IF EXISTS "esh_adrestablosu";

CREATE TABLE IF NOT EXISTS "esh_federation_regions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kod" VARCHAR(64) NOT NULL,
  "ad" VARCHAR(255) NOT NULL,
  "il_adi" VARCHAR(64) NULL DEFAULT NULL,
  "hub_node_ref" VARCHAR(64) NULL DEFAULT NULL,
  "aktif" INTEGER(1) NOT NULL DEFAULT 1,
  "aciklama" TEXT NULL DEFAULT NULL,
  "ayarlar_json" TEXT NULL DEFAULT NULL,
  "olusturma_tarihi" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_fed_region_kod" UNIQUE ("kod")
);

CREATE INDEX IF NOT EXISTS "idx_fed_region_aktif" ON "esh_federation_regions" ("aktif");

INSERT INTO "esh_federation_regions" ("id", "kod", "ad", "aktif", "aciklama") VALUES (1, 'varsayilan', 'Varsayılan bölge', 1, 'Kurulum sonrası otomatik oluşturulur');

CREATE TABLE IF NOT EXISTS "esh_kurumlar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "ad" VARCHAR(255) NOT NULL,
  "kod" VARCHAR(64) NOT NULL,
  "aktif" INTEGER(1) NOT NULL DEFAULT 1,
  "bolge_id" INTEGER NULL DEFAULT NULL,
  "federation_ref" VARCHAR(64) NULL DEFAULT NULL,
  "logo" VARCHAR(255) DEFAULT NULL,
  "adres" TEXT DEFAULT NULL,
  "telefon" VARCHAR(64) DEFAULT NULL,
  "ayarlar_json" TEXT DEFAULT NULL,
  "olusturma_tarihi" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_kurum_kod" UNIQUE ("kod")
);

CREATE INDEX IF NOT EXISTS "idx_kurum_aktif" ON "esh_kurumlar" ("aktif");
CREATE INDEX IF NOT EXISTS "idx_kurum_bolge" ON "esh_kurumlar" ("bolge_id");
CREATE INDEX IF NOT EXISTS "idx_kurum_federation_ref" ON "esh_kurumlar" ("federation_ref");

INSERT INTO "esh_kurumlar" ("id", "ad", "kod", "aktif", "bolge_id") VALUES (1, 'Varsayılan Kurum', 'varsayilan', 1, 1);

CREATE TABLE IF NOT EXISTS "esh_adrestablosu" (
  "id" VARCHAR(64) NOT NULL,
  "adi" VARCHAR(255) NOT NULL DEFAULT '',
  "ust_id" VARCHAR(64) DEFAULT NULL,
  "tip" VARCHAR(20) NOT NULL DEFAULT 'bolge',
  "federation_bolge_id" INTEGER NULL DEFAULT NULL,
  "coords" VARCHAR(255) DEFAULT NULL,
  "has_coords" INTEGER(1) NOT NULL DEFAULT 0,
  PRIMARY KEY ("ust_id", "id"),
  CONSTRAINT "uk_adres_fed_bolge" UNIQUE ("federation_bolge_id")
);

CREATE INDEX IF NOT EXISTS "idx_adres_id" ON "esh_adrestablosu" ("id");
CREATE INDEX IF NOT EXISTS "idx_tip_ust" ON "esh_adrestablosu" ("tip", "ust_id");
CREATE INDEX IF NOT EXISTS "idx_tip_has_coords" ON "esh_adrestablosu" ("tip", "has_coords");

CREATE TABLE IF NOT EXISTS "esh_mahalle_plan" (
  "kurum_id" INTEGER NOT NULL,
  "mahalle_id" VARCHAR(64) NOT NULL,
  "bolge" INTEGER NOT NULL DEFAULT 0,
  "gun" VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "mahalle_id")
);

CREATE INDEX IF NOT EXISTS "idx_mp_mahalle" ON "esh_mahalle_plan" ("mahalle_id");

CREATE TABLE IF NOT EXISTS "esh_kurum_adres" (
  "kurum_id" INTEGER NOT NULL,
  "adres_id" VARCHAR(64) NOT NULL,
  "tip" VARCHAR(20) NOT NULL,
  PRIMARY KEY ("kurum_id", "adres_id")
);

CREATE INDEX IF NOT EXISTS "idx_ka_kurum_tip" ON "esh_kurum_adres" ("kurum_id", "tip");
CREATE INDEX IF NOT EXISTS "idx_ka_adres" ON "esh_kurum_adres" ("adres_id");

CREATE TABLE IF NOT EXISTS "esh_kurum_brans" (
  "kurum_id" INTEGER NOT NULL,
  "brans_id" INTEGER NOT NULL,
  "hasta_kotasi" INTEGER NULL DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "brans_id")
);

CREATE INDEX IF NOT EXISTS "idx_kb_brans" ON "esh_kurum_brans" ("brans_id");

CREATE TABLE IF NOT EXISTS "esh_kurum_istek" (
  "kurum_id" INTEGER NOT NULL,
  "istek_id" INTEGER NOT NULL,
  PRIMARY KEY ("kurum_id", "istek_id")
);

CREATE INDEX IF NOT EXISTS "idx_ki_istek" ON "esh_kurum_istek" ("istek_id");

CREATE TABLE IF NOT EXISTS "esh_kurum_islem" (
  "kurum_id" INTEGER NOT NULL,
  "islem_id" INTEGER NOT NULL,
  PRIMARY KEY ("kurum_id", "islem_id")
);

CREATE INDEX IF NOT EXISTS "idx_km_islem" ON "esh_kurum_islem" ("islem_id");

CREATE TABLE IF NOT EXISTS "esh_kurum_hastalik" (
  "kurum_id" INTEGER NOT NULL,
  "hastalik_id" INTEGER NOT NULL,
  "icd" VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "hastalik_id")
);

CREATE INDEX IF NOT EXISTS "idx_kh_hastalik" ON "esh_kurum_hastalik" ("hastalik_id");

CREATE INDEX IF NOT EXISTS "idx_kh_kurum_icd" ON "esh_kurum_hastalik" ("kurum_id", "icd");

CREATE TABLE IF NOT EXISTS "esh_hastalikcat" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "icd_range" VARCHAR(64) DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS "esh_branslar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 0,
  "bransadi" VARCHAR(255) NOT NULL,
  "hasta_kotasi" INTEGER NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_branslar_kurum" ON "esh_branslar" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_guvence" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "guvenceadi" VARCHAR(255) NOT NULL
);


CREATE TABLE IF NOT EXISTS "esh_islemler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 0,
  "islemadi" VARCHAR(255) NOT NULL
);

CREATE INDEX IF NOT EXISTS "idx_islemler_kurum" ON "esh_islemler" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_araclar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "plaka" VARCHAR(32) NOT NULL DEFAULT '',
  "arac_bilgisi" VARCHAR(255) NOT NULL DEFAULT '',
  "kapasite" INTEGER NOT NULL DEFAULT 4
);

CREATE INDEX IF NOT EXISTS "idx_plaka" ON "esh_araclar" ("plaka");
CREATE INDEX IF NOT EXISTS "idx_araclar_kurum" ON "esh_araclar" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_istekler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 0,
  "istek_adi" VARCHAR(255) NOT NULL
);

CREATE INDEX IF NOT EXISTS "idx_istekler_kurum" ON "esh_istekler" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hastaliklar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 0,
  "cat" INTEGER NOT NULL DEFAULT 0,
  "hastalikadi" VARCHAR(255) NOT NULL,
  "icd" VARCHAR(32) DEFAULT NULL,
  "parent_icd" VARCHAR(32) DEFAULT NULL,
  "seviye" INTEGER DEFAULT NULL,
  CONSTRAINT "uk_hastaliklar_kurum_icd" UNIQUE ("kurum_id", "icd")
);

CREATE INDEX IF NOT EXISTS "idx_cat" ON "esh_hastaliklar" ("cat");
CREATE INDEX IF NOT EXISTS "idx_hastaliklar_kurum" ON "esh_hastaliklar" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_hastaliklar_icd" ON "esh_hastaliklar" ("icd");
CREATE INDEX IF NOT EXISTS "idx_hastaliklar_parent" ON "esh_hastaliklar" ("parent_icd");

CREATE TABLE IF NOT EXISTS "esh_hastailacrapor" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR(11) NOT NULL,
  "hastalikicd" VARCHAR(32) NOT NULL,
  "rapor" INTEGER(1) NOT NULL DEFAULT 0,
  "bitistarihi" DATE DEFAULT NULL,
  "brans" VARCHAR(512) NOT NULL DEFAULT '',
  "raporyeri" INTEGER(1) NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS "uk_tc_hastalik_icd" ON "esh_hastailacrapor" ("hastatckimlik", "hastalikicd");
CREATE INDEX IF NOT EXISTS "idx_tc" ON "esh_hastailacrapor" ("hastatckimlik");
CREATE INDEX IF NOT EXISTS "idx_hastalik_icd" ON "esh_hastailacrapor" ("hastalikicd");
CREATE INDEX IF NOT EXISTS "idx_hastailacrapor_kurum" ON "esh_hastailacrapor" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_ilaclar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "ilac_adi" VARCHAR(255) NOT NULL,
  "etken_madde" VARCHAR(512) NULL,
  "recete_turu" VARCHAR(128) NULL,
  "not" TEXT NULL,
  "hastalikicd" VARCHAR(32) NULL,
  "sira" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_hasta" ON "esh_hasta_ilaclar" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_hasta_ilac_hastalik_icd" ON "esh_hasta_ilaclar" ("hastalikicd");
CREATE INDEX IF NOT EXISTS "idx_hasta_ilaclar_kurum" ON "esh_hasta_ilaclar" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_users" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "username" VARCHAR(64) NOT NULL,
  "password" VARCHAR(255) NOT NULL,
  "name" VARCHAR(128) NOT NULL DEFAULT '',
  "tckimlikno" VARCHAR(11) DEFAULT NULL,
  "email" VARCHAR(255) DEFAULT NULL,
  "image" VARCHAR(255) DEFAULT NULL,
  "nowvisit" TEXT DEFAULT NULL,
  "lastvisit" TEXT DEFAULT NULL,
  "registerDate" TEXT DEFAULT NULL,
  "activated" INTEGER(1) NOT NULL DEFAULT 0,
  "activation" VARCHAR(64) DEFAULT NULL,
  "isadmin" INTEGER NOT NULL DEFAULT 0,
  "kurum_id" INTEGER NULL DEFAULT 1,
  "bolge_id" INTEGER NULL DEFAULT NULL,
  "unvan" VARCHAR(64) DEFAULT NULL,
  "ui_theme" VARCHAR(64) DEFAULT NULL,
  CONSTRAINT "uk_username" UNIQUE ("username")
);

CREATE INDEX IF NOT EXISTS "idx_users_activated_name" ON "esh_users" ("activated", "name");
CREATE INDEX IF NOT EXISTS "idx_users_kurum" ON "esh_users" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_users_bolge" ON "esh_users" ("bolge_id");

CREATE TABLE IF NOT EXISTS "esh_hastalar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "tckimlik" VARCHAR(11) NOT NULL,
  "isim" VARCHAR(128) DEFAULT NULL,
  "soyisim" VARCHAR(128) DEFAULT NULL,
  "anneAdi" VARCHAR(128) DEFAULT NULL,
  "babaAdi" VARCHAR(128) DEFAULT NULL,
  "dogumtarihi" DATE DEFAULT NULL,
  "cinsiyet" VARCHAR(2) DEFAULT NULL,
  "kilo" DECIMAL(6,2) DEFAULT NULL,
  "boy" DECIMAL(6,2) DEFAULT NULL,
  "kayittarihi" DATE DEFAULT NULL,
  "ceptel1" VARCHAR(32) DEFAULT NULL,
  "ceptel2" VARCHAR(32) DEFAULT NULL,
  "bakimveren_ad" VARCHAR(128) DEFAULT NULL,
  "bakimveren_tel" VARCHAR(32) DEFAULT NULL,
  "bakimveren_yakinlik" VARCHAR(64) DEFAULT NULL,
  "alerji" TEXT,
  "acil_not" TEXT,
  "guvence" INTEGER DEFAULT NULL,
  "yupasno" VARCHAR(64) DEFAULT NULL,
  "ailehekimi" VARCHAR(128) DEFAULT NULL,
  "ailehekimitel" VARCHAR(32) DEFAULT NULL,
  "sms_bilgilendirme_onay" INTEGER(1) NOT NULL DEFAULT 1,
  "kangrubu" VARCHAR(8) DEFAULT NULL,
  "ilce" VARCHAR(64) DEFAULT NULL,
  "mahalle" VARCHAR(64) DEFAULT NULL,
  "sokak" VARCHAR(64) DEFAULT NULL,
  "kapino" VARCHAR(64) DEFAULT NULL,
  "adres_aciklama" TEXT,
  "diger_adres" TEXT,
  "bagimlilik" VARCHAR(10) DEFAULT NULL,
  "pasif" VARCHAR(10) NOT NULL DEFAULT '0',
  "pasiftarihi" DATE DEFAULT NULL,
  "pasifnedeni" VARCHAR(16) DEFAULT NULL,
  "gecici" INTEGER(1) NOT NULL DEFAULT 0,
  "ng" INTEGER(1) NOT NULL DEFAULT 0,
  "peg" INTEGER(1) NOT NULL DEFAULT 0,
  "port" INTEGER(1) NOT NULL DEFAULT 0,
  "o2bagimli" INTEGER(1) NOT NULL DEFAULT 0,
  "ventilator" INTEGER(1) NOT NULL DEFAULT 0,
  "kolostomi" INTEGER(1) NOT NULL DEFAULT 0,
  "trakeostomi" INTEGER(1) NOT NULL DEFAULT 0,
  "cpap" INTEGER(1) NOT NULL DEFAULT 0,
  "aspirasyon" INTEGER(1) NOT NULL DEFAULT 0,
  "ileostomi" INTEGER(1) NOT NULL DEFAULT 0,
  "urostomi" INTEGER(1) NOT NULL DEFAULT 0,
  "picc" INTEGER(1) NOT NULL DEFAULT 0,
  "dren" INTEGER(1) NOT NULL DEFAULT 0,
  "diyaliz" INTEGER(1) NOT NULL DEFAULT 0,
  "basiyarasi" INTEGER(1) NOT NULL DEFAULT 0,
  "ivtedavi" INTEGER(1) NOT NULL DEFAULT 0,
  "izolasyon" INTEGER(1) NOT NULL DEFAULT 0,
  "sonda" INTEGER(1) NOT NULL DEFAULT 0,
  "sondatarihi" DATE DEFAULT NULL,
  "pansuman" INTEGER(1) NOT NULL DEFAULT 0,
  "pgunleri" VARCHAR(64) DEFAULT NULL,
  "pzaman" VARCHAR(32) DEFAULT NULL,
  "mama" INTEGER(1) NOT NULL DEFAULT 0,
  "mamacesit" VARCHAR(128) DEFAULT NULL,
  "mamaraporbitis" DATE DEFAULT NULL,
  "mamaraporyeri" VARCHAR(255) DEFAULT NULL,
  "bez" INTEGER(1) NOT NULL DEFAULT 0,
  "bezrapor" INTEGER(1) NOT NULL DEFAULT 0,
  "bezraporbitis" DATE DEFAULT NULL,
  "yatak" INTEGER(1) NOT NULL DEFAULT 0,
  "hastaliklar" TEXT,
  "erapor" VARCHAR(255) DEFAULT NULL,
  "esys_hasta_ref" VARCHAR(64) DEFAULT NULL,
  "esys_basvuru_ref" VARCHAR(64) DEFAULT NULL,
  "enabiz_hasta_ref" VARCHAR(64) DEFAULT NULL,
  "usbs_hasta_ref" VARCHAR(64) DEFAULT NULL,
  "randevutarihi" DATE DEFAULT NULL,
  "zaman" INTEGER DEFAULT NULL,
  "notes" TEXT,
  "profil_foto" VARCHAR(255) DEFAULT NULL,
  CONSTRAINT "uk_tckimlik" UNIQUE ("tckimlik")
);

CREATE INDEX IF NOT EXISTS "idx_pasif" ON "esh_hastalar" ("pasif");
CREATE INDEX IF NOT EXISTS "idx_mahalle" ON "esh_hastalar" ("mahalle");
CREATE INDEX IF NOT EXISTS "idx_ilce" ON "esh_hastalar" ("ilce");
CREATE INDEX IF NOT EXISTS "idx_randevu_pasif_zaman" ON "esh_hastalar" ("randevutarihi", "pasif", "zaman");
CREATE INDEX IF NOT EXISTS "idx_pansuman_slot" ON "esh_hastalar" ("pasif", "pansuman", "pzaman");
CREATE INDEX IF NOT EXISTS "idx_pasif_isim" ON "esh_hastalar" ("pasif", "isim");
CREATE INDEX IF NOT EXISTS "idx_hastalar_kurum" ON "esh_hastalar" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_hastalar_esys_hasta_ref" ON "esh_hastalar" ("esys_hasta_ref");
CREATE INDEX IF NOT EXISTS "idx_hastalar_esys_basvuru_ref" ON "esh_hastalar" ("esys_basvuru_ref");
CREATE INDEX IF NOT EXISTS "idx_hastalar_enabiz_ref" ON "esh_hastalar" ("enabiz_hasta_ref");
CREATE INDEX IF NOT EXISTS "idx_hastalar_usbs_hasta_ref" ON "esh_hastalar" ("usbs_hasta_ref");

CREATE TABLE IF NOT EXISTS "esh_izlemler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR(11) NOT NULL,
  "izlemtarihi" DATE NOT NULL,
  "izlemtarihi_dt" DATE DEFAULT NULL,
  "yapilan" TEXT,
  "yapildimi" INTEGER(1) NOT NULL DEFAULT 0,
  "neden" VARCHAR(255) DEFAULT NULL,
  "izlemiyapan" VARCHAR(255) DEFAULT NULL,
  "zaman" VARCHAR(32) DEFAULT NULL,
  "aciklama" TEXT,
  "checkin_lat" DECIMAL(10,7) NULL DEFAULT NULL,
  "checkin_lon" DECIMAL(10,7) NULL DEFAULT NULL,
  "checkin_at" TEXT NULL DEFAULT NULL,
  "checkin_accuracy" DECIMAL(8,1) NULL DEFAULT NULL,
  "arac" INTEGER DEFAULT NULL,
  "brans" VARCHAR(255) DEFAULT NULL,
  "kons_istekler" VARCHAR(512) DEFAULT NULL,
  "kons_brans_istek" TEXT DEFAULT NULL,
  "esys_izlem_ref" VARCHAR(64) DEFAULT NULL,
  "esys_konsultasyon_ref" VARCHAR(64) DEFAULT NULL,
  "usbs_bildirim_ref" VARCHAR(64) NULL DEFAULT NULL,
  "usbs_bildirim_durum" VARCHAR(16) NOT NULL DEFAULT '',
  "usbs_bildirim_at" TEXT NULL DEFAULT NULL,
  "erecete_ref" VARCHAR(64) NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_izlem_tc_yapildi_tarih" ON "esh_izlemler" ("hastatckimlik", "yapildimi", "izlemtarihi");
CREATE INDEX IF NOT EXISTS "idx_izlem_tarih_yapildi" ON "esh_izlemler" ("izlemtarihi", "yapildimi");
CREATE INDEX IF NOT EXISTS "idx_izlem_tarih_tc" ON "esh_izlemler" ("izlemtarihi", "hastatckimlik");
CREATE INDEX IF NOT EXISTS "idx_izlem_yapildi_tarih_dt" ON "esh_izlemler" ("yapildimi", "izlemtarihi_dt");
CREATE INDEX IF NOT EXISTS "idx_arac" ON "esh_izlemler" ("arac");
CREATE INDEX IF NOT EXISTS "idx_izlemler_kurum" ON "esh_izlemler" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_izlemler_esys_izlem_ref" ON "esh_izlemler" ("esys_izlem_ref");
CREATE INDEX IF NOT EXISTS "idx_izlemler_usbs_bildirim_ref" ON "esh_izlemler" ("usbs_bildirim_ref");
CREATE INDEX IF NOT EXISTS "idx_izlemler_usbs_bildirim_durum" ON "esh_izlemler" ("usbs_bildirim_durum");

CREATE TABLE IF NOT EXISTS "esh_pizlemler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR(11) NOT NULL,
  "planlanantarih" DATE NOT NULL,
  "yapilacak" VARCHAR(255) NOT NULL DEFAULT '',
  "zaman" INTEGER NOT NULL DEFAULT 0,
  "planiyapan" INTEGER DEFAULT NULL,
  "plantarihi" TEXT DEFAULT NULL,
  "oncelik" INTEGER NOT NULL DEFAULT 1,
  "aciklama" TEXT,
  "notlar" TEXT,
  "durum" INTEGER(1) NOT NULL DEFAULT 0,
  "esys_plan_ref" VARCHAR(64) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_plan_zaman_durum" ON "esh_pizlemler" ("planlanantarih", "zaman", "durum");
CREATE INDEX IF NOT EXISTS "idx_tc_durum" ON "esh_pizlemler" ("hastatckimlik", "durum");
CREATE INDEX IF NOT EXISTS "idx_pizlemler_kurum" ON "esh_pizlemler" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_erapor" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR(11) DEFAULT NULL,
  "isim" VARCHAR(128) DEFAULT NULL,
  "soyisim" VARCHAR(128) DEFAULT NULL,
  "ceptel1" VARCHAR(32) DEFAULT NULL,
  "basvurutarihi" DATE DEFAULT NULL,
  "brans" INTEGER DEFAULT NULL,
  "kayitlimi" INTEGER(1) NOT NULL DEFAULT 0,
  "yenilendimi" INTEGER(1) NOT NULL DEFAULT 0,
  "neden" VARCHAR(255) DEFAULT NULL,
  "esys_erapor_ref" VARCHAR(64) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_tc" ON "esh_erapor" ("hastatckimlik");
CREATE INDEX IF NOT EXISTS "idx_erapor_basvuru_id" ON "esh_erapor" ("basvurutarihi", "id");
CREATE INDEX IF NOT EXISTS "idx_erapor_kurum" ON "esh_erapor" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_ekipler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "tarih" DATE NOT NULL,
  "vardiya" VARCHAR(32) DEFAULT NULL,
  "ekip_no" INTEGER DEFAULT NULL,
  "user_ids" VARCHAR(512) DEFAULT NULL,
  "arac_id" INTEGER NULL DEFAULT NULL,
  "baslangic_saati" VARCHAR(32) DEFAULT NULL,
  "kayit_tarihi" TEXT DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_tarih" ON "esh_ekipler" ("tarih");
CREATE INDEX IF NOT EXISTS "idx_ekipler_kurum" ON "esh_ekipler" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_ekipler_arac" ON "esh_ekipler" ("arac_id");

CREATE TABLE IF NOT EXISTS "esh_personel_izin" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "personel_id" INTEGER NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "sebep" VARCHAR(255) DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_izin_personel" ON "esh_personel_izin" ("personel_id");
CREATE INDEX IF NOT EXISTS "idx_izin_tarih" ON "esh_personel_izin" ("baslangic_tarihi", "bitis_tarihi");
CREATE INDEX IF NOT EXISTS "idx_personel_izin_kurum" ON "esh_personel_izin" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_personel_istek" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "personel_id" INTEGER NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "aciklama" VARCHAR(255) DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_istek_personel" ON "esh_personel_istek" ("personel_id");
CREATE INDEX IF NOT EXISTS "idx_istek_tarih" ON "esh_personel_istek" ("baslangic_tarihi", "bitis_tarihi");
CREATE INDEX IF NOT EXISTS "idx_personel_istek_kurum" ON "esh_personel_istek" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_resmi_tatiller" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "aciklama" VARCHAR(255) NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "tatil_tipi" VARCHAR(50) NOT NULL DEFAULT 'resmi_tatil'
);

CREATE INDEX IF NOT EXISTS "idx_tatil_tarih" ON "esh_resmi_tatiller" ("baslangic_tarihi", "bitis_tarihi");

CREATE TABLE IF NOT EXISTS "esh_personel_nobet" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "personel_id" INTEGER NOT NULL,
  "nobet_tarihi" DATE NOT NULL,
  "nobet_tipi" VARCHAR(50) NOT NULL DEFAULT 'normal',
  "durum" INTEGER(1) NOT NULL DEFAULT 1,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "tekil_nobet" UNIQUE ("personel_id", "nobet_tarihi")
);

CREATE INDEX IF NOT EXISTS "idx_nobet_tarih" ON "esh_personel_nobet" ("nobet_tarihi");
CREATE INDEX IF NOT EXISTS "idx_nobet_personel" ON "esh_personel_nobet" ("personel_id");
CREATE INDEX IF NOT EXISTS "idx_personel_nobet_kurum" ON "esh_personel_nobet" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_yara_fotolar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "dosya_adi" VARCHAR(255) NOT NULL,
  "orijinal_ad" VARCHAR(255) DEFAULT NULL,
  "mime" VARCHAR(100) DEFAULT NULL,
  "boyut" INTEGER DEFAULT NULL,
  "aciklama" VARCHAR(255) DEFAULT NULL,
  "yara_bolgesi" VARCHAR(100) DEFAULT NULL,
  "yara_evresi" VARCHAR(50) DEFAULT NULL,
  "cekim_tarihi" TEXT DEFAULT NULL,
  "yukleyen_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_hasta" ON "esh_hasta_yara_fotolar" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_created" ON "esh_hasta_yara_fotolar" ("created_at");
CREATE INDEX IF NOT EXISTS "idx_yara_fotolar_kurum" ON "esh_hasta_yara_fotolar" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_braden" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "duyusal" INTEGER NOT NULL,
  "nem" INTEGER NOT NULL,
  "aktivite" INTEGER NOT NULL,
  "hareket" INTEGER NOT NULL,
  "beslenme" INTEGER NOT NULL,
  "surtunme" INTEGER NOT NULL,
  "toplam_skor" INTEGER NOT NULL,
  "risk_duzeyi" VARCHAR(32) NOT NULL DEFAULT '',
  "notlar" TEXT DEFAULT NULL,
  "kaydeden_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_braden_hasta" ON "esh_hasta_braden" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_braden_tarih" ON "esh_hasta_braden" ("degerlendirme_tarihi");
CREATE INDEX IF NOT EXISTS "idx_braden_kurum" ON "esh_hasta_braden" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_itaki" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "degerlendirme_gerekcesi" INTEGER NOT NULL DEFAULT 1,
  "secimler_json" TEXT NOT NULL,
  "toplam_skor" INTEGER NOT NULL DEFAULT 0,
  "risk_duzeyi" VARCHAR(32) NOT NULL DEFAULT '',
  "notlar" TEXT DEFAULT NULL,
  "kaydeden_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_itaki_hasta" ON "esh_hasta_itaki" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_itaki_tarih" ON "esh_hasta_itaki" ("degerlendirme_tarihi");
CREATE INDEX IF NOT EXISTS "idx_itaki_kurum" ON "esh_hasta_itaki" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_harizmi" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "degerlendirme_gerekcesi" INTEGER NOT NULL DEFAULT 1,
  "secimler_json" TEXT NOT NULL,
  "toplam_skor" INTEGER NOT NULL DEFAULT 0,
  "risk_duzeyi" VARCHAR(32) NOT NULL DEFAULT '',
  "notlar" TEXT DEFAULT NULL,
  "kaydeden_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_harizmi_hasta" ON "esh_hasta_harizmi" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_harizmi_tarih" ON "esh_hasta_harizmi" ("degerlendirme_tarihi");
CREATE INDEX IF NOT EXISTS "idx_harizmi_kurum" ON "esh_hasta_harizmi" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_mna" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "besin_alimi" INTEGER NOT NULL,
  "kilo_kaybi" INTEGER NOT NULL,
  "mobilite" INTEGER NOT NULL,
  "stres_hastalik" INTEGER NOT NULL,
  "noropsikolojik" INTEGER NOT NULL,
  "bmi_olcum_tipi" VARCHAR(20) NOT NULL DEFAULT 'bmi',
  "bmi_skor" INTEGER NOT NULL,
  "toplam_skor" INTEGER NOT NULL,
  "durum_duzeyi" VARCHAR(32) NOT NULL DEFAULT '',
  "notlar" TEXT DEFAULT NULL,
  "kaydeden_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_mna_hasta" ON "esh_hasta_mna" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_mna_tarih" ON "esh_hasta_mna" ("degerlendirme_tarihi");
CREATE INDEX IF NOT EXISTS "idx_mna_kurum" ON "esh_hasta_mna" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_barthel" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "hasta_id" INTEGER NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "barbeslenme" INTEGER NOT NULL DEFAULT 0,
  "barbanyo" INTEGER NOT NULL DEFAULT 0,
  "barbakim" INTEGER NOT NULL DEFAULT 0,
  "bargiyinme" INTEGER NOT NULL DEFAULT 0,
  "barbarsak" INTEGER NOT NULL DEFAULT 0,
  "barmesane" INTEGER NOT NULL DEFAULT 0,
  "bartuvalet" INTEGER NOT NULL DEFAULT 0,
  "bartransfer" INTEGER NOT NULL DEFAULT 0,
  "barmobilite" INTEGER NOT NULL DEFAULT 0,
  "barmerdiven" INTEGER NOT NULL DEFAULT 0,
  "toplam_skor" INTEGER NOT NULL DEFAULT 0,
  "bagimlilik_duzeyi" VARCHAR(32) NOT NULL DEFAULT '',
  "notlar" TEXT DEFAULT NULL,
  "kaydeden_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_barthel_hasta" ON "esh_hasta_barthel" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_barthel_tarih" ON "esh_hasta_barthel" ("degerlendirme_tarihi");
CREATE INDEX IF NOT EXISTS "idx_barthel_kurum" ON "esh_hasta_barthel" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_rota_cache" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "hash" VARCHAR(128) NOT NULL,
  "origin" VARCHAR(512) NOT NULL,
  "destination" VARCHAR(512) NOT NULL,
  "sure" INTEGER NOT NULL DEFAULT 0,
  "mesafe" INTEGER NOT NULL DEFAULT 0,
  "updated_at" TEXT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_hash" UNIQUE ("hash")
);


CREATE TABLE IF NOT EXISTS "esh_kons_randevu" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "randevu_tarihi" DATE NOT NULL,
  "zaman" INTEGER NOT NULL DEFAULT 0,
  "kons_istekler" VARCHAR(512) NOT NULL DEFAULT '',
  "brans_id" INTEGER NOT NULL,
  "hastatckimlik" VARCHAR(11) NOT NULL,
  "notlar" VARCHAR(512) DEFAULT NULL,
  "hasta_geldi" INTEGER NULL DEFAULT NULL,
  "olusturan_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_tarih_brans_hasta" UNIQUE ("randevu_tarihi", "brans_id", "hastatckimlik")
);

CREATE INDEX IF NOT EXISTS "idx_kons_randevu_tarih" ON "esh_kons_randevu" ("randevu_tarihi");
CREATE INDEX IF NOT EXISTS "idx_kons_randevu_hasta" ON "esh_kons_randevu" ("hastatckimlik");
CREATE INDEX IF NOT EXISTS "idx_kons_randevu_brans_tarih" ON "esh_kons_randevu" ("brans_id", "randevu_tarihi");
CREATE INDEX IF NOT EXISTS "idx_kons_randevu_kurum" ON "esh_kons_randevu" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_goruntulu_randevu" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL DEFAULT 1,
  "randevu_tarihi" DATE NOT NULL,
  "zaman" INTEGER NOT NULL DEFAULT 0,
  "kons_istekler" VARCHAR(512) NOT NULL DEFAULT '',
  "brans_id" INTEGER NOT NULL,
  "hastatckimlik" VARCHAR(11) NOT NULL,
  "notlar" VARCHAR(512) DEFAULT NULL,
  "hasta_geldi" INTEGER NULL DEFAULT NULL,
  "video_room_id" VARCHAR(64) NULL DEFAULT NULL,
  "video_started_at" TEXT NULL DEFAULT NULL,
  "video_ended_at" TEXT NULL DEFAULT NULL,
  "visit_id" INTEGER NULL DEFAULT NULL,
  "telehealth_summary" TEXT NULL DEFAULT NULL,
  "olusturan_id" INTEGER DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_gor_tarih_brans_hasta" UNIQUE ("randevu_tarihi", "brans_id", "hastatckimlik")
);

CREATE INDEX IF NOT EXISTS "idx_gor_randevu_tarih" ON "esh_goruntulu_randevu" ("randevu_tarihi");
CREATE INDEX IF NOT EXISTS "idx_gor_randevu_hasta" ON "esh_goruntulu_randevu" ("hastatckimlik");
CREATE INDEX IF NOT EXISTS "idx_gor_randevu_brans_tarih" ON "esh_goruntulu_randevu" ("brans_id", "randevu_tarihi");
CREATE INDEX IF NOT EXISTS "idx_gor_randevu_kurum" ON "esh_goruntulu_randevu" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_gor_video_room" ON "esh_goruntulu_randevu" ("video_room_id");
CREATE INDEX IF NOT EXISTS "idx_gor_visit" ON "esh_goruntulu_randevu" ("visit_id");

CREATE TABLE IF NOT EXISTS "esh_hasta_nakil" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kaynak_hasta_id" INTEGER NOT NULL,
  "kaynak_kurum_id" INTEGER NOT NULL,
  "hedef_kurum_id" INTEGER NULL DEFAULT NULL,
  "hedef_bolge_id" INTEGER NULL DEFAULT NULL,
  "hedef_hasta_id" INTEGER NULL DEFAULT NULL,
  "onceki_nakil_id" INTEGER NULL DEFAULT NULL,
  "orijinal_kaynak_hasta_id" INTEGER NULL DEFAULT NULL,
  "tip" TEXT NOT NULL DEFAULT 'kurum_ici',
  "durum" TEXT NOT NULL DEFAULT 'beklemede',
  "talep_eden_user_id" INTEGER NOT NULL,
  "talep_tarihi" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "onaylayan_user_id" INTEGER NULL DEFAULT NULL,
  "onay_tarihi" TEXT NULL DEFAULT NULL,
  "red_nedeni" VARCHAR(500) NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_hedef_durum" ON "esh_hasta_nakil" ("hedef_kurum_id", "durum");
CREATE INDEX IF NOT EXISTS "idx_hedef_bolge_durum" ON "esh_hasta_nakil" ("hedef_bolge_id", "durum");
CREATE INDEX IF NOT EXISTS "idx_kaynak_hasta" ON "esh_hasta_nakil" ("kaynak_hasta_id");
CREATE INDEX IF NOT EXISTS "idx_kaynak_kurum_durum" ON "esh_hasta_nakil" ("kaynak_kurum_id", "durum");

CREATE TABLE IF NOT EXISTS "esh_sms_sablonlari" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NULL DEFAULT NULL,
  "kod" VARCHAR(64) NOT NULL DEFAULT '',
  "baslik" VARCHAR(255) NOT NULL DEFAULT '',
  "govde" VARCHAR(1600) NOT NULL DEFAULT '',
  "degiskenler_json" TEXT NULL,
  "aktif" INTEGER(1) NOT NULL DEFAULT 1,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_sms_sablon_kurum" ON "esh_sms_sablonlari" ("kurum_id", "aktif");
CREATE INDEX IF NOT EXISTS "idx_sms_sablon_kod" ON "esh_sms_sablonlari" ("kod");

CREATE TABLE IF NOT EXISTS "esh_sms_gonderim" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "olusturan_id" INTEGER NOT NULL,
  "segment_tipi" VARCHAR(32) NOT NULL DEFAULT 'tek_hasta',
  "segment_param_json" TEXT NULL,
  "sablon_id" INTEGER NULL DEFAULT NULL,
  "govde_ozet" VARCHAR(500) NOT NULL DEFAULT '',
  "mesaj_turu" TEXT NOT NULL DEFAULT 'bilgilendirme',
  "durum" TEXT NOT NULL DEFAULT 'beklemede',
  "toplam" INTEGER NOT NULL DEFAULT 0,
  "basarili" INTEGER NOT NULL DEFAULT 0,
  "basarisiz" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_sms_gonderim_kurum" ON "esh_sms_gonderim" ("kurum_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_sms_gonderim_durum" ON "esh_sms_gonderim" ("durum");

CREATE TABLE IF NOT EXISTS "esh_sms_alici" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "gonderim_id" INTEGER NOT NULL,
  "hasta_id" INTEGER NULL DEFAULT NULL,
  "rol" TEXT NOT NULL DEFAULT 'hasta',
  "telefon_norm" VARCHAR(16) NOT NULL DEFAULT '',
  "govde" VARCHAR(1600) NOT NULL DEFAULT '',
  "provider_msg_id" VARCHAR(64) NULL DEFAULT NULL,
  "durum" TEXT NOT NULL DEFAULT 'beklemede',
  "hata_kodu" VARCHAR(32) NULL DEFAULT NULL,
  "hata_mesaj" VARCHAR(255) NULL DEFAULT NULL,
  "gonderim_at" TEXT NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_sms_alici_gonderim" ON "esh_sms_alici" ("gonderim_id");
CREATE INDEX IF NOT EXISTS "idx_sms_alici_durum" ON "esh_sms_alici" ("durum");

CREATE TABLE IF NOT EXISTS "esh_sms_optout" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "telefon_norm" VARCHAR(16) NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "kaynak" TEXT NOT NULL DEFAULT 'manuel',
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uq_sms_optout_tel_kurum" UNIQUE ("telefon_norm", "kurum_id")
);

CREATE INDEX IF NOT EXISTS "idx_sms_optout_kurum" ON "esh_sms_optout" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_stok_malzeme" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "kod" VARCHAR(64) NULL DEFAULT NULL,
  "ad" VARCHAR(255) NOT NULL DEFAULT '',
  "kategori" TEXT NOT NULL DEFAULT 'sarf',
  "birim" TEXT NOT NULL DEFAULT 'adet',
  "min_stok" DECIMAL(12,3) NOT NULL DEFAULT 0,
  "aktif" INTEGER(1) NOT NULL DEFAULT 1,
  "aciklama" VARCHAR(512) NULL DEFAULT NULL,
  "tedarikci_adi" VARCHAR(255) NULL DEFAULT NULL,
  "tedarikci_tel" VARCHAR(32) NULL DEFAULT NULL,
  "birim_fiyat" DECIMAL(12,2) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_stok_malzeme_kurum" ON "esh_stok_malzeme" ("kurum_id", "aktif");
CREATE INDEX IF NOT EXISTS "idx_stok_malzeme_kategori" ON "esh_stok_malzeme" ("kategori");

CREATE TABLE IF NOT EXISTS "esh_stok_mevcut" (
  "kurum_id" INTEGER NOT NULL,
  "malzeme_id" INTEGER NOT NULL,
  "miktar" DECIMAL(12,3) NOT NULL DEFAULT 0,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("kurum_id", "malzeme_id")
);

CREATE INDEX IF NOT EXISTS "idx_stok_mevcut_malzeme" ON "esh_stok_mevcut" ("malzeme_id");

CREATE TABLE IF NOT EXISTS "esh_stok_hareket" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "malzeme_id" INTEGER NOT NULL,
  "hareket_tipi" TEXT NOT NULL,
  "miktar" DECIMAL(12,3) NOT NULL,
  "hareket_tarihi" DATE NOT NULL,
  "hasta_id" INTEGER NULL DEFAULT NULL,
  "ekip_id" INTEGER NULL DEFAULT NULL,
  "kullanici_id" INTEGER NOT NULL,
  "aciklama" VARCHAR(512) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_stok_hareket_kurum_tarih" ON "esh_stok_hareket" ("kurum_id", "hareket_tarihi");
CREATE INDEX IF NOT EXISTS "idx_stok_hareket_malzeme" ON "esh_stok_hareket" ("malzeme_id");
CREATE INDEX IF NOT EXISTS "idx_stok_hareket_hasta" ON "esh_stok_hareket" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_stok_hareket_ekip" ON "esh_stok_hareket" ("ekip_id");

CREATE TABLE IF NOT EXISTS "esh_stok_uyari_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "malzeme_id" INTEGER NOT NULL,
  "uyari_tarihi" DATE NOT NULL,
  "sms_gonderildi" INTEGER(1) NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uq_stok_uyari_gun" UNIQUE ("kurum_id", "malzeme_id", "uyari_tarihi")
);

CREATE INDEX IF NOT EXISTS "idx_stok_uyari_kurum" ON "esh_stok_uyari_log" ("kurum_id", "uyari_tarihi");

CREATE TABLE IF NOT EXISTS "esh_stok_parti" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "malzeme_id" INTEGER NOT NULL,
  "lot_no" VARCHAR(64) NULL DEFAULT NULL,
  "skt" DATE NULL DEFAULT NULL,
  "miktar" DECIMAL(12,3) NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_stok_parti_malzeme" ON "esh_stok_parti" ("kurum_id", "malzeme_id", "skt");

CREATE TABLE IF NOT EXISTS "esh_mesaj_konusmalar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "tip" TEXT NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "hasta_id" INTEGER NULL DEFAULT NULL,
  "dm_kucuk_id" INTEGER NULL DEFAULT NULL,
  "dm_buyuk_id" INTEGER NULL DEFAULT NULL,
  "baslik" VARCHAR(255) NOT NULL DEFAULT '',
  "olusturan_id" INTEGER NULL DEFAULT NULL,
  "son_mesaj_at" TEXT NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uq_dm_pair" UNIQUE ("dm_kucuk_id", "dm_buyuk_id"),
  CONSTRAINT "uq_hasta_thread" UNIQUE ("hasta_id")
);

CREATE INDEX IF NOT EXISTS "idx_kurum_son" ON "esh_mesaj_konusmalar" ("kurum_id", "son_mesaj_at");
CREATE INDEX IF NOT EXISTS "idx_tip" ON "esh_mesaj_konusmalar" ("tip");

CREATE TABLE IF NOT EXISTS "esh_mesaj_konusma_uyeler" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "konusma_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "son_okunan_mesaj_id" INTEGER NOT NULL DEFAULT 0,
  "katilim_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "silindi_at" TEXT NULL DEFAULT NULL,
  CONSTRAINT "uq_konusma_user" UNIQUE ("konusma_id", "user_id")
);

CREATE INDEX IF NOT EXISTS "idx_user" ON "esh_mesaj_konusma_uyeler" ("user_id");
CREATE INDEX IF NOT EXISTS "idx_user_silindi" ON "esh_mesaj_konusma_uyeler" ("user_id", "silindi_at");

CREATE TABLE IF NOT EXISTS "esh_mesajlar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "konusma_id" INTEGER NOT NULL,
  "gonderen_id" INTEGER NULL DEFAULT NULL,
  "gonderen_tip" TEXT NOT NULL DEFAULT 'user',
  "govde" TEXT NOT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_konusma_id" ON "esh_mesajlar" ("konusma_id", "id");

CREATE TABLE IF NOT EXISTS "esh_eimza_challenges" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "nonce_hash" CHAR(64) NOT NULL,
  "issued_at" TEXT NOT NULL,
  "expires_at" TEXT NOT NULL,
  "consumed_at" TEXT NULL DEFAULT NULL,
  "ip_address" VARCHAR(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR(255) NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_eimza_challenge_expires" ON "esh_eimza_challenges" ("expires_at");
CREATE INDEX IF NOT EXISTS "idx_eimza_challenge_user" ON "esh_eimza_challenges" ("user_id");

CREATE TABLE IF NOT EXISTS "esh_eimza_login_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "tc_kimlikno" VARCHAR(16) NOT NULL,
  "success" INTEGER(1) NOT NULL DEFAULT 0,
  "reason" VARCHAR(128) NOT NULL,
  "cert_serial" VARCHAR(128) NULL DEFAULT NULL,
  "cert_fingerprint" VARCHAR(128) NULL DEFAULT NULL,
  "ip_address" VARCHAR(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR(255) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "idx_eimza_logs_user_created" ON "esh_eimza_login_logs" ("user_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_eimza_logs_tc_created" ON "esh_eimza_login_logs" ("tc_kimlikno", "created_at");
CREATE INDEX IF NOT EXISTS "idx_eimza_logs_success_created" ON "esh_eimza_login_logs" ("success", "created_at");

CREATE TABLE IF NOT EXISTS "esh_audit_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NULL DEFAULT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "action" VARCHAR(64) NOT NULL,
  "entity_type" VARCHAR(32) NOT NULL DEFAULT '',
  "entity_id" INTEGER NULL DEFAULT NULL,
  "entity_ref" VARCHAR(64) NULL DEFAULT NULL,
  "ip_address" VARCHAR(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR(255) NULL DEFAULT NULL,
  "request_uri" VARCHAR(512) NULL DEFAULT NULL,
  "context_json" TEXT NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_audit_user_created" ON "esh_audit_log" ("user_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_audit_action_created" ON "esh_audit_log" ("action", "created_at");
CREATE INDEX IF NOT EXISTS "idx_audit_entity" ON "esh_audit_log" ("entity_type", "entity_id");
CREATE INDEX IF NOT EXISTS "idx_audit_kurum_created" ON "esh_audit_log" ("kurum_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_audit_entity_ref" ON "esh_audit_log" ("entity_ref", "created_at");
CREATE INDEX IF NOT EXISTS "idx_audit_created" ON "esh_audit_log" ("created_at");

CREATE TABLE IF NOT EXISTS "esh_esys_sync_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NULL DEFAULT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "direction" VARCHAR(24) NOT NULL,
  "status" VARCHAR(16) NOT NULL,
  "file_name" VARCHAR(255) NULL DEFAULT NULL,
  "stats_json" TEXT NULL DEFAULT NULL,
  "error_message" VARCHAR(512) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_esys_sync_created" ON "esh_esys_sync_log" ("created_at");
CREATE INDEX IF NOT EXISTS "idx_esys_sync_kurum_created" ON "esh_esys_sync_log" ("kurum_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_esys_sync_direction" ON "esh_esys_sync_log" ("direction", "created_at");

CREATE TABLE IF NOT EXISTS "esh_api_tokens" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "kurum_id" INTEGER NULL DEFAULT NULL,
  "label" VARCHAR(128) NOT NULL DEFAULT '',
  "token_prefix" VARCHAR(16) NOT NULL,
  "token_hash" CHAR(64) NOT NULL,
  "scopes" VARCHAR(255) NOT NULL DEFAULT 'read',
  "expires_at" TEXT NULL DEFAULT NULL,
  "last_used_at" TEXT NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "revoked_at" TEXT NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_api_token_prefix" ON "esh_api_tokens" ("token_prefix");
CREATE INDEX IF NOT EXISTS "idx_api_token_user" ON "esh_api_tokens" ("user_id");
CREATE INDEX IF NOT EXISTS "idx_api_token_kurum" ON "esh_api_tokens" ("kurum_id");

CREATE TABLE IF NOT EXISTS "esh_federation_sync_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "direction" VARCHAR(32) NOT NULL,
  "status" VARCHAR(16) NOT NULL,
  "file_name" VARCHAR(255) NULL DEFAULT NULL,
  "stats_json" TEXT NULL DEFAULT NULL,
  "error_message" VARCHAR(512) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_fed_sync_created" ON "esh_federation_sync_log" ("created_at");
CREATE INDEX IF NOT EXISTS "idx_fed_sync_direction" ON "esh_federation_sync_log" ("direction", "created_at");

CREATE TABLE IF NOT EXISTS "esh_usbs_sync_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kurum_id" INTEGER NULL DEFAULT NULL,
  "user_id" INTEGER NULL DEFAULT NULL,
  "direction" VARCHAR(24) NOT NULL,
  "status" VARCHAR(16) NOT NULL,
  "file_name" VARCHAR(255) NULL DEFAULT NULL,
  "stats_json" TEXT NULL DEFAULT NULL,
  "error_message" VARCHAR(512) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_usbs_sync_created" ON "esh_usbs_sync_log" ("created_at");
CREATE INDEX IF NOT EXISTS "idx_usbs_sync_kurum_created" ON "esh_usbs_sync_log" ("kurum_id", "created_at");
CREATE INDEX IF NOT EXISTS "idx_usbs_sync_direction" ON "esh_usbs_sync_log" ("direction", "created_at");

CREATE TABLE IF NOT EXISTS "esh_portal_appointment_requests" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "hasta_id" INTEGER NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "uhds_id" INTEGER NOT NULL,
  "talep_tarihi" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "mevcut_tarih" DATE NOT NULL,
  "talep_tarih" DATE NOT NULL,
  "talep_zaman" INTEGER NULL DEFAULT NULL,
  "neden" VARCHAR(500) NOT NULL,
  "durum" VARCHAR(20) NOT NULL DEFAULT 'queued',
  "durum_notu" VARCHAR(500) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "idx_portal_req_hasta" ON "esh_portal_appointment_requests" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_portal_req_kurum" ON "esh_portal_appointment_requests" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_portal_req_durum" ON "esh_portal_appointment_requests" ("durum");
CREATE INDEX IF NOT EXISTS "idx_portal_req_uhds" ON "esh_portal_appointment_requests" ("uhds_id");

CREATE TABLE IF NOT EXISTS "esh_cds_ack" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "hasta_id" INTEGER NOT NULL,
  "kurum_id" INTEGER NOT NULL,
  "alert_code" VARCHAR(80) NOT NULL,
  "ack_by_user_id" INTEGER NULL DEFAULT NULL,
  "ack_note" VARCHAR(500) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_cds_ack_hasta" ON "esh_cds_ack" ("hasta_id");
CREATE INDEX IF NOT EXISTS "idx_cds_ack_kurum" ON "esh_cds_ack" ("kurum_id");
CREATE INDEX IF NOT EXISTS "idx_cds_ack_code" ON "esh_cds_ack" ("alert_code");

CREATE TABLE IF NOT EXISTS "esh_rehber_import_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "source_site" VARCHAR(32) NOT NULL,
  "started_at" TEXT NOT NULL,
  "finished_at" TEXT NULL DEFAULT NULL,
  "etken_count" INTEGER NOT NULL DEFAULT 0,
  "ilac_count" INTEGER NOT NULL DEFAULT 0,
  "error_summary" TEXT NULL,
  "options_json" TEXT NULL
);

CREATE INDEX IF NOT EXISTS "idx_rehber_import_finished" ON "esh_rehber_import_log" ("finished_at");

CREATE TABLE IF NOT EXISTS "esh_rehber_etken" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "ad" VARCHAR(512) NOT NULL,
  "ad_normalized" VARCHAR(512) NOT NULL,
  "source_site" VARCHAR(32) NOT NULL,
  "source_key" VARCHAR(128) NOT NULL,
  "scraped_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_rehber_etken_site_key" UNIQUE ("source_site", "source_key")
);

CREATE INDEX IF NOT EXISTS "idx_rehber_etken_norm" ON "esh_rehber_etken" ("ad_normalized");

CREATE TABLE IF NOT EXISTS "esh_rehber_ilac" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "etken_id" INTEGER NOT NULL,
  "ad" VARCHAR(512) NOT NULL,
  "firma" VARCHAR(255) NULL,
  "recete_turu" VARCHAR(128) NULL,
  "source_site" VARCHAR(32) NOT NULL,
  "source_url" VARCHAR(1024) NULL,
  "source_key" VARCHAR(256) NOT NULL,
  "scraped_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_rehber_ilac_site_key" UNIQUE ("source_site", "source_key")
);

CREATE INDEX IF NOT EXISTS "idx_rehber_ilac_etken" ON "esh_rehber_ilac" ("etken_id");

CREATE TABLE IF NOT EXISTS "esh_unvanlar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "kod" VARCHAR(64) NOT NULL,
  "ad" VARCHAR(128) NOT NULL,
  "kategori" VARCHAR(32) NOT NULL DEFAULT 'diger',
  "izin_sablonu" VARCHAR(64) NOT NULL DEFAULT 'personel',
  "sort_order" INTEGER NOT NULL DEFAULT 100,
  "aktif" INTEGER(1) NOT NULL DEFAULT 1,
  "is_system" INTEGER(1) NOT NULL DEFAULT 0,
  "mevzuat_notu" VARCHAR(512) NULL DEFAULT NULL,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_unvan_kod" UNIQUE ("kod")
);

CREATE INDEX IF NOT EXISTS "idx_unvan_aktif_sort" ON "esh_unvanlar" ("aktif", "sort_order");

CREATE TABLE IF NOT EXISTS "esh_roles" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "slug" VARCHAR(64) NOT NULL,
  "unvan_code" VARCHAR(64) NULL DEFAULT NULL,
  "name" VARCHAR(128) NOT NULL,
  "description" VARCHAR(512) NULL DEFAULT NULL,
  "is_system" INTEGER(1) NOT NULL DEFAULT 0,
  "sort_order" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uk_roles_slug" UNIQUE ("slug"),
  CONSTRAINT "uk_roles_unvan_code" UNIQUE ("unvan_code")
);


CREATE TABLE IF NOT EXISTS "esh_permissions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "module_key" VARCHAR(64) NOT NULL,
  "crud" VARCHAR(32) NOT NULL,
  "slug" VARCHAR(128) NOT NULL,
  "label" VARCHAR(255) NOT NULL DEFAULT '',
  "description" VARCHAR(512) NULL DEFAULT NULL,
  CONSTRAINT "uk_permissions_slug" UNIQUE ("slug")
);

CREATE INDEX IF NOT EXISTS "idx_permissions_module" ON "esh_permissions" ("module_key");

CREATE TABLE IF NOT EXISTS "esh_role_permissions" (
  "role_id" INTEGER NOT NULL,
  "permission_id" INTEGER NOT NULL,
  PRIMARY KEY ("role_id", "permission_id")
);

CREATE INDEX IF NOT EXISTS "idx_role_permissions_permission" ON "esh_role_permissions" ("permission_id");

CREATE TABLE IF NOT EXISTS "esh_user_roles" (
  "user_id" INTEGER NOT NULL,
  "role_id" INTEGER NOT NULL,
  PRIMARY KEY ("user_id")
);

CREATE INDEX IF NOT EXISTS "idx_user_roles_role" ON "esh_user_roles" ("role_id");

PRAGMA foreign_keys = ON;

