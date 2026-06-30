-- =============================================================================
-- ESH Panel — SQL Server şeması (schema.sql'den üretildi)
-- Üretim: php tools/build_schema_mssql.php
-- SQL Server 2019+ · Turkish_100_CI_AS
-- Kurulum sihirbazı bu dosyayı db_driver=sqlsrv ile çalıştırır.
-- =============================================================================

SET NOCOUNT ON;
GO

IF OBJECT_ID(N'[dbo].[esh_rehber_ilac]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_rehber_ilac];
GO

IF OBJECT_ID(N'[dbo].[esh_rehber_etken]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_rehber_etken];
GO

IF OBJECT_ID(N'[dbo].[esh_rehber_import_log]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_rehber_import_log];
GO

IF OBJECT_ID(N'[dbo].[esh_eimza_login_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_eimza_login_logs];
GO

IF OBJECT_ID(N'[dbo].[esh_eimza_challenges]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_eimza_challenges];
GO

IF OBJECT_ID(N'[dbo].[esh_stok_parti]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_stok_parti];
GO

IF OBJECT_ID(N'[dbo].[esh_stok_uyari_log]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_stok_uyari_log];
GO

IF OBJECT_ID(N'[dbo].[esh_stok_hareket]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_stok_hareket];
GO

IF OBJECT_ID(N'[dbo].[esh_stok_mevcut]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_stok_mevcut];
GO

IF OBJECT_ID(N'[dbo].[esh_stok_malzeme]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_stok_malzeme];
GO

IF OBJECT_ID(N'[dbo].[esh_sms_alici]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_sms_alici];
GO

IF OBJECT_ID(N'[dbo].[esh_sms_gonderim]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_sms_gonderim];
GO

IF OBJECT_ID(N'[dbo].[esh_sms_optout]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_sms_optout];
GO

IF OBJECT_ID(N'[dbo].[esh_sms_sablonlari]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_sms_sablonlari];
GO

IF OBJECT_ID(N'[dbo].[esh_mesajlar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_mesajlar];
GO

IF OBJECT_ID(N'[dbo].[esh_mesaj_konusma_uyeler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_mesaj_konusma_uyeler];
GO

IF OBJECT_ID(N'[dbo].[esh_mesaj_konusmalar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_mesaj_konusmalar];
GO

IF OBJECT_ID(N'[dbo].[esh_hasta_nakil]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hasta_nakil];
GO

IF OBJECT_ID(N'[dbo].[esh_rota_cache]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_rota_cache];
GO

IF OBJECT_ID(N'[dbo].[esh_ekipler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_ekipler];
GO

IF OBJECT_ID(N'[dbo].[esh_hasta_ilaclar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hasta_ilaclar];
GO

IF OBJECT_ID(N'[dbo].[esh_hasta_yara_fotolar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hasta_yara_fotolar];
GO

IF OBJECT_ID(N'[dbo].[esh_erapor]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_erapor];
GO

IF OBJECT_ID(N'[dbo].[esh_pizlemler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_pizlemler];
GO

IF OBJECT_ID(N'[dbo].[esh_izlemler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_izlemler];
GO

IF OBJECT_ID(N'[dbo].[esh_istekler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_istekler];
GO

IF OBJECT_ID(N'[dbo].[esh_araclar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_araclar];
GO

IF OBJECT_ID(N'[dbo].[esh_hastalar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hastalar];
GO

IF OBJECT_ID(N'[dbo].[esh_personel_nobet]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_personel_nobet];
GO

IF OBJECT_ID(N'[dbo].[esh_personel_istek]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_personel_istek];
GO

IF OBJECT_ID(N'[dbo].[esh_personel_izin]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_personel_izin];
GO

IF OBJECT_ID(N'[dbo].[esh_resmi_tatiller]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_resmi_tatiller];
GO

IF OBJECT_ID(N'[dbo].[esh_users]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_users];
GO

IF OBJECT_ID(N'[dbo].[esh_hastailacrapor]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hastailacrapor];
GO

IF OBJECT_ID(N'[dbo].[esh_hastaliklar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hastaliklar];
GO

IF OBJECT_ID(N'[dbo].[esh_islemler]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_islemler];
GO

IF OBJECT_ID(N'[dbo].[esh_branslar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_branslar];
GO

IF OBJECT_ID(N'[dbo].[esh_guvence]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_guvence];
GO

IF OBJECT_ID(N'[dbo].[esh_hastalikcat]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_hastalikcat];
GO

IF OBJECT_ID(N'[dbo].[esh_kurumlar]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_kurumlar];
GO

IF OBJECT_ID(N'[dbo].[esh_adrestablosu]', N'U') IS NOT NULL DROP TABLE [dbo].[esh_adrestablosu];
GO

IF OBJECT_ID(N'[dbo].[esh_kurumlar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurumlar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [ad] NVARCHAR(255) NOT NULL,
  [kod] NVARCHAR(64) NOT NULL,
  [aktif] TINYINT(1) NOT NULL DEFAULT 1,
  [logo] NVARCHAR(255) DEFAULT NULL,
  [adres] NVARCHAR(MAX) DEFAULT NULL,
  [telefon] NVARCHAR(64) DEFAULT NULL,
  [ayarlar_json] NVARCHAR(MAX) DEFAULT NULL,
  [olusturma_tarihi] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_kurum_kod] UNIQUE ([kod]),
  INDEX [idx_kurum_aktif] ([aktif])
    );
END
GO


SET IDENTITY_INSERT [dbo].[esh_kurumlar] ON;
INSERT INTO [esh_kurumlar] ([id], [ad], [kod], [aktif]) VALUES (1, 'Varsayılan Kurum', 'varsayilan', 1);
SET IDENTITY_INSERT [dbo].[esh_kurumlar] OFF;
GO


IF OBJECT_ID(N'[dbo].[esh_adrestablosu]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_adrestablosu] (
  [id] NVARCHAR(64) NOT NULL,
  [adi] NVARCHAR(255) NOT NULL DEFAULT '',
  [ust_id] NVARCHAR(64) DEFAULT NULL,
  [tip] NVARCHAR(20) NOT NULL DEFAULT 'ilce',
  [coords] NVARCHAR(255) DEFAULT NULL,
  [has_coords] TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY ([ust_id], [id]),
  INDEX [idx_adres_id] ([id]),
  INDEX [idx_tip_ust] ([tip], [ust_id]),
  INDEX [idx_tip_has_coords] ([tip], [has_coords])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_mahalle_plan]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_mahalle_plan] (
  [kurum_id] INT NOT NULL,
  [mahalle_id] NVARCHAR(64) NOT NULL,
  [bolge] INT NOT NULL DEFAULT 0,
  [gun] NVARCHAR(64) DEFAULT NULL,
  PRIMARY KEY ([kurum_id], [mahalle_id]),
  INDEX [idx_mp_mahalle] ([mahalle_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kurum_adres]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurum_adres] (
  [kurum_id] INT NOT NULL,
  [adres_id] NVARCHAR(64) NOT NULL,
  [tip] NVARCHAR(20) NOT NULL,
  PRIMARY KEY ([kurum_id], [adres_id]),
  INDEX [idx_ka_kurum_tip] ([kurum_id], [tip]),
  INDEX [idx_ka_adres] ([adres_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kurum_brans]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurum_brans] (
  [kurum_id] INT NOT NULL,
  [brans_id] INT NOT NULL,
  [hasta_kotasi] INT NULL DEFAULT NULL,
  PRIMARY KEY ([kurum_id], [brans_id]),
  INDEX [idx_kb_brans] ([brans_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kurum_istek]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurum_istek] (
  [kurum_id] INT NOT NULL,
  [istek_id] INT NOT NULL,
  PRIMARY KEY ([kurum_id], [istek_id]),
  INDEX [idx_ki_istek] ([istek_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kurum_islem]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurum_islem] (
  [kurum_id] INT NOT NULL,
  [islem_id] INT NOT NULL,
  PRIMARY KEY ([kurum_id], [islem_id]),
  INDEX [idx_km_islem] ([islem_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kurum_hastalik]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kurum_hastalik] (
  [kurum_id] INT NOT NULL,
  [hastalik_id] INT NOT NULL,
  PRIMARY KEY ([kurum_id], [hastalik_id]),
  INDEX [idx_kh_hastalik] ([hastalik_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hastalikcat]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hastalikcat] (
  [id] INT NOT NULL IDENTITY(1,1),
  [name] NVARCHAR(255) NOT NULL,
  [icd_range] NVARCHAR(64) DEFAULT NULL,
  PRIMARY KEY ([id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_branslar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_branslar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 0,
  [bransadi] NVARCHAR(255) NOT NULL,
  [hasta_kotasi] INT NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_branslar_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_guvence]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_guvence] (
  [id] INT NOT NULL IDENTITY(1,1),
  [guvenceadi] NVARCHAR(255) NOT NULL,
  PRIMARY KEY ([id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_islemler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_islemler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 0,
  [islemadi] NVARCHAR(255) NOT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_islemler_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_araclar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_araclar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [plaka] NVARCHAR(32) NOT NULL DEFAULT '',
  [arac_bilgisi] NVARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY ([id]),
  INDEX [idx_plaka] ([plaka]),
  INDEX [idx_araclar_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_istekler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_istekler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 0,
  [istek_adi] NVARCHAR(255) NOT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_istekler_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hastaliklar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hastaliklar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 0,
  [cat] INT NOT NULL DEFAULT 0,
  [hastalikadi] NVARCHAR(255) NOT NULL,
  [icd] NVARCHAR(32) DEFAULT NULL,
  [parent_icd] NVARCHAR(32) DEFAULT NULL,
  [seviye] TINYINT DEFAULT NULL,
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_hastaliklar_kurum_icd] UNIQUE ([kurum_id], [icd]),
  INDEX [idx_cat] ([cat]),
  INDEX [idx_hastaliklar_kurum] ([kurum_id]),
  INDEX [idx_hastaliklar_icd] ([icd]),
  INDEX [idx_hastaliklar_parent] ([parent_icd])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hastailacrapor]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hastailacrapor] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hastatckimlik] NVARCHAR(11) NOT NULL,
  [hastalikid] INT NOT NULL,
  [rapor] TINYINT(1) NOT NULL DEFAULT 0,
  [bitistarihi] DATE DEFAULT NULL,
  [brans] NVARCHAR(512) NOT NULL DEFAULT '',
  [raporyeri] TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY ([id]),
  INDEX [uk_tc_hastalik] ([hastatckimlik], [hastalikid]),
  INDEX [idx_tc] ([hastatckimlik]),
  INDEX [idx_hastalik] ([hastalikid]),
  INDEX [idx_hastailacrapor_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hasta_ilaclar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hasta_ilaclar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hasta_id] INT NOT NULL,
  [ilac_adi] NVARCHAR(255) NOT NULL,
  [etken_madde] NVARCHAR(512) NULL,
  [recete_turu] NVARCHAR(128) NULL,
  [not] NVARCHAR(MAX) NULL,
  [hastalikid] INT NULL,
  [sira] SMALLINT NOT NULL DEFAULT 0,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_hasta] ([hasta_id]),
  INDEX [idx_hasta_ilaclar_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_users]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_users] (
  [id] INT NOT NULL IDENTITY(1,1),
  [username] NVARCHAR(64) NOT NULL,
  [password] NVARCHAR(255) NOT NULL,
  [name] NVARCHAR(128) NOT NULL DEFAULT '',
  [tckimlikno] NVARCHAR(11) DEFAULT NULL,
  [email] NVARCHAR(255) DEFAULT NULL,
  [image] NVARCHAR(255) DEFAULT NULL,
  [nowvisit] DATETIME2 DEFAULT NULL,
  [lastvisit] DATETIME2 DEFAULT NULL,
  [registerDate] DATETIME2 DEFAULT NULL,
  [activated] TINYINT(1) NOT NULL DEFAULT 0,
  [activation] NVARCHAR(64) DEFAULT NULL,
  [isadmin] TINYINT NOT NULL DEFAULT 0,
  [kurum_id] INT NULL DEFAULT 1,
  [unvan] NVARCHAR(64) DEFAULT NULL,
  [ui_theme] NVARCHAR(64) DEFAULT NULL,
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_username] UNIQUE ([username]),
  INDEX [idx_users_activated_name] ([activated], [name]),
  INDEX [idx_users_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hastalar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hastalar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [tckimlik] NVARCHAR(11) NOT NULL,
  [isim] NVARCHAR(128) DEFAULT NULL,
  [soyisim] NVARCHAR(128) DEFAULT NULL,
  [anneAdi] NVARCHAR(128) DEFAULT NULL,
  [babaAdi] NVARCHAR(128) DEFAULT NULL,
  [dogumtarihi] DATE DEFAULT NULL,
  [cinsiyet] NVARCHAR(2) DEFAULT NULL,
  [kilo] DECIMAL(6,2) DEFAULT NULL,
  [boy] DECIMAL(6,2) DEFAULT NULL,
  [kayittarihi] DATE DEFAULT NULL,
  [ceptel1] NVARCHAR(32) DEFAULT NULL,
  [ceptel2] NVARCHAR(32) DEFAULT NULL,
  [bakimveren_ad] NVARCHAR(128) DEFAULT NULL,
  [bakimveren_tel] NVARCHAR(32) DEFAULT NULL,
  [bakimveren_yakinlik] NVARCHAR(64) DEFAULT NULL,
  [alerji] NVARCHAR(MAX),
  [acil_not] NVARCHAR(MAX),
  [guvence] INT DEFAULT NULL,
  [yupasno] NVARCHAR(64) DEFAULT NULL,
  [ailehekimi] NVARCHAR(128) DEFAULT NULL,
  [ailehekimitel] NVARCHAR(32) DEFAULT NULL,
  [sms_bilgilendirme_onay] TINYINT(1) NOT NULL DEFAULT 1,
  [kangrubu] NVARCHAR(8) DEFAULT NULL,
  [ilce] NVARCHAR(64) DEFAULT NULL,
  [mahalle] NVARCHAR(64) DEFAULT NULL,
  [sokak] NVARCHAR(64) DEFAULT NULL,
  [kapino] NVARCHAR(64) DEFAULT NULL,
  [adres_aciklama] NVARCHAR(MAX),
  [diger_adres] NVARCHAR(MAX),
  [coords] NVARCHAR(255) DEFAULT NULL,
  [bagimlilik] NVARCHAR(10) DEFAULT NULL,
  [barbeslenme] TINYINT DEFAULT NULL,
  [barbanyo] TINYINT DEFAULT NULL,
  [barbakim] TINYINT DEFAULT NULL,
  [bargiyinme] TINYINT DEFAULT NULL,
  [barbarsak] TINYINT DEFAULT NULL,
  [barmesane] TINYINT DEFAULT NULL,
  [bartuvalet] TINYINT DEFAULT NULL,
  [bartransfer] TINYINT DEFAULT NULL,
  [barmobilite] TINYINT DEFAULT NULL,
  [barmerdiven] TINYINT DEFAULT NULL,
  [pasif] NVARCHAR(10) NOT NULL DEFAULT '0',
  [pasiftarihi] DATE DEFAULT NULL,
  [pasifnedeni] NVARCHAR(16) DEFAULT NULL,
  [gecici] TINYINT(1) NOT NULL DEFAULT 0,
  [ng] TINYINT(1) NOT NULL DEFAULT 0,
  [peg] TINYINT(1) NOT NULL DEFAULT 0,
  [port] TINYINT(1) NOT NULL DEFAULT 0,
  [o2bagimli] TINYINT(1) NOT NULL DEFAULT 0,
  [ventilator] TINYINT(1) NOT NULL DEFAULT 0,
  [kolostomi] TINYINT(1) NOT NULL DEFAULT 0,
  [trakeostomi] TINYINT(1) NOT NULL DEFAULT 0,
  [cpap] TINYINT(1) NOT NULL DEFAULT 0,
  [aspirasyon] TINYINT(1) NOT NULL DEFAULT 0,
  [ileostomi] TINYINT(1) NOT NULL DEFAULT 0,
  [urostomi] TINYINT(1) NOT NULL DEFAULT 0,
  [picc] TINYINT(1) NOT NULL DEFAULT 0,
  [dren] TINYINT(1) NOT NULL DEFAULT 0,
  [diyaliz] TINYINT(1) NOT NULL DEFAULT 0,
  [basiyarasi] TINYINT(1) NOT NULL DEFAULT 0,
  [ivtedavi] TINYINT(1) NOT NULL DEFAULT 0,
  [izolasyon] TINYINT(1) NOT NULL DEFAULT 0,
  [sonda] TINYINT(1) NOT NULL DEFAULT 0,
  [sondatarihi] DATE DEFAULT NULL,
  [pansuman] TINYINT(1) NOT NULL DEFAULT 0,
  [pgunleri] NVARCHAR(64) DEFAULT NULL,
  [pzaman] NVARCHAR(32) DEFAULT NULL,
  [mama] TINYINT(1) NOT NULL DEFAULT 0,
  [mamacesit] NVARCHAR(128) DEFAULT NULL,
  [mamaraporbitis] DATE DEFAULT NULL,
  [mamaraporyeri] NVARCHAR(255) DEFAULT NULL,
  [bez] TINYINT(1) NOT NULL DEFAULT 0,
  [bezrapor] TINYINT(1) NOT NULL DEFAULT 0,
  [bezraporbitis] DATE DEFAULT NULL,
  [yatak] TINYINT(1) NOT NULL DEFAULT 0,
  [hastaliklar] NVARCHAR(MAX),
  [erapor] NVARCHAR(255) DEFAULT NULL,
  [randevutarihi] DATE DEFAULT NULL,
  [zaman] TINYINT DEFAULT NULL,
  [notes] NVARCHAR(MAX),
  [profil_foto] NVARCHAR(255) DEFAULT NULL,
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_tckimlik] UNIQUE ([tckimlik]),
  INDEX [idx_pasif] ([pasif]),
  INDEX [idx_mahalle] ([mahalle]),
  INDEX [idx_ilce] ([ilce]),
  INDEX [idx_randevu_pasif_zaman] ([randevutarihi], [pasif], [zaman]),
  INDEX [idx_pansuman_slot] ([pasif], [pansuman], [pzaman]),
  INDEX [idx_pasif_isim] ([pasif], [isim]),
  INDEX [idx_hastalar_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_izlemler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_izlemler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hastatckimlik] NVARCHAR(11) NOT NULL,
  [izlemtarihi] DATE NOT NULL,
  [izlemtarihi_dt] DATE DEFAULT NULL,
  [yapilan] NVARCHAR(MAX),
  [yapildimi] TINYINT(1) NOT NULL DEFAULT 0,
  [neden] NVARCHAR(255) DEFAULT NULL,
  [izlemiyapan] NVARCHAR(255) DEFAULT NULL,
  [zaman] NVARCHAR(32) DEFAULT NULL,
  [aciklama] NVARCHAR(MAX),
  [checkin_lat] DECIMAL(10,7) NULL DEFAULT NULL,
  [checkin_lon] DECIMAL(10,7) NULL DEFAULT NULL,
  [checkin_at] DATETIME2 NULL DEFAULT NULL,
  [arac] INT DEFAULT NULL,
  [brans] NVARCHAR(255) DEFAULT NULL,
  [kons_istekler] NVARCHAR(512) DEFAULT NULL,
  [kons_brans_istek] NVARCHAR(MAX) DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_izlem_tc_yapildi_tarih] ([hastatckimlik], [yapildimi], [izlemtarihi]),
  INDEX [idx_izlem_tarih_yapildi] ([izlemtarihi], [yapildimi]),
  INDEX [idx_izlem_tarih_tc] ([izlemtarihi], [hastatckimlik]),
  INDEX [idx_izlem_yapildi_tarih_dt] ([yapildimi], [izlemtarihi_dt]),
  INDEX [idx_arac] ([arac]),
  INDEX [idx_izlemler_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_pizlemler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_pizlemler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hastatckimlik] NVARCHAR(11) NOT NULL,
  [planlanantarih] DATE NOT NULL,
  [yapilacak] NVARCHAR(255) NOT NULL DEFAULT '',
  [zaman] TINYINT NOT NULL DEFAULT 0,
  [planiyapan] INT DEFAULT NULL,
  [plantarihi] DATETIME2 DEFAULT NULL,
  [oncelik] TINYINT NOT NULL DEFAULT 1,
  [aciklama] NVARCHAR(MAX),
  [notlar] NVARCHAR(MAX),
  [durum] TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY ([id]),
  INDEX [idx_plan_zaman_durum] ([planlanantarih], [zaman], [durum]),
  INDEX [idx_tc_durum] ([hastatckimlik], [durum]),
  INDEX [idx_pizlemler_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_erapor]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_erapor] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hastatckimlik] NVARCHAR(11) DEFAULT NULL,
  [isim] NVARCHAR(128) DEFAULT NULL,
  [soyisim] NVARCHAR(128) DEFAULT NULL,
  [ceptel1] NVARCHAR(32) DEFAULT NULL,
  [basvurutarihi] DATE DEFAULT NULL,
  [brans] INT DEFAULT NULL,
  [kayitlimi] TINYINT(1) NOT NULL DEFAULT 0,
  [yenilendimi] TINYINT(1) NOT NULL DEFAULT 0,
  [neden] NVARCHAR(255) DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_tc] ([hastatckimlik]),
  INDEX [idx_erapor_basvuru_id] ([basvurutarihi], [id]),
  INDEX [idx_erapor_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_ekipler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_ekipler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [tarih] DATE NOT NULL,
  [vardiya] NVARCHAR(32) DEFAULT NULL,
  [ekip_no] INT DEFAULT NULL,
  [user_ids] NVARCHAR(512) DEFAULT NULL,
  [baslangic_saati] NVARCHAR(32) DEFAULT NULL,
  [kayit_tarihi] DATETIME2 DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_tarih] ([tarih]),
  INDEX [idx_ekipler_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_personel_izin]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_personel_izin] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [personel_id] INT NOT NULL,
  [baslangic_tarihi] DATE NOT NULL,
  [bitis_tarihi] DATE NOT NULL,
  [sebep] NVARCHAR(255) DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_izin_personel] ([personel_id]),
  INDEX [idx_izin_tarih] ([baslangic_tarihi], [bitis_tarihi]),
  INDEX [idx_personel_izin_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_personel_istek]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_personel_istek] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [personel_id] INT NOT NULL,
  [baslangic_tarihi] DATE NOT NULL,
  [bitis_tarihi] DATE NOT NULL,
  [aciklama] NVARCHAR(255) DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_istek_personel] ([personel_id]),
  INDEX [idx_istek_tarih] ([baslangic_tarihi], [bitis_tarihi]),
  INDEX [idx_personel_istek_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_resmi_tatiller]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_resmi_tatiller] (
  [id] INT NOT NULL IDENTITY(1,1),
  [aciklama] NVARCHAR(255) NOT NULL,
  [baslangic_tarihi] DATE NOT NULL,
  [bitis_tarihi] DATE NOT NULL,
  [tatil_tipi] NVARCHAR(50) NOT NULL DEFAULT 'resmi_tatil',
  PRIMARY KEY ([id]),
  INDEX [idx_tatil_tarih] ([baslangic_tarihi], [bitis_tarihi])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_personel_nobet]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_personel_nobet] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [personel_id] INT NOT NULL,
  [nobet_tarihi] DATE NOT NULL,
  [nobet_tipi] NVARCHAR(50) NOT NULL DEFAULT 'normal',
  [durum] TINYINT(1) NOT NULL DEFAULT 1,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [tekil_nobet] UNIQUE ([personel_id], [nobet_tarihi]),
  INDEX [idx_nobet_tarih] ([nobet_tarihi]),
  INDEX [idx_nobet_personel] ([personel_id]),
  INDEX [idx_personel_nobet_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hasta_yara_fotolar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hasta_yara_fotolar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [hasta_id] INT NOT NULL,
  [dosya_adi] NVARCHAR(255) NOT NULL,
  [orijinal_ad] NVARCHAR(255) DEFAULT NULL,
  [mime] NVARCHAR(100) DEFAULT NULL,
  [boyut] INT DEFAULT NULL,
  [aciklama] NVARCHAR(255) DEFAULT NULL,
  [yara_bolgesi] NVARCHAR(100) DEFAULT NULL,
  [yara_evresi] NVARCHAR(50) DEFAULT NULL,
  [cekim_tarihi] DATETIME2 DEFAULT NULL,
  [yukleyen_id] INT DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_hasta] ([hasta_id]),
  INDEX [idx_created] ([created_at]),
  INDEX [idx_yara_fotolar_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_rota_cache]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_rota_cache] (
  [id] INT NOT NULL IDENTITY(1,1),
  [hash] NVARCHAR(128) NOT NULL,
  [origin] NVARCHAR(512) NOT NULL,
  [destination] NVARCHAR(512) NOT NULL,
  [sure] INT NOT NULL DEFAULT 0,
  [mesafe] INT NOT NULL DEFAULT 0,
  [updated_at] DATETIME2 NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_hash] UNIQUE ([hash])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_kons_randevu]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_kons_randevu] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [randevu_tarihi] DATE NOT NULL,
  [zaman] TINYINT NOT NULL DEFAULT 0,
  [kons_istekler] NVARCHAR(512) NOT NULL DEFAULT '',
  [brans_id] INT NOT NULL,
  [hastatckimlik] NVARCHAR(11) NOT NULL,
  [notlar] NVARCHAR(512) DEFAULT NULL,
  [hasta_geldi] TINYINT NULL DEFAULT NULL,
  [olusturan_id] INT DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_tarih_brans_hasta] UNIQUE ([randevu_tarihi], [brans_id], [hastatckimlik]),
  INDEX [idx_kons_randevu_tarih] ([randevu_tarihi]),
  INDEX [idx_kons_randevu_hasta] ([hastatckimlik]),
  INDEX [idx_kons_randevu_brans_tarih] ([brans_id], [randevu_tarihi]),
  INDEX [idx_kons_randevu_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_goruntulu_randevu]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_goruntulu_randevu] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL DEFAULT 1,
  [randevu_tarihi] DATE NOT NULL,
  [zaman] TINYINT NOT NULL DEFAULT 0,
  [kons_istekler] NVARCHAR(512) NOT NULL DEFAULT '',
  [brans_id] INT NOT NULL,
  [hastatckimlik] NVARCHAR(11) NOT NULL,
  [notlar] NVARCHAR(512) DEFAULT NULL,
  [hasta_geldi] TINYINT NULL DEFAULT NULL,
  [olusturan_id] INT DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_gor_tarih_brans_hasta] UNIQUE ([randevu_tarihi], [brans_id], [hastatckimlik]),
  INDEX [idx_gor_randevu_tarih] ([randevu_tarihi]),
  INDEX [idx_gor_randevu_hasta] ([hastatckimlik]),
  INDEX [idx_gor_randevu_brans_tarih] ([brans_id], [randevu_tarihi]),
  INDEX [idx_gor_randevu_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_hasta_nakil]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_hasta_nakil] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kaynak_hasta_id] INT NOT NULL,
  [kaynak_kurum_id] INT NOT NULL,
  [hedef_kurum_id] INT NULL DEFAULT NULL,
  [hedef_hasta_id] INT NULL DEFAULT NULL,
  [onceki_nakil_id] INT NULL DEFAULT NULL,
  [orijinal_kaynak_hasta_id] INT NULL DEFAULT NULL,
  [tip] NVARCHAR(32) NOT NULL DEFAULT 'kurum_ici',
  [durum] NVARCHAR(32) NOT NULL DEFAULT 'beklemede',
  [talep_eden_user_id] INT NOT NULL,
  [talep_tarihi] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [onaylayan_user_id] INT NULL DEFAULT NULL,
  [onay_tarihi] DATETIME2 NULL DEFAULT NULL,
  [red_nedeni] NVARCHAR(500) NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_hedef_durum] ([hedef_kurum_id], [durum]),
  INDEX [idx_kaynak_hasta] ([kaynak_hasta_id]),
  INDEX [idx_kaynak_kurum_durum] ([kaynak_kurum_id], [durum])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_sms_sablonlari]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_sms_sablonlari] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NULL DEFAULT NULL,
  [kod] NVARCHAR(64) NOT NULL DEFAULT '',
  [baslik] NVARCHAR(255) NOT NULL DEFAULT '',
  [govde] NVARCHAR(1600) NOT NULL DEFAULT '',
  [degiskenler_json] NVARCHAR(MAX) NULL,
  [aktif] TINYINT(1) NOT NULL DEFAULT 1,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_sms_sablon_kurum] ([kurum_id], [aktif]),
  INDEX [idx_sms_sablon_kod] ([kod])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_sms_gonderim]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_sms_gonderim] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL,
  [olusturan_id] INT NOT NULL,
  [segment_tipi] NVARCHAR(32) NOT NULL DEFAULT 'tek_hasta',
  [segment_param_json] NVARCHAR(MAX) NULL,
  [sablon_id] INT NULL DEFAULT NULL,
  [govde_ozet] NVARCHAR(500) NOT NULL DEFAULT '',
  [mesaj_turu] NVARCHAR(32) NOT NULL DEFAULT 'bilgilendirme',
  [durum] NVARCHAR(32) NOT NULL DEFAULT 'beklemede',
  [toplam] INT NOT NULL DEFAULT 0,
  [basarili] INT NOT NULL DEFAULT 0,
  [basarisiz] INT NOT NULL DEFAULT 0,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_sms_gonderim_kurum] ([kurum_id], [created_at]),
  INDEX [idx_sms_gonderim_durum] ([durum])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_sms_alici]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_sms_alici] (
  [id] BIGINT NOT NULL IDENTITY(1,1),
  [gonderim_id] INT NOT NULL,
  [hasta_id] INT NULL DEFAULT NULL,
  [rol] NVARCHAR(32) NOT NULL DEFAULT 'hasta',
  [telefon_norm] NVARCHAR(16) NOT NULL DEFAULT '',
  [govde] NVARCHAR(1600) NOT NULL DEFAULT '',
  [provider_msg_id] NVARCHAR(64) NULL DEFAULT NULL,
  [durum] NVARCHAR(32) NOT NULL DEFAULT 'beklemede',
  [hata_kodu] NVARCHAR(32) NULL DEFAULT NULL,
  [hata_mesaj] NVARCHAR(255) NULL DEFAULT NULL,
  [gonderim_at] DATETIME2 NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_sms_alici_gonderim] ([gonderim_id]),
  INDEX [idx_sms_alici_durum] ([durum])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_sms_optout]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_sms_optout] (
  [id] INT NOT NULL IDENTITY(1,1),
  [telefon_norm] NVARCHAR(16) NOT NULL,
  [kurum_id] INT NOT NULL,
  [kaynak] NVARCHAR(32) NOT NULL DEFAULT 'manuel',
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uq_sms_optout_tel_kurum] UNIQUE ([telefon_norm], [kurum_id]),
  INDEX [idx_sms_optout_kurum] ([kurum_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_stok_malzeme]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_stok_malzeme] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL,
  [kod] NVARCHAR(64) NULL DEFAULT NULL,
  [ad] NVARCHAR(255) NOT NULL DEFAULT '',
  [kategori] NVARCHAR(32) NOT NULL DEFAULT 'sarf',
  [birim] NVARCHAR(32) NOT NULL DEFAULT 'adet',
  [min_stok] DECIMAL(12,3) NOT NULL DEFAULT 0,
  [aktif] TINYINT(1) NOT NULL DEFAULT 1,
  [aciklama] NVARCHAR(512) NULL DEFAULT NULL,
  [tedarikci_adi] NVARCHAR(255) NULL DEFAULT NULL,
  [tedarikci_tel] NVARCHAR(32) NULL DEFAULT NULL,
  [birim_fiyat] DECIMAL(12,2) NULL DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_stok_malzeme_kurum] ([kurum_id], [aktif]),
  INDEX [idx_stok_malzeme_kategori] ([kategori])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_stok_mevcut]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_stok_mevcut] (
  [kurum_id] INT NOT NULL,
  [malzeme_id] INT NOT NULL,
  [miktar] DECIMAL(12,3) NOT NULL DEFAULT 0,
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([kurum_id], [malzeme_id]),
  INDEX [idx_stok_mevcut_malzeme] ([malzeme_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_stok_hareket]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_stok_hareket] (
  [id] BIGINT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL,
  [malzeme_id] INT NOT NULL,
  [hareket_tipi] NVARCHAR(32) NOT NULL,
  [miktar] DECIMAL(12,3) NOT NULL,
  [hareket_tarihi] DATE NOT NULL,
  [hasta_id] INT NULL DEFAULT NULL,
  [ekip_id] INT NULL DEFAULT NULL,
  [kullanici_id] INT NOT NULL,
  [aciklama] NVARCHAR(512) NULL DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_stok_hareket_kurum_tarih] ([kurum_id], [hareket_tarihi]),
  INDEX [idx_stok_hareket_malzeme] ([malzeme_id]),
  INDEX [idx_stok_hareket_hasta] ([hasta_id]),
  INDEX [idx_stok_hareket_ekip] ([ekip_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_stok_uyari_log]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_stok_uyari_log] (
  [id] BIGINT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL,
  [malzeme_id] INT NOT NULL,
  [uyari_tarihi] DATE NOT NULL,
  [sms_gonderildi] TINYINT(1) NOT NULL DEFAULT 0,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uq_stok_uyari_gun] UNIQUE ([kurum_id], [malzeme_id], [uyari_tarihi]),
  INDEX [idx_stok_uyari_kurum] ([kurum_id], [uyari_tarihi])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_stok_parti]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_stok_parti] (
  [id] BIGINT NOT NULL IDENTITY(1,1),
  [kurum_id] INT NOT NULL,
  [malzeme_id] INT NOT NULL,
  [lot_no] NVARCHAR(64) NULL DEFAULT NULL,
  [skt] DATE NULL DEFAULT NULL,
  [miktar] DECIMAL(12,3) NOT NULL DEFAULT 0,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_stok_parti_malzeme] ([kurum_id], [malzeme_id], [skt])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_mesaj_konusmalar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_mesaj_konusmalar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [tip] NVARCHAR(32) NOT NULL,
  [kurum_id] INT NOT NULL,
  [hasta_id] INT NULL DEFAULT NULL,
  [dm_kucuk_id] INT NULL DEFAULT NULL,
  [dm_buyuk_id] INT NULL DEFAULT NULL,
  [baslik] NVARCHAR(255) NOT NULL DEFAULT '',
  [olusturan_id] INT NULL DEFAULT NULL,
  [son_mesaj_at] DATETIME2 NULL DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uq_dm_pair] UNIQUE ([dm_kucuk_id], [dm_buyuk_id]),
  CONSTRAINT [uq_hasta_thread] UNIQUE ([hasta_id]),
  INDEX [idx_kurum_son] ([kurum_id], [son_mesaj_at]),
  INDEX [idx_tip] ([tip])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_mesaj_konusma_uyeler]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_mesaj_konusma_uyeler] (
  [id] INT NOT NULL IDENTITY(1,1),
  [konusma_id] INT NOT NULL,
  [user_id] INT NOT NULL,
  [son_okunan_mesaj_id] INT NOT NULL DEFAULT 0,
  [katilim_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [silindi_at] DATETIME2 NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  CONSTRAINT [uq_konusma_user] UNIQUE ([konusma_id], [user_id]),
  INDEX [idx_user] ([user_id]),
  INDEX [idx_user_silindi] ([user_id], [silindi_at])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_mesajlar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_mesajlar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [konusma_id] INT NOT NULL,
  [gonderen_id] INT NULL DEFAULT NULL,
  [gonderen_tip] NVARCHAR(32) NOT NULL DEFAULT 'user',
  [govde] NVARCHAR(MAX) NOT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  INDEX [idx_konusma_id] ([konusma_id], [id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_eimza_challenges]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_eimza_challenges] (
  [id] INT NOT NULL IDENTITY(1,1),
  [user_id] INT NULL DEFAULT NULL,
  [nonce_hash] CHAR(64) NOT NULL,
  [issued_at] DATETIME2 NOT NULL,
  [expires_at] DATETIME2 NOT NULL,
  [consumed_at] DATETIME2 NULL DEFAULT NULL,
  [ip_address] NVARCHAR(64) NULL DEFAULT NULL,
  [user_agent] NVARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_eimza_challenge_expires] ([expires_at]),
  INDEX [idx_eimza_challenge_user] ([user_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_eimza_login_logs]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_eimza_login_logs] (
  [id] BIGINT NOT NULL IDENTITY(1,1),
  [user_id] INT NULL DEFAULT NULL,
  [tc_kimlikno] NVARCHAR(16) NOT NULL,
  [success] TINYINT(1) NOT NULL DEFAULT 0,
  [reason] NVARCHAR(128) NOT NULL,
  [cert_serial] NVARCHAR(128) NULL DEFAULT NULL,
  [cert_fingerprint] NVARCHAR(128) NULL DEFAULT NULL,
  [ip_address] NVARCHAR(64) NULL DEFAULT NULL,
  [user_agent] NVARCHAR(255) NULL DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_eimza_logs_user_created] ([user_id], [created_at]),
  INDEX [idx_eimza_logs_tc_created] ([tc_kimlikno], [created_at]),
  INDEX [idx_eimza_logs_success_created] ([success], [created_at])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_rehber_import_log]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_rehber_import_log] (
  [id] INT NOT NULL IDENTITY(1,1),
  [source_site] NVARCHAR(32) NOT NULL,
  [started_at] DATETIME2 NOT NULL,
  [finished_at] DATETIME2 NULL DEFAULT NULL,
  [etken_count] INT NOT NULL DEFAULT 0,
  [ilac_count] INT NOT NULL DEFAULT 0,
  [error_summary] NVARCHAR(MAX) NULL,
  [options_json] NVARCHAR(MAX) NULL,
  PRIMARY KEY ([id]),
  INDEX [idx_rehber_import_finished] ([finished_at])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_rehber_etken]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_rehber_etken] (
  [id] INT NOT NULL IDENTITY(1,1),
  [ad] NVARCHAR(512) NOT NULL,
  [ad_normalized] NVARCHAR(512) NOT NULL,
  [source_site] NVARCHAR(32) NOT NULL,
  [source_key] NVARCHAR(128) NOT NULL,
  [scraped_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_rehber_etken_site_key] UNIQUE ([source_site], [source_key]),
  INDEX [idx_rehber_etken_norm] ([ad_normalized])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_rehber_ilac]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_rehber_ilac] (
  [id] INT NOT NULL IDENTITY(1,1),
  [etken_id] INT NOT NULL,
  [ad] NVARCHAR(512) NOT NULL,
  [firma] NVARCHAR(255) NULL,
  [recete_turu] NVARCHAR(128) NULL,
  [source_site] NVARCHAR(32) NOT NULL,
  [source_url] NVARCHAR(1024) NULL,
  [source_key] NVARCHAR(256) NOT NULL,
  [scraped_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_rehber_ilac_site_key] UNIQUE ([source_site], [source_key]),
  INDEX [idx_rehber_ilac_etken] ([etken_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_unvanlar]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_unvanlar] (
  [id] INT NOT NULL IDENTITY(1,1),
  [kod] NVARCHAR(64) NOT NULL,
  [ad] NVARCHAR(128) NOT NULL,
  [kategori] NVARCHAR(32) NOT NULL DEFAULT 'diger',
  [izin_sablonu] NVARCHAR(64) NOT NULL DEFAULT 'personel',
  [sort_order] INT NOT NULL DEFAULT 100,
  [aktif] TINYINT(1) NOT NULL DEFAULT 1,
  [is_system] TINYINT(1) NOT NULL DEFAULT 0,
  [mevzuat_notu] NVARCHAR(512) NULL DEFAULT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_unvan_kod] UNIQUE ([kod]),
  INDEX [idx_unvan_aktif_sort] ([aktif], [sort_order])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_roles]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_roles] (
  [id] INT NOT NULL IDENTITY(1,1),
  [slug] NVARCHAR(64) NOT NULL,
  [unvan_code] NVARCHAR(64) NULL DEFAULT NULL,
  [name] NVARCHAR(128) NOT NULL,
  [description] NVARCHAR(512) NULL DEFAULT NULL,
  [is_system] TINYINT(1) NOT NULL DEFAULT 0,
  [sort_order] INT NOT NULL DEFAULT 0,
  [created_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  [updated_at] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_roles_slug] UNIQUE ([slug]),
  CONSTRAINT [uk_roles_unvan_code] UNIQUE ([unvan_code])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_permissions]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_permissions] (
  [id] INT NOT NULL IDENTITY(1,1),
  [module_key] NVARCHAR(64) NOT NULL,
  [crud] NVARCHAR(32) NOT NULL,
  [slug] NVARCHAR(128) NOT NULL,
  [label] NVARCHAR(255) NOT NULL DEFAULT '',
  [description] NVARCHAR(512) NULL DEFAULT NULL,
  PRIMARY KEY ([id]),
  CONSTRAINT [uk_permissions_slug] UNIQUE ([slug]),
  INDEX [idx_permissions_module] ([module_key])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_role_permissions]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_role_permissions] (
  [role_id] INT NOT NULL,
  [permission_id] INT NOT NULL,
  PRIMARY KEY ([role_id], [permission_id]),
  INDEX [idx_role_permissions_permission] ([permission_id])
    );
END
GO


IF OBJECT_ID(N'[dbo].[esh_user_roles]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[esh_user_roles] (
  [user_id] INT NOT NULL,
  [role_id] INT NOT NULL,
  PRIMARY KEY ([user_id]),
  INDEX [idx_user_roles_role] ([role_id])
    );
END
GO


