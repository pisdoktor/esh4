-- ESH Panel — Oracle şeması (schema.sql'den üretildi)
-- Üretim: php tools/build_schema_dialect.php oci
-- Kurulum: db_driver=oci
-- =============================================================================

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_ilac" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_etken" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_import_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_eimza_login_logs" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_eimza_challenges" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesajlar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesaj_konusma_uyeler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesaj_konusmalar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_nakil" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rota_cache" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_ekipler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_ilaclar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_yara_fotolar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_erapor" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_pizlemler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_izlemler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_istekler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_araclar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hastalar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_personel_nobet" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_personel_istek" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_personel_izin" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_resmi_tatiller" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_users" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hastailacrapor" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hastaliklar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_islemler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_branslar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_guvence" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hastalikcat" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurumlar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_adrestablosu" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

CREATE TABLE "esh_kurumlar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "ad" VARCHAR2(255) NOT NULL,
  "kod" VARCHAR2(64) NOT NULL,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "logo" VARCHAR2(255) DEFAULT NULL,
  "adres" CLOB DEFAULT NULL,
  "telefon" VARCHAR2(64) DEFAULT NULL,
  "ayarlar_json" CLOB DEFAULT NULL,
  "olusturma_tarihi" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_kurum_kod" UNIQUE ("kod")
);

CREATE INDEX "idx_kurum_aktif" ON "esh_kurumlar" ("aktif");

INSERT INTO "esh_kurumlar" ("id", "ad", "kod", "aktif") VALUES (1, 'Varsayılan Kurum', 'varsayilan', 1);

CREATE TABLE "esh_adrestablosu" (
  "id" VARCHAR2(64) NOT NULL,
  "adi" VARCHAR2(255) NOT NULL DEFAULT '',
  "ust_id" VARCHAR2(64) DEFAULT NULL,
  "tip" VARCHAR2(20) NOT NULL DEFAULT 'ilce',
  "coords" VARCHAR2(255) DEFAULT NULL,
  "has_coords" NUMBER(3) NOT NULL DEFAULT 0,
  PRIMARY KEY ("ust_id", "id")
);

CREATE INDEX "idx_adres_id" ON "esh_adrestablosu" ("id");
CREATE INDEX "idx_tip_ust" ON "esh_adrestablosu" ("tip", "ust_id");
CREATE INDEX "idx_tip_has_coords" ON "esh_adrestablosu" ("tip", "has_coords");

CREATE TABLE "esh_mahalle_plan" (
  "kurum_id" NUMBER(10) NOT NULL,
  "mahalle_id" VARCHAR2(64) NOT NULL,
  "bolge" NUMBER(10) NOT NULL DEFAULT 0,
  "gun" VARCHAR2(64) DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "mahalle_id")
);

CREATE INDEX "idx_mp_mahalle" ON "esh_mahalle_plan" ("mahalle_id");

CREATE TABLE "esh_kurum_adres" (
  "kurum_id" NUMBER(10) NOT NULL,
  "adres_id" VARCHAR2(64) NOT NULL,
  "tip" VARCHAR2(20) NOT NULL,
  PRIMARY KEY ("kurum_id", "adres_id")
);

CREATE INDEX "idx_ka_kurum_tip" ON "esh_kurum_adres" ("kurum_id", "tip");
CREATE INDEX "idx_ka_adres" ON "esh_kurum_adres" ("adres_id");

CREATE TABLE "esh_kurum_brans" (
  "kurum_id" NUMBER(10) NOT NULL,
  "brans_id" NUMBER(10) NOT NULL,
  "hasta_kotasi" NUMBER(10) NULL DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "brans_id")
);

CREATE INDEX "idx_kb_brans" ON "esh_kurum_brans" ("brans_id");

CREATE TABLE "esh_kurum_istek" (
  "kurum_id" NUMBER(10) NOT NULL,
  "istek_id" NUMBER(10) NOT NULL,
  PRIMARY KEY ("kurum_id", "istek_id")
);

CREATE INDEX "idx_ki_istek" ON "esh_kurum_istek" ("istek_id");

CREATE TABLE "esh_kurum_islem" (
  "kurum_id" NUMBER(10) NOT NULL,
  "islem_id" NUMBER(10) NOT NULL,
  PRIMARY KEY ("kurum_id", "islem_id")
);

