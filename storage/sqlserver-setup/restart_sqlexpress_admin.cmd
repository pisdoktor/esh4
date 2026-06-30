@echo off
setlocal
title SQL Server SQLEXPRESS yeniden baslat

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Yonetici hakki gerekli. UAC penceresi aciliyor...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo.
echo [1/3] SQL Server (SQLEXPRESS) durduruluyor...
net stop "MSSQL$SQLEXPRESS" /y
if %errorlevel% neq 0 (
    echo HATA: Servis durdurulamadi.
    pause
    exit /b 1
)

echo [2/3] SQL Server (SQLEXPRESS) baslatiliyor...
net start "MSSQL$SQLEXPRESS"
if %errorlevel% neq 0 (
    echo HATA: Servis baslatilamadi.
    pause
    exit /b 1
)

timeout /t 4 /nobreak >nul

echo [3/3] esh_app baglanti testi...
"C:\xampp\php\php.exe" "C:\xampp\htdocs\tools\test_mssql_auth.php"
echo.
echo Tamamlandi. Her iki satirda OK gormelisiniz.
pause
