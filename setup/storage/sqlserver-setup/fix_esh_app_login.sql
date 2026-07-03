-- SSMS'te Windows Authentication ile baglandiktan sonra calistirin.
-- Sorun: PHP esh_app ile giremiyorsa bu script sifreyi ve yetkiyi yeniler.

USE [master];
GO

-- Karma mod (SQL + Windows) acik degilse SSMS'te:
-- Sunucuya sag tik -> Properties -> Security -> "SQL Server and Windows Authentication mode"
-- Sonra SQL Server (SQLEXPRESS) servisini yeniden baslatin.

IF EXISTS (SELECT 1 FROM sys.server_principals WHERE name = N'esh_app')
BEGIN
    ALTER LOGIN [esh_app] WITH PASSWORD = N'EshApp!2026', CHECK_POLICY = OFF;
END
ELSE
BEGIN
    CREATE LOGIN [esh_app] WITH PASSWORD = N'EshApp!2026', CHECK_POLICY = OFF;
END
GO

USE [esh4];
GO

IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'esh_app')
BEGIN
    CREATE USER [esh_app] FOR LOGIN [esh_app];
END
GO

ALTER ROLE [db_owner] ADD MEMBER [esh_app];
GO

PRINT 'esh_app hazir. Sifre: EshApp!2026';
GO