CREATE INDEX "idx_km_islem" ON "esh_kurum_islem" ("islem_id");

CREATE TABLE "esh_kurum_hastalik" (
  "kurum_id" NUMBER(10) NOT NULL,
  "hastalik_id" NUMBER(10) NOT NULL,
  PRIMARY KEY ("kurum_id", "hastalik_id")
);

CREATE INDEX "idx_kh_hastalik" ON "esh_kurum_hastalik" ("hastalik_id");

CREATE TABLE "esh_hastalikcat" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "name" VARCHAR2(255) NOT NULL,
  "icd_range" VARCHAR2(64) DEFAULT NULL,
  PRIMARY KEY ("id")
);


CREATE TABLE "esh_branslar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 0,
  "bransadi" VARCHAR2(255) NOT NULL,
  "hasta_kotasi" NUMBER(10) NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_branslar_kurum" ON "esh_branslar" ("kurum_id");

CREATE TABLE "esh_guvence" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "guvenceadi" VARCHAR2(255) NOT NULL,
  PRIMARY KEY ("id")
);


CREATE TABLE "esh_islemler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 0,
  "islemadi" VARCHAR2(255) NOT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_islemler_kurum" ON "esh_islemler" ("kurum_id");

CREATE TABLE "esh_araclar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "plaka" VARCHAR2(32) NOT NULL DEFAULT '',
  "arac_bilgisi" VARCHAR2(255) NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_plaka" ON "esh_araclar" ("plaka");
CREATE INDEX "idx_araclar_kurum" ON "esh_araclar" ("kurum_id");

CREATE TABLE "esh_istekler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 0,
  "istek_adi" VARCHAR2(255) NOT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_istekler_kurum" ON "esh_istekler" ("kurum_id");

CREATE TABLE "esh_hastaliklar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 0,
  "cat" NUMBER(10) NOT NULL DEFAULT 0,
  "hastalikadi" VARCHAR2(255) NOT NULL,
  "icd" VARCHAR2(32) DEFAULT NULL,
  "parent_icd" VARCHAR2(32) DEFAULT NULL,
  "seviye" NUMBER(3) DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_hastaliklar_kurum_icd" UNIQUE ("kurum_id", "icd")
);

CREATE INDEX "idx_cat" ON "esh_hastaliklar" ("cat");
CREATE INDEX "idx_hastaliklar_kurum" ON "esh_hastaliklar" ("kurum_id");
CREATE INDEX "idx_hastaliklar_icd" ON "esh_hastaliklar" ("icd");
CREATE INDEX "idx_hastaliklar_parent" ON "esh_hastaliklar" ("parent_icd");

CREATE TABLE "esh_hastailacrapor" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR2(11) NOT NULL,
  "hastalikid" NUMBER(10) NOT NULL,
  "rapor" NUMBER(3) NOT NULL DEFAULT 0,
  "bitistarihi" DATE DEFAULT NULL,
  "brans" VARCHAR2(512) NOT NULL DEFAULT '',
  "raporyeri" NUMBER(3) NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);

CREATE INDEX "uk_tc_hastalik" ON "esh_hastailacrapor" ("hastatckimlik", "hastalikid");
CREATE INDEX "idx_tc" ON "esh_hastailacrapor" ("hastatckimlik");
CREATE INDEX "idx_hastalik" ON "esh_hastailacrapor" ("hastalikid");
CREATE INDEX "idx_hastailacrapor_kurum" ON "esh_hastailacrapor" ("kurum_id");

CREATE TABLE "esh_hasta_ilaclar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "ilac_adi" VARCHAR2(255) NOT NULL,
  "etken_madde" VARCHAR2(512) NULL,
  "recete_turu" VARCHAR2(128) NULL,
  "not" CLOB NULL,
  "hastalikid" NUMBER(10) NULL,
  "sira" NUMBER(5) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_hasta" ON "esh_hasta_ilaclar" ("hasta_id");
CREATE INDEX "idx_hasta_ilaclar_kurum" ON "esh_hasta_ilaclar" ("kurum_id");

