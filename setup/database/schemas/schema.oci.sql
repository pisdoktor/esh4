-- ESH Panel — Oracle şeması (schema.sql'den üretildi)
-- Üretim: php tools/build_schema_dialect.php oci
-- Kurulum: db_driver=oci
-- =============================================================================

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_cds_ack" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_portal_appointment_requests" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_api_tokens" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_usbs_sync_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_federation_sync_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_esys_sync_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_audit_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_ilac" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_etken" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rehber_import_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_eimza_login_logs" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_eimza_challenges" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_stok_parti" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_stok_uyari_log" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_stok_hareket" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_stok_mevcut" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_stok_malzeme" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_sms_alici" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_sms_gonderim" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_sms_optout" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_sms_sablonlari" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesajlar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesaj_konusma_uyeler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mesaj_konusmalar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_nakil" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_rota_cache" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_ekipler" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_ilaclar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_barthel" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_mna" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_harizmi" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_itaki" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_hasta_braden" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

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

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurum_hastalik" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurum_islem" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurum_istek" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurum_brans" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurum_adres" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_mahalle_plan" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_federation_regions" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_kurumlar" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

BEGIN EXECUTE IMMEDIATE 'DROP TABLE "esh_adrestablosu" CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;

CREATE TABLE "esh_federation_regions" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kod" VARCHAR2(64) NOT NULL,
  "ad" VARCHAR2(255) NOT NULL,
  "il_adi" VARCHAR2(64) NULL DEFAULT NULL,
  "hub_node_ref" VARCHAR2(64) NULL DEFAULT NULL,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "aciklama" CLOB NULL DEFAULT NULL,
  "ayarlar_json" CLOB NULL DEFAULT NULL,
  "olusturma_tarihi" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_fed_region_kod" UNIQUE ("kod")
);

CREATE INDEX "idx_fed_region_aktif" ON "esh_federation_regions" ("aktif");

INSERT INTO "esh_federation_regions" ("id", "kod", "ad", "aktif", "aciklama") VALUES (1, 'varsayilan', 'Varsayılan bölge', 1, 'Kurulum sonrası otomatik oluşturulur');

CREATE TABLE "esh_kurumlar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "ad" VARCHAR2(255) NOT NULL,
  "kod" VARCHAR2(64) NOT NULL,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "bolge_id" NUMBER(10) NULL DEFAULT NULL,
  "federation_ref" VARCHAR2(64) NULL DEFAULT NULL,
  "logo" VARCHAR2(255) DEFAULT NULL,
  "adres" CLOB DEFAULT NULL,
  "telefon" VARCHAR2(64) DEFAULT NULL,
  "ayarlar_json" CLOB DEFAULT NULL,
  "olusturma_tarihi" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_kurum_kod" UNIQUE ("kod")
);

CREATE INDEX "idx_kurum_aktif" ON "esh_kurumlar" ("aktif");
CREATE INDEX "idx_kurum_bolge" ON "esh_kurumlar" ("bolge_id");
CREATE INDEX "idx_kurum_federation_ref" ON "esh_kurumlar" ("federation_ref");

INSERT INTO "esh_kurumlar" ("id", "ad", "kod", "aktif", "bolge_id") VALUES (1, 'Varsayılan Kurum', 'varsayilan', 1, 1);

CREATE TABLE "esh_adrestablosu" (
  "id" VARCHAR2(64) NOT NULL,
  "adi" VARCHAR2(255) NOT NULL DEFAULT '',
  "ust_id" VARCHAR2(64) DEFAULT NULL,
  "tip" VARCHAR2(20) NOT NULL DEFAULT 'bolge',
  "federation_bolge_id" NUMBER(10) NULL DEFAULT NULL,
  "coords" VARCHAR2(255) DEFAULT NULL,
  "has_coords" NUMBER(3) NOT NULL DEFAULT 0,
  PRIMARY KEY ("ust_id", "id"),
  CONSTRAINT "uk_adres_fed_bolge" UNIQUE ("federation_bolge_id")
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
  "icd" VARCHAR2(32) DEFAULT NULL,
  PRIMARY KEY ("kurum_id", "hastalik_id")
);

CREATE INDEX "idx_kh_hastalik" ON "esh_kurum_hastalik" ("hastalik_id");

