-- ESH: SQL Server Express kurulumu sonrasi calistirin (SSMS veya sqlcmd)
-- Baglanti: localhost\SQLEXPRESS  (SA: EshApp!2026)

IF DB_ID(N'esh4') IS NULL
BEGIN
    CREATE DATABASE [esh4] COLLATE Turkish_100_CI_AS;
END
GO

USE [master];
GO

IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = N'esh_app')
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