CREATE TABLE "esh_users" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "username" VARCHAR2(64) NOT NULL,
  "password" VARCHAR2(255) NOT NULL,
  "name" VARCHAR2(128) NOT NULL DEFAULT '',
  "tckimlikno" VARCHAR2(11) DEFAULT NULL,
  "email" VARCHAR2(255) DEFAULT NULL,
  "image" VARCHAR2(255) DEFAULT NULL,
  "nowvisit" TIMESTAMP DEFAULT NULL,
  "lastvisit" TIMESTAMP DEFAULT NULL,
  "registerDate" TIMESTAMP DEFAULT NULL,
  "activated" NUMBER(3) NOT NULL DEFAULT 0,
  "activation" VARCHAR2(64) DEFAULT NULL,
  "isadmin" NUMBER(3) NOT NULL DEFAULT 0,
  "kurum_id" NUMBER(10) NULL DEFAULT 1,
  "unvan" VARCHAR2(64) DEFAULT NULL,
  "ui_theme" VARCHAR2(64) DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_username" UNIQUE ("username")
);

CREATE INDEX "idx_users_activated_name" ON "esh_users" ("activated", "name");
CREATE INDEX "idx_users_kurum" ON "esh_users" ("kurum_id");

CREATE TABLE "esh_hastalar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "tckimlik" VARCHAR2(11) NOT NULL,
  "isim" VARCHAR2(128) DEFAULT NULL,
  "soyisim" VARCHAR2(128) DEFAULT NULL,
  "anneAdi" VARCHAR2(128) DEFAULT NULL,
  "babaAdi" VARCHAR2(128) DEFAULT NULL,
  "dogumtarihi" DATE DEFAULT NULL,
  "cinsiyet" VARCHAR2(2) DEFAULT NULL,
  "kilo" NUMBER(6,2) DEFAULT NULL,
  "boy" NUMBER(6,2) DEFAULT NULL,
  "kayittarihi" DATE DEFAULT NULL,
  "ceptel1" VARCHAR2(32) DEFAULT NULL,
  "ceptel2" VARCHAR2(32) DEFAULT NULL,
  "bakimveren_ad" VARCHAR2(128) DEFAULT NULL,
  "bakimveren_tel" VARCHAR2(32) DEFAULT NULL,
  "bakimveren_yakinlik" VARCHAR2(64) DEFAULT NULL,
  "alerji" CLOB,
  "acil_not" CLOB,
  "guvence" NUMBER(10) DEFAULT NULL,
  "yupasno" VARCHAR2(64) DEFAULT NULL,
  "ailehekimi" VARCHAR2(128) DEFAULT NULL,
  "ailehekimitel" VARCHAR2(32) DEFAULT NULL,
  "kangrubu" VARCHAR2(8) DEFAULT NULL,
  "ilce" VARCHAR2(64) DEFAULT NULL,
  "mahalle" VARCHAR2(64) DEFAULT NULL,
  "sokak" VARCHAR2(64) DEFAULT NULL,
  "kapino" VARCHAR2(64) DEFAULT NULL,
  "adres_aciklama" CLOB,
  "diger_adres" CLOB,
  "coords" VARCHAR2(255) DEFAULT NULL,
  "bagimlilik" VARCHAR2(10) DEFAULT NULL,
  "barbeslenme" NUMBER(3) DEFAULT NULL,
  "barbanyo" NUMBER(3) DEFAULT NULL,
  "barbakim" NUMBER(3) DEFAULT NULL,
  "bargiyinme" NUMBER(3) DEFAULT NULL,
  "barbarsak" NUMBER(3) DEFAULT NULL,
  "barmesane" NUMBER(3) DEFAULT NULL,
  "bartuvalet" NUMBER(3) DEFAULT NULL,
  "bartransfer" NUMBER(3) DEFAULT NULL,
  "barmobilite" NUMBER(3) DEFAULT NULL,
  "barmerdiven" NUMBER(3) DEFAULT NULL,
  "pasif" VARCHAR2(10) NOT NULL DEFAULT '0',
  "pasiftarihi" DATE DEFAULT NULL,
  "pasifnedeni" VARCHAR2(16) DEFAULT NULL,
  "gecici" NUMBER(3) NOT NULL DEFAULT 0,
  "ng" NUMBER(3) NOT NULL DEFAULT 0,
  "peg" NUMBER(3) NOT NULL DEFAULT 0,
  "port" NUMBER(3) NOT NULL DEFAULT 0,
  "o2bagimli" NUMBER(3) NOT NULL DEFAULT 0,
  "ventilator" NUMBER(3) NOT NULL DEFAULT 0,
  "kolostomi" NUMBER(3) NOT NULL DEFAULT 0,
  "trakeostomi" NUMBER(3) NOT NULL DEFAULT 0,
  "cpap" NUMBER(3) NOT NULL DEFAULT 0,
  "aspirasyon" NUMBER(3) NOT NULL DEFAULT 0,
  "ileostomi" NUMBER(3) NOT NULL DEFAULT 0,
  "urostomi" NUMBER(3) NOT NULL DEFAULT 0,
  "picc" NUMBER(3) NOT NULL DEFAULT 0,
  "dren" NUMBER(3) NOT NULL DEFAULT 0,
  "diyaliz" NUMBER(3) NOT NULL DEFAULT 0,
  "basiyarasi" NUMBER(3) NOT NULL DEFAULT 0,
  "ivtedavi" NUMBER(3) NOT NULL DEFAULT 0,
  "izolasyon" NUMBER(3) NOT NULL DEFAULT 0,
  "sonda" NUMBER(3) NOT NULL DEFAULT 0,
  "sondatarihi" DATE DEFAULT NULL,
  "pansuman" NUMBER(3) NOT NULL DEFAULT 0,
  "pgunleri" VARCHAR2(64) DEFAULT NULL,
  "pzaman" VARCHAR2(32) DEFAULT NULL,
  "mama" NUMBER(3) NOT NULL DEFAULT 0,
  "mamacesit" VARCHAR2(128) DEFAULT NULL,
  "mamaraporbitis" DATE DEFAULT NULL,
  "mamaraporyeri" VARCHAR2(255) DEFAULT NULL,
  "bez" NUMBER(3) NOT NULL DEFAULT 0,
  "bezrapor" NUMBER(3) NOT NULL DEFAULT 0,
  "bezraporbitis" DATE DEFAULT NULL,
  "yatak" NUMBER(3) NOT NULL DEFAULT 0,
  "hastaliklar" CLOB,
  "erapor" VARCHAR2(255) DEFAULT NULL,
  "randevutarihi" DATE DEFAULT NULL,
  "zaman" NUMBER(3) DEFAULT NULL,
  "notes" CLOB,
  "profil_foto" VARCHAR2(255) DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_tckimlik" UNIQUE ("tckimlik")
);