CREATE INDEX "idx_kh_kurum_icd" ON "esh_kurum_hastalik" ("kurum_id", "icd");

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
  "kapasite" NUMBER(3) NOT NULL DEFAULT 4,
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
  "hastalikicd" VARCHAR2(32) NOT NULL,
  "rapor" NUMBER(3) NOT NULL DEFAULT 0,
  "bitistarihi" DATE DEFAULT NULL,
  "brans" VARCHAR2(512) NOT NULL DEFAULT '',
  "raporyeri" NUMBER(3) NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);

CREATE INDEX "uk_tc_hastalik_icd" ON "esh_hastailacrapor" ("hastatckimlik", "hastalikicd");
CREATE INDEX "idx_tc" ON "esh_hastailacrapor" ("hastatckimlik");
CREATE INDEX "idx_hastalik_icd" ON "esh_hastailacrapor" ("hastalikicd");
CREATE INDEX "idx_hastailacrapor_kurum" ON "esh_hastailacrapor" ("kurum_id");

CREATE TABLE "esh_hasta_ilaclar" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "ilac_adi" VARCHAR2(255) NOT NULL,
  "etken_madde" VARCHAR2(512) NULL,
  "recete_turu" VARCHAR2(128) NULL,
  "not" CLOB NULL,
  "hastalikicd" VARCHAR2(32) NULL,
  "sira" NUMBER(5) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_hasta" ON "esh_hasta_ilaclar" ("hasta_id");
CREATE INDEX "idx_hasta_ilac_hastalik_icd" ON "esh_hasta_ilaclar" ("hastalikicd");
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
  "bolge_id" NUMBER(10) NULL DEFAULT NULL,
  "unvan" VARCHAR2(64) DEFAULT NULL,
  "ui_theme" VARCHAR2(64) DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_username" UNIQUE ("username")
);

CREATE INDEX "idx_users_activated_name" ON "esh_users" ("activated", "name");
CREATE INDEX "idx_users_kurum" ON "esh_users" ("kurum_id");
CREATE INDEX "idx_users_bolge" ON "esh_users" ("bolge_id");

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
  "sms_bilgilendirme_onay" NUMBER(3) NOT NULL DEFAULT 1,
  "kangrubu" VARCHAR2(8) DEFAULT NULL,
  "ilce" VARCHAR2(64) DEFAULT NULL,
  "mahalle" VARCHAR2(64) DEFAULT NULL,
  "sokak" VARCHAR2(64) DEFAULT NULL,
  "kapino" VARCHAR2(64) DEFAULT NULL,
  "adres_aciklama" CLOB,
  "diger_adres" CLOB,
  "bagimlilik" VARCHAR2(10) DEFAULT NULL,
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
  "esys_hasta_ref" VARCHAR2(64) DEFAULT NULL,
  "esys_basvuru_ref" VARCHAR2(64) DEFAULT NULL,
  "enabiz_hasta_ref" VARCHAR2(64) DEFAULT NULL,
  "usbs_hasta_ref" VARCHAR2(64) DEFAULT NULL,
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
CREATE INDEX "idx_hastalar_esys_hasta_ref" ON "esh_hastalar" ("esys_hasta_ref");
CREATE INDEX "idx_hastalar_esys_basvuru_ref" ON "esh_hastalar" ("esys_basvuru_ref");
CREATE INDEX "idx_hastalar_enabiz_ref" ON "esh_hastalar" ("enabiz_hasta_ref");
CREATE INDEX "idx_hastalar_usbs_hasta_ref" ON "esh_hastalar" ("usbs_hasta_ref");

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
  "checkin_lat" NUMBER(10,7) NULL DEFAULT NULL,
  "checkin_lon" NUMBER(10,7) NULL DEFAULT NULL,
  "checkin_at" TIMESTAMP NULL DEFAULT NULL,
  "checkin_accuracy" NUMBER(8,1) NULL DEFAULT NULL,
  "arac" NUMBER(10) DEFAULT NULL,
  "brans" VARCHAR2(255) DEFAULT NULL,
  "kons_istekler" VARCHAR2(512) DEFAULT NULL,
  "kons_brans_istek" CLOB DEFAULT NULL,
  "esys_izlem_ref" VARCHAR2(64) DEFAULT NULL,
  "esys_konsultasyon_ref" VARCHAR2(64) DEFAULT NULL,
  "usbs_bildirim_ref" VARCHAR2(64) NULL DEFAULT NULL,
  "usbs_bildirim_durum" VARCHAR2(16) NOT NULL DEFAULT '',
  "usbs_bildirim_at" TIMESTAMP NULL DEFAULT NULL,
  "erecete_ref" VARCHAR2(64) NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_izlem_tc_yapildi_tarih" ON "esh_izlemler" ("hastatckimlik", "yapildimi", "izlemtarihi");