CREATE INDEX "idx_pasif" ON "esh_hastalar" ("pasif");
CREATE INDEX "idx_mahalle" ON "esh_hastalar" ("mahalle");
CREATE INDEX "idx_ilce" ON "esh_hastalar" ("ilce");
CREATE INDEX "idx_randevu_pasif_zaman" ON "esh_hastalar" ("randevutarihi", "pasif", "zaman");
CREATE INDEX "idx_pansuman_slot" ON "esh_hastalar" ("pasif", "pansuman", "pzaman");
CREATE INDEX "idx_pasif_isim" ON "esh_hastalar" ("pasif", "isim");
CREATE INDEX "idx_hastalar_kurum" ON "esh_hastalar" ("kurum_id");

CREATE TABLE "esh_izlemler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR2(11) NOT NULL,
  "izlemtarihi" DATE NOT NULL,
  "izlemtarihi_dt" DATE DEFAULT NULL,
  "yapilan" CLOB,
  "yapildimi" NUMBER(3) NOT NULL DEFAULT 0,
  "neden" VARCHAR2(255) DEFAULT NULL,
  "izlemiyapan" VARCHAR2(255) DEFAULT NULL,
  "zaman" VARCHAR2(32) DEFAULT NULL,
  "aciklama" CLOB,
  "arac" NUMBER(10) DEFAULT NULL,
  "brans" VARCHAR2(255) DEFAULT NULL,
  "kons_istekler" VARCHAR2(512) DEFAULT NULL,
  "kons_brans_istek" CLOB DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_izlem_tc_yapildi_tarih" ON "esh_izlemler" ("hastatckimlik", "yapildimi", "izlemtarihi");
CREATE INDEX "idx_izlem_tarih_yapildi" ON "esh_izlemler" ("izlemtarihi", "yapildimi");
CREATE INDEX "idx_izlem_tarih_tc" ON "esh_izlemler" ("izlemtarihi", "hastatckimlik");
CREATE INDEX "idx_izlem_yapildi_tarih_dt" ON "esh_izlemler" ("yapildimi", "izlemtarihi_dt");
CREATE INDEX "idx_arac" ON "esh_izlemler" ("arac");
CREATE INDEX "idx_izlemler_kurum" ON "esh_izlemler" ("kurum_id");

CREATE TABLE "esh_pizlemler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR2(11) NOT NULL,
  "planlanantarih" DATE NOT NULL,
  "yapilacak" VARCHAR2(255) NOT NULL DEFAULT '',
  "zaman" NUMBER(3) NOT NULL DEFAULT 0,
  "planiyapan" NUMBER(10) DEFAULT NULL,
  "plantarihi" TIMESTAMP DEFAULT NULL,
  "oncelik" NUMBER(3) NOT NULL DEFAULT 1,
  "aciklama" CLOB,
  "notlar" CLOB,
  "durum" NUMBER(3) NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_plan_zaman_durum" ON "esh_pizlemler" ("planlanantarih", "zaman", "durum");
CREATE INDEX "idx_tc_durum" ON "esh_pizlemler" ("hastatckimlik", "durum");
CREATE INDEX "idx_pizlemler_kurum" ON "esh_pizlemler" ("kurum_id");

CREATE TABLE "esh_erapor" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hastatckimlik" VARCHAR2(11) DEFAULT NULL,
  "isim" VARCHAR2(128) DEFAULT NULL,
  "soyisim" VARCHAR2(128) DEFAULT NULL,
  "ceptel1" VARCHAR2(32) DEFAULT NULL,
  "basvurutarihi" DATE DEFAULT NULL,
  "brans" NUMBER(10) DEFAULT NULL,
  "kayitlimi" NUMBER(3) NOT NULL DEFAULT 0,
  "yenilendimi" NUMBER(3) NOT NULL DEFAULT 0,
  "neden" VARCHAR2(255) DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_tc" ON "esh_erapor" ("hastatckimlik");
CREATE INDEX "idx_erapor_basvuru_id" ON "esh_erapor" ("basvurutarihi", "id");
CREATE INDEX "idx_erapor_kurum" ON "esh_erapor" ("kurum_id");

CREATE TABLE "esh_ekipler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "tarih" DATE NOT NULL,
  "vardiya" VARCHAR2(32) DEFAULT NULL,
  "ekip_no" NUMBER(10) DEFAULT NULL,
  "user_ids" VARCHAR2(512) DEFAULT NULL,
  "baslangic_saati" VARCHAR2(32) DEFAULT NULL,
  "kayit_tarihi" TIMESTAMP DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_tarih" ON "esh_ekipler" ("tarih");
CREATE INDEX "idx_ekipler_kurum" ON "esh_ekipler" ("kurum_id");

CREATE TABLE "esh_personel_izin" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "personel_id" NUMBER(10) NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "sebep" VARCHAR2(255) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_izin_personel" ON "esh_personel_izin" ("personel_id");
CREATE INDEX "idx_izin_tarih" ON "esh_personel_izin" ("baslangic_tarihi", "bitis_tarihi");
CREATE INDEX "idx_personel_izin_kurum" ON "esh_personel_izin" ("kurum_id");

CREATE TABLE "esh_personel_istek" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "personel_id" NUMBER(10) NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "aciklama" VARCHAR2(255) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_istek_personel" ON "esh_personel_istek" ("personel_id");
CREATE INDEX "idx_istek_tarih" ON "esh_personel_istek" ("baslangic_tarihi", "bitis_tarihi");
CREATE INDEX "idx_personel_istek_kurum" ON "esh_personel_istek" ("kurum_id");

CREATE TABLE "esh_resmi_tatiller" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "aciklama" VARCHAR2(255) NOT NULL,
  "baslangic_tarihi" DATE NOT NULL,
  "bitis_tarihi" DATE NOT NULL,
  "tatil_tipi" VARCHAR2(50) NOT NULL DEFAULT 'resmi_tatil',
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_tatil_tarih" ON "esh_resmi_tatiller" ("baslangic_tarihi", "bitis_tarihi");

CREATE TABLE "esh_personel_nobet" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "personel_id" NUMBER(10) NOT NULL,
  "nobet_tarihi" DATE NOT NULL,
  "nobet_tipi" VARCHAR2(50) NOT NULL DEFAULT 'normal',
  "durum" NUMBER(3) NOT NULL DEFAULT 1,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "tekil_nobet" UNIQUE ("personel_id", "nobet_tarihi")
);