CREATE INDEX "idx_izlem_tarih_yapildi" ON "esh_izlemler" ("izlemtarihi", "yapildimi");
CREATE INDEX "idx_izlem_tarih_tc" ON "esh_izlemler" ("izlemtarihi", "hastatckimlik");
CREATE INDEX "idx_izlem_yapildi_tarih_dt" ON "esh_izlemler" ("yapildimi", "izlemtarihi_dt");
CREATE INDEX "idx_arac" ON "esh_izlemler" ("arac");
CREATE INDEX "idx_izlemler_kurum" ON "esh_izlemler" ("kurum_id");
CREATE INDEX "idx_izlemler_esys_izlem_ref" ON "esh_izlemler" ("esys_izlem_ref");
CREATE INDEX "idx_izlemler_usbs_bildirim_ref" ON "esh_izlemler" ("usbs_bildirim_ref");
CREATE INDEX "idx_izlemler_usbs_bildirim_durum" ON "esh_izlemler" ("usbs_bildirim_durum");

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
  "esys_plan_ref" VARCHAR2(64) DEFAULT NULL,
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
  "esys_erapor_ref" VARCHAR2(64) DEFAULT NULL,
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
  "arac_id" NUMBER(10) NULL DEFAULT NULL,
  "baslangic_saati" VARCHAR2(32) DEFAULT NULL,
  "kayit_tarihi" TIMESTAMP DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_tarih" ON "esh_ekipler" ("tarih");
CREATE INDEX "idx_ekipler_kurum" ON "esh_ekipler" ("kurum_id");
CREATE INDEX "idx_ekipler_arac" ON "esh_ekipler" ("arac_id");

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

CREATE TABLE "esh_hasta_braden" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "duyusal" NUMBER(3) NOT NULL,
  "nem" NUMBER(3) NOT NULL,
  "aktivite" NUMBER(3) NOT NULL,
  "hareket" NUMBER(3) NOT NULL,
  "beslenme" NUMBER(3) NOT NULL,
  "surtunme" NUMBER(3) NOT NULL,
  "toplam_skor" NUMBER(3) NOT NULL,
  "risk_duzeyi" VARCHAR2(32) NOT NULL DEFAULT '',
  "notlar" CLOB DEFAULT NULL,
  "kaydeden_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_braden_hasta" ON "esh_hasta_braden" ("hasta_id");
CREATE INDEX "idx_braden_tarih" ON "esh_hasta_braden" ("degerlendirme_tarihi");
CREATE INDEX "idx_braden_kurum" ON "esh_hasta_braden" ("kurum_id");

CREATE TABLE "esh_hasta_itaki" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "degerlendirme_gerekcesi" NUMBER(3) NOT NULL DEFAULT 1,
  "secimler_json" CLOB NOT NULL,
  "toplam_skor" NUMBER(5) NOT NULL DEFAULT 0,
  "risk_duzeyi" VARCHAR2(32) NOT NULL DEFAULT '',
  "notlar" CLOB DEFAULT NULL,
  "kaydeden_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_itaki_hasta" ON "esh_hasta_itaki" ("hasta_id");
CREATE INDEX "idx_itaki_tarih" ON "esh_hasta_itaki" ("degerlendirme_tarihi");
CREATE INDEX "idx_itaki_kurum" ON "esh_hasta_itaki" ("kurum_id");

CREATE TABLE "esh_hasta_harizmi" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "degerlendirme_gerekcesi" NUMBER(3) NOT NULL DEFAULT 1,
  "secimler_json" CLOB NOT NULL,
  "toplam_skor" NUMBER(5) NOT NULL DEFAULT 0,
  "risk_duzeyi" VARCHAR2(32) NOT NULL DEFAULT '',
  "notlar" CLOB DEFAULT NULL,
  "kaydeden_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_harizmi_hasta" ON "esh_hasta_harizmi" ("hasta_id");