CREATE INDEX "idx_nobet_tarih" ON "esh_personel_nobet" ("nobet_tarihi");
CREATE INDEX "idx_nobet_personel" ON "esh_personel_nobet" ("personel_id");
CREATE INDEX "idx_personel_nobet_kurum" ON "esh_personel_nobet" ("kurum_id");

CREATE TABLE "esh_hasta_yara_fotolar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "dosya_adi" VARCHAR2(255) NOT NULL,
  "orijinal_ad" VARCHAR2(255) DEFAULT NULL,
  "mime" VARCHAR2(100) DEFAULT NULL,
  "boyut" NUMBER(10) DEFAULT NULL,
  "aciklama" VARCHAR2(255) DEFAULT NULL,
  "yara_bolgesi" VARCHAR2(100) DEFAULT NULL,
  "yara_evresi" VARCHAR2(50) DEFAULT NULL,
  "cekim_tarihi" TIMESTAMP DEFAULT NULL,
  "yukleyen_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_hasta" ON "esh_hasta_yara_fotolar" ("hasta_id");
CREATE INDEX "idx_created" ON "esh_hasta_yara_fotolar" ("created_at");
CREATE INDEX "idx_yara_fotolar_kurum" ON "esh_hasta_yara_fotolar" ("kurum_id");

CREATE TABLE "esh_rota_cache" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "hash" VARCHAR2(128) NOT NULL,
  "origin" VARCHAR2(512) NOT NULL,
  "destination" VARCHAR2(512) NOT NULL,
  "sure" NUMBER(10) NOT NULL DEFAULT 0,
  "mesafe" NUMBER(10) NOT NULL DEFAULT 0,
  "updated_at" TIMESTAMP NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_hash" UNIQUE ("hash")
);


CREATE TABLE "esh_kons_randevu" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "randevu_tarihi" DATE NOT NULL,
  "zaman" NUMBER(3) NOT NULL DEFAULT 0,
  "kons_istekler" VARCHAR2(512) NOT NULL DEFAULT '',
  "brans_id" NUMBER(10) NOT NULL,
  "hastatckimlik" VARCHAR2(11) NOT NULL,
  "notlar" VARCHAR2(512) DEFAULT NULL,
  "hasta_geldi" NUMBER(3) NULL DEFAULT NULL,
  "olusturan_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_tarih_brans_hasta" UNIQUE ("randevu_tarihi", "brans_id", "hastatckimlik")
);

CREATE INDEX "idx_kons_randevu_tarih" ON "esh_kons_randevu" ("randevu_tarihi");
CREATE INDEX "idx_kons_randevu_hasta" ON "esh_kons_randevu" ("hastatckimlik");
CREATE INDEX "idx_kons_randevu_brans_tarih" ON "esh_kons_randevu" ("brans_id", "randevu_tarihi");
CREATE INDEX "idx_kons_randevu_kurum" ON "esh_kons_randevu" ("kurum_id");

CREATE TABLE "esh_goruntulu_randevu" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "randevu_tarihi" DATE NOT NULL,
  "zaman" NUMBER(3) NOT NULL DEFAULT 0,
  "kons_istekler" VARCHAR2(512) NOT NULL DEFAULT '',
  "brans_id" NUMBER(10) NOT NULL,
  "hastatckimlik" VARCHAR2(11) NOT NULL,
  "notlar" VARCHAR2(512) DEFAULT NULL,
  "hasta_geldi" NUMBER(3) NULL DEFAULT NULL,
  "olusturan_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_gor_tarih_brans_hasta" UNIQUE ("randevu_tarihi", "brans_id", "hastatckimlik")
);

CREATE INDEX "idx_gor_randevu_tarih" ON "esh_goruntulu_randevu" ("randevu_tarihi");
CREATE INDEX "idx_gor_randevu_hasta" ON "esh_goruntulu_randevu" ("hastatckimlik");
CREATE INDEX "idx_gor_randevu_brans_tarih" ON "esh_goruntulu_randevu" ("brans_id", "randevu_tarihi");
CREATE INDEX "idx_gor_randevu_kurum" ON "esh_goruntulu_randevu" ("kurum_id");

CREATE TABLE "esh_hasta_nakil" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kaynak_hasta_id" NUMBER(10) NOT NULL,
  "kaynak_kurum_id" NUMBER(10) NOT NULL,
  "hedef_kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "hedef_hasta_id" NUMBER(10) NULL DEFAULT NULL,
  "onceki_nakil_id" NUMBER(10) NULL DEFAULT NULL,
  "orijinal_kaynak_hasta_id" NUMBER(10) NULL DEFAULT NULL,
  "tip" VARCHAR2(32) NOT NULL DEFAULT 'kurum_ici',
  "durum" VARCHAR2(32) NOT NULL DEFAULT 'beklemede',
  "talep_eden_user_id" NUMBER(10) NOT NULL,
  "talep_tarihi" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "onaylayan_user_id" NUMBER(10) NULL DEFAULT NULL,
  "onay_tarihi" TIMESTAMP NULL DEFAULT NULL,
  "red_nedeni" VARCHAR2(500) NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_hedef_durum" ON "esh_hasta_nakil" ("hedef_kurum_id", "durum");
CREATE INDEX "idx_kaynak_hasta" ON "esh_hasta_nakil" ("kaynak_hasta_id");
CREATE INDEX "idx_kaynak_kurum_durum" ON "esh_hasta_nakil" ("kaynak_kurum_id", "durum");

CREATE TABLE "esh_mesaj_konusmalar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "tip" VARCHAR2(32) NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "hasta_id" NUMBER(10) NULL DEFAULT NULL,
  "dm_kucuk_id" NUMBER(10) NULL DEFAULT NULL,
  "dm_buyuk_id" NUMBER(10) NULL DEFAULT NULL,
  "baslik" VARCHAR2(255) NOT NULL DEFAULT '',
  "olusturan_id" NUMBER(10) NULL DEFAULT NULL,
  "son_mesaj_at" TIMESTAMP NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uq_dm_pair" UNIQUE ("dm_kucuk_id", "dm_buyuk_id"),
  CONSTRAINT "uq_hasta_thread" UNIQUE ("hasta_id")
);

CREATE INDEX "idx_kurum_son" ON "esh_mesaj_konusmalar" ("kurum_id", "son_mesaj_at");
CREATE INDEX "idx_tip" ON "esh_mesaj_konusmalar" ("tip");

CREATE TABLE "esh_mesaj_konusma_uyeler" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "konusma_id" NUMBER(10) NOT NULL,
  "user_id" NUMBER(10) NOT NULL,
  "son_okunan_mesaj_id" NUMBER(10) NOT NULL DEFAULT 0,
  "katilim_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "silindi_at" TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uq_konusma_user" UNIQUE ("konusma_id", "user_id")
);

CREATE INDEX "idx_user" ON "esh_mesaj_konusma_uyeler" ("user_id");
CREATE INDEX "idx_user_silindi" ON "esh_mesaj_konusma_uyeler" ("user_id", "silindi_at");

CREATE TABLE "esh_mesajlar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "konusma_id" NUMBER(10) NOT NULL,
  "gonderen_id" NUMBER(10) NULL DEFAULT NULL,
  "gonderen_tip" VARCHAR2(32) NOT NULL DEFAULT 'user',
  "govde" CLOB NOT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_konusma_id" ON "esh_mesajlar" ("konusma_id", "id");