CREATE INDEX "idx_harizmi_tarih" ON "esh_hasta_harizmi" ("degerlendirme_tarihi");
CREATE INDEX "idx_harizmi_kurum" ON "esh_hasta_harizmi" ("kurum_id");

CREATE TABLE "esh_hasta_mna" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "besin_alimi" NUMBER(3) NOT NULL,
  "kilo_kaybi" NUMBER(3) NOT NULL,
  "mobilite" NUMBER(3) NOT NULL,
  "stres_hastalik" NUMBER(3) NOT NULL,
  "noropsikolojik" NUMBER(3) NOT NULL,
  "bmi_olcum_tipi" VARCHAR2(20) NOT NULL DEFAULT 'bmi',
  "bmi_skor" NUMBER(3) NOT NULL,
  "toplam_skor" NUMBER(3) NOT NULL,
  "durum_duzeyi" VARCHAR2(32) NOT NULL DEFAULT '',
  "notlar" CLOB DEFAULT NULL,
  "kaydeden_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_mna_hasta" ON "esh_hasta_mna" ("hasta_id");
CREATE INDEX "idx_mna_tarih" ON "esh_hasta_mna" ("degerlendirme_tarihi");
CREATE INDEX "idx_mna_kurum" ON "esh_hasta_mna" ("kurum_id");

CREATE TABLE "esh_hasta_barthel" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL DEFAULT 1,
  "hasta_id" NUMBER(10) NOT NULL,
  "degerlendirme_tarihi" DATE NOT NULL,
  "barbeslenme" NUMBER(3) NOT NULL DEFAULT 0,
  "barbanyo" NUMBER(3) NOT NULL DEFAULT 0,
  "barbakim" NUMBER(3) NOT NULL DEFAULT 0,
  "bargiyinme" NUMBER(3) NOT NULL DEFAULT 0,
  "barbarsak" NUMBER(3) NOT NULL DEFAULT 0,
  "barmesane" NUMBER(3) NOT NULL DEFAULT 0,
  "bartuvalet" NUMBER(3) NOT NULL DEFAULT 0,
  "bartransfer" NUMBER(3) NOT NULL DEFAULT 0,
  "barmobilite" NUMBER(3) NOT NULL DEFAULT 0,
  "barmerdiven" NUMBER(3) NOT NULL DEFAULT 0,
  "toplam_skor" NUMBER(5) NOT NULL DEFAULT 0,
  "bagimlilik_duzeyi" VARCHAR2(32) NOT NULL DEFAULT '',
  "notlar" CLOB DEFAULT NULL,
  "kaydeden_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_barthel_hasta" ON "esh_hasta_barthel" ("hasta_id");
CREATE INDEX "idx_barthel_tarih" ON "esh_hasta_barthel" ("degerlendirme_tarihi");
CREATE INDEX "idx_barthel_kurum" ON "esh_hasta_barthel" ("kurum_id");

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
  "video_room_id" VARCHAR2(64) NULL DEFAULT NULL,
  "video_started_at" TIMESTAMP NULL DEFAULT NULL,
  "video_ended_at" TIMESTAMP NULL DEFAULT NULL,
  "visit_id" NUMBER(10) NULL DEFAULT NULL,
  "telehealth_summary" CLOB NULL DEFAULT NULL,
  "olusturan_id" NUMBER(10) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uk_gor_tarih_brans_hasta" UNIQUE ("randevu_tarihi", "brans_id", "hastatckimlik")
);

CREATE INDEX "idx_gor_randevu_tarih" ON "esh_goruntulu_randevu" ("randevu_tarihi");
CREATE INDEX "idx_gor_randevu_hasta" ON "esh_goruntulu_randevu" ("hastatckimlik");
CREATE INDEX "idx_gor_randevu_brans_tarih" ON "esh_goruntulu_randevu" ("brans_id", "randevu_tarihi");
CREATE INDEX "idx_gor_randevu_kurum" ON "esh_goruntulu_randevu" ("kurum_id");
CREATE INDEX "idx_gor_video_room" ON "esh_goruntulu_randevu" ("video_room_id");
CREATE INDEX "idx_gor_visit" ON "esh_goruntulu_randevu" ("visit_id");