CREATE TABLE "esh_eimza_challenges" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "nonce_hash" CHAR(64) NOT NULL,
  "issued_at" TIMESTAMP NOT NULL,
  "expires_at" TIMESTAMP NOT NULL,
  "consumed_at" TIMESTAMP NULL DEFAULT NULL,
  "ip_address" VARCHAR2(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR2(255) NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_eimza_challenge_expires" ON "esh_eimza_challenges" ("expires_at");
CREATE INDEX "idx_eimza_challenge_user" ON "esh_eimza_challenges" ("user_id");

CREATE TABLE "esh_eimza_login_logs" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "tc_kimlikno" VARCHAR2(16) NOT NULL,
  "success" NUMBER(3) NOT NULL DEFAULT 0,
  "reason" VARCHAR2(128) NOT NULL,
  "cert_serial" VARCHAR2(128) NULL DEFAULT NULL,
  "cert_fingerprint" VARCHAR2(128) NULL DEFAULT NULL,
  "ip_address" VARCHAR2(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR2(255) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_eimza_logs_user_created" ON "esh_eimza_login_logs" ("user_id", "created_at");
CREATE INDEX "idx_eimza_logs_tc_created" ON "esh_eimza_login_logs" ("tc_kimlikno", "created_at");
CREATE INDEX "idx_eimza_logs_success_created" ON "esh_eimza_login_logs" ("success", "created_at");

CREATE TABLE "esh_rehber_import_log" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "source_site" VARCHAR2(32) NOT NULL,
  "started_at" TIMESTAMP NOT NULL,
  "finished_at" TIMESTAMP NULL DEFAULT NULL,
  "etken_count" NUMBER(10) NOT NULL DEFAULT 0,
  "ilac_count" NUMBER(10) NOT NULL DEFAULT 0,
  "error_summary" CLOB NULL,
  "options_json" CLOB NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_rehber_import_finished" ON "esh_rehber_import_log" ("finished_at");

CREATE TABLE "esh_rehber_etken" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "ad" VARCHAR2(512) NOT NULL,
  "ad_normalized" VARCHAR2(512) NOT NULL,
  "source_site" VARCHAR2(32) NOT NULL,
  "source_key" VARCHAR2(128) NOT NULL,
  "scraped_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_rehber_etken_site_key" UNIQUE ("source_site", "source_key")
);

CREATE INDEX "idx_rehber_etken_norm" ON "esh_rehber_etken" ("ad_normalized");

CREATE TABLE "esh_rehber_ilac" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "etken_id" NUMBER(10) NOT NULL,
  "ad" VARCHAR2(512) NOT NULL,
  "firma" VARCHAR2(255) NULL,
  "recete_turu" VARCHAR2(128) NULL,
  "source_site" VARCHAR2(32) NOT NULL,
  "source_url" VARCHAR2(1024) NULL,
  "source_key" VARCHAR2(256) NOT NULL,
  "scraped_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_rehber_ilac_site_key" UNIQUE ("source_site", "source_key")
);

CREATE INDEX "idx_rehber_ilac_etken" ON "esh_rehber_ilac" ("etken_id");

CREATE TABLE "esh_unvanlar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kod" VARCHAR2(64) NOT NULL,
  "ad" VARCHAR2(128) NOT NULL,
  "kategori" VARCHAR2(32) NOT NULL DEFAULT 'diger',
  "izin_sablonu" VARCHAR2(64) NOT NULL DEFAULT 'personel',
  "sort_order" NUMBER(10) NOT NULL DEFAULT 100,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "is_system" NUMBER(3) NOT NULL DEFAULT 0,
  "mevzuat_notu" VARCHAR2(512) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_unvan_kod" UNIQUE ("kod")
);

CREATE INDEX "idx_unvan_aktif_sort" ON "esh_unvanlar" ("aktif", "sort_order");

CREATE TABLE "esh_roles" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "slug" VARCHAR2(64) NOT NULL,
  "unvan_code" VARCHAR2(64) NULL DEFAULT NULL,
  "name" VARCHAR2(128) NOT NULL,
  "description" VARCHAR2(512) NULL DEFAULT NULL,
  "is_system" NUMBER(3) NOT NULL DEFAULT 0,
  "sort_order" NUMBER(10) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_roles_slug" UNIQUE ("slug"),
  CONSTRAINT "uk_roles_unvan_code" UNIQUE ("unvan_code")
);


CREATE TABLE "esh_permissions" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "module_key" VARCHAR2(64) NOT NULL,
  "crud" VARCHAR2(32) NOT NULL,
  "slug" VARCHAR2(128) NOT NULL,
  "label" VARCHAR2(255) NOT NULL DEFAULT '',
  "description" VARCHAR2(512) NULL DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_permissions_slug" UNIQUE ("slug")
);

CREATE INDEX "idx_permissions_module" ON "esh_permissions" ("module_key");

CREATE TABLE "esh_role_permissions" (
  "role_id" NUMBER(10) NOT NULL,
  "permission_id" NUMBER(10) NOT NULL,
  PRIMARY KEY ("role_id", "permission_id")
);

CREATE INDEX "idx_role_permissions_permission" ON "esh_role_permissions" ("permission_id");

CREATE TABLE "esh_user_roles" (
  "user_id" NUMBER(10) NOT NULL,
  "role_id" NUMBER(10) NOT NULL,
  PRIMARY KEY ("user_id")
);

CREATE INDEX "idx_user_roles_role" ON "esh_user_roles" ("role_id");