CREATE TABLE "esh_hasta_nakil" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kaynak_hasta_id" NUMBER(10) NOT NULL,
  "kaynak_kurum_id" NUMBER(10) NOT NULL,
  "hedef_kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "hedef_bolge_id" NUMBER(10) NULL DEFAULT NULL,
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
CREATE INDEX "idx_hedef_bolge_durum" ON "esh_hasta_nakil" ("hedef_bolge_id", "durum");
CREATE INDEX "idx_kaynak_hasta" ON "esh_hasta_nakil" ("kaynak_hasta_id");
CREATE INDEX "idx_kaynak_kurum_durum" ON "esh_hasta_nakil" ("kaynak_kurum_id", "durum");

CREATE TABLE "esh_sms_sablonlari" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "kod" VARCHAR2(64) NOT NULL DEFAULT '',
  "baslik" VARCHAR2(255) NOT NULL DEFAULT '',
  "govde" VARCHAR2(1600) NOT NULL DEFAULT '',
  "degiskenler_json" CLOB NULL,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_sms_sablon_kurum" ON "esh_sms_sablonlari" ("kurum_id", "aktif");
CREATE INDEX "idx_sms_sablon_kod" ON "esh_sms_sablonlari" ("kod");

CREATE TABLE "esh_sms_gonderim" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "olusturan_id" NUMBER(10) NOT NULL,
  "segment_tipi" VARCHAR2(32) NOT NULL DEFAULT 'tek_hasta',
  "segment_param_json" CLOB NULL,
  "sablon_id" NUMBER(10) NULL DEFAULT NULL,
  "govde_ozet" VARCHAR2(500) NOT NULL DEFAULT '',
  "mesaj_turu" VARCHAR2(32) NOT NULL DEFAULT 'bilgilendirme',
  "durum" VARCHAR2(32) NOT NULL DEFAULT 'beklemede',
  "toplam" NUMBER(10) NOT NULL DEFAULT 0,
  "basarili" NUMBER(10) NOT NULL DEFAULT 0,
  "basarisiz" NUMBER(10) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_sms_gonderim_kurum" ON "esh_sms_gonderim" ("kurum_id", "created_at");
CREATE INDEX "idx_sms_gonderim_durum" ON "esh_sms_gonderim" ("durum");

CREATE TABLE "esh_sms_alici" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "gonderim_id" NUMBER(10) NOT NULL,
  "hasta_id" NUMBER(10) NULL DEFAULT NULL,
  "rol" VARCHAR2(32) NOT NULL DEFAULT 'hasta',
  "telefon_norm" VARCHAR2(16) NOT NULL DEFAULT '',
  "govde" VARCHAR2(1600) NOT NULL DEFAULT '',
  "provider_msg_id" VARCHAR2(64) NULL DEFAULT NULL,
  "durum" VARCHAR2(32) NOT NULL DEFAULT 'beklemede',
  "hata_kodu" VARCHAR2(32) NULL DEFAULT NULL,
  "hata_mesaj" VARCHAR2(255) NULL DEFAULT NULL,
  "gonderim_at" TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_sms_alici_gonderim" ON "esh_sms_alici" ("gonderim_id");
CREATE INDEX "idx_sms_alici_durum" ON "esh_sms_alici" ("durum");

CREATE TABLE "esh_sms_optout" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "telefon_norm" VARCHAR2(16) NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "kaynak" VARCHAR2(32) NOT NULL DEFAULT 'manuel',
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uq_sms_optout_tel_kurum" UNIQUE ("telefon_norm", "kurum_id")
);

CREATE INDEX "idx_sms_optout_kurum" ON "esh_sms_optout" ("kurum_id");

CREATE TABLE "esh_stok_malzeme" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "kod" VARCHAR2(64) NULL DEFAULT NULL,
  "ad" VARCHAR2(255) NOT NULL DEFAULT '',
  "kategori" VARCHAR2(32) NOT NULL DEFAULT 'sarf',
  "birim" VARCHAR2(32) NOT NULL DEFAULT 'adet',
  "min_stok" NUMBER(12,3) NOT NULL DEFAULT 0,
  "aktif" NUMBER(3) NOT NULL DEFAULT 1,
  "aciklama" VARCHAR2(512) NULL DEFAULT NULL,
  "tedarikci_adi" VARCHAR2(255) NULL DEFAULT NULL,
  "tedarikci_tel" VARCHAR2(32) NULL DEFAULT NULL,
  "birim_fiyat" NUMBER(12,2) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_stok_malzeme_kurum" ON "esh_stok_malzeme" ("kurum_id", "aktif");
CREATE INDEX "idx_stok_malzeme_kategori" ON "esh_stok_malzeme" ("kategori");

CREATE TABLE "esh_stok_mevcut" (
  "kurum_id" NUMBER(10) NOT NULL,
  "malzeme_id" NUMBER(10) NOT NULL,
  "miktar" NUMBER(12,3) NOT NULL DEFAULT 0,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("kurum_id", "malzeme_id")
);

CREATE INDEX "idx_stok_mevcut_malzeme" ON "esh_stok_mevcut" ("malzeme_id");

CREATE TABLE "esh_stok_hareket" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "malzeme_id" NUMBER(10) NOT NULL,
  "hareket_tipi" VARCHAR2(32) NOT NULL,
  "miktar" NUMBER(12,3) NOT NULL,
  "hareket_tarihi" DATE NOT NULL,
  "hasta_id" NUMBER(10) NULL DEFAULT NULL,
  "ekip_id" NUMBER(10) NULL DEFAULT NULL,
  "kullanici_id" NUMBER(10) NOT NULL,
  "aciklama" VARCHAR2(512) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_stok_hareket_kurum_tarih" ON "esh_stok_hareket" ("kurum_id", "hareket_tarihi");
CREATE INDEX "idx_stok_hareket_malzeme" ON "esh_stok_hareket" ("malzeme_id");
CREATE INDEX "idx_stok_hareket_hasta" ON "esh_stok_hareket" ("hasta_id");
CREATE INDEX "idx_stok_hareket_ekip" ON "esh_stok_hareket" ("ekip_id");

CREATE TABLE "esh_stok_uyari_log" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "malzeme_id" NUMBER(10) NOT NULL,
  "uyari_tarihi" DATE NOT NULL,
  "sms_gonderildi" NUMBER(3) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id"),
  CONSTRAINT "uq_stok_uyari_gun" UNIQUE ("kurum_id", "malzeme_id", "uyari_tarihi")
);

CREATE INDEX "idx_stok_uyari_kurum" ON "esh_stok_uyari_log" ("kurum_id", "uyari_tarihi");

CREATE TABLE "esh_stok_parti" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "malzeme_id" NUMBER(10) NOT NULL,
  "lot_no" VARCHAR2(64) NULL DEFAULT NULL,
  "skt" DATE NULL DEFAULT NULL,
  "miktar" NUMBER(12,3) NOT NULL DEFAULT 0,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_stok_parti_malzeme" ON "esh_stok_parti" ("kurum_id", "malzeme_id", "skt");

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

CREATE TABLE "esh_audit_log" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "action" VARCHAR2(64) NOT NULL,
  "entity_type" VARCHAR2(32) NOT NULL DEFAULT '',
  "entity_id" NUMBER(10) NULL DEFAULT NULL,
  "entity_ref" VARCHAR2(64) NULL DEFAULT NULL,
  "ip_address" VARCHAR2(64) NULL DEFAULT NULL,
  "user_agent" VARCHAR2(255) NULL DEFAULT NULL,
  "request_uri" VARCHAR2(512) NULL DEFAULT NULL,
  "context_json" CLOB NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_audit_user_created" ON "esh_audit_log" ("user_id", "created_at");
CREATE INDEX "idx_audit_action_created" ON "esh_audit_log" ("action", "created_at");
CREATE INDEX "idx_audit_entity" ON "esh_audit_log" ("entity_type", "entity_id");
CREATE INDEX "idx_audit_kurum_created" ON "esh_audit_log" ("kurum_id", "created_at");
CREATE INDEX "idx_audit_entity_ref" ON "esh_audit_log" ("entity_ref", "created_at");
CREATE INDEX "idx_audit_created" ON "esh_audit_log" ("created_at");

CREATE TABLE "esh_esys_sync_log" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "direction" VARCHAR2(24) NOT NULL,
  "status" VARCHAR2(16) NOT NULL,
  "file_name" VARCHAR2(255) NULL DEFAULT NULL,
  "stats_json" CLOB NULL DEFAULT NULL,
  "error_message" VARCHAR2(512) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_esys_sync_created" ON "esh_esys_sync_log" ("created_at");
CREATE INDEX "idx_esys_sync_kurum_created" ON "esh_esys_sync_log" ("kurum_id", "created_at");
CREATE INDEX "idx_esys_sync_direction" ON "esh_esys_sync_log" ("direction", "created_at");

CREATE TABLE "esh_api_tokens" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "user_id" NUMBER(10) NOT NULL,
  "kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "label" VARCHAR2(128) NOT NULL DEFAULT '',
  "token_prefix" VARCHAR2(16) NOT NULL,
  "token_hash" CHAR(64) NOT NULL,
  "scopes" VARCHAR2(255) NOT NULL DEFAULT 'read',
  "expires_at" TIMESTAMP NULL DEFAULT NULL,
  "last_used_at" TIMESTAMP NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "revoked_at" TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_api_token_prefix" ON "esh_api_tokens" ("token_prefix");
CREATE INDEX "idx_api_token_user" ON "esh_api_tokens" ("user_id");
CREATE INDEX "idx_api_token_kurum" ON "esh_api_tokens" ("kurum_id");

CREATE TABLE "esh_federation_sync_log" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "direction" VARCHAR2(32) NOT NULL,
  "status" VARCHAR2(16) NOT NULL,
  "file_name" VARCHAR2(255) NULL DEFAULT NULL,
  "stats_json" CLOB NULL DEFAULT NULL,
  "error_message" VARCHAR2(512) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_fed_sync_created" ON "esh_federation_sync_log" ("created_at");
CREATE INDEX "idx_fed_sync_direction" ON "esh_federation_sync_log" ("direction", "created_at");

CREATE TABLE "esh_usbs_sync_log" (
  "id" NUMBER(19) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "kurum_id" NUMBER(10) NULL DEFAULT NULL,
  "user_id" NUMBER(10) NULL DEFAULT NULL,
  "direction" VARCHAR2(24) NOT NULL,
  "status" VARCHAR2(16) NOT NULL,
  "file_name" VARCHAR2(255) NULL DEFAULT NULL,
  "stats_json" CLOB NULL DEFAULT NULL,
  "error_message" VARCHAR2(512) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_usbs_sync_created" ON "esh_usbs_sync_log" ("created_at");
CREATE INDEX "idx_usbs_sync_kurum_created" ON "esh_usbs_sync_log" ("kurum_id", "created_at");
CREATE INDEX "idx_usbs_sync_direction" ON "esh_usbs_sync_log" ("direction", "created_at");

CREATE TABLE "esh_portal_appointment_requests" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "hasta_id" NUMBER(10) NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "uhds_id" NUMBER(10) NOT NULL,
  "talep_tarihi" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "mevcut_tarih" DATE NOT NULL,
  "talep_tarih" DATE NOT NULL,
  "talep_zaman" NUMBER(3) NULL DEFAULT NULL,
  "neden" VARCHAR2(500) NOT NULL,
  "durum" VARCHAR2(20) NOT NULL DEFAULT 'queued',
  "durum_notu" VARCHAR2(500) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  "updated_at" TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_portal_req_hasta" ON "esh_portal_appointment_requests" ("hasta_id");
CREATE INDEX "idx_portal_req_kurum" ON "esh_portal_appointment_requests" ("kurum_id");
CREATE INDEX "idx_portal_req_durum" ON "esh_portal_appointment_requests" ("durum");
CREATE INDEX "idx_portal_req_uhds" ON "esh_portal_appointment_requests" ("uhds_id");

CREATE TABLE "esh_cds_ack" (
  "id" NUMBER(10) GENERATED BY DEFAULT ON NULL AS IDENTITY NOT NULL,
  "hasta_id" NUMBER(10) NOT NULL,
  "kurum_id" NUMBER(10) NOT NULL,
  "alert_code" VARCHAR2(80) NOT NULL,
  "ack_by_user_id" NUMBER(10) NULL DEFAULT NULL,
  "ack_note" VARCHAR2(500) NULL DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT SYSTIMESTAMP,
  PRIMARY KEY ("id")
);

CREATE INDEX "idx_cds_ack_hasta" ON "esh_cds_ack" ("hasta_id");
CREATE INDEX "idx_cds_ack_kurum" ON "esh_cds_ack" ("kurum_id");
CREATE INDEX "idx_cds_ack_code" ON "esh_cds_ack" ("alert_code");

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

